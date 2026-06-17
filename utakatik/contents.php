<?php
require_once 'auth/check.php';
$page_title = 'List Content';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contents WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_activity('delete', 'contents', 'Menghapus content ID: ' . ($_GET['delete'] ?? ''));
    redirect('contents.php');
}

$where = "";
$params = [];

if (!empty($_GET['q'])) {
    $where = " WHERE (title LIKE ? OR type LIKE ? OR status LIKE ?)";
    $keyword = '%' . trim($_GET['q']) . '%';
    $params = [$keyword, $keyword, $keyword];
}

$limit = 12;
$page = max(1, clean_int($_GET['page'] ?? 1, 1));
$offset = ($page - 1) * $limit;
$viewSource = $_GET['view'] ?? ($_COOKIE['content_view_mode'] ?? 'card');
$view = $viewSource === 'table' ? 'table' : 'card';

if (isset($_GET['view'])) {
    setcookie('content_view_mode', $view, time() + (86400 * 30), '/');
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contents $where");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $limit));

$stmt = $pdo->prepare("
    SELECT contents.*,
           (SELECT COUNT(*) FROM content_images WHERE content_images.content_id = contents.id) AS image_count
    FROM contents
    $where
    ORDER BY id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$items = $stmt->fetchAll();

function content_page_url($page) {
    $query = $_GET;
    $query['page'] = $page;
    return 'contents.php?' . http_build_query($query);
}

function content_view_url($view) {
    $query = $_GET;
    $query['view'] = $view;
    $query['page'] = 1;
    return 'contents.php?' . http_build_query($query);
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
                <h5 class="fw-bold mb-0">Content List</h5>
                <small class="text-muted">Showing <?php echo count($items); ?> of <?php echo e($totalItems); ?> contents</small>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group view-toggle" role="group" aria-label="Content view">
                    <a href="<?php echo e(content_view_url('card')); ?>" class="btn btn-sm <?php echo $view === 'card' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Card
                    </a>
                    <a href="<?php echo e(content_view_url('table')); ?>" class="btn btn-sm <?php echo $view === 'table' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-table"></i> Table
                    </a>
                </div>

                <form class="d-flex gap-2 content-search-form" method="get">
                    <input type="hidden" name="view" value="<?php echo e($view); ?>">
                    <input type="text" name="q" class="form-control" placeholder="Search title, type, status..." value="<?php echo e($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-primary">Search</button>
                    <?php if(!empty($_GET['q'])): ?><a href="contents.php?view=<?php echo e($view); ?>" class="btn btn-light">Reset</a><?php endif; ?>
                </form>

                <?php if(has_permission('contents-add')): ?>
                <a href="content-add.php" class="btn btn-warning">
                    <i class="bi bi-plus-circle"></i> Add Content
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($view === 'card'): ?>
            <div class="content-grid-4">
                <?php foreach($items as $item): ?>
                <div class="content-card-compact">
                    <div class="content-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="product-card-body">
                        <h6><?php echo e($item['title']); ?></h6>
                        <p><?php echo e($item['type']); ?> • <?php echo e($item['status']); ?></p>
                        <small class="text-muted"><?php echo e($item['slug']); ?> • Images: <?php echo e($item['image_count'] ?? 0); ?></small>

                        <div class="content-excerpt">
                            <?php echo e(wp_trim_words(strip_tags($item['body']), 16, '...')); ?>
                        </div>

                        <div class="mt-2 d-flex gap-2">
                            <?php if(has_permission('contents-edit')): ?>
                            <a href="content-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo e($item['id']); ?>&view=<?php echo e($view); ?>" onclick="return confirm('Delete content?')" class="btn btn-sm btn-outline-danger flex-fill">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if(!$items): ?>
                    <div class="alert alert-warning mb-0">Konten tidak ditemukan.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle content-table-view admin-datatable">
                    <thead>
                        <tr>
                            <th style="width:70px;">Icon</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Images</th>
                            <th>Excerpt</th>
                            <th style="width:170px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td>
                                <div class="table-content-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            </td>
                            <td><strong><?php echo e($item['title']); ?></strong></td>
                            <td><small class="text-muted"><?php echo e($item['slug']); ?></small></td>
                            <td><span class="badge bg-light text-dark"><?php echo e($item['type']); ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $item['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                    <?php echo e($item['status']); ?>
                                </span>
                            </td>
                            <td><?php echo e($item['image_count'] ?? 0); ?></td>
                            <td><?php echo e(wp_trim_words(strip_tags($item['body']), 12, '...')); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if(has_permission('contents-edit')): ?>
                                    <a href="content-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo e($item['id']); ?>&view=<?php echo e($view); ?>" onclick="return confirm('Delete content?')" class="btn btn-sm btn-outline-danger">Delete</a>
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
                    <a class="page-link" href="<?php echo e(content_page_url($page - 1)); ?>">Previous</a>
                </li>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php if($startPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo e(content_page_url(1)); ?>">1</a></li>
                    <?php if($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo e(content_page_url($i)); ?>"><?php echo e($i); ?></a>
                    </li>
                <?php endfor; ?>

                <?php if($endPage < $totalPages): ?>
                    <?php if($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?php echo e(content_page_url($totalPages)); ?>"><?php echo e($totalPages); ?></a></li>
                <?php endif; ?>

                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo e(content_page_url($page + 1)); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
