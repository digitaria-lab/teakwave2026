<?php
// Optional Composer autoload for HTML Purifier if installed.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function utf8_substr($value, $start, $length = null) {
    if (function_exists('mb_substr')) {
        return $length === null
            ? mb_substr((string) $value, $start, null, 'UTF-8')
            : mb_substr((string) $value, $start, $length, 'UTF-8');
    }

    return $length === null
        ? substr((string) $value, $start)
        : substr((string) $value, $start, $length);
}

function utf8_strtolower($value) {
    return function_exists('mb_strtolower')
        ? mb_strtolower((string) $value, 'UTF-8')
        : strtolower((string) $value);
}


function admin_asset_url($path) {
    $cleanPath = ltrim((string) $path, '/');
    $filePath = __DIR__ . '/../' . $cleanPath;
    $version = file_exists($filePath) ? filemtime($filePath) : time();

    return $cleanPath . '?v=' . $version;
}


function app_url($path = '') {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    // Jika sedang di subfolder seperti /project/auth, naikkan otomatis saat dipakai dari auth/check.php
    if (str_ends_with($scriptDir, '/auth')) {
        $scriptDir = dirname($scriptDir);
    }

    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    }

    return $scriptDir . '/' . ltrim($path, '/');
}

function redirect($url) {
    if (!preg_match('#^https?://#i', $url) && !str_starts_with($url, '/')) {
        $url = app_url($url);
    }

    header("Location: $url");
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function current_user_name() {
    return $_SESSION['user']['name'] ?? 'Admin';
}

function current_user_role() {
    return $_SESSION['user']['role_name'] ?? 'User';
}

function make_slug($text) {
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    return $slug ?: uniqid('item-');
}


function upload_storage_dir() {
    // Dashboard berada di folder /utakatik, sedangkan folder upload publik berada di root project.
    return '../uploads/';
}

function upload_public_path($filename) {
    return upload_storage_dir() . ltrim($filename, '/');
}

function normalize_upload_storage_path($path) {
    $path = trim(str_replace('\\', '/', (string) $path));

    if ($path === '') return '';

    // Jangan mengubah URL eksternal.
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    // Hilangkan query string/cache-buster sebelum path disimpan kembali.
    $path = preg_replace('/[?#].*$/', '', $path);

    // Mendukung nilai lama berupa absolute filesystem path, /uploads/file,
    // uploads/file, ../uploads/file, maupun folder upload lama dashboard.
    if (preg_match('#(?:^|/)(?:utakatik/assets/uploads|assets/uploads|uploads)/([^/]+)$#i', $path, $matches)) {
        return '../uploads/' . basename($matches[1]);
    }

    if (str_starts_with($path, '../uploads/')) {
        return '../uploads/' . basename($path);
    }

    return $path;
}

function upload_storage_filesystem_path($storedPath) {
    $storedPath = normalize_upload_storage_path($storedPath);

    if (str_starts_with($storedPath, '../uploads/')) {
        return dirname(__DIR__, 2) . '/uploads/' . basename($storedPath);
    }

    return $storedPath;
}

function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_start();
}

function send_security_headers() {
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Permitted-Cross-Domain-Policies: none');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf($logModule = '') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token = $_POST['csrf_token'] ?? '';

    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if ($logModule !== '') {
            $requestedAction = sanitize_plain_text($_POST['action'] ?? 'unknown', 120);
            log_activity(
                'csrf_failed',
                $logModule,
                'Permintaan ditolak karena token CSRF tidak valid; action: ' . ($requestedAction ?: 'unknown')
            );
        }

        http_response_code(419);
        die('Invalid security token. Please refresh the page and try again.');
    }
}

function clean_int($value, $default = 0) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
}

function clean_decimal($value, $default = 0) {
    return is_numeric($value) ? (float) $value : $default;
}

function sanitize_plain_text($value, $maxLength = 255) {
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    return utf8_substr($value, 0, $maxLength);
}

function sanitize_status($value, $allowed = ['active', 'inactive'], $default = 'active') {
    return in_array($value, $allowed, true) ? $value : $default;
}

function purifier_configured() {
    if (!class_exists('HTMLPurifier') || !class_exists('HTMLPurifier_Config')) {
        return null;
    }

    static $purifier = null;

    if ($purifier !== null) return $purifier;

    $config = HTMLPurifier_Config::createDefault();

    $cacheDir = __DIR__ . '/../cache/htmlpurifier';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    $config->set('Cache.SerializerPath', $cacheDir);

    // Strict whitelist for WYSIWYG content.
    $config->set('HTML.Allowed', implode(',', [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'ul',
        'ol',
        'li',
        'blockquote',
        'pre',
        'code',
        'h1',
        'h2',
        'h3',
        'h4',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'a[href|title|target|rel]'
    ]));

    $config->set('URI.AllowedSchemes', [
        'http' => true,
        'https' => true,
        'mailto' => true,
        'tel' => true
    ]);

    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('HTML.Nofollow', true);
    $config->set('HTML.TargetBlank', true);
    $config->set('AutoFormat.RemoveEmpty', true);
    $config->set('AutoFormat.AutoParagraph', false);

    $purifier = new HTMLPurifier($config);

    return $purifier;
}

