<?php
$pageTitle = 'Produk Teakwave - Perangkat Jaringan Nirkabel';
$activePage = 'product';
require __DIR__ . '/includes/header.php';
?>
<main class="produk-page" id="produk-page">
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
    <!-- Product Catalog -->
    <section class="catalog-section pb-5">
        <div class="container">
            <div class="product-filterbar reveal">
                <div aria-label="Filter brand produk" class="brand-tabs" role="tablist">
                    <button class="filter-pill active" data-filter-brand="all" type="button">Semua</button>
                    <button class="filter-pill" data-filter-brand="Ubiquiti" type="button">Ubiquiti</button>
                    <button class="filter-pill" data-filter-brand="V-SOL" type="button">V-SOL</button>
                    <button class="filter-pill" data-filter-brand="Mikrotik" type="button">Mikrotik</button>
                    <button class="filter-pill" data-filter-brand="VOL.TECH" type="button">VOL.TECH</button>
                </div>
                <label aria-label="Cari produk" class="product-search">
                    <input id="productSearch" placeholder="search" type="search" />
                    <i class="bi bi-search"></i>
                </label>
            </div>
            <div class="catalog-grid-wrap reveal">
                <div class="row g-3" id="catalogGrid">
                    <!-- Produk dirender melalui JavaScript agar pagination 55 produk berjalan dinamis. -->
                </div>
                <div class="empty-state" id="emptyProductState">Produk tidak ditemukan. Coba gunakan kata kunci
                    lain.</div>
            </div>
            <div aria-label="Navigasi halaman produk" class="product-pagination reveal" id="productPagination">
            </div>
        </div>
    </section>
    <!-- Marketplace -->
    <section class="section-space pt-3" id="marketplace">
        <div class="container text-center" data-content-slug="produk-marketplace">
            <span class="market-title-pill reveal">Marketplace</span>
            <h2 class="section-title mb-4 reveal">Beli Produk Teakwave Secara Online</h2>
            <div class="market-mini-wrap">
                <div class="row g-3">
                    <div class="col-md-4 reveal slide-left">
                        <div class="market-card-mini">
                            <p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>
                            <a class="market-btn" data-external-url="tokopedia" href="https://www.tokopedia.com/teakwave" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-tokopedia.png" /></a>
                        </div>
                    </div>
                    <div class="col-md-4 reveal">
                        <div class="market-card-mini">
                            <p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>
                            </p>
                            <a class="market-btn" data-external-url="shopee" href="https://shopee.co.id/teakwave" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-shopee.png" /></a>
                        </div>
                    </div>
                    <div class="col-md-4 reveal slide-right">
                        <div class="market-card-mini">
                            <p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>
                            <a class="market-btn" data-external-url="whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img
                                    src="./assets/img/logo-whatsapp.png" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Brands -->
    <section class="brand-section">
        <div class="container text-center" data-content-slug="produk-brands">
            <span class="brand-title-pill reveal">Our Brands</span>
            <div class="brand-grid">
                <div class="row g-3">
                    <div class="col-md-6 reveal slide-left">
                        <div class="brand-card">
                            <div class="brand-logo-text ubiquiti"><img src="./assets/img/logo-ubiquiti.png" />
                            </div>
                            <p>Brand global yang dikenal dengan performa tinggi dan stabilitas untuk jaringan
                                wireless, banyak digunakan oleh ISP dan enterprise.</p>
                        </div>
                    </div>
                    <div class="col-md-6 reveal slide-right">
                        <div class="brand-card">
                            <div class="brand-logo-text vsol"><img src="./assets/img/logo-vsol.png" /></div>
                            <p>Solusi perangkat GPON dan fiber optic yang handal dan efisien, cocok untuk kebutuhan
                                ISP dan pengembangan jaringan FTTH.</p>
                        </div>
                    </div>
                    <div class="col-md-6 reveal slide-left">
                        <div class="brand-card">
                            <div class="brand-logo-text mikrotik"><img src="./assets/img/logo-mikrotik.png" /></div>
                            <p>Perangkat jaringan dengan fleksibilitas tinggi, fitur lengkap, dan harga kompetitif,
                                menjadi pilihan utama para profesional jaringan.</p>
                        </div>
                    </div>
                    <div class="col-md-6 reveal slide-right">
                        <div class="brand-card">
                            <div class="brand-logo-text voltech"><img src="./assets/img/logo-voltech.png" /></div>
                            <p>Brand perangkat jaringan yang menghadirkan solusi modern untuk kebutuhan
                                konektivitas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
$footerClass = 'produk-footer-space';
$footerContainerClass = 'container';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>