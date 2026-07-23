<?php

/**
 * Teakwave media backup and restore helpers.
 *
 * Archives only local image files referenced by Products, Contents, Videos,
 * and Brands. Database records are intentionally not changed by media restore;
 * files are returned to their original project-relative paths.
 */

function media_backup_project_root() {
    $root = realpath(__DIR__ . '/../..');
    return $root !== false ? $root : dirname(__DIR__, 2);
}

function media_backup_secure_directory($name) {
    $safeName = preg_replace('/[^a-z0-9_-]/i', '', (string) $name);
    if ($safeName === '') {
        throw new RuntimeException('Nama folder penyimpanan media tidak valid.');
    }

    $directory = __DIR__ . '/../storage/' . $safeName;

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan media tidak dapat dibuat.');
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

function media_backup_temp_directory() {
    return media_backup_secure_directory('media-temp');
}

function media_backup_safety_directory() {
    return media_backup_secure_directory('media-backups');
}

function media_backup_quarantine_directory() {
    return media_backup_secure_directory('media-restore-quarantine');
}

function media_backup_zip_available() {
    return class_exists('ZipArchive');
}

function media_backup_require_zip() {
    if (!media_backup_zip_available()) {
        throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif. Aktifkan extension zip pada hosting agar backup dan restore foto dapat digunakan.');
    }
}

function media_backup_categories() {
    return [
        'products' => 'Foto Produk',
        'contents' => 'Foto Contents',
        'videos' => 'Thumbnail Video',
        'brands' => 'Logo Brands'
    ];
}

function media_backup_allowed_prefixes() {
    return ['uploads/', 'produk/', 'assets/img/'];
}

function media_backup_allowed_extensions() {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'ico'];
}

function media_backup_table_exists(PDO $pdo, $table) {
    static $cache = [];
    $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
    if ($table === '') return false;

    $key = spl_object_id($pdo) . ':' . strtolower($table);
    if (array_key_exists($key, $cache)) return $cache[$key];

    try {
        // Use information_schema instead of SHOW TABLES so this check remains
        // compatible when PDO native prepared statements are enabled.
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?
             LIMIT 1'
        );
        $stmt->execute([$table]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[media-backup] Table check failed for ' . $table . ': ' . $e->getMessage());
        $cache[$key] = false;
    }

    return $cache[$key];
}

function media_backup_column_exists(PDO $pdo, $table, $column) {
    static $cache = [];
    $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
    $column = preg_replace('/[^a-z0-9_]/i', '', (string) $column);
    if ($table === '' || $column === '') return false;

    $key = spl_object_id($pdo) . ':' . strtolower($table) . ':' . strtolower($column);
    if (array_key_exists($key, $cache)) return $cache[$key];

    if (!media_backup_table_exists($pdo, $table)) {
        $cache[$key] = false;
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[media-backup] Column check failed for ' . $table . '.' . $column . ': ' . $e->getMessage());
        $cache[$key] = false;
    }

    return $cache[$key];
}

function media_backup_is_allowed_relative_path($relativePath) {
    $path = str_replace('\\', '/', trim((string) $relativePath));
    if ($path === '' || str_contains($path, "\0")) return false;
    if ($path[0] === '/' || preg_match('/^[A-Za-z]:\//', $path)) return false;

    $segments = explode('/', $path);
    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') return false;
    }

    $allowedPrefix = false;
    foreach (media_backup_allowed_prefixes() as $prefix) {
        if (str_starts_with($path, $prefix)) {
            $allowedPrefix = true;
            break;
        }
    }
    if (!$allowedPrefix) return false;

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($extension, media_backup_allowed_extensions(), true);
}

