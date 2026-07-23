<?php
$siteName = 'Teakwave';
$siteUrl = rtrim(
    (string) (getenv('TEAKWAVE_SITE_URL') ?: 'https://digitaria.click/teakwave'),
    '/'
);
$defaultWhatsappUrl = 'https://wa.me/6282112345678';
$defaultMetaDescription = 'Teakwave adalah distributor perangkat jaringan nirkabel, fiber optic, router, switch, access point, dan solusi internet untuk kebutuhan bisnis di Indonesia.';
$defaultSocialImage = 'assets/img/banner-home-1.webp';

/**
 * Menormalkan path aset lokal yang mungkin masih memakai format lama dari database.
 * Fungsi ini juga otomatis memilih file WebP bila database masih menunjuk PNG/JPG lama
 * yang sudah digantikan oleh versi WebP.
 */
function teakwave_normalize_asset_path($path, $fallback = '') {
    $value = trim((string) $path);
    if ($value === '') {
        $value = trim((string) $fallback);
    }

    if ($value === '' || preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $value = str_replace('\\', '/', $value);
    $value = preg_replace('/[?#].*$/', '', $value);
    $value = rawurldecode($value);

    // Ambil bagian path publik bila database menyimpan absolute filesystem path.
    if (preg_match('#(?:^|/)(uploads|produk|assets)/(.+)$#i', $value, $matches)) {
        $value = strtolower($matches[1]) . '/' . $matches[2];
    }

    $value = preg_replace('#^(?:\./|\.\./)+#', '', $value);
    $value = ltrim($value, '/');

    if (str_starts_with($value, 'utakatik/assets/uploads/')) {
        $value = 'uploads/' . basename($value);
    } elseif (str_starts_with($value, 'assets/uploads/')) {
        $value = 'uploads/' . basename($value);
    }

    // Hilangkan traversal yang tidak diperlukan dari path publik.
    $segments = [];
    foreach (explode('/', $value) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $segment;
    }
    $value = implode('/', $segments);

    $root = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
    $candidates = [];

    if ($value !== '') {
        $candidates[] = $value;
    }

    // Untuk nilai database yang hanya menyimpan nama file.
    if ($value !== '' && strpos($value, '/') === false) {
        $candidates[] = 'uploads/' . $value;
        $candidates[] = 'produk/' . $value;
        $candidates[] = 'assets/img/' . $value;
    }

    // Tambahkan kandidat WebP untuk aset lama PNG/JPG/JPEG.
    $expanded = [];
    foreach ($candidates as $candidate) {
        $expanded[] = $candidate;
        if (preg_match('/\.(?:png|jpe?g)$/i', $candidate)) {
            $expanded[] = preg_replace('/\.(?:png|jpe?g)$/i', '.webp', $candidate);
        }
    }

    foreach (array_values(array_unique($expanded)) as $candidate) {
        $candidatePath = $root . '/' . ltrim($candidate, '/');
        if (is_file($candidatePath)) {
            return ltrim($candidate, '/');
        }
    }

    if ($fallback !== '' && $value !== trim((string) $fallback)) {
        return teakwave_normalize_asset_path($fallback, '');
    }

    return $value;
}

function teakwave_asset_url($path, $fallback = '') {
    global $siteUrl;

    $rawPath = trim((string) $path);
    if (preg_match('#^https?://#i', $rawPath)) {
        return $rawPath;
    }

    $cleanPath = teakwave_normalize_asset_path($rawPath, $fallback);
    if ($cleanPath === '') {
        return '';
    }

    $filePath = __DIR__ . '/../' . $cleanPath;
    $version = is_file($filePath) ? (string) filemtime($filePath) : '1';

    return $siteUrl . '/' . ltrim($cleanPath, '/') . '?v=' . rawurlencode($version);
}

function teakwave_absolute_url($path = '') {
    global $siteUrl;

    $value = trim((string) $path);
    if ($value === '') {
        return $siteUrl . '/';
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    return $siteUrl . '/' . ltrim($value, '/');
}

function teakwave_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function teakwave_excerpt($value, $length = 160) {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
        return rtrim(mb_substr($text, 0, max(1, $length - 1), 'UTF-8')) . '…';
    }
    if (strlen($text) > $length) {
        return rtrim(substr($text, 0, max(1, $length - 1))) . '…';
    }

    return $text;
}

function teakwave_json($value) {
    return json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}
?>
