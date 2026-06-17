<?php
require_once 'auth/check.php';
$page_title = 'Add Product';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $image = upload_file('image', upload_storage_dir(), $name, 'product-main');

        $stmt = $pdo->prepare("
            INSERT INTO products (brand_id, category_id, name, sku, price, image, stock, status, is_best_seller, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$brand_id, $category_id, $name, $sku, $price, $image, $stock, $status, $is_best_seller, $description]);

        $product_id = $pdo->lastInsertId();

        $multiImages = upload_multiple_files('gallery', upload_storage_dir(), $name, 'product-gallery');
        foreach ($multiImages as $index => $img) {
            $isPrimary = (!$image && $index === 0) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $img, $isPrimary]);
        }

        log_activity('create', 'products', 'Menambahkan produk: ' . $name);
        $pdo->commit();
        redirect('products.php');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$brands = $pdo->query("SELECT * FROM brands WHERE status='active' ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card soft-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Add New Product</h5>
                    <div class="d-flex gap-2"><button type="submit" form="productForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button><a href="products.php" class="btn btn-light btn-sm">Back to List</a></div>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="productForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="description_encoded" value="">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Product Name</label>
                                    <input name="name" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">SKU</label>
                                    <input name="sku" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Brand</label>
                                    <select name="brand_id" class="form-select" required>
                                        <option value="">Choose Brand</option>
                                        <?php foreach($brands as $b): ?>
                                            <option value="<?php echo e($b['id']); ?>"><?php echo e($b['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Choose Category</option>
                                        <?php foreach($categories as $c): ?>
                                            <option value="<?php echo e($c['id']); ?>"><?php echo e($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control wysiwyg"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="product-side-panel">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input name="price" type="number" min="0" step="0.01" class="form-control" value="0">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Stock</label>
                                    <input name="stock" type="number" min="0" class="form-control" value="0">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="best-seller-option">
                                        <div>
                                            <label class="form-check-label fw-bold" for="isBestSellerAdd">Produk Best Seller</label>
                                            <small class="text-muted d-block">Tampilkan produk ini di section Best Seller homepage.</small>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="isBestSellerAdd" name="is_best_seller" value="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Main Image</label>
                                    <input name="image" type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Gallery Images</label>
                                    <input name="gallery[]" type="file" multiple class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <small class="text-muted">Bisa upload lebih dari 1 gambar. Maks 5MB per file.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Product</button>
                        <a href="products.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