function media_backup_reference_to_relative_path($reference) {
    $reference = html_entity_decode(trim((string) $reference), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($reference === '' || str_contains($reference, "\0")) return null;
    if (preg_match('#^(?:data:|blob:|javascript:|mailto:|tel:)#i', $reference)) return null;

    $path = $reference;
    if (preg_match('#^(?:https?:)?//#i', $reference)) {
        $parsed = parse_url($reference);
        if (!is_array($parsed) || empty($parsed['path'])) return null;
        $path = (string) $parsed['path'];
    } else {
        $path = preg_split('/[?#]/', $path, 2)[0] ?? $path;
    }

    $path = rawurldecode($path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#/+#', '/', $path);
    $path = trim($path);

    // Locate one of the public media roots even when the database stores a full
    // URL, a subfolder URL, or paths such as ../uploads/file.webp.
    $candidates = [];
    foreach (media_backup_allowed_prefixes() as $prefix) {
        $needle = '/' . rtrim($prefix, '/');
        $position = stripos('/' . ltrim($path, '/'), $needle . '/');
        if ($position !== false) {
            $normalized = substr('/' . ltrim($path, '/'), $position + 1);
            $candidates[] = $normalized;
        }
    }

    $trimmed = preg_replace('#^(?:\.\./|\./)+#', '', ltrim($path, '/'));
    if ($trimmed !== '') $candidates[] = $trimmed;

    foreach ($candidates as $candidate) {
        $candidate = preg_replace('#/+#', '/', str_replace('\\', '/', $candidate));
        if (!media_backup_is_allowed_relative_path($candidate)) continue;

        $absolute = media_backup_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        $real = realpath($absolute);
        if ($real === false || !is_file($real) || !is_readable($real)) continue;

        $projectRoot = rtrim(str_replace('\\', '/', media_backup_project_root()), '/');
        $realNormalized = str_replace('\\', '/', $real);
        if (!str_starts_with($realNormalized . '/', $projectRoot . '/')) continue;

        return str_replace('\\', '/', substr($realNormalized, strlen($projectRoot) + 1));
    }

    return null;
}

function media_backup_extract_html_references($html) {
    $html = (string) $html;
    if ($html === '') return [];

    $references = [];

    if (class_exists('DOMDocument')) {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        if ($loaded) {
            foreach ($document->getElementsByTagName('img') as $image) {
                $src = trim((string) $image->getAttribute('src'));
                if ($src !== '') $references[] = $src;

                $srcset = trim((string) $image->getAttribute('srcset'));
                if ($srcset !== '') {
                    foreach (explode(',', $srcset) as $candidate) {
                        $url = trim((string) preg_split('/\s+/', trim($candidate), 2)[0]);
                        if ($url !== '') $references[] = $url;
                    }
                }
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }

    if (preg_match_all('/\b(?:src|data-src)\s*=\s*(["\'])(.*?)\1/is', $html, $matches)) {
        foreach ($matches[2] as $value) {
            if (trim($value) !== '') $references[] = trim($value);
        }
    }

    if (preg_match_all('/url\(\s*(["\']?)(.*?)\1\s*\)/is', $html, $matches)) {
        foreach ($matches[2] as $value) {
            if (trim($value) !== '') $references[] = trim($value);
        }
    }

    return array_values(array_unique($references));
}

function media_backup_reference_targets_local_root($reference) {
    $reference = str_replace('\\', '/', html_entity_decode(trim((string) $reference), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($reference === '' || preg_match('#^(?:data:|blob:|javascript:|mailto:|tel:)#i', $reference)) return false;
    return (bool) preg_match('#(?:^|/)(?:uploads|produk|assets/img)/#i', $reference);
}

function media_backup_add_reference(&$files, &$missing, $category, $reference, $source) {
    $reference = trim((string) $reference);
    if ($reference === '' || !media_backup_reference_targets_local_root($reference)) return;

    $relative = media_backup_reference_to_relative_path($reference);
    if ($relative === null) {
        $missingKey = $category . '|' . $reference;
        if (!isset($missing[$missingKey])) {
            $missing[$missingKey] = [
                'category' => $category,
                'reference' => utf8_substr($reference, 0, 500),
                'source' => utf8_substr((string) $source, 0, 300)
            ];
        }
        return;
    }

    $absolute = media_backup_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!isset($files[$relative])) {
        $size = filesize($absolute);
        if ($size === false) return;

        $files[$relative] = [
            'path' => $relative,
            'absolute' => $absolute,
            'size' => (int) $size,
            'categories' => [],
            'sources' => []
        ];
    }

    if (!in_array($category, $files[$relative]['categories'], true)) {
        $files[$relative]['categories'][] = $category;
    }

    if (count($files[$relative]['sources']) < 30 && !in_array($source, $files[$relative]['sources'], true)) {
        $files[$relative]['sources'][] = utf8_substr((string) $source, 0, 300);
    }
}


function media_backup_add_relative_file(&$files, $category, $relativePath, $source) {
    $relativePath = preg_replace('#/+#', '/', str_replace('\\', '/', trim((string) $relativePath)));
    if (!media_backup_is_allowed_relative_path($relativePath)) return;

    $projectRoot = media_backup_project_root();
    $absolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $real = realpath($absolute);
    if ($real === false || !is_file($real) || !is_readable($real) || is_link($real)) return;

    $projectRootNormalized = rtrim(str_replace('\\', '/', realpath($projectRoot) ?: $projectRoot), '/');
    $realNormalized = str_replace('\\', '/', $real);
    if (!str_starts_with($realNormalized . '/', $projectRootNormalized . '/')) return;

    $relative = ltrim(substr($realNormalized, strlen($projectRootNormalized)), '/');
    if (!media_backup_is_allowed_relative_path($relative)) return;

    if (!isset($files[$relative])) {
        $size = filesize($real);
        if ($size === false) return;

        $files[$relative] = [
            'path' => $relative,
            'absolute' => $real,
            'size' => (int) $size,
            'categories' => [],
            'sources' => []
        ];
    }

    if (!in_array($category, $files[$relative]['categories'], true)) {
        $files[$relative]['categories'][] = $category;
    }

    if (count($files[$relative]['sources']) < 30 && !in_array($source, $files[$relative]['sources'], true)) {
        $files[$relative]['sources'][] = utf8_substr((string) $source, 0, 300);
    }
}

function media_backup_scan_directory(&$files, $category, $relativeDirectory, $sourceLabel, $filenamePattern = null) {
    $relativeDirectory = trim(str_replace('\\', '/', (string) $relativeDirectory), '/');
    if ($relativeDirectory === '') return;

    $directory = media_backup_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($directory) || !is_readable($directory)) return;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile() || $fileInfo->isLink()) continue;

            $filename = $fileInfo->getFilename();
            if ($filenamePattern !== null && !preg_match($filenamePattern, $filename)) continue;

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, media_backup_allowed_extensions(), true)) continue;

            $absolute = str_replace('\\', '/', $fileInfo->getPathname());
            $root = rtrim(str_replace('\\', '/', media_backup_project_root()), '/');
            if (!str_starts_with($absolute . '/', $root . '/')) continue;

            $relative = ltrim(substr($absolute, strlen($root)), '/');
            media_backup_add_relative_file(
                $files,
                $category,
                $relative,
                $sourceLabel . ': ' . $relative
            );
        }
    } catch (Throwable $e) {
        error_log('[media-backup] Directory scan failed for ' . $relativeDirectory . ': ' . $e->getMessage());
    }
}

