<?php
require_once 'auth/check.php';
$page_title = 'Add Content';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $pdo->beginTransaction();

        $title = sanitize_plain_text($_POST['title'] ?? '', 200);
        $slug = make_slug($_POST['slug'] ?? $title);
        $type = in_array($_POST['type'] ?? 'page', ['page','section','blog'], true) ? $_POST['type'] : 'page';
        $status = in_array($_POST['status'] ?? 'draft', ['draft','published'], true) ? $_POST['status'] : 'draft';
        $body = sanitize_html_content($_POST['body'] ?? '');

        if ($title === '') {
            throw new Exception('Title tidak boleh kosong.');
        }

        $stmt = $pdo->prepare("INSERT INTO contents (title, slug, type, body, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $type, $body, $status]);

        $content_id = $pdo->lastInsertId();

        $images = upload_multiple_files('images', upload_storage_dir(), $title, 'content-image');
        foreach ($images as $index => $img) {
            $stmt = $pdo->prepare("INSERT INTO content_images (content_id, image_path, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$content_id, $img, $index]);
        }

        log_activity('create', 'contents', 'Menambahkan content: ' . $title);
        $pdo->commit();
        redirect('contents.php');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

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
                    <h5 class="fw-bold mb-0">Add New Content</h5>
                    <div class="d-flex gap-2"><button type="submit" form="contentForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button><a href="contents.php" class="btn btn-light btn-sm">Back to List</a></div>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="contentForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Title</label>
                                    <input name="title" class="form-control" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Slug</label>
                                    <input name="slug" class="form-control" placeholder="tentang-kami">
                                    <small class="text-muted">Kosongkan untuk auto.</small>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="page">page</option>
                                        <option value="section">section</option>
                                        <option value="blog">blog</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Body</label>
                                    <textarea name="body" rows="8" class="form-control wysiwyg"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="product-side-panel">
                                <label class="form-label">Content Images</label>
                                <input name="images[]" type="file" multiple class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="text-muted d-block mt-2">
                                    Bisa upload lebih dari 1 gambar. Format JPG, PNG, GIF, WEBP. Maks 5MB per file.
                                </small>

                                <div class="alert alert-info mt-3 mb-0 small">
                                    Gambar akan tersimpan sebagai galeri konten dan bisa dikelola di halaman Edit Content.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Content</button>
                        <a href="contents.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
