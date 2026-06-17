<?php
require_once 'auth/check.php';
$page_title = 'Activity Logs';

$where = "";
$params = [];

if (!empty($_GET['q'])) {
    $where = " WHERE (users.name LIKE ? OR users.email LIKE ? OR activity_logs.action LIKE ? OR activity_logs.module LIKE ? OR activity_logs.description LIKE ? OR activity_logs.ip_address LIKE ?)";
    $keyword = '%' . trim($_GET['q']) . '%';
    $params = [$keyword, $keyword, $keyword, $keyword, $keyword, $keyword];
}

$limit = 25;
$page = max(1, clean_int($_GET['page'] ?? 1, 1));
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM activity_logs
    LEFT JOIN users ON users.id = activity_logs.user_id
    $where
");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $limit));

$stmt = $pdo->prepare("
    SELECT activity_logs.*, users.name AS user_name, users.email AS user_email
    FROM activity_logs
    LEFT JOIN users ON users.id = activity_logs.user_id
    $where
    ORDER BY activity_logs.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$items = $stmt->fetchAll();

function log_page_url($page) {
    $query = $_GET;
    $query['page'] = $page;
    return 'logs.php?' . http_build_query($query);
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
                <h5 class="fw-bold mb-0">Activity Logs</h5>
                <small class="text-muted">Showing <?php echo count($items); ?> of <?php echo e($totalItems); ?> activities</small>
            </div>

            <form method="get" class="d-flex gap-2">
                <input type="text" name="q" class="form-control" placeholder="Search user, action, module, IP..." value="<?php echo e($_GET['q'] ?? ''); ?>">
                <button class="btn btn-primary">Search</button>
                <?php if(!empty($_GET['q'])): ?><a href="logs.php" class="btn btn-light">Reset</a><?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle log-table">
                <thead>
                    <tr>
                        <th style="width:170px;">Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo e(date('d M Y', strtotime($item['created_at']))); ?></strong><br>
                            <small class="text-muted"><?php echo e(date('H:i:s', strtotime($item['created_at']))); ?></small>
                        </td>
                        <td>
                            <?php if($item['user_name']): ?>
                                <strong><?php echo e($item['user_name']); ?></strong><br>
                                <small class="text-muted"><?php echo e($item['user_email']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">Deleted User / Guest</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary"><?php echo e($item['action']); ?></span></td>
                        <td><span class="badge bg-light text-dark"><?php echo e($item['module']); ?></span></td>
                        <td><?php echo e($item['description']); ?></td>
                        <td><small><?php echo e($item['ip_address']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(!$items): ?>
                    <tr>
                        <td colspan="6">
                            <div class="alert alert-warning mb-0">Belum ada aktivitas.</div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(log_page_url($page - 1)); ?>">Previous</a>
                </li>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php if($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo e(log_page_url(1)); ?>">1</a>
                    </li>
                    <?php if($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(log_page_url($i)); ?>"><?php echo e($i); ?></a>
                    </li>
                <?php endfor; ?>

                <?php if($endPage < $totalPages): ?>
                    <?php if($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo e(log_page_url($totalPages)); ?>"><?php echo e($totalPages); ?></a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(log_page_url($page + 1)); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
