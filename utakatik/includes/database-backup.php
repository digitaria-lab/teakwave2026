<?php

function db_backup_directory() {
    $directory = __DIR__ . '/../storage/database-backups';

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan backup tidak dapat dibuat.');
    }

    $htaccess = $directory . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }

    $index = $directory . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    return $directory;
}

function db_backup_safe_name($filename) {
    $filename = basename((string) $filename);

    if (!preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $filename)) {
        return null;
    }

    return $filename;
}

function db_backup_path($filename) {
    $safeName = db_backup_safe_name($filename);

    if (!$safeName) {
        return null;
    }

    return db_backup_directory() . DIRECTORY_SEPARATOR . $safeName;
}

function db_current_database(PDO $pdo) {
    $name = $pdo->query('SELECT DATABASE()')->fetchColumn();

    if (!$name) {
        throw new RuntimeException('Nama database aktif tidak ditemukan.');
    }

    return (string) $name;
}

function db_quote_identifier($identifier) {
    return '`' . str_replace('`', '``', (string) $identifier) . '`';
}

function db_backup_sql_value(PDO $pdo, $value, $type = '') {
    if ($value === null) {
        return 'NULL';
    }

    $type = strtolower((string) $type);

    if (preg_match('/\b(binary|varbinary|blob|tinyblob|mediumblob|longblob|bit)\b/', $type)) {
        return '0x' . bin2hex((string) $value);
    }

    $quoted = $pdo->quote((string) $value);

    if ($quoted === false) {
        throw new RuntimeException('Gagal mengamankan nilai saat membuat backup.');
    }

    return $quoted;
}

function db_backup_write($handle, $content) {
    $length = strlen($content);
    $offset = 0;

    while ($offset < $length) {
        $written = fwrite($handle, substr($content, $offset));

        if ($written === false || $written === 0) {
            throw new RuntimeException('Gagal menulis file backup.');
        }

        $offset += $written;
    }
}

