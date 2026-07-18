<?php

function db_backup_directory() {
    $directory = __DIR__ . '/../storage/database-backups';

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan backup tidak dapat dibuat.');
    }

    $htaccess = $directory . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
        );
    }

    $index = $directory . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    return $directory;
}


function db_dump_directory() {
    $directory = __DIR__ . '/../storage/database-dumps';

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder sementara dump database tidak dapat dibuat.');
    }

    $htaccess = $directory . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
        );
    }

    $index = $directory . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    // Remove abandoned temporary dumps. Normal downloads are deleted at shutdown.
    $expiresBefore = time() - 3600;
    foreach (glob($directory . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
        if (is_file($path) && (filemtime($path) ?: 0) < $expiresBefore) {
            @unlink($path);
        }
    }

    return $directory;
}

function db_dump_path($filename) {
    $safeName = db_backup_safe_name($filename);

    if (!$safeName) {
        return null;
    }

    return db_dump_directory() . DIRECTORY_SEPARATOR . $safeName;
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
        $hex = bin2hex((string) $value);
        return $hex === '' ? "X''" : '0x' . $hex;
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

function db_create_backup(PDO $pdo, $targetPath, $databaseName = null, array $options = []) {
    $databaseName = $databaseName ?: db_current_database($pdo);
    $includeStructure = array_key_exists('include_structure', $options)
        ? (bool) $options['include_structure']
        : true;
    $includeData = array_key_exists('include_data', $options)
        ? (bool) $options['include_data']
        : true;

    if (!$includeStructure && !$includeData) {
        throw new InvalidArgumentException('Dump database harus memuat struktur, data, atau keduanya.');
    }

    $artifactType = trim((string) ($options['artifact_type'] ?? 'Backup'));
    $artifactType = preg_replace('/[^A-Za-z0-9 _.-]/', '', $artifactType);
    $artifactType = $artifactType !== '' ? $artifactType : 'Backup';

    if ($includeStructure && $includeData) {
        $modeLabel = 'Structure and data';
    } elseif ($includeStructure) {
        $modeLabel = 'Structure only';
    } else {
        $modeLabel = 'Data only';
    }

    $handle = fopen($targetPath, 'wb');

    if (!$handle) {
        throw new RuntimeException('File ' . strtolower($artifactType) . ' tidak dapat dibuat.');
    }

    try {
        db_backup_write($handle, '-- Teakwave Database ' . $artifactType . "\n");
        db_backup_write($handle, '-- Database: ' . str_replace(["\r", "\n"], '', $databaseName) . "\n");
        db_backup_write($handle, '-- Generated: ' . date('Y-m-d H:i:s') . "\n");
        db_backup_write($handle, '-- Mode: ' . $modeLabel . "\n");
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

            db_backup_write($handle, "-- --------------------------------------------------------\n");

            if ($includeStructure) {
                $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);

                if (!$createRow || empty($createRow[1])) {
                    throw new RuntimeException('Struktur tabel ' . $table . ' tidak dapat dibaca.');
                }

                db_backup_write($handle, '-- Table structure for ' . $quotedTable . "\n");
                db_backup_write($handle, 'DROP TABLE IF EXISTS ' . $quotedTable . ";\n");
                db_backup_write($handle, $createRow[1] . ";\n\n");
            }

            if (!$includeData) {
                continue;
            }

            db_backup_write($handle, '-- Data for ' . $quotedTable . "\n");

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
                db_backup_write($handle, "\n");
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

        if ($includeStructure) {
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

                // Avoid restore failures and privilege surprises when the original DEFINER account does not exist.
                $createSql = preg_replace('/\s+DEFINER=`[^`]+`@`[^`]+`/i', '', $createSql);
                $createSql = preg_replace('/\bSQL\s+SECURITY\s+DEFINER\b/i', 'SQL SECURITY INVOKER', $createSql);

                db_backup_write($handle, "-- --------------------------------------------------------\n");
                db_backup_write($handle, '-- View structure for ' . $quotedView . "\n");
                db_backup_write($handle, 'DROP VIEW IF EXISTS ' . $quotedView . ";\n");
                db_backup_write($handle, $createSql . ";\n\n");
            }
        }

        db_backup_write($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        db_backup_write($handle, '-- End of Teakwave Database ' . $artifactType . "\n");
    } catch (Throwable $e) {
        fclose($handle);
        @unlink($targetPath);
        throw $e;
    }

    fclose($handle);

    if (!file_exists($targetPath) || filesize($targetPath) === 0) {
        @unlink($targetPath);
        throw new RuntimeException($artifactType . ' gagal karena file hasil kosong.');
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

function db_restore_max_bytes() {
    return 64 * 1024 * 1024;
}

function db_restore_max_statements() {
    return 100000;
}

function db_restore_upload_error_message($errorCode) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi upload_max_filesize pada server.',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form upload.',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian. Silakan ulangi.',
        UPLOAD_ERR_NO_FILE => 'Pilih file backup SQL yang akan direstore.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload pada server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload ke penyimpanan sementara.',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi keamanan PHP/server.'
    ];

    return $messages[(int) $errorCode] ?? ('Upload file SQL gagal. Kode error: ' . (int) $errorCode);
}