function sanitize_html_content($html) {
    $html = (string) $html;

    // Limit payload size to reduce abuse.
    if (strlen($html) > 250000) {
        $html = substr($html, 0, 250000);
    }

    // Preferred production-grade sanitizer if Composer dependency exists.
    $purifier = purifier_configured();
    if ($purifier) {
        return $purifier->purify($html);
    }

    // Fallback sanitizer using DOMDocument whitelist.
    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'a'
    ];

    $allowedAttrs = [
        'a' => ['href', 'title', 'target', 'rel']
    ];

    $safeSchemes = ['http', 'https', 'mailto', 'tel'];

    libxml_use_internal_errors(true);

    $doc = new DOMDocument('1.0', 'UTF-8');
    $encodedHtml = function_exists('mb_convert_encoding') ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : $html;
    $wrapped = '<!doctype html><html><body>' . $encodedHtml . '</body></html>';
    $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($doc);

    // Remove comments.
    foreach ($xpath->query('//comment()') as $comment) {
        $comment->parentNode->removeChild($comment);
    }

    $nodes = [];
    foreach ($doc->getElementsByTagName('*') as $node) {
        $nodes[] = $node;
    }

    // Process deepest nodes first to preserve safe children.
    for ($i = count($nodes) - 1; $i >= 0; $i--) {
        $node = $nodes[$i];
        $tag = strtolower($node->nodeName);

        if (in_array($tag, ['html', 'body'], true)) continue;

        if (!in_array($tag, $allowedTags, true)) {
            $fragment = $doc->createDocumentFragment();

            while ($node->firstChild) {
                $fragment->appendChild($node->firstChild);
            }

            if ($node->parentNode) {
                $node->parentNode->replaceChild($fragment, $node);
            }

            continue;
        }

        if ($node->hasAttributes()) {
            $removeAttrs = [];

            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim($attr->value);

                $isAllowed = isset($allowedAttrs[$tag]) && in_array($name, $allowedAttrs[$tag], true);

                if (!$isAllowed || str_starts_with($name, 'on') || in_array($name, ['style', 'class', 'id'], true)) {
                    $removeAttrs[] = $attr->name;
                    continue;
                }

                if ($tag === 'a' && $name === 'href') {
                    $scheme = parse_url($value, PHP_URL_SCHEME);

                    if ($scheme && !in_array(strtolower($scheme), $safeSchemes, true)) {
                        $removeAttrs[] = $attr->name;
                    }
                }
            }

            foreach ($removeAttrs as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        if ($tag === 'a') {
            if (!$node->hasAttribute('href')) {
                $node->setAttribute('href', '#');
            }

            if ($node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }

    $body = $doc->getElementsByTagName('body')->item(0);
    $clean = '';

    if ($body) {
        foreach ($body->childNodes as $child) {
            $clean .= $doc->saveHTML($child);
        }
    }

    libxml_clear_errors();

    // Final hard-block dangerous fragments.
    $clean = preg_replace('#<(script|style|iframe|object|embed|form|input|button|meta|link)[^>]*>.*?</\1>#is', '', $clean);
    $clean = preg_replace('#<(script|style|iframe|object|embed|form|input|button|meta|link)[^>]*/?>#is', '', $clean);
    $clean = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $clean);
    $clean = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $clean);
    $clean = preg_replace('/javascript\s*:/i', '', $clean);

    return trim($clean);
}


function get_upload_allowed_extensions($imageOnly = false) {
    $default = 'jpg,jpeg,png,gif,webp,pdf,ico';
    $raw = function_exists('get_website_setting') ? get_website_setting('upload_allowed_extensions', $default) : $default;

    $items = preg_split('/[,\\s]+/', strtolower((string) $raw));
    $items = array_map(function ($item) {
        return trim($item, " .\t\n\r\0\x0B");
    }, $items);

    $safeMap = [
        'jpg' => true,
        'jpeg' => true,
        'png' => true,
        'gif' => true,
        'webp' => true,
        'pdf' => true,
        'ico' => true
    ];

    $imageMap = [
        'jpg' => true,
        'jpeg' => true,
        'png' => true,
        'gif' => true,
        'webp' => true,
        'ico' => true
    ];

    $allowed = [];

    foreach ($items as $item) {
        if ($item === '') continue;

        if (!isset($safeMap[$item])) continue;

        if ($imageOnly && !isset($imageMap[$item])) continue;

        $allowed[] = $item;
    }

    $allowed = array_values(array_unique($allowed));

    if (!$allowed) {
        $allowed = $imageOnly ? ['jpg','jpeg','png','webp'] : ['jpg','jpeg','png','gif','webp','pdf'];
    }

    return $allowed;
}

function get_upload_max_filesize_mb() {
    $value = function_exists('get_website_setting') ? get_website_setting('upload_max_filesize_mb', '5') : '5';
    $value = is_numeric($value) ? (float) $value : 5.0;

    if ($value <= 0) $value = 5.0;
    if ($value > 100) $value = 100.0;

    return $value;
}

function get_upload_max_filesize_bytes() {
    return (int) round(get_upload_max_filesize_mb() * 1024 * 1024);
}

function upload_settings_for_js() {
    return [
        'maxMb' => get_upload_max_filesize_mb(),
        'maxBytes' => get_upload_max_filesize_bytes(),
        'allowedExtensions' => get_upload_allowed_extensions(false),
        'allowedImageExtensions' => get_upload_allowed_extensions(true)
    ];
}

