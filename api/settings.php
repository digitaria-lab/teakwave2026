<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$defaults = [
    'tokopedia' => 'https://www.tokopedia.com/teakwave',
    'shopee' => 'https://shopee.co.id/teakwave',
    'whatsapp' => 'https://wa.me/6282112345678',
    'instagram' => 'https://www.instagram.com/teak.wave/',
    'facebook' => 'https://www.facebook.com/teakwave',
];

try {
    require_once __DIR__ . '/../utakatik/config/database.php';
    $keys = [
        'tokopedia_url',
        'shopee_url',
        'whatsapp_url',
        'instagram_url',
        'facebook_url',
        'footer_instagram_url',
        'footer_facebook_url',
    ];

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM website_settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($keys);

    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = trim((string) $row['setting_value']);
    }

    $urls = [
        'tokopedia' => $settings['tokopedia_url'] ?? $defaults['tokopedia'],
        'shopee' => $settings['shopee_url'] ?? $defaults['shopee'],
        'whatsapp' => $settings['whatsapp_url'] ?? $defaults['whatsapp'],
        'instagram' => $settings['instagram_url'] ?? ($settings['footer_instagram_url'] ?? $defaults['instagram']),
        'facebook' => $settings['facebook_url'] ?? ($settings['footer_facebook_url'] ?? $defaults['facebook']),
    ];

    foreach ($urls as $key => $url) {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            $urls[$key] = $defaults[$key];
        }
    }

    echo json_encode([
        'success' => true,
        'urls' => $urls,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'urls' => $defaults,
        'message' => 'Pengaturan URL belum dapat dimuat.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
