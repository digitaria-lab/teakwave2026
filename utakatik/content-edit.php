<?php
require_once 'auth/check.php';
$page_title = 'Edit Content';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_content_image') {
    verify_csrf();

    $image_id = clean_int($_POST['image_id'] ?? 0);
    $content_id = clean_int($_POST['content_id'] ?? 0);

    if ($image_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM content_images WHERE id = ?");
        $stmt->execute([$image_id]);
        log_activity('delete_image', 'contents', 'Menghapus gambar content ID: ' . $image_id);
    }

    redirect('content-edit.php?id=' . $content_id);
}

if (empty($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $items = $pdo->query("SELECT contents.*,
        (SELECT COUNT(*) FROM content_images WHERE content_images.content_id = contents.id) AS image_count
        FROM contents
        ORDER BY id DESC")->fetchAll();

    include 'includes/header.php';
    include 'includes/sidebar.php';
    ?>
    <main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="card soft-card">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Choose Content to Edit</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Images</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?php echo e($item['title']); ?></td>
                            <td><?php echo e($item['type']); ?></td>
                            <td><?php echo e($item['status']); ?></td>
                            <td><?php echo e($item['image_count']); ?></td>
                            <td><a href="content-edit.php?id=<?php echo e($item['id']); ?>" class="btn btn-sm btn-primary">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </main>
    <?php include 'includes/footer.php'; ?>
    <?php exit;
}

$content_id = clean_int($_POST['id'] ?? ($_GET['id'] ?? 0));

$stmt = $pdo->prepare("SELECT * FROM contents WHERE id = ?");
$stmt->execute([$content_id]);
$edit = $stmt->fetch();

if (!$edit) {
    redirect('contents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_content') {
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

        $stmt = $pdo->prepare("UPDATE contents SET title=?, slug=?, type=?, body=?, status=? WHERE id=?");
        $stmt->execute([$title, $slug, $type, $body, $status, $_POST['id']]);

        $images = upload_multiple_files('images', upload_storage_dir(), $title, 'content-image');
        foreach ($images as $index => $img) {
            $stmt = $pdo->prepare("INSERT INTO content_images (content_id, image_path, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['id'], $img, $index]);
        }

        
        // Rename semua image content existing saat judul content diubah.
        $stmt = $pdo->prepare("SELECT * FROM content_images WHERE content_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$_POST['id']]);
        $existingContentImages = $stmt->fetchAll();

        foreach ($existingContentImages as $index => $imageItem) {
            $newImagePath = rename_uploaded_file_seo($imageItem['image_path'], $title, 'content-image-' . ($index + 1));

            if ($newImagePath !== $imageItem['image_path']) {
                $updateImage = $pdo->prepare("UPDATE content_images SET image_path = ? WHERE id = ?");
                $updateImage->execute([$newImagePath, $imageItem['id']]);
            }
        }

        log_activity('update', 'contents', 'Mengubah content: ' . $title);
        $pdo->commit();
        redirect('content-edit.php?id=' . intval($_POST['id']));
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM content_images WHERE content_id = ? ORDER BY sort_order ASC, id DESC");
$stmt->execute([$edit['id']]);
$contentImages = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card soft-card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Edit Content: <?php echo e($edit['title']); ?></h5>
                    <div class="d-flex gap-2"><button type="submit" form="contentForm" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save</button><a href="contents.php" class="btn btn-light btn-sm">Back to List</a></div>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="contentForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_content">
                    <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Title</label>
                                    <input name="title" class="form-control" required value="<?php echo e($edit['title']); ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Slug</label>
                                    <input name="slug" class="form-control" value="<?php echo e($edit['slug']); ?>">
                                    <small class="text-muted">Untuk frontend: tentang-kami, kontak, produk.</small>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="page" <?php echo $edit['type']==='page'?'selected':''; ?>>page</option>
                                        <option value="section" <?php echo $edit['type']==='section'?'selected':''; ?>>section</option>
                                        <option value="blog" <?php echo $edit['type']==='blog'?'selected':''; ?>>blog</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="draft" <?php echo $edit['status']==='draft'?'selected':''; ?>>Draft</option>
                                        <option value="published" <?php echo $edit['status']==='published'?'selected':''; ?>>Published</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Body</label>
                                    <textarea name="body" rows="8" class="form-control wysiwyg"><?php echo e($edit['body']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="product-side-panel">
                                <label class="form-label">Add More Content Images</label>
                                <input name="images[]" type="file" multiple class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="text-muted d-block mt-2">Upload tambahan gambar konten. Maks 5MB per file.</small>
                            </div>
                        </div>

                        <?php if($contentImages): ?>
                        <div class="col-12">
                            <label class="form-label">Current Content Images</label>
                            <div class="row g-2">
                                <?php foreach($contentImages as $img): ?>
                                <div class="col-6 col-md-3">
                                    <div class="position-relative">
                                        <img src="<?php echo e($img['image_path']); ?>" style="width:100%;height:110px;object-fit:cover;border-radius:10px">
                                        <form method="post" class="position-absolute top-0 end-0" onsubmit="return confirm('Delete image?')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_content_image">
                                            <input type="hidden" name="image_id" value="<?php echo e($img['id']); ?>">
                                            <input type="hidden" name="content_id" value="<?php echo e($edit['id']); ?>">
                                            <button class="btn btn-sm btn-danger">×</button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Content</button>
                        <a href="contents.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