function media_backup_collect_folder_fallbacks(&$files, $selected) {
    // Product seed images are stored directly in produk/. Uploaded product
    // images follow the *-product-main-* and *-product-gallery-* naming format.
    if (isset($selected['products'])) {
        media_backup_scan_directory($files, 'products', 'produk', 'folder produk');
        media_backup_scan_directory(
            $files,
            'products',
            'uploads',
            'folder uploads produk',
            '/(?:^|[-_])product(?:[-_](?:main|gallery)(?:[-_]\d+)?)?(?:[-_.]|$)/i'
        );
    }

    // Content uploads use the content-image suffix. Static images embedded in
    // content are still collected from the database body before this fallback.
    if (isset($selected['contents'])) {
        media_backup_scan_directory(
            $files,
            'contents',
            'uploads',
            'folder uploads contents',
            '/(?:^|[-_])content[-_]image(?:[-_]\d+)?(?:[-_.]|$)/i'
        );
    }

    // Only custom local thumbnails are backed up. Automatic YouTube thumbnails
    // remain external and are intentionally skipped.
    if (isset($selected['videos'])) {
        media_backup_scan_directory(
            $files,
            'videos',
            'uploads',
            'folder uploads video',
            '/(?:^|[-_])video[-_]thumbnail(?:[-_.]|$)/i'
        );
    }

    if (isset($selected['brands'])) {
        media_backup_scan_directory(
            $files,
            'brands',
            'uploads',
            'folder uploads brands',
            '/(?:^|[-_])brand[-_]logo(?:[-_.]|$)/i'
        );
        media_backup_scan_directory(
            $files,
            'brands',
            'assets/img',
            'logo brand statis',
            '/^logo-(?!(?:teakwave|whatsapp|shopee|tokopedia)(?:[-_.]|$)).+\.(?:jpe?g|png|gif|webp|avif|ico)$/i'
        );
    }
}