function normalize_upload_extensions($value) {
    $items = preg_split('/[,\\s]+/', strtolower((string) $value));
    $safe = ['jpg','jpeg','png','gif','webp','pdf','ico'];
    $allowed = [];

    foreach ($items as $item) {
        $item = trim($item, " .\t\n\r\0\x0B");

        if ($item !== '' && in_array($item, $safe, true)) {
            $allowed[] = $item;
        }
    }

    $allowed = array_values(array_unique($allowed));

    return $allowed ? implode(',', $allowed) : 'jpg,jpeg,png,gif,webp,pdf,ico';
}

function upload_mime_map() {
    return [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream']
    ];
}

function validate_upload_file($tmpPath, $originalName, $maxSize = null, $imageOnly = false) {
    if (!is_uploaded_file($tmpPath)) {
        return [false, 'File upload tidak valid.'];
    }

    $maxSize = $maxSize ?: get_upload_max_filesize_bytes();
    $maxMb = get_upload_max_filesize_mb();

    if (filesize($tmpPath) > $maxSize) {
        return [false, 'Ukuran file terlalu besar. Maksimal ' . $maxMb . 'MB.'];
    }

    $mimeMap = upload_mime_map();
    $allowedExts = get_upload_allowed_extensions($imageOnly);

    $allowed = [];

    foreach ($allowedExts as $ext) {
        if (isset($mimeMap[$ext])) {
            $allowed[$ext] = $mimeMap[$ext];
        }
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!isset($allowed[$ext])) {
        return [false, 'Extension file tidak sesuai. Extension yang diperbolehkan: ' . implode(', ', $allowedExts) . '.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);

    if (!in_array($mime, $allowed[$ext], true)) {
        return [false, 'Tipe file tidak valid untuk extension .' . $ext . '.'];
    }

    if ($ext !== 'ico' && str_starts_with($mime, 'image/') && !@getimagesize($tmpPath)) {
        return [false, 'File gambar tidak valid.'];
    }

    return [true, $ext];
}


function seo_file_name($title, $ext, $suffix = '') {
    $base = make_slug($title);

    if (!$base || $base === 'item') {
        $base = 'upload';
    }

    $suffix = make_slug($suffix);

    if ($suffix) {
        $base .= '-' . $suffix;
    }

    // Keep filename readable but not too long.
    $base = utf8_substr($base, 0, 90);

    // Add short unique token to prevent overwrite while keeping SEO-friendly title.
    return $base . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
}


function rename_uploaded_file_seo($oldPath, $title, $suffix = 'image') {
    if (!$oldPath) return $oldPath;

    $oldPath = normalize_upload_storage_path($oldPath);
    $normalized = str_replace('\\', '/', $oldPath);

    // Hanya rename file lokal di folder uploads publik agar aman.
    if (!str_starts_with($normalized, '../uploads/')) {
        return $oldPath;
    }

    $filesystemPath = upload_storage_filesystem_path($oldPath);

    if (!file_exists($filesystemPath) || !is_file($filesystemPath)) {
        return $oldPath;
    }

    $ext = strtolower(pathinfo($filesystemPath, PATHINFO_EXTENSION));
    if (!$ext) return $oldPath;

    $dir = rtrim(dirname($filesystemPath), '/\\') . DIRECTORY_SEPARATOR;
    $newFilename = seo_file_name($title, $ext, $suffix);
    $newFilesystemPath = $dir . $newFilename;
    $newStoredPath = upload_public_path($newFilename);

    if (@rename($filesystemPath, $newFilesystemPath)) {
        return str_replace('\\', '/', $newStoredPath);
    }

    return $oldPath;
}

function upload_file($field, $targetDir = null, $seoTitle = null, $suffix = 'main') {
    $targetDir = $targetDir ?: upload_storage_dir();

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File gagal diupload. Kode error: ' . $_FILES[$field]['error']);
    }

    [$valid, $result] = validate_upload_file($_FILES[$field]['tmp_name'], $_FILES[$field]['name']);

    if (!$valid) {
        throw new Exception($result);
    }

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $filename = $seoTitle ? seo_file_name($seoTitle, $result, $suffix) : uniqid('file_', true) . '.' . $result;
    $target = $targetDir . $filename;

    return move_uploaded_file($_FILES[$field]['tmp_name'], $target) ? $target : null;
}

function upload_image_only($field, $targetDir = null) {
    $targetDir = $targetDir ?: upload_storage_dir();

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File gagal diupload. Kode error: ' . $_FILES[$field]['error']);
    }

    [$valid, $result] = validate_upload_file($_FILES[$field]['tmp_name'], $_FILES[$field]['name'], null, true);

    if (!$valid) {
        throw new Exception($result);
    }

    $imageExts = ['jpg','jpeg','png','gif','webp','ico'];

    if (!in_array($result, $imageExts, true)) {
        throw new Exception('File harus berupa gambar. Extension yang diperbolehkan: ' . implode(', ', get_upload_allowed_extensions(true)) . '.');
    }

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $filename = uniqid('avatar_', true) . '.' . $result;
    $target = $targetDir . $filename;

    return move_uploaded_file($_FILES[$field]['tmp_name'], $target) ? $target : null;
}

