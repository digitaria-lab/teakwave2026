<?php
require_once 'auth/check.php';
$page_title = 'Add Video';

$form = [
    'title' => '',
    'url' => '',
    'published_at' => video_datetime_input_value(),
    'tag' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('videos');

    $form['title'] = sanitize_plain_text($_POST['title'] ?? '', 200);
    $form['url'] = trim((string) ($_POST['url'] ?? ''));
    $form['published_at'] = trim((string) ($_POST['published_at'] ?? ''));
    $form['tag'] = sanitize_video_tags($_POST['tag'] ?? '');
    $newThumbnail = null;
    $youtubeId = null;
    $canonicalUrl = null;
    $publishedAt = null;

    try {
        if ($form['title'] === '') {
            throw new Exception('Judul tidak boleh kosong.');
        }

        $publishedAt = normalize_video_published_at($form['published_at']);
        $youtubeId = youtube_video_id($form['url']);
        $canonicalUrl = normalize_youtube_url($form['url']);

        if (!$youtubeId || !$canonicalUrl) {
            throw new Exception('URL harus berupa URL video YouTube yang valid, misalnya youtube.com/watch?v=... atau youtu.be/...');
        }

        $duplicate = $pdo->prepare("SELECT id FROM videos WHERE youtube_id = ? LIMIT 1");
        $duplicate->execute([$youtubeId]);

        if ($duplicate->fetchColumn()) {
            throw new Exception('Video YouTube tersebut sudah ada di dalam daftar.');
        }

        $newThumbnail = upload_single_image('thumbnail', upload_storage_dir(), $form['title'], 'video-thumbnail');

        $stmt = $pdo->prepare("INSERT INTO videos (title, url, youtube_id, thumbnail, tag, published_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $form['title'],
            $canonicalUrl,
            $youtubeId,
            $newThumbnail,
            $form['tag'] !== '' ? $form['tag'] : null,
            $publishedAt
        ]);

        $videoId = (int) $pdo->lastInsertId();
        log_activity(
            'video_create',
            'videos',
            'Menambahkan video ID ' . $videoId
            . ': ' . $form['title']
            . '; YouTube ID: ' . $youtubeId
            . '; tanggal posting: ' . $publishedAt
            . '; thumbnail: ' . ($newThumbnail ? 'khusus' : 'otomatis YouTube')
            . '; tag: ' . ($form['tag'] !== '' ? $form['tag'] : 'tanpa tag') . '.'
        );
        redirect('videos.php');
    } catch (Throwable $e) {
        if ($newThumbnail) {
            delete_local_upload($newThumbnail);
        }

        log_activity(
            'video_create_failed',
            'videos',
            'Gagal menambahkan video'
            . ($form['title'] !== '' ? ': ' . $form['title'] : '')
            . ($youtubeId ? '; YouTube ID: ' . $youtubeId : '')
            . '. Alasan: ' . sanitize_plain_text($e->getMessage(), 1000)
        );
        $error = $e->getMessage();
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
                        <h5 class="fw-bold mb-1">Add New Video</h5>
                        <small class="text-muted">Tambahkan URL YouTube dan informasi video.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" form="videoForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button>
                        <a href="videos.php" class="btn btn-light btn-sm">Back to List</a>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="videoForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="mb-3">
                                <label class="form-label">Judul <span class="text-danger">*</span></label>
                                <input name="title" class="form-control" maxlength="200" required value="<?php echo e($form['title']); ?>" placeholder="Contoh: Tutorial Instalasi Produk">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL YouTube <span class="text-danger">*</span></label>
                                <input id="youtubeUrl" name="url" type="url" class="form-control" maxlength="500" required value="<?php echo e($form['url']); ?>" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx">
                                <small class="text-muted">Mendukung URL youtube.com, youtu.be, Shorts, Live, dan Embed.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal &amp; Waktu Posting <span class="text-danger">*</span></label>
                                <input name="published_at" type="datetime-local" class="form-control" required value="<?php echo e(video_datetime_input_value($form['published_at'])); ?>">
                                <small class="text-muted">Zona waktu: <?php echo e(video_timezone_name()); ?>.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tag</label>
                                <input name="tag" class="form-control" maxlength="500" value="<?php echo e($form['tag']); ?>" placeholder="tutorial, produk, jaringan">
                                <small class="text-muted">Pisahkan beberapa tag menggunakan koma. Maksimal 20 tag.</small>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="product-side-panel h-100">
                                <label class="form-label">Thumbnail</label>
                                <div class="video-form-preview mb-3">
                                    <img id="youtubePreview" src="" alt="Preview thumbnail" hidden>
                                    <div id="youtubePreviewPlaceholder" class="video-preview-placeholder">
                                        <i class="bi bi-youtube"></i>
                                        <span>Preview thumbnail YouTube</span>
                                    </div>
                                </div>
                                <input name="thumbnail" type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="text-muted d-block mt-2">
                                    Opsional. Jika tidak diunggah, sistem memakai thumbnail YouTube otomatis. Maksimal <?php echo e(get_upload_max_filesize_mb()); ?>MB.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Video</button>
                        <a href="videos.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('youtubeUrl');
    const image = document.getElementById('youtubePreview');
    const placeholder = document.getElementById('youtubePreviewPlaceholder');

    function getYouTubeId(value) {
        try {
            const url = new URL(value);
            const host = url.hostname.replace(/^www\./, '');
            if (host === 'youtu.be') return url.pathname.split('/').filter(Boolean)[0] || '';
            if (!['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'].includes(host)) return '';
            if (url.pathname === '/watch') return url.searchParams.get('v') || '';
            const match = url.pathname.match(/^\/(?:embed|shorts|live|v)\/([A-Za-z0-9_-]{11})/);
            return match ? match[1] : '';
        } catch (error) {
            return '';
        }
    }

    function refreshPreview() {
        const id = getYouTubeId(input.value.trim());
        if (/^[A-Za-z0-9_-]{11}$/.test(id)) {
            image.src = 'https://i.ytimg.com/vi/' + encodeURIComponent(id) + '/hqdefault.jpg';
            image.hidden = false;
            placeholder.hidden = true;
        } else {
            image.removeAttribute('src');
            image.hidden = true;
            placeholder.hidden = false;
        }
    }

    input.addEventListener('input', refreshPreview);
    refreshPreview();
})();
</script>

</main>
<?php include 'includes/footer.php'; ?>
