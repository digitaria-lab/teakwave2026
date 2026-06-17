<?php
require_once 'auth/check.php';
$page_title = 'Category Management';

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $slug = make_slug($name);

    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, status=? WHERE id=?");
        $stmt->execute([$name, $slug, $_POST['description'], $_POST['status'], $_POST['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $_POST['description'], $_POST['status']]);
    }
    log_activity('save', 'categories', 'Menyimpan data categories.');
    redirect('categories.php');
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_activity('delete', 'categories', 'Menghapus data ID: ' . ($_GET['delete'] ?? ''));
    redirect('categories.php');
}

$items = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
include 'includes/header.php'; include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>
<div class="row g-4">
<div class="col-lg-4"><div class="card soft-card"><div class="card-body">
<h5 class="fw-bold mb-3"><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></h5>
<form method="post">
<input type="hidden" name="id" value="<?php echo e($edit['id'] ?? ''); ?>">
<div class="mb-3"><label class="form-label">Category Name</label><input name="name" class="form-control" required value="<?php echo e($edit['name'] ?? ''); ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"><?php echo e($edit['description'] ?? ''); ?></textarea></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?php echo (($edit['status'] ?? '')==='active')?'selected':''; ?>>Active</option><option value="inactive" <?php echo (($edit['status'] ?? '')==='inactive')?'selected':''; ?>>Inactive</option></select></div>
<button class="btn btn-primary w-100"><?php echo $edit ? 'Update Category' : 'Save Category'; ?></button>
<?php if($edit): ?><a href="categories.php" class="btn btn-light w-100 mt-2">Cancel</a><?php endif; ?>
</form>
</div></div></div>
<div class="col-lg-8"><div class="card soft-card"><div class="card-body">
<h5 class="fw-bold mb-3">Categories</h5>
<table class="table align-middle"><thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><?php echo e($item['name']); ?></td><td><?php echo e($item['slug']); ?></td><td><span class="badge bg-<?php echo $item['status']==='active'?'success':'secondary'; ?>"><?php echo e($item['status']); ?></span></td><td><a href="?edit=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a> <a href="?delete=<?php echo e($item['id']); ?>" onclick="return confirm('Delete category?')" class="btn btn-sm btn-outline-danger">Delete</a></td></tr><?php endforeach; ?>
</tbody></table>
</div></div></div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
