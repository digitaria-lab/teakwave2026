<?php
require_once 'auth/check.php';
$page_title = 'E-Commerce Dashboard';

$users = $pdo->query("SELECT COUNT(*) AS total FROM users")->fetch()['total'];
$products = $pdo->query("SELECT COUNT(*) AS total FROM products")->fetch()['total'];
$contents = $pdo->query("SELECT COUNT(*) AS total FROM contents")->fetch()['total'];
$banners = $pdo->query("SELECT COUNT(*) AS total FROM banners")->fetch()['total'];
$uniqueViewsToday = $pdo->query("SELECT COUNT(*) FROM page_views WHERE view_date = CURDATE()")->fetchColumn();
$uniqueVisitorsToday = $pdo->query("SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE view_date = CURDATE()")->fetchColumn();

$latestProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 5")->fetchAll();
$latestUsers = $pdo->query("SELECT users.*, roles.name AS role_name FROM users JOIN roles ON roles.id=users.role_id ORDER BY users.id DESC LIMIT 5")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-eye-fill"></i></div><div><span>Unique Views Today</span><h3><?php echo e($uniqueViewsToday); ?></h3><small><a href="page-views.php" class="text-decoration-none">View statistics</a></small></div></div>
        <div class="stat-card"><div class="stat-icon bg-success-subtle text-success"><i class="bi bi-people-fill"></i></div><div><span>Visitors Today</span><h3><?php echo e($uniqueVisitorsToday); ?></h3><small class="text-muted">Unique visitors</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-box-seam"></i></div><div><span>Products</span><h3><?php echo e($products); ?></h3><small class="text-muted"><?php echo e($users); ?> users</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-info-subtle text-info"><i class="bi bi-file-text"></i></div><div><span>Contents</span><h3><?php echo e($contents); ?></h3><small class="text-muted"><?php echo e($banners); ?> banners</small></div></div>
    </div>

    <div class="dashboard-grid mt-4">
        <div class="card soft-card sales-card">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Sales Overview</h6>
                <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary active">Daily</button><button class="btn btn-outline-secondary">Weekly</button><button class="btn btn-outline-secondary">Monthly</button></div>
            </div>
            <div class="card-body"><canvas id="salesChart" height="118"></canvas></div>
        </div>

        <div class="card soft-card">
            <div class="card-header bg-white border-0 d-flex justify-content-between"><h6 class="fw-bold mb-0">Best Selling Products</h6><small>Monthly</small></div>
            <div class="card-body">
                <?php foreach($latestProducts as $p): ?>
                <div class="product-mini">
                    <div class="product-thumb"><i class="bi bi-box-seam"></i></div>
                    <div class="flex-grow-1"><strong><?php echo e($p['name']); ?></strong><div class="stars">★★★★★ <span><?php echo e($p['category']); ?></span></div></div>
                    <div class="fw-bold">Rp<?php echo number_format($p['price'], 0, ',', '.'); ?></div>
                </div>
                <?php endforeach; ?>
                <a href="products.php" class="btn btn-warning btn-sm w-100 mt-3">View All</a>
            </div>
        </div>

        <div class="card soft-card">
            <div class="card-header bg-white border-0"><h6 class="fw-bold mb-0">Recent Users</h6></div>
            <div class="card-body">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($latestUsers as $u): ?>
                        <tr><td><?php echo e($u['name']); ?></td><td><?php echo e($u['role_name']); ?></td><td><span class="badge bg-success"><?php echo e($u['status']); ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="users.php" class="btn btn-warning btn-sm w-100">View All</a>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
