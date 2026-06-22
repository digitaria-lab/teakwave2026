<?php
$pageTitle = 'Teakwave - Solusi Perangkat Jaringan Nirkabel';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<!-- Hero Slider -->
<header class="hero-wrap" id="home">
    <div class="container">
        <div class="carousel slide hero-card reveal" data-banner-placement="homepage" data-bs-ride="carousel"
            id="heroCarousel">
            <div class="carousel-indicators">
                <button aria-current="true" aria-label="Slide 1" class="active" data-bs-slide-to="0"
                    data-bs-target="#heroCarousel" type="button"></button>
                <button aria-label="Slide 2" data-bs-slide-to="1" data-bs-target="#heroCarousel" type="button"></button>
                <button aria-label="Slide 3" data-bs-slide-to="2" data-bs-target="#heroCarousel" type="button"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="hero-slide hero-image-slide text-center">
                        <img alt="Banner Teakwave 1" class="hero-slide-img" decoding="async" loading="eager"
                            src="./assets/img/banner-home-1.png" />
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide hero-image-slide text-center">
                        <img alt="Banner Teakwave 2" class="hero-slide-img" decoding="async" loading="lazy"
                            src="./assets/img/banner-home-2.png" />
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide hero-image-slide text-center">
                        <img alt="Banner Teakwave 3" class="hero-slide-img" decoding="async" loading="lazy"
                            src="./assets/img/banner-home-3.png" />
                    </div>
                </div>
            </div>
            <button aria-label="Slide sebelumnya" class="carousel-control-prev" data-bs-slide="prev"
                data-bs-target="#heroCarousel" type="button">
                <span aria-hidden="true" class="carousel-control-prev-icon"></span>
            </button>
            <button aria-label="Slide berikutnya" class="carousel-control-next" data-bs-slide="next"
                data-bs-target="#heroCarousel" type="button">
                <span aria-hidden="true" class="carousel-control-next-icon"></span>
            </button>
            <div class="hero-stats" data-content-slug="index-hero-stats">
                <div class="row text-center g-0">
                    <div class="col-12 col-md-4">
                        <div class="stat-number">10+ Tahun</div>
                        <div class="stat-text">Pengalaman</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="stat-number">15.000+</div>
                        <div class="stat-text">Produk Terdistribusi</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="stat-number">Distributor Resmi</div>
                        <div class="stat-text">Brand Global</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- About -->
<section class="section-space" id="about">
    <div class="container">
        <div class="row align-items-center profil-index" style="">
            <div class="col-lg-8 reveal slide-left" data-content-slug="index-about">
                <span class="section-label">About Us</span>
                <h2 class="section-title mb-3">Mengenal Teakwave</h2>
                <p>Teakwave adalah distributor perangkat jaringan yang berdiri sejak tahun 2014 dan berpusat di
                    Jakarta.</p>
                <p>Kami menyediakan berbagai produk jaringan berkualitas dari brand terpercaya seperti Ubiquiti dan
                    MikroTik. Hingga saat ini, Teakwave telah mendistribusikan lebih dari 15.000 perangkat jaringan
                    ke berbagai wilayah di Indonesia.</p>
            </div>
            <div class="col-lg-4 reveal slide-right">
                <div class="about-logo-box">
                    <div class="small fw-bold text-primary mb-1">Authorized Distributor of:</div>
                    <div class="brand-mini">
                        <span><img src="./assets/img/logo-voltech.png" /></span>
                        <span><img src="./assets/img/logo-vsol.png" /></span>
                        <span><img src="./assets/img/logo-ubiquiti.png" /></span>
                        <span><img src="./assets/img/logo-mikrotik.png" /></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Why Choose Us -->
