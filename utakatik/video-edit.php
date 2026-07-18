<?php
require_once 'auth/check.php';
$page_title = 'Edit Video';

if (empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $items = $pdo->query("SELECT * FROM videos ORDER BY published_at DESC, id DESC")->fetchAll();

    include 'includes/header.php';
    include 'includes/sidebar.php';
    ?>
    <main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="card soft-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Choose Video to Edit</h5>
                <?php if (has_permission('videos-add')): ?>
                    <a href="video-add.php" class="btn btn-warning btn-sm"><i class="bi bi-plus-lg"></i> Add Video</a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px">Thumbnail</th>
                            <th>Judul</th>
                            <th>URL</th>
                            <th>Tanggal/Waktu Posting</th>
                            <th>Tag</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><img class="video-table-thumb" src="<?php echo e(video_thumbnail_src($item)); ?>" alt=""></td>
                                <td><strong><?php echo e($item['title']); ?></strong></td>
                                <td class="text-break"><?php echo e($item['url']); ?></td>
                                <td><?php echo e(format_video_published_at($item['published_at'] ?? $item['created_at'] ?? '')); ?></td>
                                <td><?php echo e($item['tag']); ?></td>
                                <td><a href="video-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-primary">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$items): ?>
                <div class="alert alert-warning mb-0">Belum ada video yang dapat diedit.</div>
            <?php endif; ?>
        </div>
    </div>

    </main>
    <?php include 'includes/footer.php'; ?>
    <?php exit;
}

$videoId = clean_int($_POST['id'] ?? ($_GET['id'] ?? 0));
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ? LIMIT 1");
$stmt->execute([$videoId]);
$edit = $stmt->fetch();

