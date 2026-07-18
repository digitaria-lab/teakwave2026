<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow', true);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'same-site', 'none'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$currentHost = preg_replace('/:\d+$/', '', $currentHost) ?: $currentHost;
foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $headerName) {
    $value = trim((string) ($_SERVER[$headerName] ?? ''));
    if ($value === '') {
        continue;
    }
    $host = strtolower((string) parse_url($value, PHP_URL_HOST));
    if ($host !== '' && $currentHost !== '' && $host !== $currentHost) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }
}

$raw = file_get_contents('php://input');
$data = [];
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (str_contains($contentType, 'application/json')) {
    $decoded = json_decode((string) $raw, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
} else {
    parse_str((string) $raw, $data);
    if (!$data) {
        $data = $_POST;
    }
}

$page = isset($data['page']) && is_string($data['page']) ? trim($data['page']) : '';
$title = isset($data['title']) && is_string($data['title']) ? trim($data['title']) : '';
$referrer = isset($data['referrer']) && is_string($data['referrer']) ? trim($data['referrer']) : '';

if ($page === '' || strlen($page) > 2000 || !str_starts_with($page, '/')) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

try {
    require_once __DIR__ . '/utakatik/config/database.php';
    require_once __DIR__ . '/includes/page-view-tracker.php';
    $ok = teakwave_store_page_view(
        $pdo,
        $page,
        substr($title, 0, 255),
        substr($referrer, 0, 2000),
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000)
    );
    echo json_encode(['ok' => $ok]);
} catch (Throwable $error) {
    if (function_exists('teakwave_page_view_error_log')) {
        teakwave_page_view_error_log($error);
    }
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