<section class="pb-5">
    <div class="container">
        <div class="feature-panel reveal" data-content-slug="index-why-choose">
            <h2 class="section-title text-center mb-5">Mengapa Memilih Teakwave?</h2>
            <div class="row g-4">
                <div class="col-md-6 reveal slide-left">
                    <div class="feature-item">
                        <div class="feature-icon"><img src="./assets/img/produk-original.png" /></div>
                        <div>
                            <h5>Produk Original</h5>
                            <p>Semua perangkat jaringan yang kami distribusikan merupakan produk asli dari brand
                                resmi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 reveal slide-right">
                    <div class="feature-item">
                        <div class="feature-icon"><img src="./assets/img/harga-kompetitif.png" /></div>
                        <div>
                            <h5>Harga Kompetitif</h5>
                            <p>Kami menyediakan harga terbaik bagi reseller, perusahaan, maupun pengguna akhir.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 reveal slide-left">
                    <div class="feature-item">
                        <div class="feature-icon"><img src="./assets/img/garansi-resmi.png" /></div>
                        <div>
                            <h5>Garansi Resmi</h5>
                            <p>Setiap produk dilengkapi garansi dengan aturan dan ketentuan yang jelas.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 reveal slide-right">
                    <div class="feature-item">
                        <div class="feature-icon"><img src="./assets/img/distribusi-nasional.png" /></div>
                        <div>
                            <h5>Distribusi Nasional</h5>
                            <p>Pengiriman produk menjangkau seluruh wilayah Indonesia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Products -->
<section class="section-space pt-4" id="produk">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4 reveal">
            <h2 class="section-title mb-0" data-content-slug="index-products-title">Produk Best Seller</h2>
            <div class="product-controls d-flex gap-2">
                <button aria-label="Previous product" class="btn" data-bs-slide="prev" data-bs-target="#productCarousel"
                    type="button"><i class="bi bi-arrow-left"></i></button>
                <button aria-label="Next product" class="btn" data-bs-slide="next" data-bs-target="#productCarousel"
                    type="button"><i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
        <div class="carousel slide reveal" data-bs-ride="false" id="productCarousel" data-best-seller-products="true">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row g-3">
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/1.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/2.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/3.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/4.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/5.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row g-3">
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/6.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/7.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/1.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/2.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg">
                            <div class="product-card">
                                <div class="product-img">
                                    <img src="./produk/3.png" />
                                </div>
                                <div class="product-name">Produk Teakwave</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Testimonials -->
<section class="pb-5">
    <div class="container">
        <div class="testimonial-panel reveal" data-content-slug="index-testimonials">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4 reveal slide-left">
                    <span class="section-label">Testimonials</span>
                    <h2 class="section-title mb-3">Apa Kata Mereka</h2>
                    <p>Banyak pelanggan telah mempercayakan kebutuhan perangkat jaringan mereka kepada Teakwave.</p>
                </div>
                <div class="col-lg-4 reveal">
                    <div class="testimonial-card">
                        <p>“Pelayanan cepat, RMA bagus, harga kompetitif.”</p>
                        <small>Bapak Donny — Gloria Net</small>
                        <div class="stars mt-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 reveal slide-right">
                    <div class="testimonial-card">
                        <p>“Selain harga yang bersaing, RMA pun cepat dan bagus.”</p>
                        <small>Bapak David — DS Comp</small>
                        <div class="stars mt-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Marketplace -->
<section class="section-space pt-4" id="marketplace">
    <div class="container text-center" data-content-slug="index-marketplace">
        <span class="section-label reveal slide-left">Marketplace</span>
        <h2 class="section-title mb-4 reveal slide-right">Beli Produk Teakwave Secara Online</h2>
        <div class="marketplace-list text-start">
            <div class="market-row reveal slide-left">
                <div>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></div>
                <a class="market-btn tokopedia" href="#" rel="noopener" target="_blank"><img
                        src="./assets/img/logo-tokopedia.png" /></a>
            </div>
            <div class="market-row reveal slide-right">
                <div>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></div>
                <a class="market-btn shopee" href="#" rel="noopener" target="_blank"><img
                        src="./assets/img/logo-shopee.png" /></a>
            </div>
            <div class="market-row reveal slide-left">
                <div>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></div>
                <a class="market-btn whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img
                        src="./assets/img/logo-whatsapp.png" /></a>
            </div>
        </div>
    </div>
</section>
<?php
$footerClass = 'pb-5';
$footerContainerClass = 'container';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>