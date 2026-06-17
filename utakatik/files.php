<?php
require_once 'auth/check.php';
$page_title = 'File / Image Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = upload_file('file');
    if ($file) {
        $stmt = $pdo->prepare("INSERT INTO media_files (title, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['title'], basename($file), $file, mime_content_type($file), filesize($file)]);
        log_activity('upload', 'files', 'Upload file: ' . basename($file));
    }
    redirect('files.php');
}
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM media_files WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_activity('delete', 'files', 'Menghapus data ID: ' . ($_GET['delete'] ?? ''));
    redirect('files.php');
}
$items = $pdo->query("SELECT * FROM media_files ORDER BY id DESC")->fetchAll();
include 'includes/header.php'; include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>
<div class="card soft-card mb-4"><div class="card-body">
<form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
<div class="col-md-4"><label class="form-label">Title</label><input name="title" class="form-control"></div>
<div class="col-md-5"><label class="form-label">File / Image</label><input name="file" type="file" class="form-control" required></div>
<div class="col-md-3"><button class="btn btn-primary w-100">Upload File</button></div>
</form>
</div></div>
<div class="row g-3">
<?php foreach($items as $item): ?>
<div class="col-md-3"><div class="media-card"><div class="media-preview"><?php if(str_starts_with($item['file_type'], 'image/')): ?><img src="<?php echo e($item['file_path']); ?>"><?php else: ?><i class="bi bi-file-earmark"></i><?php endif; ?></div><h6><?php echo e($item['title'] ?: $item['file_name']); ?></h6><small><?php echo e($item['file_type']); ?></small><a href="?delete=<?php echo e($item['id']); ?>" onclick="return confirm('Delete file?')" class="btn btn-sm btn-outline-danger w-100 mt-2">Delete</a></div></div>
<?php endforeach; ?>
</div>
</main>
<?php include 'includes/footer.php'; ?>
