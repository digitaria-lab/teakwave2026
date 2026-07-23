<?php
$pageTitle = 'Tentang Teakwave | Distributor dan Spesialis Jaringan';
$metaDescription = 'Kenali Teakwave, distributor perangkat jaringan dan wireless sejak 2014 yang menyediakan produk resmi, konsultasi teknis, dan dukungan troubleshooting.';
$canonicalPath = 'tentang-kami';
$activePage = 'profile';
require __DIR__ . '/includes/header.php';
?>
<main class="profile-page" id="main-content">
    <!-- Profile Hero -->
    <section class="profile-section">
        <div class="container profile-container">
            <div class="profile-hero-card">
                <div class="profile-hero-visual" data-banner-placement="profile"
                    style="border-radius: var(--radius-xl); background-image: url('<?= teakwave_escape(teakwave_asset_url('assets/img/banner-profil.webp')); ?>'); background-size: cover; background-position: center center;">
                </div>
                <div class="profile-hero-title">
                    <h1>Tentang <em>Teakwave</em></h1>
                </div>
            </div>
            <div class="profile-copy-wrap reveal slide-left" data-content-slug="tentang-kami">
                <p>Didirikan pada tahun 2014, Teakwave adalah perusahaan spesialis di bidang perangkat jaringan dan
                    nirkabel yang berbasis di Jakarta. Sebagai distributor resmi berbagai produk wireless, kami
                    menyediakan beragam perangkat jaringan berkualitas tinggi dengan harga yang terjangkau.</p>
                <p>Selain itu, kami juga memberikan layanan konsultasi teknis untuk membantu pelanggan memahami
                    fitur dan manfaat dari produk yang kami tawarkan. Tim kami siap membantu dalam proses
                    troubleshooting apabila dibutuhkan. Untuk memastikan solusi yang tepat, kami juga dapat
                    melakukan pengecekan awal menggunakan link planner guna menilai kesesuaian produk dengan
                    kebutuhan sistem Anda.</p>
                <p>Dengan komitmen untuk menghadirkan solusi terbaik bagi kebutuhan jaringan dan nirkabel, Teakwave
                    menjadi pilihan yang tepat untuk mengoptimalkan performa infrastruktur Anda.</p>
            </div>
            <div class="authorized-strip reveal slide-right" data-content-slug="profile-authorized">
                <div class="auth-label">Authorized Distributor of:</div>
                <div aria-label="Brand distributor resmi" class="auth-brand-row">
                    <span class="auth-logo voltech"><img src="assets/img/logo-voltech.png" alt="VOL.TECH" width="640" height="200" loading="lazy" decoding="async"></span>
                    <span class="auth-logo voltech"><img src="assets/img/logo-vsol.png" alt="V-SOL" width="640" height="200" loading="lazy" decoding="async"></span>
                    <span class="auth-logo voltech"><img src="assets/img/logo-ubiquiti.png" alt="Ubiquiti" width="640" height="200" loading="lazy" decoding="async"></span>
                    <span class="auth-logo voltech"><img src="assets/img/logo-mikrotik.png" alt="MikroTik" width="640" height="200" loading="lazy" decoding="async"></span>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$footerClass = 'pb-5 profile-footer-space';
$footerContainerClass = 'container profile-container';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>