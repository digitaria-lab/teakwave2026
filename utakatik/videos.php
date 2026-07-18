<?php
require_once 'auth/check.php';
$page_title = 'List Video';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_video') {
    verify_csrf('videos');

    $videoId = clean_int($_POST['id'] ?? 0);
    $returnView = ($_POST['view'] ?? '') === 'table' ? 'table' : 'card';

    if (!has_permission('videos-edit')) {
        log_activity('video_delete_denied', 'videos', 'Mencoba menghapus video tanpa izin; ID: ' . $videoId . '.');
        http_response_code(403);
        include 'includes/no-access.php';
        exit;
    }

    try {
        if ($videoId <= 0) {
            throw new RuntimeException('ID video tidak valid.');
        }

        $stmt = $pdo->prepare("SELECT title, youtube_id, thumbnail, published_at FROM videos WHERE id = ? LIMIT 1");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();

        if (!$video) {
            throw new RuntimeException('Data video tidak ditemukan.');
        }

        $delete = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        $delete->execute([$videoId]);

        if ($delete->rowCount() !== 1) {
            throw new RuntimeException('Data video tidak berhasil dihapus dari database.');
        }

        $thumbnailResult = 'tidak menggunakan thumbnail khusus';
        if (!empty($video['thumbnail'])) {
            $thumbnailResult = delete_local_upload($video['thumbnail'])
                ? 'file thumbnail khusus ikut dihapus'
                : 'data terhapus, tetapi file thumbnail khusus tidak ditemukan/gagal dihapus';
        }

        log_activity(
            'video_delete',
            'videos',
            'Menghapus video ID ' . $videoId
            . ': ' . $video['title']
            . '; YouTube ID: ' . ($video['youtube_id'] ?? '-')
            . '; tanggal posting: ' . ($video['published_at'] ?? '-')
            . '; ' . $thumbnailResult . '.'
        );
    } catch (Throwable $e) {
        log_activity(
            'video_delete_failed',
            'videos',
            'Gagal menghapus video ID ' . $videoId . '. Alasan: ' . sanitize_plain_text($e->getMessage(), 1000)
        );
    }

    redirect('videos.php?view=' . urlencode($returnView));
}

$where = '';
$params = [];

if (!empty($_GET['q'])) {
    $where = " WHERE (title LIKE ? OR url LIKE ? OR tag LIKE ?)";
    $keyword = '%' . trim($_GET['q']) . '%';
    $params = [$keyword, $keyword, $keyword];
}

$limit = 12;
$page = max(1, clean_int($_GET['page'] ?? 1, 1));
$offset = ($page - 1) * $limit;
$viewSource = $_GET['view'] ?? ($_COOKIE['video_view_mode'] ?? 'card');
$view = $viewSource === 'table' ? 'table' : 'card';

if (isset($_GET['view'])) {
    setcookie('video_view_mode', $view, time() + (86400 * 30), '/');
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM videos $where");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $limit));

$stmt = $pdo->prepare("SELECT * FROM videos $where ORDER BY published_at DESC, id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$items = $stmt->fetchAll();

function video_page_url($pageNumber) {
    $query = $_GET;
    $query['page'] = $pageNumber;
    return 'videos.php?' . http_build_query($query);
}

function video_view_url($viewMode) {
    $query = $_GET;
    $query['view'] = $viewMode;
    $query['page'] = 1;
    return 'videos.php?' . http_build_query($query);
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
                <h5 class="fw-bold mb-0">Video List</h5>
                <small class="text-muted">Menampilkan <?php echo count($items); ?> dari <?php echo e($totalItems); ?> video</small>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="btn-group view-toggle" role="group" aria-label="Video view">
                    <a href="<?php echo e(video_view_url('card')); ?>" class="btn btn-sm <?php echo $view === 'card' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Card
                    </a>
                    <a href="<?php echo e(video_view_url('table')); ?>" class="btn btn-sm <?php echo $view === 'table' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-table"></i> Table
                    </a>
                </div>

                <form class="d-flex gap-2 content-search-form" method="get">
                    <input type="hidden" name="view" value="<?php echo e($view); ?>">
                    <input type="text" name="q" class="form-control" placeholder="Cari judul, URL, atau tag..." value="<?php echo e($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-primary">Search</button>
                    <?php if (!empty($_GET['q'])): ?>
                        <a href="videos.php?view=<?php echo e($view); ?>" class="btn btn-light">Reset</a>
                    <?php endif; ?>
                </form>

                <?php if (has_permission('videos-add')): ?>
                    <a href="video-add.php" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Add Video</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($view === 'card'): ?>
            <div class="video-grid">
                <?php foreach ($items as $item): ?>
                    <article class="video-card-admin">
                        <a class="video-card-thumb" href="<?php echo e($item['url']); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo e(video_thumbnail_src($item)); ?>" alt="Thumbnail <?php echo e($item['title']); ?>" loading="lazy">
                            <span class="video-play-icon"><i class="bi bi-play-fill"></i></span>
                        </a>

                        <div class="video-card-body">
                            <h6 title="<?php echo e($item['title']); ?>"><?php echo e($item['title']); ?></h6>
                            <small class="text-muted d-block text-truncate mb-1"><?php echo e($item['url']); ?></small>
                            <small class="text-muted d-block mb-2"><i class="bi bi-calendar-event"></i> <?php echo e(format_video_published_at($item['published_at'] ?? $item['created_at'] ?? '')); ?></small>

                            <div class="video-tags mb-3">
                                <?php foreach (video_tags_array($item['tag']) as $tag): ?>
                                    <span class="badge bg-light text-dark">#<?php echo e($tag); ?></span>
                                <?php endforeach; ?>
                                <?php if (empty(video_tags_array($item['tag']))): ?>
                                    <span class="text-muted small">Tanpa tag</span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <?php if (has_permission('videos-edit')): ?>
                                    <a href="video-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                                    <form method="post" class="flex-fill" onsubmit="return confirm('Hapus video ini?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_video">
                                        <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                        <input type="hidden" name="view" value="<?php echo e($view); ?>">
                                        <button class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle admin-datatable">
                    <thead>
                        <tr>
                            <th style="width:120px">Thumbnail</th>
                            <th>Judul</th>
                            <th>URL YouTube</th>
                            <th style="width:170px">Tanggal/Waktu Posting</th>
                            <th>Tag</th>
                            <th style="width:175px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><img class="video-table-thumb" src="<?php echo e(video_thumbnail_src($item)); ?>" alt=""></td>
                                <td><strong><?php echo e($item['title']); ?></strong></td>
                                <td><a href="<?php echo e($item['url']); ?>" target="_blank" rel="noopener noreferrer" class="text-break"><?php echo e($item['url']); ?></a></td>
                                <td><?php echo e(format_video_published_at($item['published_at'] ?? $item['created_at'] ?? '')); ?></td>
                                <td>
                                    <div class="video-tags">
                                        <?php foreach (video_tags_array($item['tag']) as $tag): ?>
                                            <span class="badge bg-light text-dark">#<?php echo e($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (has_permission('videos-edit')): ?>
                                        <div class="d-flex gap-2">
                                            <a href="video-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus video ini?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_video">
                                                <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                                <input type="hidden" name="view" value="<?php echo e($view); ?>">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!$items): ?>
            <div class="alert alert-warning mb-0">Video tidak ditemukan.</div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center flex-wrap">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo e(video_page_url($page - 1)); ?>">Previous</a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo e(video_page_url($i)); ?>"><?php echo e($i); ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo e(video_page_url($page + 1)); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