function db_create_backup(PDO $pdo, $targetPath, $databaseName = null) {
    $databaseName = $databaseName ?: db_current_database($pdo);
    $handle = fopen($targetPath, 'wb');

    if (!$handle) {
        throw new RuntimeException('File backup tidak dapat dibuat.');
    }

    try {
        db_backup_write($handle, "-- Teakwave Database Backup\n");
        db_backup_write($handle, '-- Database: ' . str_replace(["\r", "\n"], '', $databaseName) . "\n");
        db_backup_write($handle, '-- Generated: ' . date('Y-m-d H:i:s') . "\n");
        db_backup_write($handle, "-- Restore only through the Teakwave admin backup page.\n\n");
        db_backup_write($handle, "SET NAMES utf8mb4;\n");
        db_backup_write($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        db_backup_write($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $objects = [];
        $result = $pdo->query('SHOW FULL TABLES');

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $objects[] = [
                'name' => (string) $row[0],
                'type' => strtoupper((string) ($row[1] ?? 'BASE TABLE'))
            ];
        }

        usort($objects, function ($left, $right) {
            if ($left['type'] === $right['type']) {
                return strcmp($left['name'], $right['name']);
            }

            return $left['type'] === 'BASE TABLE' ? -1 : 1;
        });

        foreach ($objects as $object) {
            if ($object['type'] !== 'BASE TABLE') {
                continue;
            }

            $table = $object['name'];
            $quotedTable = db_quote_identifier($table);
            $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);

            if (!$createRow || empty($createRow[1])) {
                throw new RuntimeException('Struktur tabel ' . $table . ' tidak dapat dibaca.');
            }

            db_backup_write($handle, "-- --------------------------------------------------------\n");
            db_backup_write($handle, '-- Table structure for ' . $quotedTable . "\n");
            db_backup_write($handle, 'DROP TABLE IF EXISTS ' . $quotedTable . ";\n");
            db_backup_write($handle, $createRow[1] . ";\n\n");

            $columnRows = $pdo->query('SHOW COLUMNS FROM ' . $quotedTable)->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            $columnTypes = [];

            foreach ($columnRows as $column) {
                $extra = strtolower((string) ($column['Extra'] ?? ''));

                // Generated columns are recalculated by MySQL and must not be included in INSERT statements.
                if (str_contains($extra, 'generated')) {
                    continue;
                }

                $columns[] = (string) $column['Field'];
                $columnTypes[(string) $column['Field']] = (string) $column['Type'];
            }

            if (!$columns) {
                continue;
            }

            $select = $pdo->query('SELECT * FROM ' . $quotedTable);
            $batch = [];
            $quotedColumns = implode(', ', array_map('db_quote_identifier', $columns));

            while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
                $values = [];

                foreach ($columns as $columnName) {
                    $values[] = db_backup_sql_value(
                        $pdo,
                        array_key_exists($columnName, $row) ? $row[$columnName] : null,
                        $columnTypes[$columnName] ?? ''
                    );
                }

                $batch[] = '(' . implode(', ', $values) . ')';

                if (count($batch) >= 100) {
                    db_backup_write(
                        $handle,
                        'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ") VALUES\n" . implode(",\n", $batch) . ";\n"
                    );
                    $batch = [];
                }
            }

            if ($batch) {
                db_backup_write(
                    $handle,
                    'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ") VALUES\n" . implode(",\n", $batch) . ";\n"
                );
            }

            db_backup_write($handle, "\n");
        }

        foreach ($objects as $object) {
            if ($object['type'] === 'BASE TABLE') {
                continue;
            }

            $view = $object['name'];
            $quotedView = db_quote_identifier($view);
            $createRow = $pdo->query('SHOW CREATE VIEW ' . $quotedView)->fetch(PDO::FETCH_ASSOC);
            $createSql = $createRow['Create View'] ?? null;

            if (!$createSql) {
                continue;
            }

            // Avoid restore failures when the original DEFINER account does not exist on another server.
            $createSql = preg_replace('/\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $createSql);

            db_backup_write($handle, "-- --------------------------------------------------------\n");
            db_backup_write($handle, '-- View structure for ' . $quotedView . "\n");
            db_backup_write($handle, 'DROP VIEW IF EXISTS ' . $quotedView . ";\n");
            db_backup_write($handle, $createSql . ";\n\n");
        }

        db_backup_write($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        db_backup_write($handle, "-- End of Teakwave Database Backup\n");
    } catch (Throwable $e) {
        fclose($handle);
        @unlink($targetPath);
        throw $e;
    }

    fclose($handle);

    if (!file_exists($targetPath) || filesize($targetPath) === 0) {
        @unlink($targetPath);
        throw new RuntimeException('Backup gagal karena file hasil kosong.');
    }

    @chmod($targetPath, 0640);

    return $targetPath;
}

function db_list_backups() {
    $directory = db_backup_directory();
    $items = [];

    foreach (glob($directory . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
        if (!is_file($path)) {
            continue;
        }

        $items[] = [
            'name' => basename($path),
            'size' => filesize($path) ?: 0,
            'modified_at' => filemtime($path) ?: 0
        ];
    }

    usort($items, function ($left, $right) {
        return $right['modified_at'] <=> $left['modified_at'];
    });

    return $items;
}

function db_format_bytes($bytes) {
    $bytes = max(0, (int) $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
    $value = $bytes / (1024 ** $power);

    return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
}

function db_split_sql_statements($sql) {
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $state = 'normal';

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($state === 'line_comment') {
            if ($char === "\n") {
                $state = 'normal';
                $buffer .= "\n";
            }
            continue;
        }

        if ($state === 'block_comment') {
            if ($char === '*' && $next === '/') {
                $state = 'normal';
                $i++;
                $buffer .= ' ';
            }
            continue;
        }

        if ($state === 'single_quote') {
            $buffer .= $char;

            if ($char === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
                continue;
            }

            if ($char === "'") {
                if ($next === "'") {
                    $buffer .= $next;
                    $i++;
                } else {
                    $state = 'normal';
                }
            }
            continue;
        }

        if ($state === 'double_quote') {
            $buffer .= $char;

            if ($char === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
                continue;
            }

            if ($char === '"') {
                if ($next === '"') {
                    $buffer .= $next;
                    $i++;
                } else {
                    $state = 'normal';
                }
            }
            continue;
        }

        if ($state === 'backtick') {
            $buffer .= $char;

            if ($char === '`') {
                if ($next === '`') {
                    $buffer .= $next;
                    $i++;
                } else {
                    $state = 'normal';
                }
            }
            continue;
        }

        if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
            $state = 'line_comment';
            $i++;
            continue;
        }

        if ($char === '#') {
            $state = 'line_comment';
            continue;
        }

        if ($char === '/' && $next === '*') {
            $state = 'block_comment';
            $i++;
            continue;
        }

        if ($char === "'") {
            $state = 'single_quote';
            $buffer .= $char;
            continue;
        }

        if ($char === '"') {
            $state = 'double_quote';
            $buffer .= $char;
            continue;
        }

        if ($char === '`') {
            $state = 'backtick';
            $buffer .= $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim($buffer);

            if ($statement !== '') {
                $statements[] = $statement;
            }

            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function db_validate_restore_statement($statement, $databaseName) {
    $statement = trim((string) $statement);

    if ($statement === '') {
        return ['execute' => false, 'statement' => ''];
    }

    $dangerousPatterns = [
        '/^\s*(CREATE|DROP|ALTER)\s+DATABASE\b/i',
        '/^\s*(CREATE|ALTER|DROP)\s+USER\b/i',
        '/^\s*(GRANT|REVOKE|SET\s+PASSWORD|FLUSH\s+PRIVILEGES|SHUTDOWN)\b/i',
        '/^\s*(INSTALL|UNINSTALL)\s+PLUGIN\b/i',
        '/^\s*LOAD\s+DATA\b/i',
        '/\bINTO\s+(OUTFILE|DUMPFILE)\b/i',
        '/^\s*(SOURCE|SYSTEM|\\!)\b/i'
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $statement)) {
            throw new RuntimeException('File SQL berisi perintah yang tidak diizinkan untuk keamanan server.');
        }
    }

    if (preg_match('/^\s*USE\s+`?([^`\s;]+)`?\s*$/i', $statement, $matches)) {
        if (strcasecmp($matches[1], $databaseName) !== 0) {
            throw new RuntimeException('File SQL mencoba menggunakan database lain.');
        }

        return ['execute' => false, 'statement' => ''];
    }

    $systemSchemas = ['mysql', 'information_schema', 'performance_schema', 'sys'];
    foreach ($systemSchemas as $schema) {
        if (preg_match('/`?' . preg_quote($schema, '/') . '`?\s*\./i', $statement)) {
            throw new RuntimeException('File SQL mencoba mengakses schema sistem yang tidak diizinkan.');
        }
    }

    if (preg_match_all('/`([^`]+)`\s*\.\s*`([^`]+)`/', $statement, $qualified, PREG_SET_ORDER)) {
        foreach ($qualified as $match) {
            if (strcasecmp($match[1], $databaseName) !== 0) {
                throw new RuntimeException('File SQL mencoba mengakses database lain.');
            }
        }
    }

    return ['execute' => true, 'statement' => $statement];
}

function db_restore_from_file(PDO $pdo, $sourcePath, $databaseName = null) {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('File restore tidak ditemukan atau tidak dapat dibaca.');
    }

    $maxBytes = 64 * 1024 * 1024;
    $size = filesize($sourcePath);

    if ($size === false || $size <= 0) {
        throw new RuntimeException('File SQL kosong.');
    }

    if ($size > $maxBytes) {
        throw new RuntimeException('Ukuran file SQL melebihi batas 64 MB.');
    }

    $sql = file_get_contents($sourcePath);

    if ($sql === false) {
        throw new RuntimeException('File SQL gagal dibaca.');
    }

    if (preg_match('/^\s*DELIMITER\b/im', $sql)) {
        throw new RuntimeException('File SQL dengan perintah DELIMITER belum didukung. Gunakan backup dari halaman ini.');
    }

    $databaseName = $databaseName ?: db_current_database($pdo);
    $statements = db_split_sql_statements($sql);

    if (!$statements) {
        throw new RuntimeException('Tidak ada perintah SQL yang dapat diproses.');
    }

    $executed = 0;

    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($statements as $index => $statement) {
            $validated = db_validate_restore_statement($statement, $databaseName);

            if (!$validated['execute']) {
                continue;
            }

            try {
                $pdo->exec($validated['statement']);
                $executed++;
            } catch (Throwable $e) {
                throw new RuntimeException(
                    'Restore gagal pada perintah ke-' . ($index + 1) . ': ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    } finally {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $ignored) {
        }
    }

    return $executed;
}
