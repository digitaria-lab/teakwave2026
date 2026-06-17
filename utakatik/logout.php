<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

secure_session_start();
send_security_headers();

$wasLoggedIn = !empty($_SESSION['user']);

if ($wasLoggedIn) {
    log_activity('logout', 'auth', 'User logout.');
}

// Hapus semua data session.
$_SESSION = [];

// Hapus cookie session di beberapa kemungkinan path agar tidak tersisa.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $sessionName = session_name();

    $paths = array_unique([
        $params['path'] ?? '/',
        '/',
        rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') ?: '/'
    ]);

    foreach ($paths as $path) {
        setcookie(
            $sessionName,
            '',
            [
                'expires' => time() - 42000,
                'path' => $path,
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? true,
                'samesite' => 'Lax'
            ]
        );
    }
}

session_destroy();

// header('Location: ' . app_url('login.php?logged_out=1'));
header('Location: ' . app_url('login.php'));
exit;
?>
