<?php
require_once 'auth/check.php';
$page_title = 'Edit Product';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_image') {
    verify_csrf();

    $image_id = clean_int($_POST['image_id'] ?? 0);
    $product_id = clean_int($_POST['product_id'] ?? 0);

    if ($image_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        log_activity('delete_image', 'products', 'Menghapus gambar produk ID: ' . $image_id);
    }

    redirect('product-edit.php?id=' . $product_id);
}

if (empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $items = $pdo->query("
        SELECT products.*, brands.name AS brand_name, categories.name AS category_name
        FROM products
        LEFT JOIN brands ON brands.id = products.brand_id
        LEFT JOIN categories ON categories.id = products.category_id
        ORDER BY products.id DESC
    ")->fetchAll();

    include 'includes/header.php';
    include 'includes/sidebar.php';
    ?>
    <main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="card soft-card">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Choose Product to Edit</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?php echo e($item['name']); ?></td>
                            <td><?php echo e($item['brand_name'] ?: '-'); ?></td>
                            <td><?php echo e($item['category_name'] ?: '-'); ?></td>
                            <td>Rp<?php echo number_format($item['price'],0,',','.'); ?></td>
                            <td><a href="product-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-primary">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </main>
    <?php include 'includes/footer.php'; ?>
    <?php exit;
}

$product_id = clean_int($_POST['id'] ?? ($_GET['id'] ?? 0));

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$edit = $stmt->fetch();

if (!$edit) {
    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_product') {
    verify_csrf();

    try {
        $pdo->beginTransaction();

        $name = sanitize_plain_text($_POST['name'] ?? '', 200);
        $brand_id = clean_int($_POST['brand_id'] ?? 0);
        $category_id = clean_int($_POST['category_id'] ?? 0);
        $sku = sanitize_plain_text($_POST['sku'] ?? '', 100);
        $price = clean_decimal($_POST['price'] ?? 0);
        $stock = clean_int($_POST['stock'] ?? 0);
        $status = in_array($_POST['status'] ?? 'active', ['active','inactive'], true) ? $_POST['status'] : 'active';
        $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
        $descriptionRaw = $_POST['description'] ?? '';
        if (!empty($_POST['description_encoded'])) {
            $decodedDescription = base64_decode($_POST['description_encoded'], true);
            if ($decodedDescription !== false) {
                $descriptionRaw = $decodedDescription;
            }
        }
        $description = sanitize_html_content($descriptionRaw);

        if ($name === '' || $brand_id <= 0 || $category_id <= 0) {
            throw new Exception('Product name, brand, and category are required.');
        }

        // File upload dipisah dari form edit utama agar request update produk tidak memakai multipart/form-data.
        // Ini mengurangi risiko request ditolak WAF/ModSecurity di shared hosting cPanel.
        $image = rename_uploaded_file_seo($_POST['old_image'] ?? $edit['image'] ?? null, $name, 'product-main');

        $stmt = $pdo->prepare("
            UPDATE products
            SET brand_id=?, category_id=?, name=?, sku=?, price=?, image=?, stock=?, status=?, is_best_seller=?, description=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$brand_id, $category_id, $name, $sku, $price, $image, $stock, $status, $is_best_seller, $description, $_POST['id']]);

        // Rename semua gallery image existing saat nama produk diubah.
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$_POST['id']]);
        $existingGallery = $stmt->fetchAll();

        foreach ($existingGallery as $index => $galleryItem) {
            $newGalleryPath = rename_uploaded_file_seo($galleryItem['image_path'], $name, 'product-gallery-' . ($index + 1));

            if ($newGalleryPath !== $galleryItem['image_path']) {
                $updateGallery = $pdo->prepare("UPDATE product_images SET image_path = ? WHERE id = ?");
                $updateGallery->execute([$newGalleryPath, $galleryItem['id']]);
            }
        }

        $pdo->commit();
        log_activity('update', 'products', 'Mengubah produk: ' . $name);
        redirect('product-edit.php?id=' . intval($_POST['id']));
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$brands = $pdo->query("SELECT * FROM brands WHERE status='active' ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id DESC");
$stmt->execute([$edit['id']]);
$productImages = $stmt->fetchAll();

if (empty($error) && !empty($_GET['upload_error'])) {
    $error = sanitize_plain_text(rawurldecode((string) $_GET['upload_error']), 300);
}