function db_restore_quarantine_directory() {
    // Prefer the operating-system temporary directory so uploaded SQL never sits
    // under the public document root, even briefly.
    $tempBase = rtrim((string) sys_get_temp_dir(), '/\\');
    $directory = $tempBase
        . DIRECTORY_SEPARATOR
        . 'digitaria-restore-' . substr(hash('sha256', __DIR__), 0, 16);

    if ((!is_dir($directory) && @mkdir($directory, 0700, true)) || is_dir($directory)) {
        @chmod($directory, 0700);
        if (is_writable($directory)) {
            return $directory;
        }
    }

    // Fallback for restrictive shared hosting. Files use a non-executable extension,
    // random names, deny rules, and are deleted immediately after processing.
    $directory = __DIR__ . '/../storage/restore-quarantine';

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder karantina restore tidak dapat dibuat.');
    }

    $htaccess = $directory . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents(
            $htaccess,
            "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
            . "<FilesMatch \".*\">\n"
            . "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
            . "</FilesMatch>\n"
        );
    }

    $index = $directory . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }

    return $directory;
}

function db_restore_acquire_lock() {
    $lockPath = db_backup_directory() . DIRECTORY_SEPARATOR . '.database-restore.lock';
    $handle = fopen($lockPath, 'c+');

    if (!$handle) {
        throw new RuntimeException('Lock restore database tidak dapat dibuat.');
    }

    @chmod($lockPath, 0600);

    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        throw new RuntimeException('Proses backup/restore lain sedang berjalan. Coba lagi setelah proses tersebut selesai.');
    }

    ftruncate($handle, 0);
    fwrite($handle, (string) getmypid());
    fflush($handle);

    return $handle;
}

function db_restore_release_lock($handle) {
    if (is_resource($handle)) {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }
}

function db_restore_original_sql_name($declaredName, $transportName = '') {
    $declaredName = basename(trim((string) $declaredName));
    $transportName = basename(trim((string) $transportName));
    $name = $declaredName !== '' ? $declaredName : $transportName;

    if ($name === '' || strlen($name) > 180 || preg_match('/[\\\/\x00-\x1F\x7F]/', $name)) {
        throw new RuntimeException('Nama file restore tidak valid.');
    }

    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'sql') {
        throw new RuntimeException('File restore harus menggunakan ekstensi .sql.');
    }

    return $name;
}

function db_restore_detect_mime($path) {
    if (!class_exists('finfo')) {
        return 'application/octet-stream';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);

    return is_string($mime) && $mime !== '' ? strtolower($mime) : 'application/octet-stream';
}