function upload_multiple_files($field, $targetDir = null, $seoTitle = null, $suffix = 'gallery') {
    $targetDir = $targetDir ?: upload_storage_dir();

    $uploaded = [];

    if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'][0])) {
        return $uploaded;
    }

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    foreach ($_FILES[$field]['name'] as $index => $name) {
        if ($_FILES[$field]['error'][$index] === UPLOAD_ERR_NO_FILE) continue;

        if ($_FILES[$field]['error'][$index] !== UPLOAD_ERR_OK) {
            throw new Exception('File ' . $name . ' gagal diupload. Kode error: ' . $_FILES[$field]['error'][$index]);
        }

        [$valid, $result] = validate_upload_file($_FILES[$field]['tmp_name'][$index], $name);

        if (!$valid) {
            throw new Exception($name . ': ' . $result);
        }

        $filename = $seoTitle ? seo_file_name($seoTitle, $result, $suffix . '-' . ($index + 1)) : uniqid('upload_', true) . '.' . $result;
        $target = $targetDir . $filename;

        if (move_uploaded_file($_FILES[$field]['tmp_name'][$index], $target)) {
            $uploaded[] = $target;
        }
    }

    return $uploaded;
}

function activity_log_storage_directory() {
    $directory = __DIR__ . '/../storage/activity-logs';

    if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
        return null;
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

function activity_log_pending_path() {
    $directory = activity_log_storage_directory();
    return $directory ? $directory . '/pending.ndjson' : null;
}

function activity_log_error_path() {
    $directory = activity_log_storage_directory();
    return $directory ? $directory . '/logger-errors.log' : null;
}

function activity_log_write_error($message) {
    $path = activity_log_error_path();
    if (!$path) return;

    $line = '[' . date('Y-m-d H:i:s') . '] ' . str_replace(["\r", "\n"], ' ', (string) $message) . PHP_EOL;
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    @chmod($path, 0640);
}

function activity_log_queue_record(array $record, $reason = '') {
    $path = activity_log_pending_path();
    if (!$path) return false;

    $record['queued_at'] = $record['queued_at'] ?? date('Y-m-d H:i:s');
    if ($reason !== '') {
        $record['queue_reason'] = substr(str_replace(["\r", "\n"], ' ', (string) $reason), 0, 1000);
    }

    $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;

    $written = @file_put_contents($path, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($written !== false) {
        @chmod($path, 0640);
        return true;
    }

    return false;
}

function activity_log_pending_count() {
    $path = activity_log_pending_path();
    if (!$path || !is_file($path)) return 0;

    $handle = @fopen($path, 'rb');
    if (!$handle) return 0;

    $count = 0;
    while (($line = fgets($handle)) !== false) {
        if (trim($line) !== '') $count++;
    }
    fclose($handle);

    return $count;
}

function activity_log_schema_columns(PDO $pdo) {
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM activity_logs')->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[strtolower((string) $column['Field'])] = $column;
    }
    return $columns;
}

function ensure_activity_log_schema($force = false) {
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) return false;
    if (!$force && !empty($GLOBALS['activity_log_schema_checked'])) return true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) DEFAULT NULL,
        action VARCHAR(120) NOT NULL,
        module VARCHAR(120) NOT NULL,
        description TEXT DEFAULT NULL,
        ip_address VARCHAR(80) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id_index (user_id),
        KEY module_index (module),
        KEY action_index (action),
        KEY created_at_index (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Older installations may still use ENUM or short VARCHAR columns. Those
    // schemas reject the newer backup/restore and video action names in strict SQL mode.
    $columns = activity_log_schema_columns($pdo);

    $requiredColumns = [
        'user_id' => "ALTER TABLE activity_logs ADD COLUMN user_id INT(11) DEFAULT NULL AFTER id",
        'action' => "ALTER TABLE activity_logs ADD COLUMN action VARCHAR(120) NOT NULL AFTER user_id",
        'module' => "ALTER TABLE activity_logs ADD COLUMN module VARCHAR(120) NOT NULL AFTER action",
        'description' => "ALTER TABLE activity_logs ADD COLUMN description TEXT DEFAULT NULL AFTER module",
        'ip_address' => "ALTER TABLE activity_logs ADD COLUMN ip_address VARCHAR(80) DEFAULT NULL AFTER description",
        'user_agent' => "ALTER TABLE activity_logs ADD COLUMN user_agent TEXT DEFAULT NULL AFTER ip_address",
        'created_at' => "ALTER TABLE activity_logs ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER user_agent"
    ];

    foreach ($requiredColumns as $name => $sql) {
        if (!isset($columns[$name])) {
            $pdo->exec($sql);
        }
    }

    $columns = activity_log_schema_columns($pdo);
    $actionType = strtolower((string) ($columns['action']['Type'] ?? ''));
    $moduleType = strtolower((string) ($columns['module']['Type'] ?? ''));
    $descriptionType = strtolower((string) ($columns['description']['Type'] ?? ''));
    $ipType = strtolower((string) ($columns['ip_address']['Type'] ?? ''));
    $userAgentType = strtolower((string) ($columns['user_agent']['Type'] ?? ''));

    if (!preg_match('/^varchar\((?:12[0-9]|1[3-9][0-9]|[2-9][0-9]{2,})\)/', $actionType)) {
        $pdo->exec("ALTER TABLE activity_logs MODIFY action VARCHAR(120) NOT NULL");
    }
    if (!preg_match('/^varchar\((?:12[0-9]|1[3-9][0-9]|[2-9][0-9]{2,})\)/', $moduleType)) {
        $pdo->exec("ALTER TABLE activity_logs MODIFY module VARCHAR(120) NOT NULL");
    }
    if (!str_contains($descriptionType, 'text')) {
        $pdo->exec("ALTER TABLE activity_logs MODIFY description TEXT DEFAULT NULL");
    }
    if (!preg_match('/^varchar\((?:8[0-9]|9[0-9]|[1-9][0-9]{2,})\)/', $ipType)) {
        $pdo->exec("ALTER TABLE activity_logs MODIFY ip_address VARCHAR(80) DEFAULT NULL");
    }
    if (!str_contains($userAgentType, 'text')) {
        $pdo->exec("ALTER TABLE activity_logs MODIFY user_agent TEXT DEFAULT NULL");
    }

    $indexes = [];
    foreach ($pdo->query('SHOW INDEX FROM activity_logs')->fetchAll(PDO::FETCH_ASSOC) as $index) {
        $indexes[strtolower((string) $index['Key_name'])] = true;
    }

    foreach ([
        'user_id_index' => 'user_id',
        'module_index' => 'module',
        'action_index' => 'action',
        'created_at_index' => 'created_at'
    ] as $indexName => $columnName) {
        if (!isset($indexes[strtolower($indexName)])) {
            try {
                $pdo->exec('ALTER TABLE activity_logs ADD INDEX ' . $indexName . ' (' . $columnName . ')');
            } catch (Throwable $ignored) {
                // A differently named equivalent index is sufficient for logging.
            }
        }
    }

    $GLOBALS['activity_log_schema_checked'] = true;
    return true;
}

