<?php
require_once __DIR__ . '/config.php';

function teakwave_product_slug($text) {
    $value = trim((string) $text);
    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }
    }
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
    return $slug !== '' ? $slug : 'produk';
}

function teakwave_product_asset_url($path) {
    return teakwave_asset_url($path, 'produk/1.png');
}

function teakwave_safe_product_html($html) {
    $html = trim((string) $html);
    if ($html === '') {
        return '<p>Deskripsi produk belum tersedia.</p>';
    }

    if (!class_exists('DOMDocument')) {
        return '<p>' . teakwave_escape(strip_tags($html)) . '</p>';
    }

    $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a'];
    $dom = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="tw-product-description">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $dom->getElementById('tw-product-description');
    if (!$root) {
        return '<p>' . teakwave_escape(strip_tags($html)) . '</p>';
    }

    $nodes = [];
    foreach ($root->getElementsByTagName('*') as $node) {
        $nodes[] = $node;
    }
    foreach (array_reverse($nodes) as $node) {
        $tag = strtolower($node->nodeName);
        if (!in_array($tag, $allowedTags, true)) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
            continue;
        }

        $attributes = [];
        foreach ($node->attributes ?? [] as $attribute) {
            $attributes[] = $attribute->name;
        }
        foreach ($attributes as $attributeName) {
            if ($tag === 'a' && $attributeName === 'href') {
                $href = trim((string) $node->getAttribute('href'));
                if (!preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                    $node->removeAttribute('href');
                }
                continue;
            }
            $node->removeAttribute($attributeName);
        }
        if ($tag === 'a' && $node->hasAttribute('href')) {
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $dom->saveHTML($child);
    }
    return $output !== '' ? $output : '<p>Deskripsi produk belum tersedia.</p>';
}

function teakwave_public_products(PDO $pdo, $limit = null, $bestSellerOnly = false) {
    $limit = $limit !== null ? min(max((int) $limit, 1), 60) : null;
    $where = ["products.status = 'active'"];
    if ($bestSellerOnly) {
        $where[] = 'products.is_best_seller = 1';
    }

    $sql = "SELECT products.id, products.name, products.sku, products.price, products.stock,
                   products.image, products.description, products.is_best_seller,
                   brands.name AS brand_name, categories.name AS category_name
            FROM products
            LEFT JOIN brands ON brands.id = products.brand_id
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY products.is_best_seller DESC, products.id DESC";
    if ($limit !== null) {
        $sql .= ' LIMIT ' . $limit;
    }

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    return array_map(static function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => teakwave_product_slug($row['name']),
            'brand' => (string) ($row['brand_name'] ?? ''),
            'category' => (string) ($row['category_name'] ?? ''),
            'sku' => (string) ($row['sku'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'stock' => (int) ($row['stock'] ?? 0),
            'is_best_seller' => !empty($row['is_best_seller']),
            'description' => strip_tags((string) ($row['description'] ?? '')),
            'image' => teakwave_product_asset_url($row['image'] ?? '')
        ];
    }, $rows);
}

function teakwave_public_product(PDO $pdo, $id = 0, $slug = '') {
    $id = (int) $id;
    $slug = trim((string) $slug);

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT products.*, brands.name AS brand_name, categories.name AS category_name
                               FROM products
                               LEFT JOIN brands ON brands.id = products.brand_id
                               LEFT JOIN categories ON categories.id = products.category_id
                               WHERE products.id = ? AND products.status = 'active' LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } elseif ($slug !== '') {
        $stmt = $pdo->query("SELECT products.*, brands.name AS brand_name, categories.name AS category_name
                             FROM products
                             LEFT JOIN brands ON brands.id = products.brand_id
                             LEFT JOIN categories ON categories.id = products.category_id
                             WHERE products.status = 'active' ORDER BY products.id DESC");
        $row = null;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
            if (hash_equals(teakwave_product_slug($candidate['name']), $slug)) {
                $row = $candidate;
                break;
            }
        }
    } else {
        return null;
    }

    if (!$row) {
        return null;
    }

    $galleryStmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC');
    $galleryStmt->execute([(int) $row['id']]);
    $images = array_values(array_filter(array_map(static fn($item) => teakwave_product_asset_url($item['image_path'] ?? ''), $galleryStmt->fetchAll(PDO::FETCH_ASSOC))));
    $mainImage = teakwave_product_asset_url($row['image'] ?? '');
    if ($mainImage !== '' && !in_array($mainImage, $images, true)) {
        array_unshift($images, $mainImage);
    }
    if (!$images) {
        $images = ['produk/1.png'];
    }

    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'slug' => teakwave_product_slug($row['name']),
        'brand' => (string) ($row['brand_name'] ?? ''),
        'category' => (string) ($row['category_name'] ?? ''),
        'sku' => (string) ($row['sku'] ?? ''),
        'price' => (float) ($row['price'] ?? 0),
        'stock' => (int) ($row['stock'] ?? 0),
        'is_best_seller' => !empty($row['is_best_seller']),
        'description' => teakwave_safe_product_html($row['description'] ?? ''),
        'image' => $mainImage ?: $images[0],
        'images' => $images
    ];
}

function teakwave_render_product_card($product) {
    $name = teakwave_escape($product['name'] ?? 'Produk Teakwave');
    $brand = teakwave_escape($product['brand'] ?? '');
    $slugValue = $product['slug'] ?? teakwave_product_slug($product['name'] ?? 'produk');
    $slug = teakwave_escape($slugValue);
    $image = teakwave_escape(teakwave_product_asset_url($product['image'] ?? ''));
    $fallback = teakwave_escape(teakwave_asset_url('produk/1.png'));
    $detailUrl = teakwave_escape(teakwave_absolute_url('produk-' . $slugValue));

    return '<div data-product-card data-brand="' . $brand . '" data-name="' . $name . '">' .
        '<a class="catalog-link" href="' . $detailUrl . '" aria-label="Lihat detail ' . $name . '">' .
        '<article class="catalog-card"><div class="catalog-img">' .
        '<img class="catalog-product-photo" src="' . $image . '" data-fallback-src="' . $fallback . '" alt="' . $name . '" loading="lazy" decoding="async" width="720" height="720">' .
        '<div class="catalog-device catalog-device-fallback" aria-hidden="true" hidden></div></div>' .
        '<h2 class="catalog-name">' . $name . '</h2></article></a></div>';
}

?>
