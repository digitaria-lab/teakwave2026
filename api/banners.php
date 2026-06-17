<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../utakatik/config/database.php';


function json_response($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function asset_url($path) {
    $path = trim((string) $path);

    if ($path === '') return '';

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');

    if (str_starts_with($path, '../uploads/')) {
        return 'uploads/' . substr($path, strlen('../uploads/'));
    }

    if (str_starts_with($path, '../assets/')) {
        return 'assets/' . substr($path, strlen('../assets/'));
    }

    if (str_starts_with($path, '../produk/')) {
        return 'produk/' . substr($path, strlen('../produk/'));
    }

    if (str_starts_with($path, 'uploads/')) {
        return $path;
    }

    if (str_starts_with($path, 'assets/uploads/')) {
        return 'uploads/' . basename($path);
    }

    if (str_starts_with($path, 'utakatik/assets/uploads/')) {
        return 'uploads/' . basename($path);
    }

    return $path;
}

function normalize_html_asset_urls($html) {
    $html = (string) $html;

    $html = str_replace(
        [
            'src="../uploads/', 'src="assets/uploads/', 'src="utakatik/assets/uploads/',
            "src='../uploads/", "src='assets/uploads/", "src='utakatik/assets/uploads/",
            'src="../assets/', "src='../assets/",
            'src="../produk/', "src='../produk/"
        ],
        [
            'src="uploads/', 'src="uploads/', 'src="uploads/',
            "src='uploads/", "src='uploads/", "src='uploads/",
            'src="assets/', "src='assets/",
            'src="produk/', "src='produk/"
        ],
        $html
    );

    return $html;
}


try {
    $placement = trim($_GET['placement'] ?? 'homepage');

    $placementAliases = [
        'homepage' => ['homepage', 'header_home', 'home'],
        'home' => ['homepage', 'header_home', 'home'],
        'profile' => ['profile', 'header_profile', 'profil', 'tentang-kami'],
        'profil' => ['profile', 'header_profile', 'profil', 'tentang-kami'],
        'product' => ['product', 'products', 'produk', 'header_product'],
        'produk' => ['product', 'products', 'produk', 'header_product'],
        'contact' => ['contact', 'kontak', 'header_contact'],
        'kontak' => ['contact', 'kontak', 'header_contact']
    ];

    $placements = $placementAliases[$placement] ?? [$placement];

    $placeholders = implode(',', array_fill(0, count($placements), '?'));

    $sql = "
        SELECT id, title, image, link_url, placement, status, start_date, end_date
        FROM banners
        WHERE status = 'active'
          AND placement IN ($placeholders)
          AND (start_date IS NULL OR start_date = '0000-00-00' OR start_date <= CURDATE())
          AND (end_date IS NULL OR end_date = '0000-00-00' OR end_date >= CURDATE())
        ORDER BY id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($placements);

    $banners = [];

    foreach ($stmt->fetchAll() as $row) {
        $image = asset_url($row['image'] ?? '');

        if (!$image) continue;

        $banners[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'] ?: '',
            'image' => $image,
            'link_url' => $row['link_url'] ?: '',
            'placement' => $row['placement']
        ];
    }

    json_response([
        'success' => true,
        'placement' => $placement,
        'banners' => $banners
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'message' => 'Gagal mengambil data banner.',
        'error' => $e->getMessage()
    ], 500);
}
?>