function reset_activity_log_schema_cache() {
    unset($GLOBALS['activity_log_schema_checked']);
}

function activity_log_session_record($action, $module, $description = '') {
    $sessionUser = $_SESSION['user'] ?? [];
    $actorName = trim((string) ($sessionUser['name'] ?? ''));
    $actorEmail = trim((string) ($sessionUser['email'] ?? ''));

    return [
        'user_id' => isset($sessionUser['id']) ? (int) $sessionUser['id'] : null,
        'actor_name' => $actorName,
        'actor_email' => $actorEmail,
        'action' => substr(trim((string) $action), 0, 120),
        'module' => substr(trim((string) $module), 0, 120),
        'description' => trim((string) $description),
        'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 80),
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => date('Y-m-d H:i:s')
    ];
}

function activity_log_insert_record(PDO $pdo, array $record) {
    $userId = !empty($record['user_id']) ? (int) $record['user_id'] : null;
    $storedUserId = null;

    if ($userId) {
        try {
            $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $userCheck->execute([$userId]);
            if ($userCheck->fetchColumn()) {
                $storedUserId = $userId;
            }
        } catch (Throwable $ignored) {
            $storedUserId = null;
        }
    }

    $description = trim((string) ($record['description'] ?? ''));
    if ($storedUserId === null) {
        $actorName = trim((string) ($record['actor_name'] ?? ''));
        $actorEmail = trim((string) ($record['actor_email'] ?? ''));
        if ($actorName !== '' || $actorEmail !== '') {
            $actorParts = [];
            if ($actorName !== '') $actorParts[] = $actorName;
            if ($actorEmail !== '') $actorParts[] = '<' . $actorEmail . '>';
            $actorSnapshot = 'Pelaku sesi: ' . implode(' ', $actorParts) . '.';
            $description = $description !== '' ? $actorSnapshot . ' ' . $description : $actorSnapshot;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO activity_logs
            (user_id, action, module, description, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        return $stmt->execute([
            $storedUserId,
            substr((string) ($record['action'] ?? ''), 0, 120),
            substr((string) ($record['module'] ?? ''), 0, 120),
            $description,
            substr((string) ($record['ip_address'] ?? ''), 0, 80),
            (string) ($record['user_agent'] ?? ''),
            (string) ($record['created_at'] ?? date('Y-m-d H:i:s'))
        ]);
    } catch (Throwable $firstError) {
        // A stale foreign key can still reject the actor after a restore. Retry as
        // a snapshot-only record so the audit event itself is never lost.
        if ($storedUserId !== null) {
            return $stmt->execute([
                null,
                substr((string) ($record['action'] ?? ''), 0, 120),
                substr((string) ($record['module'] ?? ''), 0, 120),
                $description,
                substr((string) ($record['ip_address'] ?? ''), 0, 80),
                (string) ($record['user_agent'] ?? ''),
                (string) ($record['created_at'] ?? date('Y-m-d H:i:s'))
            ]);
        }
        throw $firstError;
    }
}

function activity_log_flush_pending() {
    global $pdo;

    $path = activity_log_pending_path();
    if (!$path || !is_file($path) || !isset($pdo) || !($pdo instanceof PDO)) return 0;

    $lockPath = $path . '.lock';
    $lock = @fopen($lockPath, 'c+');
    if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if ($lock) fclose($lock);
        return 0;
    }

    $flushed = 0;
    $remaining = [];

    try {
        ensure_activity_log_schema(true);
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $record = json_decode($line, true);
            if (!is_array($record) || empty($record['action']) || empty($record['module'])) {
                continue;
            }

            try {
                activity_log_insert_record($pdo, $record);
                $flushed++;
            } catch (Throwable $e) {
                $remaining[] = $line;
                activity_log_write_error('Gagal memindahkan pending audit log: ' . $e->getMessage());
            }
        }

        if ($remaining) {
            @file_put_contents($path, implode(PHP_EOL, $remaining) . PHP_EOL, LOCK_EX);
            @chmod($path, 0640);
        } else {
            @unlink($path);
        }
    } catch (Throwable $e) {
        activity_log_write_error('Flush audit log gagal: ' . $e->getMessage());
    } finally {
        @flock($lock, LOCK_UN);
        fclose($lock);
        @unlink($lockPath);
    }

    return $flushed;
}

