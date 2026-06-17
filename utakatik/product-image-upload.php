<?php
require_once 'auth/check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

verify_csrf();

$product_id = clean_int($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

function redirect_upload_result($product_id, $params = []) {
    $query = http_build_query(array_merge(['id' => $product_id], $params));
    redirect('product-edit.php?' . $query);
}

try {
    if ($action === 'upload_main_image') {
        $image = upload_file('image', upload_storage_dir(), $product['name'], 'product-main');

        if (!$image) {
            throw new Exception('Pilih main image terlebih dahulu.');
        }

        $stmt = $pdo->prepare("UPDATE products SET image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$image, $product_id]);

        log_activity('upload_main_image', 'products', 'Upload main image produk: ' . $product['name']);
        redirect_upload_result($product_id, ['upload' => 'main-ok']);
    }

    if ($action === 'upload_gallery_images') {
        $multiImages = upload_multiple_files('gallery', upload_storage_dir(), $product['name'], 'product-gallery');

        if (!$multiImages) {
            throw new Exception('Pilih minimal satu gallery image terlebih dahulu.');
        }

        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 0)");
        foreach ($multiImages as $img) {
            $stmt->execute([$product_id, $img]);
        }

        log_activity('upload_gallery_images', 'products', 'Upload gallery image produk: ' . $product['name']);
        redirect_upload_result($product_id, ['upload' => 'gallery-ok']);
    }

    throw new Exception('Action upload tidak valid.');
} catch (Exception $e) {
    redirect_upload_result($product_id, ['upload_error' => $e->getMessage()]);
}
