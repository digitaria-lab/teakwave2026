<?php
$host = 'localhost';
$dbname = 'nzcglkgn_teakwave';
$username = 'nzcglkgn_teakwave';
$password = 'Angakhoo1986';

if ($dbname === '') {
    throw new RuntimeException('Database belum dikonfigurasi. Isi DB_NAME atau variabel $dbname.');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
} catch (PDOException $e) {
    throw new RuntimeException('Koneksi database gagal.', 0, $e);
}
?>