function log_activity($action, $module, $description = '', $forceSchemaRefresh = false) {
    global $pdo;

    $record = activity_log_session_record($action, $module, $description);
    if ($record['action'] === '' || $record['module'] === '') return false;
    if (!isset($pdo) || !($pdo instanceof PDO)) return false;

    /*
     * MySQL performs an implicit COMMIT for DDL statements such as CREATE TABLE
     * and ALTER TABLE. Schema repair must therefore never run while a business
     * transaction is active, otherwise the caller's later commit() fails with
     * "There is no active transaction".
     *
     * During an active transaction we only attempt the normal INSERT. If the
     * audit table is unavailable or outdated, queue the event for a later
     * non-transactional request instead of touching the schema.
     */
    if ($pdo->inTransaction()) {
        try {
            activity_log_insert_record($pdo, $record);
            return true;
        } catch (Throwable $transactionError) {
            activity_log_write_error(
                'Audit insert ditunda karena transaksi aktif: ' . $transactionError->getMessage()
            );
            activity_log_queue_record($record, $transactionError->getMessage());
            return false;
        }
    }

    try {
        ensure_activity_log_schema($forceSchemaRefresh);
        activity_log_insert_record($pdo, $record);

        // Opportunistically import events queued during a temporary database failure.
        if (activity_log_pending_count() > 0) {
            activity_log_flush_pending();
        }
        return true;
    } catch (Throwable $firstError) {
        try {
            // A restore or old hosting schema may have replaced the table after the
            // first schema check. Reinspect and retry once before using the file queue.
            reset_activity_log_schema_cache();
            ensure_activity_log_schema(true);
            activity_log_insert_record($pdo, $record);
            return true;
        } catch (Throwable $retryError) {
            activity_log_write_error(
                'Audit insert gagal. Pertama: ' . $firstError->getMessage()
                . ' | Ulang: ' . $retryError->getMessage()
            );
            activity_log_queue_record($record, $retryError->getMessage());
            return false;
        }
    }
}