function db_restore_sample_looks_like_sql($path) {
    $handle = fopen($path, 'rb');
    if (!$handle) {
        return false;
    }

    $sample = fread($handle, 262144);
    fclose($handle);

    if (!is_string($sample) || $sample === '' || strpos($sample, "\0") !== false) {
        return false;
    }

    $sample = preg_replace('/^\xEF\xBB\xBF/', '', $sample);

    return (bool) preg_match(
        '/\b(CREATE|DROP|ALTER|INSERT|REPLACE|SET|USE|LOCK|UNLOCK|START|COMMIT|TRUNCATE)\b/i',
        $sample
    );
}

/**
 * Validate an uploaded SQL file and move it into a non-public quarantine folder.
 * The browser may transport it with a neutral filename to avoid hosting/WAF rules
 * that reject multipart filenames ending in .sql. The declared original filename
 * is only used for UX; actual safety is enforced by content and statement validation.
 */
function db_quarantine_uploaded_sql(array $upload, $declaredOriginalName = '') {
    $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(db_restore_upload_error_message($error));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('File upload tidak valid atau bukan hasil upload HTTP.');
    }

    $originalName = db_restore_original_sql_name(
        $declaredOriginalName,
        (string) ($upload['name'] ?? '')
    );

    $size = filesize($tmpPath);
    if ($size === false || $size <= 0) {
        throw new RuntimeException('File SQL kosong.');
    }

    if ($size > db_restore_max_bytes()) {
        throw new RuntimeException('Ukuran file SQL melebihi batas 64 MB.');
    }

    $allowedMimes = [
        'text/plain',
        'text/x-sql',
        'application/sql',
        'application/x-sql',
        'application/octet-stream'
    ];
    $mime = db_restore_detect_mime($tmpPath);

    if (!in_array($mime, $allowedMimes, true) && !str_starts_with($mime, 'text/')) {
        throw new RuntimeException('Isi file tidak terdeteksi sebagai file teks/SQL yang valid.');
    }

    if (!db_restore_sample_looks_like_sql($tmpPath)) {
        throw new RuntimeException('Isi file tidak dikenali sebagai dump SQL yang valid.');
    }

    $quarantinePath = db_restore_quarantine_directory()
        . DIRECTORY_SEPARATOR
        . 'restore-' . bin2hex(random_bytes(16)) . '.upload';

    if (!move_uploaded_file($tmpPath, $quarantinePath)) {
        throw new RuntimeException('File SQL gagal dipindahkan ke area karantina server.');
    }

    @chmod($quarantinePath, 0600);

    return [
        'path' => $quarantinePath,
        'original_name' => $originalName,
        'size' => (int) $size,
        'mime' => $mime,
        'sha256' => hash_file('sha256', $quarantinePath) ?: ''
    ];
}

function db_delete_quarantined_restore($path) {
    $path = (string) $path;
    if ($path === '' || !is_file($path)) {
        return;
    }

    $realDirectory = realpath(db_restore_quarantine_directory());
    $realPath = realpath($path);

    if ($realDirectory && $realPath && str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)) {
        @unlink($realPath);
    }
}

/**
 * Return SQL code with quoted string contents masked. This lets security checks
 * inspect actual SQL syntax without false positives from ordinary text data.
 */
function db_sql_code_only($statement) {
    $sql = (string) $statement;
    $length = strlen($sql);
    $output = '';
    $state = 'normal';

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($state === 'single_quote' || $state === 'double_quote') {
            $quote = $state === 'single_quote' ? "'" : '"';

            if ($char === '\\' && $next !== '') {
                $output .= '  ';
                $i++;
                continue;
            }

            if ($char === $quote) {
                if ($next === $quote) {
                    $output .= '  ';
                    $i++;
                    continue;
                }

                $state = 'normal';
                $output .= $char;
                continue;
            }

            $output .= ctype_space($char) ? $char : ' ';
            continue;
        }

        if ($char === "'") {
            $state = 'single_quote';
            $output .= $char;
            continue;
        }

        if ($char === '"') {
            $state = 'double_quote';
            $output .= $char;
            continue;
        }

        $output .= $char;
    }

    return $output;
}