function media_backup_collect_references(PDO $pdo, $selectedCategories) {
    $known = media_backup_categories();
    $selected = [];
    foreach ((array) $selectedCategories as $category) {
        $category = strtolower(preg_replace('/[^a-z]/', '', (string) $category));
        if (isset($known[$category])) $selected[$category] = true;
    }

    if (!$selected) {
        throw new RuntimeException('Pilih minimal satu kategori foto yang akan dibackup.');
    }

    $files = [];
    $missing = [];

    if (isset($selected['products']) && media_backup_table_exists($pdo, 'products') && media_backup_column_exists($pdo, 'products', 'image')) {
        $rows = $pdo->query('SELECT id, name, image FROM products WHERE image IS NOT NULL AND image <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            media_backup_add_reference($files, $missing, 'products', $row['image'] ?? '', 'products #' . (int) $row['id'] . ' ' . ($row['name'] ?? ''));
        }

        if (media_backup_table_exists($pdo, 'product_images') && media_backup_column_exists($pdo, 'product_images', 'image_path')) {
            $rows = $pdo->query('SELECT id, product_id, image_path FROM product_images WHERE image_path IS NOT NULL AND image_path <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                media_backup_add_reference($files, $missing, 'products', $row['image_path'] ?? '', 'product_images #' . (int) $row['id'] . ' product #' . (int) $row['product_id']);
            }
        }
    }

    if (isset($selected['contents']) && media_backup_table_exists($pdo, 'contents')) {
        if (media_backup_column_exists($pdo, 'contents', 'body')) {
            $rows = $pdo->query('SELECT id, title, body FROM contents WHERE body IS NOT NULL AND body <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                foreach (media_backup_extract_html_references($row['body'] ?? '') as $reference) {
                    media_backup_add_reference($files, $missing, 'contents', $reference, 'contents #' . (int) $row['id'] . ' ' . ($row['title'] ?? ''));
                }
            }
        }

        if (media_backup_table_exists($pdo, 'content_images') && media_backup_column_exists($pdo, 'content_images', 'image_path')) {
            $rows = $pdo->query('SELECT id, content_id, image_path FROM content_images WHERE image_path IS NOT NULL AND image_path <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                media_backup_add_reference($files, $missing, 'contents', $row['image_path'] ?? '', 'content_images #' . (int) $row['id'] . ' content #' . (int) $row['content_id']);
            }
        }
    }

    if (isset($selected['videos']) && media_backup_table_exists($pdo, 'videos') && media_backup_column_exists($pdo, 'videos', 'thumbnail')) {
        $rows = $pdo->query('SELECT id, title, thumbnail FROM videos WHERE thumbnail IS NOT NULL AND thumbnail <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            media_backup_add_reference($files, $missing, 'videos', $row['thumbnail'] ?? '', 'videos #' . (int) $row['id'] . ' ' . ($row['title'] ?? ''));
        }
    }

    if (isset($selected['brands']) && media_backup_table_exists($pdo, 'brands') && media_backup_column_exists($pdo, 'brands', 'logo')) {
        $rows = $pdo->query('SELECT id, name, logo FROM brands WHERE logo IS NOT NULL AND logo <> \'\'')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            media_backup_add_reference($files, $missing, 'brands', $row['logo'] ?? '', 'brands #' . (int) $row['id'] . ' ' . ($row['name'] ?? ''));
        }
    }

    // Do not depend solely on database references. Old installations may use
    // inconsistent relative paths or have empty logo/thumbnail columns even
    // though the local media files still exist on disk.
    media_backup_collect_folder_fallbacks($files, $selected);

    ksort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return [
        'categories' => array_keys($selected),
        'files' => array_values($files),
        'missing' => array_values($missing)
    ];
}

function media_backup_random_filename($prefix, $extension) {
    return preg_replace('/[^a-z0-9_-]/i', '-', (string) $prefix)
        . '-' . date('Ymd-His')
        . '-' . substr(bin2hex(random_bytes(5)), 0, 10)
        . '.' . ltrim((string) $extension, '.');
}

function media_backup_write_json_file($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($path, $json, LOCK_EX) === false) {
        throw new RuntimeException('Manifest backup media gagal dibuat.');
    }
}