if (!$edit) {
    $missingAction = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_video')
        ? 'video_update_failed'
        : 'video_open_failed';
    log_activity($missingAction, 'videos', 'Data video tidak ditemukan; ID: ' . $videoId . '.');
    redirect('videos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_video') {
    verify_csrf('videos');

    $title = sanitize_plain_text($_POST['title'] ?? '', 200);
    $submittedUrl = trim((string) ($_POST['url'] ?? ''));
    $submittedPublishedAt = trim((string) ($_POST['published_at'] ?? ''));
    $tag = sanitize_video_tags($_POST['tag'] ?? '');
    $newThumbnail = null;
    $youtubeId = null;
    $canonicalUrl = null;
    $publishedAt = null;
    $removeThumbnail = false;

    try {
        if ($title === '') {
            throw new Exception('Judul tidak boleh kosong.');
        }

        $publishedAt = normalize_video_published_at($submittedPublishedAt);
        $youtubeId = youtube_video_id($submittedUrl);
        $canonicalUrl = normalize_youtube_url($submittedUrl);

        if (!$youtubeId || !$canonicalUrl) {
            throw new Exception('URL harus berupa URL video YouTube yang valid.');
        }

        $duplicate = $pdo->prepare("SELECT id FROM videos WHERE youtube_id = ? AND id <> ? LIMIT 1");
        $duplicate->execute([$youtubeId, $videoId]);

        if ($duplicate->fetchColumn()) {
            throw new Exception('Video YouTube tersebut sudah digunakan oleh data lain.');
        }

        $newThumbnail = upload_single_image('thumbnail', upload_storage_dir(), $title, 'video-thumbnail');
        $removeThumbnail = !empty($_POST['remove_thumbnail']);
        $thumbnail = $edit['thumbnail'];

        if ($newThumbnail) {
            $thumbnail = $newThumbnail;
        } elseif ($removeThumbnail) {
            $thumbnail = null;
        }

        $update = $pdo->prepare("UPDATE videos SET title = ?, url = ?, youtube_id = ?, thumbnail = ?, tag = ?, published_at = ? WHERE id = ?");
        $update->execute([
            $title,
            $canonicalUrl,
            $youtubeId,
            $thumbnail,
            $tag !== '' ? $tag : null,
            $publishedAt,
            $videoId
        ]);

        $oldThumbnailDeleted = null;
        if (($newThumbnail || $removeThumbnail) && !empty($edit['thumbnail']) && $edit['thumbnail'] !== $thumbnail) {
            $oldThumbnailDeleted = delete_local_upload($edit['thumbnail']);
        }

        $changedFields = [];
        if ((string) $edit['title'] !== $title) $changedFields[] = 'judul';
        if ((string) $edit['url'] !== $canonicalUrl) $changedFields[] = 'URL YouTube';
        if ((string) ($edit['tag'] ?? '') !== $tag) $changedFields[] = 'tag';
        if ((string) ($edit['published_at'] ?? '') !== $publishedAt) $changedFields[] = 'tanggal posting';
        if ($newThumbnail) {
            $changedFields[] = 'thumbnail baru';
            $thumbnailActivity = 'mengunggah thumbnail khusus';
        } elseif ($removeThumbnail) {
            $changedFields[] = 'thumbnail dihapus';
            $thumbnailActivity = 'menghapus thumbnail khusus dan memakai thumbnail YouTube';
        } else {
            $thumbnailActivity = 'thumbnail tidak berubah';
        }

        log_activity(
            'video_update',
            'videos',
            'Mengubah video ID ' . $videoId
            . ': ' . $title
            . '; YouTube ID: ' . $youtubeId
            . '; bidang berubah: ' . ($changedFields ? implode(', ', $changedFields) : 'tidak ada perubahan nilai')
            . '; tanggal posting: ' . $publishedAt
            . '; ' . $thumbnailActivity
            . ($oldThumbnailDeleted === false ? '; peringatan: file thumbnail lama tidak berhasil dihapus' : '')
            . '; tag: ' . ($tag !== '' ? $tag : 'tanpa tag') . '.'
        );
        redirect('video-edit.php?id=' . $videoId . '&saved=1');
    } catch (Throwable $e) {
        if ($newThumbnail) {
            delete_local_upload($newThumbnail);
        }

        log_activity(
            'video_update_failed',
            'videos',
            'Gagal mengubah video ID ' . $videoId
            . ($title !== '' ? ': ' . $title : '')
            . ($youtubeId ? '; YouTube ID: ' . $youtubeId : '')
            . '. Alasan: ' . sanitize_plain_text($e->getMessage(), 1000)
        );
        $error = $e->getMessage();
        $edit['title'] = $title;
        $edit['url'] = $submittedUrl;
        $edit['published_at'] = $submittedPublishedAt;
        $edit['tag'] = $tag;
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card soft-card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Edit Video</h5>
                        <small class="text-muted"><?php echo e($edit['title']); ?></small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" form="videoForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button>
                        <a href="videos.php" class="btn btn-light btn-sm">Back to List</a>
                    </div>
                </div>

                <?php if (!empty($_GET['saved'])): ?>
                    <div class="alert alert-success">Perubahan video berhasil disimpan.</div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="videoForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_video">
                    <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="mb-3">
                                <label class="form-label">Judul <span class="text-danger">*</span></label>
                                <input name="title" class="form-control" maxlength="200" required value="<?php echo e($edit['title']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL YouTube <span class="text-danger">*</span></label>
                                <input name="url" type="url" class="form-control" maxlength="500" required value="<?php echo e($edit['url']); ?>">
                                <small class="text-muted">URL akan disimpan dalam format YouTube standar.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal &amp; Waktu Posting <span class="text-danger">*</span></label>
                                <input name="published_at" type="datetime-local" class="form-control" required value="<?php echo e(video_datetime_input_value($edit['published_at'] ?? $edit['created_at'] ?? null)); ?>">
                                <small class="text-muted">Zona waktu: <?php echo e(video_timezone_name()); ?>.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tag</label>
                                <input name="tag" class="form-control" maxlength="500" value="<?php echo e($edit['tag']); ?>" placeholder="tutorial, produk, jaringan">
                                <small class="text-muted">Pisahkan beberapa tag menggunakan koma.</small>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="product-side-panel h-100">
                                <label class="form-label">Thumbnail Saat Ini</label>
                                <div class="video-form-preview mb-3">
                                    <img src="<?php echo e(video_thumbnail_src($edit)); ?>" alt="Thumbnail <?php echo e($edit['title']); ?>">
                                </div>

                                <label class="form-label">Ganti Thumbnail</label>
                                <input name="thumbnail" type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="text-muted d-block mt-2">Kosongkan untuk mempertahankan thumbnail saat ini.</small>

                                <?php if (!empty($edit['thumbnail'])): ?>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="remove_thumbnail" value="1" id="removeThumbnail">
                                        <label class="form-check-label" for="removeThumbnail">Hapus thumbnail khusus dan gunakan thumbnail YouTube</label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Video</button>
                        <a href="videos.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
