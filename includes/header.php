<?php
require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? $siteName;
$activePage = $activePage ?? '';
$extraHead = $extraHead ?? '';
$metaDescription = trim((string) ($metaDescription ?? $defaultMetaDescription));
$robots = trim((string) ($robots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
$canonicalPath = $canonicalPath ?? null;
$canonicalUrl = $canonicalUrl ?? ($canonicalPath !== null ? teakwave_absolute_url($canonicalPath) : '');
$metaImage = teakwave_absolute_url($metaImage ?? $defaultSocialImage);
$metaType = $metaType ?? 'website';
$preloadImage = $preloadImage ?? '';
$structuredData = $structuredData ?? null;

// Catat view tanpa membuat halaman publik gagal ketika database belum siap.
try {
    require_once __DIR__ . '/../utakatik/config/database.php';
    require_once __DIR__ . '/page-view-tracker.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        teakwave_track_page_view($pdo, (string) $pageTitle);
    }
} catch (Throwable $ignored) {
    // Statistik bersifat non-kritis.
}

$homeActive = ($activePage === 'home') ? ' active' : '';
$profileActive = ($activePage === 'profile') ? ' active' : '';
$productActive = ($activePage === 'product') ? ' active' : '';
$contactActive = ($activePage === 'contact') ? ' active' : '';

$hasDescriptionInExtra = stripos($extraHead, 'name="description"') !== false || stripos($extraHead, "name='description'") !== false;
$hasCanonicalInExtra = stripos($extraHead, 'rel="canonical"') !== false || stripos($extraHead, "rel='canonical'") !== false;
$hasRobotsInExtra = stripos($extraHead, 'name="robots"') !== false || stripos($extraHead, "name='robots'") !== false;
$hasOgInExtra = stripos($extraHead, 'property="og:') !== false || stripos($extraHead, "property='og:") !== false;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0647d8">
    <meta name="color-scheme" content="light">
    <meta name="teakwave-base-url" content="<?= teakwave_escape($siteUrl); ?>">
    <title><?= teakwave_escape($pageTitle); ?></title>
    <?php if (!$hasDescriptionInExtra): ?><meta name="description" content="<?= teakwave_escape($metaDescription); ?>"><?php endif; ?>
    <?php if (!$hasRobotsInExtra): ?><meta name="robots" content="<?= teakwave_escape($robots); ?>"><?php endif; ?>
    <?php if ($canonicalUrl !== '' && !$hasCanonicalInExtra): ?><link rel="canonical" href="<?= teakwave_escape($canonicalUrl); ?>"><?php endif; ?>
    <?php if (!$hasOgInExtra): ?>
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="<?= teakwave_escape($metaType); ?>">
    <meta property="og:site_name" content="Teakwave">
    <meta property="og:title" content="<?= teakwave_escape($pageTitle); ?>">
    <meta property="og:description" content="<?= teakwave_escape($metaDescription); ?>">
    <?php if ($canonicalUrl !== ''): ?><meta property="og:url" content="<?= teakwave_escape($canonicalUrl); ?>"><?php endif; ?>
    <meta property="og:image" content="<?= teakwave_escape($metaImage); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= teakwave_escape($pageTitle); ?>">
    <meta name="twitter:description" content="<?= teakwave_escape($metaDescription); ?>">
    <meta name="twitter:image" content="<?= teakwave_escape($metaImage); ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= teakwave_escape(teakwave_asset_url('uploads/favicon_6a0621935cf2e5.50652961.png')); ?>" type="image/png">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <?php if ($preloadImage !== ''): ?><link rel="preload" as="image" href="<?= teakwave_escape(teakwave_asset_url($preloadImage)); ?>" fetchpriority="high"><?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= teakwave_escape(teakwave_asset_url('assets/css/style.css')); ?>" rel="stylesheet">
    <?= $extraHead; ?>
    <?php if ($structuredData !== null): ?>
    <script type="application/ld+json"><?= teakwave_json($structuredData); ?></script>
    <?php endif; ?>
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <nav class="navbar navbar-expand-lg fixed-top" aria-label="Navigasi utama">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= teakwave_escape(teakwave_absolute_url()); ?>">
                <img src="<?= teakwave_escape(teakwave_asset_url('assets/img/logo-teakwave.png')); ?>" alt="Teakwave" width="190" height="95" decoding="async">
            </a>
            <button aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi"
                class="navbar-toggler custom-toggler" data-bs-target="#mainNav" data-bs-toggle="collapse" type="button">
                <span aria-hidden="true" class="hamburger-box">
                    <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
                </span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link<?= $homeActive; ?>"
                           href="<?= teakwave_escape(teakwave_absolute_url()); ?>">
                            Home
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link<?= $profileActive; ?>"
                           href="<?= teakwave_escape(teakwave_absolute_url('tentang-kami')); ?>">
                            Tentang Kami
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link<?= $productActive; ?>"
                           href="<?= teakwave_escape(teakwave_absolute_url('produk')); ?>">
                            Produk
                        </a>
                    </li>
                </ul>
                <a class="nav-link btn-contact<?= $contactActive; ?>" href="<?= teakwave_escape(teakwave_absolute_url('kontak')); ?>">Kontak</a>
            </div>
        </div>
    </nav>