function db_unquote_identifier($identifier) {
    $identifier = trim((string) $identifier);

    if (strlen($identifier) >= 2 && $identifier[0] === '`' && substr($identifier, -1) === '`') {
        $identifier = substr($identifier, 1, -1);
        $identifier = str_replace('``', '`', $identifier);
    }

    return $identifier;
}

function db_validate_restore_statement($statement, $databaseName) {
    $statement = trim((string) $statement);

    if ($statement === '') {
        return ['execute' => false, 'statement' => ''];
    }

    if (strlen($statement) > 32 * 1024 * 1024) {
        throw new RuntimeException('Satu perintah SQL terlalu besar untuk diproses dengan aman.');
    }

    $code = db_sql_code_only($statement);

    // Standard full dumps may include CREATE DATABASE IF NOT EXISTS for the same database.
    // Do not execute it; only accept it as harmless metadata for the active database.
    if (preg_match('/^\s*CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?([^`\s]+)`?(?:\s+DEFAULT\s+CHARACTER\s+SET\s+\w+)?(?:\s+COLLATE\s+\w+)?\s*$/i', $code, $createDatabase)) {
        if (strcasecmp($createDatabase[1], $databaseName) !== 0) {
            throw new RuntimeException('File SQL mencoba membuat database lain.');
        }

        return ['execute' => false, 'statement' => ''];
    }

    $dangerousPatterns = [
        '/^\s*(CREATE|DROP|ALTER)\s+DATABASE\b/i',
        '/^\s*(CREATE|ALTER|DROP|RENAME)\s+USER\b/i',
        '/^\s*(GRANT|REVOKE|SET\s+PASSWORD|FLUSH\s+PRIVILEGES|SHUTDOWN)\b/i',
        '/^\s*(INSTALL|UNINSTALL)\s+PLUGIN\b/i',
        '/^\s*(CREATE|ALTER|DROP)\s+(PROCEDURE|FUNCTION|TRIGGER|EVENT|SERVER|TABLESPACE)\b/i',
        '/^\s*(CALL|PREPARE|EXECUTE|DEALLOCATE|HANDLER|DO)\b/i',
        '/^\s*(LOAD\s+(DATA|XML)|SOURCE|SYSTEM|\\!)\b/i',
        '/\bINTO\s+(OUTFILE|DUMPFILE)\b/i',
        '/\bLOAD_FILE\s*\(/i',
        '/\b(SLEEP|BENCHMARK|GET_LOCK|RELEASE_LOCK|IS_USED_LOCK|MASTER_POS_WAIT)\s*\(/i',
        '/\bSET\s+(GLOBAL|PERSIST|PERSIST_ONLY)\b/i',
        '/\bDEFINER\s*=/i',
        '/\bSQL\s+SECURITY\s+DEFINER\b/i',
        '/\b(DATA|INDEX)\s+DIRECTORY\s*=/i',
        '/\bENGINE\s*=\s*FEDERATED\b/i',
        '/\bCONNECTION\s*=/i'
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $code)) {
            throw new RuntimeException('File SQL berisi perintah yang tidak diizinkan untuk keamanan server.');
        }
    }

    if (preg_match('/^\s*USE\s+`?([^`\s;]+)`?\s*$/i', $code, $matches)) {
        if (strcasecmp($matches[1], $databaseName) !== 0) {
            throw new RuntimeException('File SQL mencoba menggunakan database lain.');
        }

        return ['execute' => false, 'statement' => ''];
    }

    $systemSchemas = ['mysql', 'information_schema', 'performance_schema', 'sys'];
    foreach ($systemSchemas as $schema) {
        if (preg_match('/`?' . preg_quote($schema, '/') . '`?\s*\./i', $code)) {
            throw new RuntimeException('File SQL mencoba mengakses schema sistem yang tidak diizinkan.');
        }
    }

    // Validate database-qualified object names in SQL object contexts.
    $identifier = '(?:`(?:``|[^`])+`|[a-zA-Z_][a-zA-Z0-9_$]*)';
    $qualifiedPattern = '/\b(?:FROM|JOIN|INTO|UPDATE|TABLE|VIEW|REFERENCES|ON|LIKE|TO|RENAME\s+TO)\s+(' . $identifier . ')\s*\.\s*(' . $identifier . ')/i';
    if (preg_match_all($qualifiedPattern, $code, $qualified, PREG_SET_ORDER)) {
        foreach ($qualified as $match) {
            $schema = db_unquote_identifier($match[1]);
            if (strcasecmp($schema, $databaseName) !== 0) {
                throw new RuntimeException('File SQL mencoba mengakses database lain.');
            }
        }
    }

    $allowedPatterns = [
        '/^\s*SET\s+NAMES\b/i',
        '/^\s*SET\s+(?!GLOBAL\b|PERSIST\b|PERSIST_ONLY\b).+/is',
        '/^\s*(START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK)\s*$/i',
        '/^\s*LOCK\s+TABLES\b/is',
        '/^\s*UNLOCK\s+TABLES\s*$/i',
        '/^\s*DROP\s+(?:TEMPORARY\s+)?TABLE\b/is',
        '/^\s*CREATE\s+(?:TEMPORARY\s+)?TABLE\b/is',
        '/^\s*ALTER\s+TABLE\b/is',
        '/^\s*TRUNCATE\s+TABLE\b/is',
        '/^\s*RENAME\s+TABLE\b/is',
        '/^\s*INSERT\s+(?:IGNORE\s+)?INTO\b/is',
        '/^\s*REPLACE\s+(?:INTO\s+)?\b/is',
        '/^\s*DROP\s+VIEW\b/is',
        '/^\s*CREATE\s+(?:OR\s+REPLACE\s+)?(?:ALGORITHM\s*=\s*\w+\s+)?(?:SQL\s+SECURITY\s+INVOKER\s+)?VIEW\b/is',
        '/^\s*CREATE\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?INDEX\b/is',
        '/^\s*DROP\s+INDEX\b/is'
    ];

    foreach ($allowedPatterns as $pattern) {
        if (preg_match($pattern, $code)) {
            return ['execute' => true, 'statement' => $statement];
        }
    }

    throw new RuntimeException(
        'File SQL memuat jenis perintah yang tidak didukung. Gunakan dump database standar atau backup dari halaman ini.'
    );
}