function get_website_setting($key, $default = '') {
    global $pdo;

    try {
        // Ambil data terbaru. Ini tetap bekerja bila database lama sempat
        // memiliki setting_key duplikat karena unique index belum terpasang.
        $stmt = $pdo->prepare("
            SELECT setting_value
            FROM website_settings
            WHERE setting_key = ?
            ORDER BY COALESCE(updated_at, created_at) DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false && $value !== null ? $value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function update_website_setting($key, $value, $type = 'text') {
    global $pdo;

    $key = trim((string) $key);
    if ($key === '') {
        throw new InvalidArgumentException('Key website setting tidak boleh kosong.');
    }

    // UPDATE dahulu supaya database lama tanpa UNIQUE(setting_key) tidak
    // membuat baris duplikat yang menyebabkan nilai lama terus terbaca.
    $update = $pdo->prepare("
        UPDATE website_settings
        SET setting_value = ?, setting_type = ?, updated_at = NOW()
        WHERE setting_key = ?
    ");
    $update->execute([(string) $value, (string) $type, $key]);

    if ($update->rowCount() > 0) {
        return true;
    }

    // rowCount() dapat 0 bila nilainya sama. Pastikan baris memang ada.
    $exists = $pdo->prepare("SELECT id FROM website_settings WHERE setting_key = ? LIMIT 1");
    $exists->execute([$key]);
    if ($exists->fetchColumn() !== false) {
        return true;
    }

    $insert = $pdo->prepare("
        INSERT INTO website_settings (setting_key, setting_value, setting_type, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");

    return $insert->execute([$key, (string) $value, (string) $type]);
}

function upload_favicon_file($field, $targetDir = null) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Favicon gagal diupload. Kode error: ' . $_FILES[$field]['error']);
    }

    [$valid, $result] = validate_upload_file($_FILES[$field]['tmp_name'], $_FILES[$field]['name'], null, true);

    if (!$valid) {
        throw new Exception($result);
    }

    // Pakai absolute filesystem path agar tidak bergantung pada current
    // working directory PHP/hosting. Yang disimpan ke database tetap path publik.
    $filesystemDir = $targetDir
        ? rtrim((string) $targetDir, '/\\') . DIRECTORY_SEPARATOR
        : dirname(__DIR__, 2) . '/uploads/';

    if (!is_dir($filesystemDir) && !@mkdir($filesystemDir, 0755, true) && !is_dir($filesystemDir)) {
        throw new Exception('Folder uploads tidak dapat dibuat. Periksa permission folder uploads.');
    }

    if (!is_writable($filesystemDir)) {
        throw new Exception('Folder uploads tidak dapat ditulis. Gunakan permission folder 755 atau 775 sesuai hosting.');
    }

    $filename = seo_file_name('favicon', $result, 'website');
    $target = $filesystemDir . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new Exception('Favicon gagal dipindahkan ke folder uploads.');
    }

    return '../uploads/' . $filename;
}



function youtube_video_id($url) {
    $url = trim((string) $url);

    if ($url === '' || strlen($url) > 500) {
        return null;
    }

    // Accept a bare YouTube hostname, but always store a canonical HTTPS URL later.
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    $parts = parse_url($url);

    if (!$parts || empty($parts['host'])) {
        return null;
    }

    $scheme = strtolower($parts['scheme'] ?? 'https');
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower(rtrim($parts['host'], '.'));
    $allowedHosts = [
        'youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com',
        'youtu.be', 'www.youtu.be',
        'youtube-nocookie.com', 'www.youtube-nocookie.com'
    ];

    if (!in_array($host, $allowedHosts, true)) {
        return null;
    }

    $path = trim($parts['path'] ?? '', '/');
    $videoId = null;

    if ($host === 'youtu.be' || $host === 'www.youtu.be') {
        $videoId = explode('/', $path)[0] ?? null;
    } else {
        parse_str($parts['query'] ?? '', $query);

        if (($path === 'watch' || $path === '') && !empty($query['v'])) {
            $videoId = $query['v'];
        } elseif (preg_match('#^(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})(?:/|$)#', $path, $match)) {
            $videoId = $match[1];
        }
    }

    $videoId = trim((string) $videoId);

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) ? $videoId : null;
}

function normalize_youtube_url($url) {
    $videoId = youtube_video_id($url);
    return $videoId ? 'https://www.youtube.com/watch?v=' . $videoId : null;
}

function youtube_thumbnail_url($youtubeId) {
    $youtubeId = trim((string) $youtubeId);
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId)
        ? 'https://i.ytimg.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg'
        : '';
}

function sanitize_video_tags($value) {
    $parts = preg_split('/[,;]+/', (string) $value);
    $tags = [];

    foreach ($parts as $part) {
        $tag = sanitize_plain_text($part, 40);
        $tag = trim(preg_replace('/\s+/u', ' ', $tag));

        if ($tag === '') continue;

        $key = utf8_strtolower($tag);
        if (!isset($tags[$key])) {
            $tags[$key] = $tag;
        }

        if (count($tags) >= 20) break;
    }

    return implode(', ', array_values($tags));
}

function video_tags_array($value) {
    $tags = preg_split('/\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
    return array_slice($tags ?: [], 0, 20);
}

function video_timezone_name() {
    $timezone = trim((string) get_website_setting('timezone', 'Asia/Jakarta'));

    try {
        new DateTimeZone($timezone);
        return $timezone;
    } catch (Throwable $e) {
        return 'Asia/Jakarta';
    }
}

function normalize_video_published_at($value) {
    $value = trim((string) $value);

    if ($value === '') {
        throw new InvalidArgumentException('Tanggal dan waktu posting tidak boleh kosong.');
    }

    $timezone = new DateTimeZone(video_timezone_name());
    $formats = [
        '!Y-m-d\\TH:i',
        '!Y-m-d\\TH:i:s',
        '!Y-m-d H:i',
        '!Y-m-d H:i:s'
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        $isValid = $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));

        if ($isValid) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    throw new InvalidArgumentException('Tanggal dan waktu posting tidak valid.');
}

function video_datetime_input_value($value = null) {
    $timezone = new DateTimeZone(video_timezone_name());

    if ($value === null || trim((string) $value) === '' || $value === 'now') {
        return (new DateTimeImmutable('now', $timezone))->format('Y-m-d\\TH:i');
    }

    try {
        $normalized = normalize_video_published_at($value);
        return DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $normalized, $timezone)->format('Y-m-d\\TH:i');
    } catch (Throwable $e) {
        return (new DateTimeImmutable('now', $timezone))->format('Y-m-d\\TH:i');
    }
}

function format_video_published_at($value) {
    $timezone = new DateTimeZone(video_timezone_name());

    try {
        $normalized = normalize_video_published_at($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $normalized, $timezone);
        $dateFormat = trim((string) get_website_setting('date_format', 'd M Y')) ?: 'd M Y';
        $timeFormat = trim((string) get_website_setting('time_format', 'H:i')) ?: 'H:i';

        return $date->format($dateFormat . ' ' . $timeFormat);
    } catch (Throwable $e) {
        return '-';
    }
}

function upload_single_image($field, $targetDir = null, $seoTitle = null, $suffix = 'image') {
    $targetDir = $targetDir ?: upload_storage_dir();

    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Gambar gagal diupload. Kode error: ' . $_FILES[$field]['error']);
    }

    [$valid, $result] = validate_upload_file(
        $_FILES[$field]['tmp_name'],
        $_FILES[$field]['name'],
        null,
        true
    );

    if (!$valid) {
        throw new Exception($result);
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new Exception('Folder upload tidak dapat dibuat.');
    }

    $filename = seo_file_name($seoTitle ?: 'video', $result, $suffix);
    $target = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new Exception('Gambar gagal disimpan.');
    }

    return str_replace('\\', '/', rtrim($targetDir, '/\\') . '/' . $filename);
}

function delete_local_upload($storedPath) {
    $storedPath = normalize_upload_storage_path($storedPath);

    if (!$storedPath || !str_starts_with(str_replace('\\', '/', $storedPath), '../uploads/')) {
        return false;
    }

    $uploadRoot = realpath(__DIR__ . '/../../uploads');
    $filePath = upload_storage_filesystem_path($storedPath);
    $realFile = realpath($filePath);

    if (!$uploadRoot || !$realFile || !is_file($realFile)) {
        return false;
    }

    $uploadRoot = rtrim(str_replace('\\', '/', $uploadRoot), '/') . '/';
    $realFileNormalized = str_replace('\\', '/', $realFile);

    if (!str_starts_with($realFileNormalized, $uploadRoot)) {
        return false;
    }

    return @unlink($realFile);
}

