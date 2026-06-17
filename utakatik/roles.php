<?php
require_once 'auth/check.php';
$page_title = 'User Level Management';

$availablePages = dashboard_pages();

$edit = null;
$editPermissions = [];

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();

    if ($edit) {
        $stmt = $pdo->prepare("SELECT page_key FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$edit['id']]);
        $editPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = make_slug($name);
    $permissions = $_POST['permissions'] ?? [];

    if (!empty($_POST['id'])) {
        $role_id = $_POST['id'];

        $stmt = $pdo->prepare("UPDATE roles SET name=?, slug=? WHERE id=?");
        $stmt->execute([$name, $slug, $role_id]);
        log_activity('update', 'roles', 'Mengubah user level: ' . $name);

        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?");
        $stmt->execute([$role_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO roles (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        $role_id = $pdo->lastInsertId();
        log_activity('create', 'roles', 'Menambahkan user level: ' . $name);
    }

    foreach ($permissions as $page_key) {
        if (array_key_exists($page_key, $availablePages)) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, page_key) VALUES (?, ?)");
            $stmt->execute([$role_id, $page_key]);
        }
    }

    redirect('roles.php');
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_activity('delete', 'roles', 'Menghapus user level ID: ' . ($_GET['delete'] ?? ''));
    redirect('roles.php');
}

$items = $pdo->query("
    SELECT roles.*,
    COUNT(role_permissions.id) AS total_permissions
    FROM roles
    LEFT JOIN role_permissions ON role_permissions.role_id = roles.id
    GROUP BY roles.id
    ORDER BY roles.id DESC
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?php echo $edit ? 'Edit User Level' : 'Add User Level'; ?></h5>

                <form method="post">
                    <input type="hidden" name="id" value="<?php echo e($edit['id'] ?? ''); ?>">

                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input name="name" class="form-control" required value="<?php echo e($edit['name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Page Access</label>
                        <div class="permission-box">
                            <?php foreach($availablePages as $key => $label): ?>
                            <label class="permission-item">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="<?php echo e($key); ?>"
                                    <?php echo in_array($key, $editPermissions) ? 'checked' : ''; ?>
                                >
                                <span><?php echo e($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Centang halaman yang boleh diakses oleh user level ini.</small>
                    </div>

                    <button class="btn btn-primary w-100">
                        <?php echo $edit ? 'Update Role & Access' : 'Save Role & Access'; ?>
                    </button>

                    <?php if($edit): ?>
                        <a href="roles.php" class="btn btn-light w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">User Levels</h5>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Access</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo e($item['name']); ?></td>
                                <td><?php echo e($item['slug']); ?></td>
                                <td><span class="badge bg-primary"><?php echo e($item['total_permissions']); ?> pages</span></td>
                                <td>
                                    <a href="?edit=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="?delete=<?php echo e($item['id']); ?>" onclick="return confirm('Delete role?')" class="btn btn-sm btn-outline-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mb-0">
                    Akses halaman akan langsung berlaku setelah user login ulang.
                </div>
            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
