<?php
require_once 'config/database.php';

$email = 'admin@digitaria.id';
$password = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ?, status = 'active' WHERE email = ?");
$stmt->execute([$password, $email]);

echo "Password admin berhasil direset.<br>";
echo "Email: admin@digitaria.id<br>";
echo "Password: admin123<br>";
echo "<strong>Hapus file reset-admin-password.php setelah digunakan.</strong>";
?>
