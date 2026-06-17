<?php
$pageTitle = 'Detail Produk Teakwave - UCG-Ultra';
$activePage = 'product';
$extraHead = '<link href="" id="canonicalProductUrl" rel="canonical"/>';
require __DIR__ . '/includes/header.php';
?>
<main class="product-detail-page" id="produk-detail-page">
    <!-- Product Hero -->
    <section class="produk-hero-section">
        <div class="container">
            <div class="produk-hero-card reveal">
                <div class="produk-hero-visual" data-banner-placement="product"
                    style="background: url(./assets/img/banner-profil.png); background-size: cover; background-position: center center;">
                </div>
                <div class="produk-hero-title">
                    <h1>Produk <em>Teakwave</em></h1>
                </div>
            </div>
        </div>
    </section>
    <!-- Product Detail -->
    <section class="product-detail-section">
        <div class="container">
            <div class="product-detail-card reveal">
                <div class="product-gallery">
                    <button aria-label="Perbesar foto produk" class="main-photo-btn" id="mainPhotoButton" type="button">
                        <img alt="UCG-Ultra tampak utama" id="mainProductImage" src="">
                        </img></button>
                    <div aria-label="Thumbnail foto produk" class="thumb-row" id="productThumbs"></div>
                </div>
                <article class="product-detail-content">
                    <h1 id="detailProductName">UCG-Ultra</h1>
                    <div class="detail-divider"></div>
                    <p>UCG-Ultra menggabungkan fungsi router, security gateway, dan manajemen jaringan dalam satu
                        perangkat yang compact. Dengan integrasi UniFi OS, Anda dapat mengelola jaringan dengan
                        lebih mudah melalui satu dashboard yang intuitif.</p>
                    <p>Perangkat ini dirancang untuk memberikan performa optimal dengan konfigurasi yang sederhana,
                        sehingga cocok untuk pengguna teknis maupun non-teknis.</p>
                    <div class="detail-subtitle">Key Features</div>
                    <ul>
                        <li>Manajemen jaringan terpusat melalui UniFi OS</li>
                        <li>Desain compact dan hemat tempat</li>
                        <li>Performa stabil untuk kebutuhan harian hingga bisnis kecil</li>
                        <li>Mendukung monitoring jaringan secara real-time</li>
                        <li>Setup mudah dan cepat</li>
                    </ul>
                    <div class="detail-subtitle">Spesifikasi Singkat</div>
                    <ul>
                        <li>Brand: Ubiquiti</li>
                        <li>Tipe: Cloud Gateway</li>
                        <li>Interface: Ethernet Ports</li>
                        <li>Management: UniFi Controller / UniFi OS</li>
                        <li>Use Case: Rumah, kantor, UMKM, jaringan skala kecil–menengah</li>
                    </ul>
                    <div class="detail-actions">
                        <a class="detail-action-btn primary" href="https://wa.me/6282112345678" rel="noopener"
                            target="_blank"><i class="bi bi-whatsapp"></i> Tanya via WhatsApp</a>
                        <a class="detail-action-btn secondary" href="produk.php"><i class="bi bi-grid"></i> Kembali
                            ke Produk</a>
                    </div>
                </article>
            </div>
        </div>
    </section>
    <!-- Marketplace -->
    <section class="section-space pt-3" id="marketplace">
        <div class="container text-center" data-content-slug="produk-detail-marketplace">
            <span class="market-title-pill reveal">Marketplace</span>
            <h2 class="section-title mb-4 reveal">Beli Produk Teakwave Secara Online</h2>
            <div class="market-mini-wrap">
                <div class="row g-3">
                    <div class="col-md-4 reveal slide-left">
                        <div class="market-card-mini">
                            <p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>
                            <a class="market-btn" href="#" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-tokopedia.png" /></a>
                        </div>
                    </div>
                    <div class="col-md-4 reveal">
                        <div class="market-card-mini">
                            <p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>
                            </p>
                            <a class="market-btn" href="#" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-shopee.png" /></a>
                        </div>
                    </div>
                    <div class="col-md-4 reveal slide-right">
                        <div class="market-card-mini">
                            <p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>
                            <a class="market-btn" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-whatsapp.png" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$footerClass = 'pb-5 produk-footer-space';
$footerContainerClass = 'container';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
?>
<!-- Product Image Modal -->
<div aria-hidden="true" aria-labelledby="productImageModalTitle" class="modal fade product-modal" id="productImageModal"
    tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-product-shell">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                    <h2 class="modal-product-title" id="productImageModalTitle">UCG-Ultra</h2>
                    <div class="modal-product-stage">
                        <button aria-label="Foto sebelumnya" class="modal-gallery-nav modal-gallery-prev"
                            id="modalProductPrev" type="button">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <img alt="Foto produk diperbesar" class="modal-product-image" id="modalProductImage" src="">
                        </img>
                        <button aria-label="Foto berikutnya" class="modal-gallery-nav modal-gallery-next"
                            id="modalProductNext" type="button">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="modal-product-counter" id="modalProductCounter"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/includes/scripts.php';
?>