function db_prepare_restore_statements($sourcePath, $databaseName = null) {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('File restore tidak ditemukan atau tidak dapat dibaca.');
    }

    $size = filesize($sourcePath);

    if ($size === false || $size <= 0) {
        throw new RuntimeException('File SQL kosong.');
    }

    if ($size > db_restore_max_bytes()) {
        throw new RuntimeException('Ukuran file SQL melebihi batas 64 MB.');
    }

    $sql = file_get_contents($sourcePath);

    if ($sql === false) {
        throw new RuntimeException('File SQL gagal dibaca.');
    }

    if (strpos($sql, "\0") !== false) {
        throw new RuntimeException('File SQL mengandung data biner/NUL yang tidak diizinkan.');
    }

    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

    if (preg_match('/^\s*DELIMITER\b/im', $sql)) {
        throw new RuntimeException('File SQL dengan perintah DELIMITER tidak didukung untuk alasan keamanan.');
    }

    if (!$databaseName) {
        throw new RuntimeException('Nama database target wajib ditentukan sebelum validasi restore.');
    }

    $rawStatements = db_split_sql_statements($sql);

    if (!$rawStatements) {
        throw new RuntimeException('Tidak ada perintah SQL yang dapat diproses.');
    }

    if (count($rawStatements) > db_restore_max_statements()) {
        throw new RuntimeException('Jumlah perintah SQL melebihi batas keamanan.');
    }

    $statements = [];
    foreach ($rawStatements as $statement) {
        $validated = db_validate_restore_statement($statement, $databaseName);
        if ($validated['execute']) {
            $statements[] = $validated['statement'];
        }
    }

    if (!$statements) {
        throw new RuntimeException('Tidak ada perintah SQL yang diizinkan untuk dijalankan.');
    }

    return $statements;
}



