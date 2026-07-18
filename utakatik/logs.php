<?php
require_once 'auth/check.php';
$page_title = 'Activity Logs';

// Always inspect the live schema here. This repairs older deployments where
// action was still ENUM/short VARCHAR and silently rejected newer audit names.
$activityLogSchemaWarning = '';
$flushedPendingLogs = 0;
try {
    ensure_activity_log_schema(true);
    $flushedPendingLogs = activity_log_flush_pending();
} catch (Throwable $e) {
    $activityLogSchemaWarning = sanitize_plain_text($e->getMessage(), 1000);
    activity_log_write_error('Pemeriksaan schema dari halaman Activity Logs gagal: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'activity_log_test') {
    verify_csrf('activity-log');

    if (!is_super_admin()) {
        log_activity('audit_test_denied', 'activity-log', 'Mencoba menjalankan pengujian Activity Log tanpa hak Super Admin.');
        http_response_code(403);
        include 'includes/no-access.php';
        exit;
    }

    $testWritten = log_activity(
        'audit_test',
        'activity-log',
        'Pengujian manual Activity Log berhasil dijalankan dari halaman Activity Logs.',
        true
    );
    redirect('logs.php?test=' . ($testWritten ? 'ok' : 'queued'));
}

$pendingAuditLogs = activity_log_pending_count();
$clauses = [];
$params = [];
$search = trim((string) ($_GET['q'] ?? ''));
$moduleFilter = trim((string) ($_GET['module'] ?? ''));
$actionFilter = trim((string) ($_GET['action'] ?? ''));

if ($search !== '') {
    $clauses[] = "(users.name LIKE ? OR users.email LIKE ? OR activity_logs.action LIKE ? OR activity_logs.module LIKE ? OR activity_logs.description LIKE ? OR activity_logs.ip_address LIKE ?)";
    $keyword = '%' . $search . '%';
    array_push($params, $keyword, $keyword, $keyword, $keyword, $keyword, $keyword);
}

if ($moduleFilter !== '') {
    $clauses[] = "activity_logs.module = ?";
    $params[] = $moduleFilter;
}

if ($actionFilter !== '') {
    $clauses[] = "activity_logs.action = ?";
    $params[] = $actionFilter;
}

$where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
$moduleOptions = $pdo->query("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
$actionOptions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

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

function activity_action_badge_class($action) {
    $action = strtolower((string) $action);

    if (str_contains($action, 'failed') || str_contains($action, 'denied') || $action === 'error') {
        return 'bg-danger';
    }

    if (str_contains($action, 'delete')) {
        return 'bg-warning text-dark';
    }

    if (str_contains($action, 'reset')) {
        return 'text-bg-danger';
    }

    if (str_contains($action, 'restore') || str_contains($action, 'backup') || str_contains($action, 'dump')) {
        return 'bg-info text-dark';
    }

    if (str_contains($action, 'create') || str_contains($action, 'update')) {
        return 'bg-success';
    }

    return 'bg-primary';
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="card soft-card">
    <div class="card-body">
        <?php if ($activityLogSchemaWarning !== ''): ?>
            <div class="alert alert-danger">
                Struktur Activity Log belum dapat diperbaiki otomatis: <?php echo e($activityLogSchemaWarning); ?>
            </div>
        <?php endif; ?>

        <?php if (($_GET['test'] ?? '') === 'ok'): ?>
            <div class="alert alert-success">Test Activity Log berhasil dan langsung tersimpan ke database.</div>
        <?php elseif (($_GET['test'] ?? '') === 'queued'): ?>
            <div class="alert alert-warning">Database belum menerima test log. Aktivitas sudah diamankan di antrean lokal dan akan dicoba lagi otomatis.</div>
        <?php endif; ?>

        <?php if ($flushedPendingLogs > 0): ?>
            <div class="alert alert-success">Sebanyak <?php echo e($flushedPendingLogs); ?> aktivitas tertunda berhasil dipindahkan ke database.</div>
        <?php endif; ?>

        <?php if ($pendingAuditLogs > 0): ?>
            <div class="alert alert-warning">
                Terdapat <?php echo e($pendingAuditLogs); ?> aktivitas yang masih berada di antrean aman karena database menolak penulisan log. Periksa file
                <code>utakatik/storage/activity-logs/logger-errors.log</code> untuk diagnosis server.
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Activity Logs</h5>
                <small class="text-muted">Showing <?php echo count($items); ?> of <?php echo e($totalItems); ?> activities</small>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if (is_super_admin()): ?>
                    <form method="post">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="activity_log_test">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-activity"></i> Test Activity Log</button>
                    </form>
                <?php endif; ?>

            <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" class="form-control" style="min-width:220px" placeholder="Search user, description, IP..." value="<?php echo e($search); ?>">
                <select name="module" class="form-select" style="min-width:170px">
                    <option value="">All Modules</option>
                    <?php foreach ($moduleOptions as $moduleOption): ?>
                        <option value="<?php echo e($moduleOption); ?>" <?php echo $moduleFilter === $moduleOption ? 'selected' : ''; ?>><?php echo e($moduleOption); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="action" class="form-select" style="min-width:190px">
                    <option value="">All Actions</option>
                    <?php foreach ($actionOptions as $actionOption): ?>
                        <option value="<?php echo e($actionOption); ?>" <?php echo $actionFilter === $actionOption ? 'selected' : ''; ?>><?php echo e($actionOption); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Filter</button>
                <?php if($search !== '' || $moduleFilter !== '' || $actionFilter !== ''): ?><a href="logs.php" class="btn btn-light">Reset</a><?php endif; ?>
            </form>
            </div>
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
                        <td><span class="badge <?php echo e(activity_action_badge_class($item['action'])); ?>"><?php echo e($item['action']); ?></span></td>
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
