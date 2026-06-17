<?php
// Optional Composer autoload for HTML Purifier if installed.
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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

    if ($path === '') return $path;

    if (str_starts_with($path, '../uploads/')) {
        return $path;
    }

    if (str_starts_with($path, 'uploads/')) {
        return '../' . $path;
    }

    if (str_starts_with($path, 'assets/uploads/')) {
        return '../uploads/' . basename($path);
    }

    if (str_starts_with($path, 'utakatik/assets/uploads/')) {
        return '../uploads/' . basename($path);
    }

    return $path;
}

function upload_storage_filesystem_path($storedPath) {
    $storedPath = normalize_upload_storage_path($storedPath);

    if (str_starts_with($storedPath, '../uploads/')) {
        return __DIR__ . '/../' . $storedPath;
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

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token = $_POST['csrf_token'] ?? '';

    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
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
    return mb_substr($value, 0, $maxLength);
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
    $wrapped = '<!doctype html><html><body>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</body></html>';
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
    $base = mb_substr($base, 0, 90);

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

function log_activity($action, $module, $description = '') {
    global $pdo;

    try {
        $user_id = $_SESSION['user']['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $module, $description, $ip, $user_agent]);
    } catch (Throwable $e) {
        // Silent fail so logging never breaks the main app.
    }
}


function get_website_setting($key, $default = '') {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM website_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false && $value !== null ? $value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function update_website_setting($key, $value, $type = 'text') {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO website_settings (setting_key, setting_value, setting_type, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type),
            updated_at = NOW()
    ");
    return $stmt->execute([$key, $value, $type]);
}

function upload_favicon_file($field, $targetDir = null) {
    $targetDir = $targetDir ?: upload_storage_dir();

    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Favicon gagal diupload. Kode error: ' . $_FILES[$field]['error']);
    }

    [$valid, $result] = validate_upload_file($_FILES[$field]['tmp_name'], $_FILES[$field]['name'], null, true);

    if (!$valid) {
        throw new Exception($result);
    }

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $filename = seo_file_name('favicon', $result, 'website');
    $target = $targetDir . $filename;

    return move_uploaded_file($_FILES[$field]['tmp_name'], $target) ? $target : null;
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
        'products-list' => 'List Product',
        'products-add' => 'Add Product',
        'products-edit' => 'Edit Product',
        'brands' => 'Brand Management',
        'categories' => 'Category Management',
        'files' => 'File / Image Management',
        'banners' => 'Banner Ads Management',
        'logs' => 'Activity Logs',
        'website-settings' => 'Website Front Settings'
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
        'products.php' => 'products-list',
        'product-add.php' => 'products-add',
        'product-edit.php' => 'products-edit',
        'brands.php' => 'brands',
        'categories.php' => 'categories',
        'files.php' => 'files',
        'banners.php' => 'banners',
        'logs.php' => 'logs',
        'website-settings.php' => 'website-settings'
    ];

    return $map[$file] ?? null;
}

function has_permission($page_key) {
    global $pdo;

    if (!$page_key) return true;

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