function media_backup_create_archive(PDO $pdo, $selectedCategories) {
    media_backup_require_zip();

    $collection = media_backup_collect_references($pdo, $selectedCategories);
    if (!$collection['files']) {
        throw new RuntimeException('Tidak ada file gambar lokal yang ditemukan. Pastikan kategori dipilih, folder produk/, uploads/, atau assets/img/ berisi gambar dan dapat dibaca PHP. Thumbnail YouTube eksternal memang tidak termasuk dalam backup.');
    }

    $filename = media_backup_random_filename('teakwave-media', 'zip');
    $targetPath = media_backup_temp_directory() . DIRECTORY_SEPARATOR . $filename;
    $manifestPath = media_backup_temp_directory() . DIRECTORY_SEPARATOR . media_backup_random_filename('manifest', 'json');

    $manifestFiles = [];
    $totalSize = 0;
    foreach ($collection['files'] as $file) {
        $hash = hash_file('sha256', $file['absolute']);
        if ($hash === false) {
            throw new RuntimeException('Hash file gagal dibuat: ' . $file['path']);
        }

        $manifestFiles[] = [
            'path' => $file['path'],
            'archive_path' => 'files/' . $file['path'],
            'size' => (int) $file['size'],
            'sha256' => $hash,
            'categories' => $file['categories'],
            'sources' => $file['sources']
        ];
        $totalSize += (int) $file['size'];
    }

    $manifest = [
        'format' => 'teakwave-media-backup',
        'version' => 1,
        'created_at' => date(DATE_ATOM),
        'categories' => $collection['categories'],
        'file_count' => count($manifestFiles),
        'total_uncompressed_size' => $totalSize,
        'files' => $manifestFiles,
        'unresolved_references' => $collection['missing']
    ];

    media_backup_write_json_file($manifestPath, $manifest);

    $zip = new ZipArchive();
    $opened = $zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($opened !== true) {
        @unlink($manifestPath);
        throw new RuntimeException('Arsip ZIP backup media gagal dibuat. Kode ZipArchive: ' . $opened . '.');
    }

    try {
        if (!$zip->addFile($manifestPath, 'manifest.json')) {
            throw new RuntimeException('Manifest gagal dimasukkan ke arsip backup.');
        }

        $readme = "TEAKWAVE MEDIA BACKUP\n"
            . "Created: " . $manifest['created_at'] . "\n"
            . "Categories: " . implode(', ', $manifest['categories']) . "\n"
            . "Files: " . count($manifestFiles) . "\n\n"
            . "Restore this archive only through utakatik/backup-restore.php.\n"
            . "Database records are not included in this archive.\n";
        $zip->addFromString('README.txt', $readme);

        foreach ($collection['files'] as $file) {
            $archivePath = 'files/' . $file['path'];
            if (!$zip->addFile($file['absolute'], $archivePath)) {
                throw new RuntimeException('File gagal dimasukkan ke arsip: ' . $file['path']);
            }
            if (method_exists($zip, 'setCompressionName')) {
                // Images are already compressed. Storing avoids unnecessary CPU usage.
                @$zip->setCompressionName($archivePath, ZipArchive::CM_STORE);
            }
        }
    } catch (Throwable $e) {
        $zip->close();
        @unlink($targetPath);
        @unlink($manifestPath);
        throw $e;
    }

    if (!$zip->close()) {
        @unlink($targetPath);
        @unlink($manifestPath);
        throw new RuntimeException('Arsip ZIP backup media gagal diselesaikan.');
    }

    @unlink($manifestPath);

    return [
        'path' => $targetPath,
        'filename' => $filename,
        'file_count' => count($manifestFiles),
        'total_size' => $totalSize,
        'missing_count' => count($collection['missing']),
        'categories' => $collection['categories']
    ];
}

function media_backup_ini_bytes($value) {
    $value = trim((string) $value);
    if ($value === '') return 0;

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') return (int) round($number * 1024 * 1024 * 1024);
    if ($unit === 'm') return (int) round($number * 1024 * 1024);
    if ($unit === 'k') return (int) round($number * 1024);
    return (int) round($number);
}

function media_backup_restore_max_bytes() {
    $featureLimit = 256 * 1024 * 1024;
    $limits = [$featureLimit];

    $upload = media_backup_ini_bytes(ini_get('upload_max_filesize'));
    $post = media_backup_ini_bytes(ini_get('post_max_size'));
    if ($upload > 0) $limits[] = $upload;
    if ($post > 0) $limits[] = $post;

    return max(1, min($limits));
}

function media_backup_format_bytes($bytes) {
    $bytes = max(0, (float) $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $index = 0;
    while ($bytes >= 1024 && $index < count($units) - 1) {
        $bytes /= 1024;
        $index++;
    }
    return number_format($bytes, $index === 0 ? 0 : 2, ',', '.') . ' ' . $units[$index];
}

function media_backup_validate_archive_entry_name($name) {
    $name = str_replace('\\', '/', (string) $name);
    if ($name === '' || str_contains($name, "\0")) return false;
    if ($name[0] === '/' || preg_match('/^[A-Za-z]:\//', $name)) return false;

    foreach (explode('/', $name) as $segment) {
        if ($segment === '..' || $segment === '.') return false;
    }

    return true;
}

function media_backup_zip_entry_is_symlink(ZipArchive $zip, $index) {
    $opsys = 0;
    $attributes = 0;
    if (!method_exists($zip, 'getExternalAttributesIndex')) return false;
    if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) return false;

    // Unix file type bits are stored in the high 16 bits.
    $mode = ($attributes >> 16) & 0xF000;
    return $mode === 0xA000;
}

function media_backup_validate_image_file($path, $relativePath) {
    $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    if (!in_array($extension, media_backup_allowed_extensions(), true)) {
        throw new RuntimeException('Ekstensi gambar tidak diizinkan: ' . $relativePath);
    }

    $size = filesize($path);
    if ($size === false || $size <= 0) {
        throw new RuntimeException('File gambar kosong atau tidak dapat dibaca: ' . $relativePath);
    }

    $imageInfo = @getimagesize($path);
    if ($imageInfo === false) {
        throw new RuntimeException('Isi file bukan gambar yang valid: ' . $relativePath);
    }

    $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif',
        'image/x-icon', 'image/vnd.microsoft.icon'
    ];
    $mime = strtolower((string) ($imageInfo['mime'] ?? ''));

    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Tipe gambar tidak diizinkan: ' . $relativePath);
    }
}

