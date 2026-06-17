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
    $slug = trim($_GET['slug'] ?? '');
    $type = trim($_GET['type'] ?? '');

    if ($slug !== '') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM contents
            WHERE slug = ?
              AND status = 'published'
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $content = $stmt->fetch();

        if (!$content) {
            json_response([
                'success' => false,
                'message' => 'Content tidak ditemukan.'
            ], 404);
        }

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM content_images
            WHERE content_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$content['id']]);
        $images = array_map(fn($row) => asset_url($row['image_path']), $stmt->fetchAll());

        json_response([
            'success' => true,
            'content' => [
                'id' => (int) $content['id'],
                'title' => $content['title'],
                'slug' => $content['slug'],
                'type' => $content['type'],
                'body' => normalize_html_asset_urls($content['body'] ?? ''),
                'images' => $images
            ]
        ]);
    }

    $params = [];
    $where = ["status = 'published'"];

    if ($type !== '') {
        $where[] = "type = ?";
        $params[] = $type;
    }

    $stmt = $pdo->prepare("
        SELECT id, title, slug, type, body
        FROM contents
        WHERE " . implode(' AND ', $where) . "
        ORDER BY id DESC
    ");
    $stmt->execute($params);

    $contents = [];

    foreach ($stmt->fetchAll() as $row) {
        $contents[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'type' => $row['type'],
            'body' => normalize_html_asset_urls($row['body'] ?? '')
        ];
    }

    json_response([
        'success' => true,
        'contents' => $contents
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'message' => 'Gagal mengambil data content.',
        'error' => $e->getMessage()
    ], 500);
}
?>
