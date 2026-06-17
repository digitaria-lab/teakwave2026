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

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');

    if (str_starts_with($path, '../uploads/')) {
        return 'uploads/' . substr($path, strlen('../uploads/'));
    }

    if (str_starts_with($path, '../produk/')) {
        return 'produk/' . substr($path, strlen('../produk/'));
    }

    if (str_starts_with($path, 'uploads/')) {
        return $path;
    }

    // Kompatibilitas path lama sebelum folder upload dipindahkan keluar dari /utakatik.
    if (str_starts_with($path, 'assets/uploads/')) {
        return 'uploads/' . basename($path);
    }

    if (str_starts_with($path, 'utakatik/assets/uploads/')) {
        return 'uploads/' . basename($path);
    }

    if (str_starts_with($path, 'utakatik/')) {
        return $path;
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

function product_slug($text) {
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $text), '-'));
    return $slug ?: 'produk';
}



try {
    $mode = $_GET['mode'] ?? 'list';

    if ($mode === 'detail') {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $slug = trim($_GET['slug'] ?? '');

        if ($id) {
            $stmt = $pdo->prepare("
                SELECT
                    products.*,
                    brands.name AS brand_name,
                    categories.name AS category_name
                FROM products
                LEFT JOIN brands ON brands.id = products.brand_id
                LEFT JOIN categories ON categories.id = products.category_id
                WHERE products.id = ?
                  AND products.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
        } elseif ($slug !== '') {
            $stmt = $pdo->query("
                SELECT
                    products.*,
                    brands.name AS brand_name,
                    categories.name AS category_name
                FROM products
                LEFT JOIN brands ON brands.id = products.brand_id
                LEFT JOIN categories ON categories.id = products.category_id
                WHERE products.status = 'active'
                ORDER BY products.id DESC
            ");

            $product = null;

            foreach ($stmt->fetchAll() as $candidate) {
                if (product_slug($candidate['name']) === $slug) {
                    $product = $candidate;
                    break;
                }
            }
        } else {
            json_response([
                'success' => false,
                'message' => 'Parameter produk tidak valid.'
            ], 400);
        }

        if (!$product) {
            json_response([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, id ASC
        ");
        $stmt->execute([$product['id']]);
        $gallery = array_map(fn($row) => asset_url($row['image_path']), $stmt->fetchAll());

        $mainImage = asset_url($product['image'] ?? '');

        if ($mainImage && !in_array($mainImage, $gallery, true)) {
            array_unshift($gallery, $mainImage);
        }

        if (!$gallery) {
            $gallery = ['produk/1.png'];
        }

        json_response([
            'success' => true,
            'product' => [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'slug' => product_slug($product['name']),
                'brand' => $product['brand_name'] ?: '',
                'category' => $product['category_name'] ?: '',
                'sku' => $product['sku'] ?: '',
                'price' => (float) $product['price'],
                'stock' => (int) $product['stock'],
                'is_best_seller' => !empty($product['is_best_seller']),
                'description' => normalize_html_asset_urls($product['description'] ?: ''),
                'image' => $mainImage ?: $gallery[0],
                'images' => $gallery
            ]
        ]);
    }

    $keyword = trim($_GET['q'] ?? '');
    $brand = trim($_GET['brand'] ?? '');
    $bestSellerOnly = isset($_GET['best_seller']) && in_array(strtolower((string) $_GET['best_seller']), ['1','true','yes'], true);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
    $limitSql = $limit ? ' LIMIT ' . min(max($limit, 1), 60) : '';

    $where = ["products.status = 'active'"];
    $params = [];

    if ($keyword !== '') {
        $where[] = "(products.name LIKE ? OR products.sku LIKE ? OR products.description LIKE ? OR brands.name LIKE ? OR categories.name LIKE ?)";
        $like = '%' . $keyword . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    if ($brand !== '' && strtolower($brand) !== 'all') {
        $where[] = "brands.name = ?";
        $params[] = $brand;
    }

    if ($bestSellerOnly) {
        $where[] = "products.is_best_seller = 1";
    }

    $sql = "
        SELECT
            products.id,
            products.name,
            products.sku,
            products.price,
            products.stock,
            products.image,
            products.description,
            products.is_best_seller,
            brands.name AS brand_name,
            categories.name AS category_name
        FROM products
        LEFT JOIN brands ON brands.id = products.brand_id
        LEFT JOIN categories ON categories.id = products.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY products.is_best_seller DESC, products.id DESC
        $limitSql
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = [];

    foreach ($stmt->fetchAll() as $row) {
        $products[] = [
            'id' => (int) $row['id'],
            'brand' => $row['brand_name'] ?: '',
            'category' => $row['category_name'] ?: '',
            'name' => $row['name'],
            'slug' => product_slug($row['name']),
            'sku' => $row['sku'] ?: '',
            'price' => (float) $row['price'],
            'stock' => (int) $row['stock'],
            'is_best_seller' => !empty($row['is_best_seller']),
            'description' => strip_tags((string) $row['description']),
            'image' => asset_url($row['image'] ?: '')
        ];
    }

    $brandStmt = $pdo->query("
        SELECT DISTINCT brands.name
        FROM products
        JOIN brands ON brands.id = products.brand_id
        WHERE products.status = 'active'
          AND brands.name IS NOT NULL
          AND brands.name <> ''
        ORDER BY brands.name ASC
    ");
    $brands = array_values(array_filter(array_map(fn($row) => $row['name'], $brandStmt->fetchAll())));

    json_response([
        'success' => true,
        'products' => $products,
        'brands' => $brands
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'message' => 'Gagal mengambil data produk.',
        'error' => $e->getMessage()
    ], 500);
}
?>
