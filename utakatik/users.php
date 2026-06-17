<?php
require_once 'auth/check.php';
$page_title = 'User Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'create_user';

    if ($action === 'create_user') {
        $name = sanitize_plain_text($_POST['name'] ?? '', 150);
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $role_id = clean_int($_POST['role_id'] ?? 2);
        $status = sanitize_status($_POST['status'] ?? 'active', ['active','inactive'], 'active');
        $raw_password = !empty($_POST['password']) ? $_POST['password'] : 'user123';

        if (!$email) {
            $error = 'Email tidak valid.';
        } elseif ($name === '') {
            $error = 'Nama tidak boleh kosong.';
        } elseif (!validate_password_strength($raw_password)) {
            $error = 'Password minimal 8 karakter.';
        } else {
            $password = password_hash($raw_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role_id, name, email, password, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$role_id, $name, $email, $password, $status]);

            log_activity('create', 'users', 'Menambahkan user: ' . $email);
            redirect('users.php?created=1');
        }
    }

    if ($action === 'delete_user') {
        $target_id = clean_int($_POST['id'] ?? 0);

        if ($target_id > 0 && $target_id != $_SESSION['user']['id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
            $stmt->execute([$target_id, $_SESSION['user']['id']]);

            log_activity('delete', 'users', 'Menghapus user ID: ' . $target_id);
        }

        redirect('users.php');
    }

    if ($action === 'reset_password') {
        if (!is_super_admin()) {
            http_response_code(403);
            die('Akses ditolak.');
        }

        $target_id = clean_int($_POST['id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("
            SELECT users.*, roles.slug AS role_slug, roles.name AS role_name
            FROM users
            JOIN roles ON roles.id = users.role_id
            WHERE users.id = ?
            LIMIT 1
        ");
        $stmt->execute([$target_id]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            $reset_error = 'User tidak ditemukan.';
        } elseif ((int)$targetUser['role_id'] === 1 || $targetUser['role_slug'] === 'super-admin') {
            $reset_error = 'Password Super Admin lain tidak boleh direset dari halaman ini.';
        } elseif ($target_id == $_SESSION['user']['id']) {
            $reset_error = 'Gunakan halaman Edit Profile untuk mengganti password sendiri.';
        } elseif ($new_password !== $confirm_password) {
            $reset_error = 'Konfirmasi password tidak sama.';
        } elseif (!validate_password_strength($new_password)) {
            $reset_error = 'Password baru minimal 8 karakter.';
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $target_id]);

            log_activity('reset_password', 'users', 'Super Admin reset password user: ' . $targetUser['email']);
            redirect('users.php?reset=1');
        }
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY name")->fetchAll();
$items = $pdo->query("
    SELECT users.*, roles.name AS role_name, roles.slug AS role_slug
    FROM users
    JOIN roles ON roles.id = users.role_id
    ORDER BY users.id DESC
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <?php if(!empty($reset_error)): ?><div class="alert alert-danger"><?php echo e($reset_error); ?></div><?php endif; ?>
    <?php if(!empty($_GET['created'])): ?><div class="alert alert-success">User berhasil ditambahkan.</div><?php endif; ?>
    <?php if(!empty($_GET['reset'])): ?><div class="alert alert-success">Password user berhasil direset.</div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card soft-card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Add User</h5>
                    <form method="post" autocomplete="off">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="create_user">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" class="form-control" placeholder="Default: user12345">
                            <small class="text-muted">Minimal 8 karakter. Jika kosong memakai default user123.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select">
                                <?php foreach($roles as $r): ?>
                                    <option value="<?php echo e($r['id']); ?>"><?php echo e($r['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button class="btn btn-primary w-100">Save User</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card soft-card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Users</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th style="width:230px;">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach($items as $item): ?>
                                <?php
                                    $canReset = is_super_admin()
                                        && (int)$item['id'] !== (int)$_SESSION['user']['id']
                                        && (int)$item['role_id'] !== 1
                                        && $item['role_slug'] !== 'super-admin';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($item['name']); ?></strong>
                                    </td>
                                    <td><?php echo e($item['email']); ?></td>
                                    <td><?php echo e($item['role_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo e($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if($canReset): ?>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo e($item['id']); ?>">
                                                Reset Password
                                            </button>
                                            <?php endif; ?>

                                            <?php if((int)$item['id'] !== (int)$_SESSION['user']['id']): ?>
                                            <form method="post" onsubmit="return confirm('Delete user?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>

                                        <?php if($canReset): ?>
                                        <div class="modal fade" id="resetPasswordModal<?php echo e($item['id']); ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="post" autocomplete="off">
                                                        <?php csrf_field(); ?>
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">

                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reset Password</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <p class="text-muted">
                                                                Reset password untuk user:
                                                                <strong><?php echo e($item['name']); ?></strong>
                                                                <br><?php echo e($item['email']); ?>
                                                            </p>

                                                            <div class="mb-3">
                                                                <label class="form-label">Password Baru</label>
                                                                <input name="new_password" type="password" class="form-control" required minlength="8">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Konfirmasi Password Baru</label>
                                                                <input name="confirm_password" type="password" class="form-control" required minlength="8">
                                                            </div>

                                                            <small class="text-muted">Minimal 8 karakter. User dapat mengganti lagi dari halaman Edit Profile.</small>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button class="btn btn-primary">Reset Password</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if(is_super_admin()): ?>
                        <div class="alert alert-info mb-0">
                            Super Admin dapat me-reset password user lain dengan level di bawah Super Admin.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
