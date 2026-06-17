<?php
require_once 'auth/check.php';
$page_title = 'Search Results';

$q = trim($_GET['q'] ?? '');
$products = [];
$contents = [];

if ($q !== '') {
    $keyword = '%' . $q . '%';

    $stmt = $pdo->prepare("
        SELECT products.*, brands.name AS brand_name, categories.name AS category_name
        FROM products
        LEFT JOIN brands ON brands.id = products.brand_id
        LEFT JOIN categories ON categories.id = products.category_id
        WHERE products.name LIKE ?
           OR products.sku LIKE ?
           OR products.description LIKE ?
           OR brands.name LIKE ?
           OR categories.name LIKE ?
        ORDER BY products.id DESC
        LIMIT 30
    ");
    $stmt->execute([$keyword, $keyword, $keyword, $keyword, $keyword]);
    $products = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT contents.*,
               (SELECT COUNT(*) FROM content_images WHERE content_images.content_id = contents.id) AS image_count
        FROM contents
        WHERE contents.title LIKE ?
           OR contents.slug LIKE ?
           OR contents.body LIKE ?
           OR contents.type LIKE ?
        ORDER BY contents.id DESC
        LIMIT 30
    ");
    $stmt->execute([$keyword, $keyword, $keyword, $keyword]);
    $contents = $stmt->fetchAll();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="card soft-card mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-2">Hasil Pencarian</h5>
        <form method="get" action="search.php" class="search-page-form">
            <input type="text" name="q" class="form-control" placeholder="Cari product atau content..." value="<?php echo e($q); ?>" autofocus>
            <button class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
        </form>
        <?php if($q !== ''): ?>
            <small class="text-muted d-block mt-2">
                Keyword: <strong><?php echo e($q); ?></strong> —
                <?php echo count($products); ?> product ditemukan,
                <?php echo count($contents); ?> content ditemukan.
            </small>
        <?php endif; ?>
    </div>
</div>

<?php if($q === ''): ?>
    <div class="alert alert-info">Masukkan keyword pencarian terlebih dahulu.</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-xl-6">
        <div class="card soft-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Product Results</h5>
                    <span class="badge bg-primary"><?php echo count($products); ?></span>
                </div>
                <?php if($products): ?>
                    <div class="search-result-list">
                        <?php foreach($products as $item): ?>
                        <div class="search-result-item">
                            <div class="search-result-thumb">
                                <?php if(!empty($item['image'])): ?>
                                    <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                                <?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6><?php echo e($item['name']); ?></h6>
                                <p><?php echo e($item['brand_name'] ?: '-'); ?> • <?php echo e($item['category_name'] ?: '-'); ?> • SKU: <?php echo e($item['sku'] ?: '-'); ?></p>
                                <strong>Rp<?php echo number_format($item['price'], 0, ',', '.'); ?></strong>
                            </div>
                            <?php if(has_permission('products-edit')): ?>
                            <a href="product-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?><div class="alert alert-warning mb-0">Tidak ada product yang cocok.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card soft-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Content Results</h5>
                    <span class="badge bg-primary"><?php echo count($contents); ?></span>
                </div>
                <?php if($contents): ?>
                    <div class="search-result-list">
                        <?php foreach($contents as $item): ?>
                        <div class="search-result-item">
                            <div class="search-result-thumb"><i class="bi bi-file-earmark-text"></i></div>
                            <div class="flex-grow-1">
                                <h6><?php echo e($item['title']); ?></h6>
                                <p><?php echo e($item['type']); ?> • <?php echo e($item['status']); ?> • Images: <?php echo e($item['image_count'] ?? 0); ?></p>
                                <small class="text-muted"><?php echo e(wp_trim_words(strip_tags($item['body']), 18, '...')); ?></small>
                            </div>
                            <?php if(has_permission('contents-edit')): ?>
                            <a href="content-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?><div class="alert alert-warning mb-0">Tidak ada content yang cocok.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