function db_reset_validate_teakwave_dump($sourcePath, $databaseName = null) {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) {
        throw new RuntimeException('File dump reset tidak ditemukan atau tidak dapat dibaca.');
    }

    $databaseName = $databaseName ?: '';
    $sql = file_get_contents($sourcePath);

    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('File dump reset kosong atau gagal dibaca.');
    }

    if (!preg_match('/^-- Teakwave Database Dump\R/m', $sql)) {
        throw new RuntimeException('Reset hanya menerima file yang dibuat oleh fasilitas Dump Database Teakwave.');
    }

    if (!preg_match('/^-- Mode: Structure and data\R/m', $sql)) {
        throw new RuntimeException('Reset database wajib menggunakan dump dengan mode Struktur dan data.');
    }

    if (!preg_match('/^-- End of Teakwave Database Dump\s*$/m', $sql)) {
        throw new RuntimeException('File dump tidak lengkap atau penanda akhir file tidak ditemukan.');
    }

    if (preg_match('/^-- Database:\s*([^\r\n]+)$/m', $sql, $databaseMatch)) {
        $dumpDatabase = trim($databaseMatch[1]);
        if ($databaseName !== '' && strcasecmp($dumpDatabase, $databaseName) !== 0) {
            throw new RuntimeException('Nama database pada dump tidak sama dengan database aktif.');
        }
    } else {
        throw new RuntimeException('Identitas database pada file dump tidak ditemukan.');
    }

    $requiredTables = ['users', 'roles', 'role_permissions', 'website_settings'];
    foreach ($requiredTables as $table) {
        if (!preg_match('/\bCREATE\s+TABLE\s+`?' . preg_quote($table, '/') . '`?\b/i', $sql)) {
            throw new RuntimeException('Dump reset tidak lengkap karena struktur tabel wajib ' . $table . ' tidak ditemukan.');
        }
    }

    $createCount = preg_match_all('/\bCREATE\s+TABLE\s+`?[^`\s(]+`?/i', $sql, $unused);
    if ($createCount < count($requiredTables)) {
        throw new RuntimeException('Jumlah struktur tabel pada dump tidak mencukupi untuk reset database.');
    }

    $statementCount = db_restore_preflight_file($sourcePath, $databaseName);

    return [
        'statement_count' => $statementCount,
        'table_count' => $createCount,
        'sha256' => hash_file('sha256', $sourcePath) ?: ''
    ];
}

function db_reset_drop_all_objects(PDO $pdo) {
    $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
    $views = [];
    $tables = [];

    foreach ($objects as $row) {
        $name = (string) ($row[0] ?? '');
        $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
        if ($name === '') continue;

        if ($type === 'VIEW') {
            $views[] = db_quote_identifier($name);
        } else {
            $tables[] = db_quote_identifier($name);
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        foreach ($views as $view) {
            $pdo->exec('DROP VIEW IF EXISTS ' . $view);
        }
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table);
        }
    } finally {
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $ignored) {}
    }

    return count($views) + count($tables);
}

function db_reset_from_teakwave_dump(PDO $pdo, $sourcePath, $databaseName = null) {
    $databaseName = $databaseName ?: db_current_database($pdo);
    $validation = db_reset_validate_teakwave_dump($sourcePath, $databaseName);
    $dropped = db_reset_drop_all_objects($pdo);
    $executed = db_restore_from_file($pdo, $sourcePath, $databaseName);

    return [
        'dropped_objects' => $dropped,
        'executed_statements' => $executed,
        'validated_statements' => $validation['statement_count'],
        'dump_tables' => $validation['table_count'],
        'sha256' => $validation['sha256']
    ];
}

function db_restore_preflight_file($sourcePath, $databaseName = null) {
    return count(db_prepare_restore_statements($sourcePath, $databaseName));
}

function db_restore_from_file(PDO $pdo, $sourcePath, $databaseName = null) {
    $databaseName = $databaseName ?: db_current_database($pdo);
    $statements = db_prepare_restore_statements($sourcePath, $databaseName);
    $executed = 0;

    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($statements as $index => $statement) {
            try {
                $pdo->exec($statement);
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
