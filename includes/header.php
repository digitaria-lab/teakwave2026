<?php
require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? $siteName;
$activePage = $activePage ?? '';
$extraHead = $extraHead ?? '';

// Catat satu view unik untuk pengunjung + halaman + hari.
try {
    require_once __DIR__ . '/../utakatik/config/database.php';
    require_once __DIR__ . '/page-view-tracker.php';
    teakwave_track_page_view($pdo, (string) $pageTitle);
} catch (Throwable $ignored) {
    // Statistik tidak boleh mengganggu halaman publik.
}

$homeActive = ($activePage === 'home') ? ' active' : '';
$profileActive = ($activePage === 'profile') ? ' active' : '';
$productActive = ($activePage === 'product') ? ' active' : '';
$contactActive = ($activePage === 'contact') ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="<?= htmlspecialchars(teakwave_asset_url('assets/css/style.css'), ENT_QUOTES, 'UTF-8'); ?>"
        rel="stylesheet" />
    <?= $extraHead; ?>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a aria-label="Teakwave Home" class="navbar-brand fw-bold d-flex align-items-center gap-2"
                href="index.php#home">
                <img src="./assets/img/logo-teakwave.png" />
            </a>
            <button aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"
                class="navbar-toggler custom-toggler" data-bs-target="#mainNav" data-bs-toggle="collapse" type="button">
                <span aria-hidden="true" class="hamburger-box">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link<?= $homeActive; ?>" href="index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link<?= $profileActive; ?>" href="profil.php">Tentang Kami</a>
                    </li>
                    <li class="nav-item"><a class="nav-link<?= $productActive; ?>" href="produk.php">Produk</a></li>
                </ul>
                <a class="nav-link btn-contact<?= $contactActive; ?>" href="kontak.php">Kontak</a>
            </div>
        </div>
    </nav>