function media_backup_copy_stream_to_file($stream, $targetPath, $expectedSize, $expectedHash) {
    $directory = dirname($targetPath);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder staging restore tidak dapat dibuat.');
    }

    $handle = fopen($targetPath, 'wb');
    if (!$handle) {
        throw new RuntimeException('File staging restore tidak dapat dibuat.');
    }

    $hash = hash_init('sha256');
    $written = 0;

    try {
        while (!feof($stream)) {
            $chunk = fread($stream, 1024 * 1024);
            if ($chunk === false) {
                throw new RuntimeException('Gagal membaca data dari arsip media.');
            }
            if ($chunk === '') continue;

            $length = strlen($chunk);
            $offset = 0;
            while ($offset < $length) {
                $result = fwrite($handle, substr($chunk, $offset));
                if ($result === false || $result === 0) {
                    throw new RuntimeException('Gagal menulis file staging restore.');
                }
                $offset += $result;
                $written += $result;
            }
            hash_update($hash, $chunk);

            if ($written > $expectedSize || $written > 64 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file dalam arsip melebihi batas keamanan.');
            }
        }
    } finally {
        fclose($handle);
    }

    if ($written !== $expectedSize) {
        throw new RuntimeException('Ukuran file dalam arsip tidak sesuai manifest.');
    }

    $actualHash = hash_final($hash);
    if (!hash_equals(strtolower($expectedHash), strtolower($actualHash))) {
        throw new RuntimeException('Checksum file dalam arsip tidak sesuai manifest.');
    }
}

function media_backup_remove_tree($path) {
    if (!is_dir($path)) {
        if (is_file($path)) @unlink($path);
        return;
    }

    $items = scandir($path);
    if ($items === false) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $child = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($child) && !is_link($child)) {
            media_backup_remove_tree($child);
        } else {
            @unlink($child);
        }
    }
    @rmdir($path);
}

function media_backup_create_safety_archive($existingFiles) {
    if (!$existingFiles) return null;
    media_backup_require_zip();

    $filename = media_backup_random_filename('before-media-restore', 'zip');
    $targetPath = media_backup_safety_directory() . DIRECTORY_SEPARATOR . $filename;
    $manifest = [
        'format' => 'teakwave-media-backup',
        'categories' => ['safety'],
        'version' => 1,
        'created_at' => date(DATE_ATOM),
        'file_count' => count($existingFiles),
        'files' => []
    ];

    $zip = new ZipArchive();
    $opened = $zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($opened !== true) {
        throw new RuntimeException('Backup pengaman media gagal dibuat.');
    }

    try {
        foreach ($existingFiles as $relative => $absolute) {
            $size = filesize($absolute);
            $hash = hash_file('sha256', $absolute);
            if ($size === false || $hash === false || !$zip->addFile($absolute, 'files/' . $relative)) {
                throw new RuntimeException('Backup pengaman gagal menyimpan: ' . $relative);
            }
            $manifest['files'][] = [
                'path' => $relative,
                'archive_path' => 'files/' . $relative,
                'size' => (int) $size,
                'sha256' => $hash
            ];
        }
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } catch (Throwable $e) {
        $zip->close();
        @unlink($targetPath);
        throw $e;
    }

    if (!$zip->close()) {
        @unlink($targetPath);
        throw new RuntimeException('Backup pengaman media gagal diselesaikan.');
    }

    return ['path' => $targetPath, 'filename' => $filename];
}

function media_backup_acquire_lock() {
    $lockPath = media_backup_secure_directory('locks') . DIRECTORY_SEPARATOR . 'media-backup-restore.lock';
    $handle = fopen($lockPath, 'c+');
    if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
        if (is_resource($handle)) fclose($handle);
        throw new RuntimeException('Proses backup atau restore media lain sedang berjalan. Coba lagi setelah proses tersebut selesai.');
    }
    return $handle;
}

function media_backup_release_lock($handle) {
    if (is_resource($handle)) {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }
}

