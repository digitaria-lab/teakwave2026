<?php
require_once 'auth/check.php';
$page_title = 'Edit Profile';

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT users.*, roles.name AS role_name FROM users JOIN roles ON roles.id = users.role_id WHERE users.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name = sanitize_plain_text($_POST['name'] ?? '', 150);
        $avatar = upload_image_only('avatar') ?: ($user['avatar'] ?? null);

        if ($name === '') {
            $error = 'Nama tidak boleh kosong.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$name, $avatar, $user_id]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['avatar'] = $avatar;

            log_activity('update', 'profile', 'Mengubah profil user.');
            redirect('profile.php?updated=profile');
        }
    }

    if ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $user['password'])) {
            $password_error = 'Password lama tidak sesuai.';
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'Konfirmasi password baru tidak sama.';
        } elseif (!validate_password_strength($new_password)) {
            $password_error = 'Password baru minimal 8 karakter.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);

            log_activity('update_password', 'profile', 'Mengubah password sendiri.');
            redirect('profile.php?updated=password');
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Edit Profile</h5>

                <?php if(($_GET['updated'] ?? '') === 'profile'): ?>
                    <div class="alert alert-success">Profil berhasil diperbarui.</div>
                <?php endif; ?>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="profile-edit-preview mb-4">
                        <div class="profile-photo-large">
                            <?php if(!empty($user['avatar'])): ?>
                                <img src="<?php echo e($user['avatar']); ?>" alt="<?php echo e($user['name']); ?>">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h6 class="fw-bold mb-1"><?php echo e($user['name']); ?></h6>
                            <p class="text-muted mb-0"><?php echo e($user['email']); ?></p>
                            <small class="badge bg-primary mt-2"><?php echo e($user['role_name']); ?></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input name="name" class="form-control" required value="<?php echo e($user['name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Photo Profil</label>
                        <input name="avatar" type="file" class="form-control upload-validate-image" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <small class="text-muted upload-help-text">Format mengikuti Website Settings. Khusus profil hanya gambar yang diperbolehkan.</small>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                        <a href="index.php" class="btn btn-light">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Edit Password</h5>

                <?php if(($_GET['updated'] ?? '') === 'password'): ?>
                    <div class="alert alert-success">Password berhasil diperbarui.</div>
                <?php endif; ?>

                <?php if(!empty($password_error)): ?>
                    <div class="alert alert-danger"><?php echo e($password_error); ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_password">

                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input name="current_password" type="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input name="new_password" type="password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimal 8 karakter.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input name="confirm_password" type="password" class="form-control" required minlength="8">
                    </div>

                    <button class="btn btn-primary">
                        <i class="bi bi-shield-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>
