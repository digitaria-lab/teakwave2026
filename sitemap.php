<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/public-products.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

function sitemap_escape($value) {
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$urls = [
    ['loc' => teakwave_absolute_url(''), 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => teakwave_absolute_url('tentang-kami'), 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => teakwave_absolute_url('produk'), 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => teakwave_absolute_url('kontak'), 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => teakwave_absolute_url('video'), 'changefreq' => 'weekly', 'priority' => '0.7']
];

try {
    require_once __DIR__ . '/utakatik/config/database.php';

    $productStmt = $pdo->query("SELECT name, COALESCE(updated_at, created_at) AS modified_at FROM products WHERE status = 'active' ORDER BY id DESC");
    foreach ($productStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc' => teakwave_absolute_url('produk-' . teakwave_product_slug($row['name'])),
            'lastmod' => !empty($row['modified_at']) ? date('Y-m-d', strtotime($row['modified_at'])) : null,
            'changefreq' => 'weekly',
            'priority' => '0.8'
        ];
    }

    require_once __DIR__ . '/includes/video-public.php';
    $videoStmt = $pdo->query('SELECT id, title, published_at FROM videos ORDER BY published_at DESC, id DESC');
    foreach ($videoStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $urls[] = [
            'loc' => teakwave_absolute_url(public_video_detail_url((int) $row['id'], $row['title'])),
            'lastmod' => !empty($row['published_at']) ? date('Y-m-d', strtotime($row['published_at'])) : null,
            'changefreq' => 'monthly',
            'priority' => '0.6'
        ];
    }
} catch (Throwable $ignored) {
    // Sitemap statis tetap valid ketika database belum tersedia.
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . sitemap_escape($url['loc']) . "</loc>\n";
    if (!empty($url['lastmod'])) echo '    <lastmod>' . sitemap_escape($url['lastmod']) . "</lastmod>\n";
    if (!empty($url['changefreq'])) echo '    <changefreq>' . sitemap_escape($url['changefreq']) . "</changefreq>\n";
    if (!empty($url['priority'])) echo '    <priority>' . sitemap_escape($url['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
?>