function media_backup_restore_uploaded_archive($uploadedFile, $originalName = '') {
    media_backup_require_zip();

    if (!is_array($uploadedFile) || !isset($uploadedFile['error'])) {
        throw new RuntimeException('File backup media tidak ditemukan.');
    }
    if ((int) $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file backup media gagal. Kode error: ' . (int) $uploadedFile['error'] . '.');
    }

    $tmpName = (string) ($uploadedFile['tmp_name'] ?? '');
    $displayName = trim((string) ($originalName !== '' ? $originalName : ($uploadedFile['name'] ?? '')));
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Upload file backup media tidak valid.');
    }
    if (!preg_match('/\.zip$/i', $displayName)) {
        throw new RuntimeException('File restore media harus menggunakan ekstensi .zip.');
    }

    $size = filesize($tmpName);
    $maxBytes = media_backup_restore_max_bytes();
    if ($size === false || $size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('Ukuran arsip restore harus lebih dari 0 byte dan maksimal ' . media_backup_format_bytes($maxBytes) . '.');
    }

    $magic = file_get_contents($tmpName, false, null, 0, 4);
    if ($magic === false || !str_starts_with($magic, "PK")) {
        throw new RuntimeException('Isi file bukan arsip ZIP yang valid.');
    }

    $operationId = date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
    $operationDirectory = media_backup_quarantine_directory() . DIRECTORY_SEPARATOR . $operationId;
    $stagingDirectory = $operationDirectory . DIRECTORY_SEPARATOR . 'staging';
    $rollbackDirectory = $operationDirectory . DIRECTORY_SEPARATOR . 'rollback';
    if (!mkdir($operationDirectory, 0750, true) && !is_dir($operationDirectory)) {
        throw new RuntimeException('Folder karantina restore media tidak dapat dibuat.');
    }

    $archivePath = $operationDirectory . DIRECTORY_SEPARATOR . 'restore.zip';
    if (!move_uploaded_file($tmpName, $archivePath)) {
        media_backup_remove_tree($operationDirectory);
        throw new RuntimeException('File restore media gagal dipindahkan ke karantina.');
    }

    $zip = new ZipArchive();
    $opened = $zip->open($archivePath, ZipArchive::RDONLY);
    if ($opened !== true) {
        media_backup_remove_tree($operationDirectory);
        throw new RuntimeException('Arsip restore media tidak dapat dibuka. Kode ZipArchive: ' . $opened . '.');
    }

    $lock = null;
    $applied = [];
    $rollbackMap = [];
    $safetyArchive = null;

    try {
        if ($zip->numFiles <= 0 || $zip->numFiles > 10000) {
            throw new RuntimeException('Jumlah file dalam arsip tidak valid atau melebihi batas keamanan.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $entryName = (string) ($stat['name'] ?? '');
            if (!media_backup_validate_archive_entry_name($entryName)) {
                throw new RuntimeException('Arsip mengandung nama file yang tidak aman.');
            }
            if (media_backup_zip_entry_is_symlink($zip, $index)) {
                throw new RuntimeException('Arsip mengandung symbolic link yang tidak diizinkan.');
            }
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        if ($manifestRaw === false || strlen($manifestRaw) > 2 * 1024 * 1024) {
            throw new RuntimeException('Manifest backup media tidak ditemukan atau terlalu besar.');
        }

        $manifest = json_decode($manifestRaw, true);
        if (!is_array($manifest)
            || ($manifest['format'] ?? '') !== 'teakwave-media-backup'
            || (int) ($manifest['version'] ?? 0) !== 1
            || !isset($manifest['files'])
            || !is_array($manifest['files'])) {
            throw new RuntimeException('Format manifest backup media tidak valid.');
        }

        if (!$manifest['files'] || count($manifest['files']) > 5000) {
            throw new RuntimeException('Daftar file pada manifest kosong atau terlalu banyak.');
        }

        $validatedFiles = [];
        $totalUncompressed = 0;
        $seenPaths = [];

        foreach ($manifest['files'] as $item) {
            if (!is_array($item)) throw new RuntimeException('Data file pada manifest tidak valid.');

            $relative = str_replace('\\', '/', trim((string) ($item['path'] ?? '')));
            $archiveEntry = str_replace('\\', '/', trim((string) ($item['archive_path'] ?? '')));
            $expectedSize = (int) ($item['size'] ?? -1);
            $expectedHash = strtolower(trim((string) ($item['sha256'] ?? '')));

            if (!media_backup_is_allowed_relative_path($relative)
                || $archiveEntry !== 'files/' . $relative
                || $expectedSize <= 0
                || $expectedSize > 64 * 1024 * 1024
                || !preg_match('/^[a-f0-9]{64}$/', $expectedHash)
                || isset($seenPaths[$relative])) {
                throw new RuntimeException('Manifest mengandung informasi file yang tidak valid.');
            }
            $seenPaths[$relative] = true;

            $entryIndex = $zip->locateName($archiveEntry);
            if ($entryIndex === false) {
                throw new RuntimeException('File pada manifest tidak ditemukan di arsip: ' . $relative);
            }

            $stat = $zip->statIndex($entryIndex);
            $entrySize = (int) ($stat['size'] ?? -1);
            $compressedSize = (int) ($stat['comp_size'] ?? 0);
            if ($entrySize !== $expectedSize) {
                throw new RuntimeException('Ukuran file arsip tidak sesuai manifest: ' . $relative);
            }
            if ($compressedSize > 0 && $entrySize > 1024 * 1024 && ($entrySize / $compressedSize) > 250) {
                throw new RuntimeException('Rasio kompresi arsip tidak aman.');
            }

            $totalUncompressed += $entrySize;
            if ($totalUncompressed > 512 * 1024 * 1024) {
                throw new RuntimeException('Total ukuran file hasil ekstraksi melebihi 512 MB.');
            }

            $validatedFiles[] = [
                'relative' => $relative,
                'archive_entry' => $archiveEntry,
                'size' => $expectedSize,
                'sha256' => $expectedHash
            ];
        }

        // Stage and validate every image before touching the live project files.
        $stagedFiles = [];
        foreach ($validatedFiles as $file) {
            $stream = $zip->getStream($file['archive_entry']);
            if (!is_resource($stream)) {
                throw new RuntimeException('File dalam arsip tidak dapat dibaca: ' . $file['relative']);
            }

            $stagePath = $stagingDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['relative']);
            try {
                media_backup_copy_stream_to_file($stream, $stagePath, $file['size'], $file['sha256']);
            } finally {
                fclose($stream);
            }
            media_backup_validate_image_file($stagePath, $file['relative']);
            $file['stage_path'] = $stagePath;
            $stagedFiles[] = $file;
        }

        $projectRoot = media_backup_project_root();
        $existingFiles = [];
        foreach ($stagedFiles as $file) {
            $target = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['relative']);
            if (is_file($target)) {
                $existingFiles[$file['relative']] = $target;
            }
        }

        $lock = media_backup_acquire_lock();
        $safetyArchive = media_backup_create_safety_archive($existingFiles);

        if (!mkdir($rollbackDirectory, 0750, true) && !is_dir($rollbackDirectory)) {
            throw new RuntimeException('Folder rollback restore media tidak dapat dibuat.');
        }

        foreach ($stagedFiles as $file) {
            $target = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['relative']);
            $targetDirectory = dirname($target);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                throw new RuntimeException('Folder tujuan restore tidak dapat dibuat: ' . $file['relative']);
            }

            $targetRealParent = realpath($targetDirectory);
            $projectReal = realpath($projectRoot);
            if ($targetRealParent === false || $projectReal === false
                || !str_starts_with(str_replace('\\', '/', $targetRealParent) . '/', rtrim(str_replace('\\', '/', $projectReal), '/') . '/')) {
                throw new RuntimeException('Tujuan restore berada di luar folder project.');
            }

            if (is_file($target)) {
                $rollbackPath = $rollbackDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['relative']);
                $rollbackParent = dirname($rollbackPath);
                if (!is_dir($rollbackParent) && !mkdir($rollbackParent, 0750, true) && !is_dir($rollbackParent)) {
                    throw new RuntimeException('Folder rollback tidak dapat dibuat.');
                }
                if (!copy($target, $rollbackPath)) {
                    throw new RuntimeException('File lama gagal diamankan sebelum restore: ' . $file['relative']);
                }
                $rollbackMap[$target] = $rollbackPath;
            } else {
                $rollbackMap[$target] = null;
            }

            $temporaryTarget = $targetDirectory . DIRECTORY_SEPARATOR . '.media-restore-' . bin2hex(random_bytes(6));
            if (!copy($file['stage_path'], $temporaryTarget)) {
                throw new RuntimeException('File hasil restore gagal disalin: ' . $file['relative']);
            }
            @chmod($temporaryTarget, 0644);

            // Mark the target before replacing it so rollback also covers a
            // failure that occurs after the old file is removed but before rename.
            $applied[] = $target;

            if (is_file($target) && !@unlink($target)) {
                @unlink($temporaryTarget);
                throw new RuntimeException('File lama tidak dapat diganti: ' . $file['relative']);
            }
            if (!@rename($temporaryTarget, $target)) {
                @unlink($temporaryTarget);
                throw new RuntimeException('File hasil restore gagal diaktifkan: ' . $file['relative']);
            }
        }

        return [
            'restored_count' => count($stagedFiles),
            'overwritten_count' => count($existingFiles),
            'new_count' => count($stagedFiles) - count($existingFiles),
            'safety_backup' => $safetyArchive['filename'] ?? null,
            'categories' => array_values(array_filter((array) ($manifest['categories'] ?? []), 'is_string'))
        ];
    } catch (Throwable $e) {
        // Best-effort transactional rollback if activation already started.
        foreach (array_reverse($applied) as $target) {
            if (array_key_exists($target, $rollbackMap)) {
                $rollbackPath = $rollbackMap[$target];
                if ($rollbackPath !== null && is_file($rollbackPath)) {
                    @copy($rollbackPath, $target);
                } else {
                    @unlink($target);
                }
            }
        }
        throw $e;
    } finally {
        media_backup_release_lock($lock);
        $zip->close();
        media_backup_remove_tree($operationDirectory);
    }
}
