<?php
require_once 'auth/check.php';
$page_title = 'List Product';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    verify_csrf();

    $product_id = clean_int($_POST['id'] ?? 0);

    if ($product_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        log_activity('delete', 'products', 'Menghapus produk ID: ' . $product_id);
    }

    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_best_seller') {
    verify_csrf();

    $product_id = clean_int($_POST['id'] ?? 0);
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;

    if ($product_id > 0) {
        $stmt = $pdo->prepare("UPDATE products SET is_best_seller = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$is_best_seller, $product_id]);
        log_activity('update', 'products', 'Mengubah status best seller produk ID: ' . $product_id);
    }

    redirect(product_page_url_current());
}

function product_page_url_current() {
    $query = $_GET;
    return 'products.php' . ($query ? '?' . http_build_query($query) : '');
}

$where = "";
$params = [];

if (!empty($_GET['q'])) {
    $where .= " WHERE (products.name LIKE ? OR brands.name LIKE ? OR categories.name LIKE ?)";
    $keyword = '%' . trim($_GET['q']) . '%';
    $params = [$keyword, $keyword, $keyword];
}

$limit = 20;
$page = max(1, clean_int($_GET['page'] ?? 1, 1));
$offset = ($page - 1) * $limit;
$viewSource = $_GET['view'] ?? ($_COOKIE['product_view_mode'] ?? 'card');
$view = $viewSource === 'table' ? 'table' : 'card';

if (isset($_GET['view'])) {
    setcookie('product_view_mode', $view, time() + (86400 * 30), '/');
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    LEFT JOIN brands ON brands.id = products.brand_id
    LEFT JOIN categories ON categories.id = products.category_id
    $where
");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $limit));

$stmt = $pdo->prepare("
    SELECT products.*, brands.name AS brand_name, categories.name AS category_name,
           (SELECT COUNT(*) FROM product_images WHERE product_images.product_id = products.id) AS gallery_count
    FROM products
    LEFT JOIN brands ON brands.id = products.brand_id
    LEFT JOIN categories ON categories.id = products.category_id
    $where
    ORDER BY products.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$items = $stmt->fetchAll();

function product_page_url($page) {
    $query = $_GET;
    $query['page'] = $page;
    return 'products.php?' . http_build_query($query);
}

function product_view_url($view) {
    $query = $_GET;
    $query['view'] = $view;
    $query['page'] = 1;
    return 'products.php?' . http_build_query($query);
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="card soft-card">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Product List</h5>
                <small class="text-muted">Showing <?php echo count($items); ?> of <?php echo e($totalItems); ?> products</small>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group view-toggle" role="group" aria-label="Product view">
                    <a href="<?php echo e(product_view_url('card')); ?>" class="btn btn-sm <?php echo $view === 'card' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Card
                    </a>
                    <a href="<?php echo e(product_view_url('table')); ?>" class="btn btn-sm <?php echo $view === 'table' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-table"></i> Table
                    </a>
                </div>

                <form class="d-flex gap-2 product-search-form" method="get">
                    <input type="hidden" name="view" value="<?php echo e($view); ?>">
                    <input type="text" name="q" class="form-control" placeholder="Search name, brand, category..." value="<?php echo e($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-primary">Search</button>
                    <?php if(!empty($_GET['q'])): ?><a href="products.php?view=<?php echo e($view); ?>" class="btn btn-light">Reset</a><?php endif; ?>
                </form>

                <?php if(has_permission('products-add')): ?>
                <a href="product-add.php" class="btn btn-warning">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($view === 'card'): ?>
            <div class="product-grid-4">
                <?php foreach($items as $item): ?>
                <div class="catalog-card product-card-compact">
                    <div class="catalog-img product-thumb-wide">
                        <?php if($item['image']): ?>
                            <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                        <?php else: ?>
                            <i class="bi bi-box-seam"></i>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-body">
                        <h6><?php echo e($item['name']); ?></h6>
                        <p><?php echo e($item['brand_name'] ?: '-'); ?> • <?php echo e($item['category_name'] ?: '-'); ?></p>

                        <?php if(!empty($item['is_best_seller'])): ?>
                            <span class="badge text-bg-warning mb-2"><i class="bi bi-star-fill"></i> Best Seller</span>
                        <?php endif; ?>

                        <form method="post" class="best-seller-list-form mb-2">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="update_best_seller">
                            <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                            <label class="form-check form-switch m-0">
                                <input class="form-check-input best-seller-auto-submit" type="checkbox" name="is_best_seller" value="1" <?php echo !empty($item['is_best_seller']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Best Seller</span>
                            </label>
                        </form>

                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <small class="text-muted">Stock <?php echo e($item['stock']); ?> • Gallery <?php echo e($item['gallery_count']); ?></small>
                        </div>

                        <strong>Rp<?php echo number_format($item['price'],0,',','.'); ?></strong>

                        <div class="mt-2 d-flex gap-2">
                            <?php if(has_permission('products-edit')): ?>
                            <a href="product-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                            <?php endif; ?>

                            <form method="post" class="flex-fill" onsubmit="return confirm('Delete product?')">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                <button class="btn btn-sm btn-outline-danger w-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if(!$items): ?>
                    <div class="alert alert-warning mb-0">Produk tidak ditemukan.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle product-table-view admin-datatable">
                    <thead>
                        <tr>
                            <th style="width:80px;">Image</th>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Best Seller</th>
                            <th>Stock</th>
                            <th>Gallery</th>
                            <th>Price</th>
                            <th style="width:170px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td>
                                <div class="table-product-img">
                                    <?php if($item['image']): ?>
                                        <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                                    <?php else: ?>
                                        <i class="bi bi-box-seam"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo e($item['name']); ?></strong><br>
                                <small class="text-muted"><?php echo e($item['sku'] ?: '-'); ?></small>
                            </td>
                            <td><?php echo e($item['brand_name'] ?: '-'); ?></td>
                            <td><?php echo e($item['category_name'] ?: '-'); ?></td>
                            <td>
                                <form method="post" class="best-seller-list-form">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_best_seller">
                                    <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                    <label class="form-check form-switch m-0">
                                        <input class="form-check-input best-seller-auto-submit" type="checkbox" name="is_best_seller" value="1" <?php echo !empty($item['is_best_seller']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label small"><?php echo !empty($item['is_best_seller']) ? 'Yes' : 'No'; ?></span>
                                    </label>
                                </form>
                            </td>
                            <td><?php echo e($item['stock']); ?></td>
                            <td><?php echo e($item['gallery_count']); ?></td>
                            <td><strong>Rp<?php echo number_format($item['price'],0,',','.'); ?></strong></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if(has_permission('products-edit')): ?>
                                    <a href="product-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php endif; ?>

                                    <form method="post" onsubmit="return confirm('Delete product?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if(!$items): ?>
                <div class="alert alert-warning mb-0 datatable-empty-alert">Data tidak ditemukan.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(product_page_url($page - 1)); ?>">Previous</a>
                </li>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php if($startPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo e(product_page_url(1)); ?>">1</a></li>
                    <?php if($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(product_page_url($i)); ?>"><?php echo e($i); ?></a>
                    </li>
                <?php endfor; ?>

                <?php if($endPage < $totalPages): ?>
                    <?php if($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?php echo e(product_page_url($totalPages)); ?>"><?php echo e($totalPages); ?></a></li>
                <?php endif; ?>

                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(product_page_url($page + 1)); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