function video_thumbnail_src($video) {
    $custom = normalize_upload_storage_path($video['thumbnail'] ?? '');
    return $custom !== '' ? $custom : youtube_thumbnail_url($video['youtube_id'] ?? '');
}

function ensure_video_schema() {
    global $pdo;

    static $checked = false;
    if ($checked || !isset($pdo)) return;

    $exists = (bool) $pdo->query("SHOW TABLES LIKE 'videos'")->fetchColumn();

    if (!$exists) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            url VARCHAR(500) NOT NULL,
            youtube_id VARCHAR(11) NOT NULL,
            thumbnail VARCHAR(255) DEFAULT NULL,
            tag VARCHAR(500) DEFAULT NULL,
            published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY youtube_id_unique (youtube_id),
            KEY title_index (title),
            KEY published_at_index (published_at),
            KEY created_at_index (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } else {
        $publishedColumn = $pdo->query("SHOW COLUMNS FROM videos LIKE 'published_at'")->fetchColumn();

        if (!$publishedColumn) {
            $pdo->exec("ALTER TABLE videos
                ADD COLUMN published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER tag,
                ADD KEY published_at_index (published_at)");
            $pdo->exec("UPDATE videos SET published_at = created_at WHERE created_at IS NOT NULL");
        }
    }

    $roles = $pdo->query("SELECT id FROM roles WHERE id IN (1,2,3)")->fetchAll(PDO::FETCH_COLUMN);
    $permissions = ['videos-list', 'videos-add', 'videos-edit'];
    $insert = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, page_key) VALUES (?, ?)");

    foreach ($roles as $roleId) {
        foreach ($permissions as $permission) {
            $insert->execute([(int) $roleId, $permission]);
        }
    }

    $checked = true;
}

function ensure_page_view_schema() {
    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) return;
    require_once __DIR__ . '/../../includes/page-view-tracker.php';
    teakwave_ensure_page_view_schema($pdo);

    $roles = $pdo->query("SELECT id FROM roles WHERE id IN (1,2)")->fetchAll(PDO::FETCH_COLUMN);
    $insert = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, page_key) VALUES (?, 'page-views')");
    foreach ($roles as $roleId) $insert->execute([(int) $roleId]);
}

function is_super_admin() {
    return (($_SESSION['user']['role_id'] ?? null) == 1) || strtolower($_SESSION['user']['role_name'] ?? '') === 'super admin';
}

function validate_password_strength($password) {
    return is_string($password) && strlen($password) >= 8;
}

function dashboard_pages() {
    return [
        'dashboard' => 'Dashboard',
        'users' => 'User Management',
        'roles' => 'User Level',
        'contents-list' => 'List Content',
        'contents-add' => 'Add Content',
        'contents-edit' => 'Edit Content',
        'videos-list' => 'List Video',
        'videos-add' => 'Add Video',
        'videos-edit' => 'Edit Video',
        'products-list' => 'List Product',
        'products-add' => 'Add Product',
        'products-edit' => 'Edit Product',
        'brands' => 'Brand Management',
        'categories' => 'Category Management',
        'files' => 'File / Image Management',
        'banners' => 'Banner Ads Management',
        'logs' => 'Activity Logs',
        'page-views' => 'Page View Statistics',
        'website-settings' => 'Website Front Settings',
        'backup-restore' => 'Backup & Restore Database'
    ];
}

function page_key_from_file($file) {
    $map = [
        'index.php' => 'dashboard',
        'users.php' => 'users',
        'roles.php' => 'roles',
        'contents.php' => 'contents-list',
        'content-add.php' => 'contents-add',
        'content-edit.php' => 'contents-edit',
        'videos.php' => 'videos-list',
        'video-add.php' => 'videos-add',
        'video-edit.php' => 'videos-edit',
        'products.php' => 'products-list',
        'product-add.php' => 'products-add',
        'product-edit.php' => 'products-edit',
        'brands.php' => 'brands',
        'categories.php' => 'categories',
        'files.php' => 'files',
        'banners.php' => 'banners',
        'logs.php' => 'logs',
        'page-views.php' => 'page-views',
        'website-settings.php' => 'website-settings',
        'backup-restore.php' => 'backup-restore'
    ];

    return $map[$file] ?? null;
}

function has_permission($page_key) {
    global $pdo;

    if (!$page_key || is_super_admin()) return true;

    $role_id = $_SESSION['user']['role_id'] ?? null;

    if (!$role_id) return false;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND page_key = ?");
    $stmt->execute([$role_id, $page_key]);

    return $stmt->fetchColumn() > 0;
}

function require_permission($page_key = null) {
    $page_key = $page_key ?: page_key_from_file(basename($_SERVER['PHP_SELF']));

    if (!has_permission($page_key)) {
        http_response_code(403);
        include __DIR__ . '/../includes/no-access.php';
        exit;
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = '...') {
        $words = preg_split('/\s+/', trim($text));
        if (count($words) <= $num_words) return $text;
        return implode(' ', array_slice($words, 0, $num_words)) . $more;
    }
}
?>