$success = '';
if (isset($_GET['upload'])) {
    if ($_GET['upload'] === 'main-ok') {
        $success = 'Main image berhasil diupload.';
    } elseif ($_GET['upload'] === 'gallery-ok') {
        $success = 'Gallery image berhasil diupload.';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card soft-card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Edit Product: <?php echo e($edit['name']); ?></h5>
                    <div class="d-flex gap-2"><button type="submit" form="productForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button><a href="products.php" class="btn btn-light btn-sm">Back to List</a></div>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <?php if(!empty($success)): ?>
                    <div class="alert alert-success"><?php echo e($success); ?></div>
                <?php endif; ?>

                <form id="productForm" method="post" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="description_encoded" value="">
                    <input type="hidden" name="action" value="update_product">
                    <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">
                    <input type="hidden" name="old_image" value="<?php echo e($edit['image']); ?>">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Product Name</label>
                                    <input name="name" class="form-control" required value="<?php echo e($edit['name']); ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">SKU</label>
                                    <input name="sku" class="form-control" value="<?php echo e($edit['sku']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Brand</label>
                                    <select name="brand_id" class="form-select" required>
                                        <option value="">Choose Brand</option>
                                        <?php foreach($brands as $b): ?>
                                            <option value="<?php echo e($b['id']); ?>" <?php echo ($edit['brand_id']==$b['id'])?'selected':''; ?>>
                                                <?php echo e($b['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Choose Category</option>
                                        <?php foreach($categories as $c): ?>
                                            <option value="<?php echo e($c['id']); ?>" <?php echo ($edit['category_id']==$c['id'])?'selected':''; ?>>
                                                <?php echo e($c['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control wysiwyg"><?php echo e($edit['description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="product-side-panel">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input name="price" type="number" min="0" step="0.01" class="form-control" value="<?php echo e($edit['price']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Stock</label>
                                    <input name="stock" type="number" min="0" class="form-control" value="<?php echo e($edit['stock']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo ($edit['status']==='active')?'selected':''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($edit['status']==='inactive')?'selected':''; ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="best-seller-option">
                                        <div>
                                            <label class="form-check-label fw-bold" for="isBestSellerEdit">Produk Best Seller</label>
                                            <small class="text-muted d-block">Tampilkan produk ini di section Best Seller homepage.</small>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="isBestSellerEdit" name="is_best_seller" value="1" <?php echo !empty($edit['is_best_seller']) ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Main Image</label>
                                    <input name="image" type="file" form="mainImageUploadForm" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <button type="submit" form="mainImageUploadForm" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-upload"></i> Upload Main Image
                                    </button>
                                    <small class="text-muted d-block mt-1">Upload gambar dipisah dari form edit agar lebih aman di server cPanel.</small>

                                    <?php if(!empty($edit['image'])): ?>
                                        <img src="<?php echo e($edit['image']); ?>" class="mt-2 rounded" style="width:100%;height:150px;object-fit:cover">
                                    <?php endif; ?>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Add More Gallery Images</label>
                                    <input name="gallery[]" type="file" multiple form="galleryImageUploadForm" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <button type="submit" form="galleryImageUploadForm" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-images"></i> Upload Gallery Images
                                    </button>
                                    <small class="text-muted d-block mt-1">Maks 5MB per file.</small>
                                </div>
                            </div>
                        </div>

                        <?php if($productImages): ?>
                        <div class="col-12">
                            <label class="form-label">Current Gallery</label>
                            <div class="row g-2">
                                <?php foreach($productImages as $img): ?>
                                <div class="col-6 col-md-3">
                                    <div class="position-relative">
                                        <img src="<?php echo e($img['image_path']); ?>" style="width:100%;height:90px;object-fit:cover;border-radius:10px">
                                        <button type="submit" form="deleteImageForm-<?php echo e($img['id']); ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="return confirm('Delete image?')">×</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Product</button>
                        <a href="products.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>

                <form id="mainImageUploadForm" action="product-image-upload.php" method="post" enctype="multipart/form-data" class="d-none">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="upload_main_image">
                    <input type="hidden" name="product_id" value="<?php echo e($edit['id']); ?>">
                </form>

                <form id="galleryImageUploadForm" action="product-image-upload.php" method="post" enctype="multipart/form-data" class="d-none">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="upload_gallery_images">
                    <input type="hidden" name="product_id" value="<?php echo e($edit['id']); ?>">
                </form>

                <?php if($productImages): ?>
                    <?php foreach($productImages as $img): ?>
                        <form id="deleteImageForm-<?php echo e($img['id']); ?>" method="post" class="d-none">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_image">
                            <input type="hidden" name="image_id" value="<?php echo e($img['id']); ?>">
                            <input type="hidden" name="product_id" value="<?php echo e($edit['id']); ?>">
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
