<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

secure_session_start();
send_security_headers();

$error = '';

if (isset($_GET['logged_out'])) {
    // Jangan expire cookie session di halaman login karena token CSRF baru perlu disimpan.
    // Cookie lama sudah dibersihkan oleh logout.php.
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} elseif (is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT users.*, roles.name AS role_name
        FROM users
        JOIN roles ON roles.id = users.role_id
        WHERE users.email = ? AND users.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name'],
            'avatar' => $user['avatar'] ?? null
        ];

        log_activity('login', 'auth', 'User login berhasil.');
        redirect('index.php');
    } else {
        $error = 'Email atau password salah.';
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - Digitaria Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-card card border-0 shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3">D</div>
                <h3 class="fw-bold mb-1">Digitaria Admin</h3>
                <p class="text-muted mb-0">Masuk untuk mengelola dashboard</p>
            </div>
            <?php if(!empty($_GET['logged_out'])): ?>
                <div class="alert alert-success">Berhasil logout.</div>
            <?php endif; ?>

            <?php if($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <form method="post">
                <?php csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control form-control-lg" value="admin@digitaria.id" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" value="admin123" required>
                </div>
                <button class="btn btn-primary w-100 btn-lg">Login</button>
            </form>
            <div class="small text-muted mt-4 text-center">Demo: admin@digitaria.id / admin123</div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('input[type="password"]').forEach(function(input){
        const wrapper = document.createElement('div');
        wrapper.className = 'password-field-wrap';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.setAttribute('aria-label', 'Lihat password');
        btn.innerHTML = '<i class="bi bi-eye"></i>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', function(){
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            btn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
        });
    });
});
</script>
</body>
</html>
