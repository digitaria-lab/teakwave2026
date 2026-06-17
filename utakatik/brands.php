<?php
require_once 'auth/check.php';
$page_title = 'Brand Management';

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $slug = make_slug($name);
    $logo = upload_file('logo') ?: ($_POST['old_logo'] ?? null);

    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE brands SET name=?, slug=?, logo=?, description=?, status=? WHERE id=?");
        $stmt->execute([$name, $slug, $logo, $_POST['description'], $_POST['status'], $_POST['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO brands (name, slug, logo, description, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $logo, $_POST['description'], $_POST['status']]);
    }
    log_activity('save', 'brands', 'Menyimpan data brands.');
    redirect('brands.php');
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_activity('delete', 'brands', 'Menghapus data ID: ' . ($_GET['delete'] ?? ''));
    redirect('brands.php');
}

$items = $pdo->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll();
include 'includes/header.php'; include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>
<div class="row g-4">
<div class="col-lg-4"><div class="card soft-card"><div class="card-body">
<h5 class="fw-bold mb-3"><?php echo $edit ? 'Edit Brand' : 'Add Brand'; ?></h5>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo e($edit['id'] ?? ''); ?>">
<input type="hidden" name="old_logo" value="<?php echo e($edit['logo'] ?? ''); ?>">
<div class="mb-3"><label class="form-label">Brand Name</label><input name="name" class="form-control" required value="<?php echo e($edit['name'] ?? ''); ?>"></div>
<div class="mb-3"><label class="form-label">Logo</label><input name="logo" type="file" class="form-control"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"><?php echo e($edit['description'] ?? ''); ?></textarea></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?php echo (($edit['status'] ?? '')==='active')?'selected':''; ?>>Active</option><option value="inactive" <?php echo (($edit['status'] ?? '')==='inactive')?'selected':''; ?>>Inactive</option></select></div>
<button class="btn btn-primary w-100"><?php echo $edit ? 'Update Brand' : 'Save Brand'; ?></button>
<?php if($edit): ?><a href="brands.php" class="btn btn-light w-100 mt-2">Cancel</a><?php endif; ?>
</form>
</div></div></div>
<div class="col-lg-8"><div class="card soft-card"><div class="card-body">
<h5 class="fw-bold mb-3">Brands</h5>
<table class="table align-middle"><thead><tr><th>Logo</th><th>Name</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr>
<td><?php if($item['logo']): ?><img src="<?php echo e($item['logo']); ?>" style="width:46px;height:46px;object-fit:cover;border-radius:10px"><?php else: ?><span class="badge bg-light text-dark">No Logo</span><?php endif; ?></td>
<td><strong><?php echo e($item['name']); ?></strong><br><small><?php echo e($item['slug']); ?></small></td>
<td><span class="badge bg-<?php echo $item['status']==='active'?'success':'secondary'; ?>"><?php echo e($item['status']); ?></span></td>
<td><a href="?edit=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a> <a href="?delete=<?php echo e($item['id']); ?>" onclick="return confirm('Delete brand?')" class="btn btn-sm btn-outline-danger">Delete</a></td>
</tr><?php endforeach; ?>
</tbody></table>
</div></div></div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
