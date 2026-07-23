<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/public-products.php';

$bestSellerProducts = [];
$homepageBanners = [];

try {
    require_once __DIR__ . '/utakatik/config/database.php';
    $bestSellerProducts = teakwave_public_products($pdo, 10, true);

    // Banner homepage dirender langsung oleh PHP agar gambar LCP sudah tersedia
    // pada HTML awal dan tidak diganti lagi setelah request API JavaScript.
    $bannerStmt = $pdo->query("
        SELECT id, title, image, link_url
        FROM banners
        WHERE status = 'active'
          AND placement IN ('homepage', 'header_home', 'home')
          AND (start_date IS NULL OR start_date = '0000-00-00' OR start_date <= CURDATE())
          AND (end_date IS NULL OR end_date = '0000-00-00' OR end_date >= CURDATE())
        ORDER BY id DESC
        LIMIT 3
    ");

    foreach ($bannerStmt->fetchAll() as $bannerRow) {
        $bannerPath = teakwave_normalize_asset_path($bannerRow['image'] ?? '');
        if ($bannerPath === '') {
            continue;
        }

        $homepageBanners[] = [
            'title' => trim((string) ($bannerRow['title'] ?? '')),
            'image' => $bannerPath,
            'link_url' => trim((string) ($bannerRow['link_url'] ?? '')),
        ];
    }
} catch (Throwable $ignored) {
    $bestSellerProducts = [];
    $homepageBanners = [];
}

if (!$bestSellerProducts) {
    $fallbackNames = [
        'UniFi U6 Lite Access Point', 'UniFi U6 Plus Indoor AP', 'UniFi U7 Pro WiFi AP',
        'UniFi Dream Router', 'UniFi Cloud Gateway Ultra', 'UniFi Switch Lite 8 PoE', 'UniFi Switch 24 PoE'
    ];
    foreach ($fallbackNames as $index => $name) {
        $bestSellerProducts[] = [
            'id' => $index + 1,
            'name' => $name,
            'slug' => teakwave_product_slug($name),
            'brand' => 'Ubiquiti',
            'category' => 'Perangkat Jaringan',
            'sku' => '', 'price' => 0, 'stock' => 0, 'is_best_seller' => true,
            'description' => '', 'image' => 'produk/' . (($index % 7) + 1) . '.png'
        ];
    }
}

if (!$homepageBanners) {
    $homepageBanners = [
        ['title' => 'Solusi perangkat jaringan Teakwave', 'image' => 'assets/img/banner-home-1.webp', 'link_url' => ''],
        ['title' => 'Produk jaringan nirkabel Teakwave', 'image' => 'assets/img/banner-home-2.webp', 'link_url' => ''],
        ['title' => 'Distributor perangkat jaringan terpercaya', 'image' => 'assets/img/banner-home-3.webp', 'link_url' => ''],
    ];
}

// Siapkan varian AVIF dan gambar mobile hanya bila filenya tersedia.
foreach ($homepageBanners as $bannerIndex => &$banner) {
    $path = teakwave_normalize_asset_path($banner['image'] ?? '');
    $banner['image'] = $path;
    $banner['url'] = teakwave_asset_url($path, 'assets/img/banner-home-' . (($bannerIndex % 3) + 1) . '.webp');
    $banner['mobile_url'] = '';
    $banner['avif_url'] = '';
    $banner['mobile_avif_url'] = '';

    if ($path !== '' && !preg_match('#^https?://#i', $path)) {
        $baseWithoutExtension = preg_replace('/\.(?:png|jpe?g|webp|avif)$/i', '', $path);
        $mobilePath = $baseWithoutExtension . '-mobile.webp';
        $avifPath = $baseWithoutExtension . '.avif';
        $mobileAvifPath = $baseWithoutExtension . '-mobile.avif';

        if (is_file(__DIR__ . '/' . $mobilePath)) {
            $banner['mobile_url'] = teakwave_asset_url($mobilePath);
        }
        if (is_file(__DIR__ . '/' . $avifPath)) {
            $banner['avif_url'] = teakwave_asset_url($avifPath);
        }
        if (is_file(__DIR__ . '/' . $mobileAvifPath)) {
            $banner['mobile_avif_url'] = teakwave_asset_url($mobileAvifPath);
        }
    }
}
unset($banner);

$pageTitle = $defaultMetaTitle;
$metaDescription = $defaultMetaDescription;
$metaKeywords = $defaultMetaKeywords;
$canonicalPath = '';
$activePage = 'home';
$preloadImage = '';
$firstBanner = $homepageBanners[0] ?? [];
$desktopPreload = (string) ($firstBanner['avif_url'] ?? $firstBanner['url'] ?? '');
$mobilePreload = (string) ($firstBanner['mobile_avif_url'] ?? $firstBanner['mobile_url'] ?? $desktopPreload);
$desktopPreloadType = !empty($firstBanner['avif_url']) ? 'image/avif' : 'image/webp';
$mobilePreloadType = !empty($firstBanner['mobile_avif_url']) ? 'image/avif' : (!empty($firstBanner['mobile_url']) ? 'image/webp' : $desktopPreloadType);
$extraHead = '';
if ($mobilePreload !== '') {
    $extraHead .= '<link rel="preload" as="image" href="' . teakwave_escape($mobilePreload) . '" type="' . $mobilePreloadType . '" media="(max-width: 767.98px)" fetchpriority="high">' . "\n";
}
if ($desktopPreload !== '') {
    $extraHead .= '<link rel="preload" as="image" href="' . teakwave_escape($desktopPreload) . '" type="' . $desktopPreloadType . '" media="(min-width: 768px)" fetchpriority="high">' . "\n";
}
$structuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => teakwave_absolute_url('#organization'),
            'name' => $siteName,
            'url' => teakwave_absolute_url(''),
            'logo' => teakwave_absolute_url('assets/img/logo-teakwave.png'),
            'email' => 'sales@teakwave.com',
            'telephone' => '+62-21-6121005',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Kompleks Harco Elektronik Mangga Dua Blok H-6, Jl. Mangga Dua Dalam',
                'addressLocality' => 'Jakarta',
                'postalCode' => '10730',
                'addressCountry' => 'ID'
            ],
            'sameAs' => ['https://www.instagram.com/teak.wave/', 'https://www.facebook.com/teakwave']
        ],
        [
            '@type' => 'WebSite',
            '@id' => teakwave_absolute_url('#website'),
            'url' => teakwave_absolute_url(''),
            'name' => $siteName,
            'publisher' => ['@id' => teakwave_absolute_url('#organization')],
            'inLanguage' => 'id-ID'
        ]
    ]
];
require __DIR__ . '/includes/header.php';
?>
<main id="main-content">
    <h1 class="visually-hidden">Distributor Perangkat Jaringan Nirkabel dan Fiber Optic Teakwave</h1>
    <header class="hero-wrap" id="home">
        <div class="container">
            <div class="carousel slide hero-card" data-server-rendered="true" data-bs-ride="carousel" id="heroCarousel">
                <div class="carousel-indicators">
                    <?php foreach ($homepageBanners as $bannerIndex => $banner): ?>
                    <button aria-current="<?= $bannerIndex === 0 ? 'true' : 'false'; ?>"
                        aria-label="Slide <?= $bannerIndex + 1; ?>"
                        class="<?= $bannerIndex === 0 ? 'active' : ''; ?>"
                        data-bs-slide-to="<?= $bannerIndex; ?>" data-bs-target="#heroCarousel" type="button"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($homepageBanners as $bannerIndex => $banner):
                        $bannerTitle = $banner['title'] !== '' ? $banner['title'] : 'Banner Teakwave ' . ($bannerIndex + 1);
                        $bannerLink = trim((string) ($banner['link_url'] ?? ''));
                    ?>
                    <div class="carousel-item<?= $bannerIndex === 0 ? ' active' : ''; ?>">
                        <?php if ($bannerLink !== ''): ?><a href="<?= teakwave_escape($bannerLink); ?>" class="hero-banner-link" rel="noopener"><?php endif; ?>
                        <div class="hero-slide hero-image-slide text-center">
                            <picture>
                                <?php if (!empty($banner['mobile_avif_url'])): ?><source media="(max-width: 767.98px)" srcset="<?= teakwave_escape($banner['mobile_avif_url']); ?>" type="image/avif"><?php endif; ?>
                                <?php if (!empty($banner['avif_url'])): ?><source media="(min-width: 768px)" srcset="<?= teakwave_escape($banner['avif_url']); ?>" type="image/avif"><?php endif; ?>
                                <?php if (!empty($banner['mobile_url'])): ?><source media="(max-width: 767.98px)" srcset="<?= teakwave_escape($banner['mobile_url']); ?>" type="image/webp"><?php endif; ?>
                                <img alt="<?= teakwave_escape($bannerTitle); ?>" class="hero-slide-img"
                                    decoding="async" <?= $bannerIndex === 0 ? 'fetchpriority="high" loading="eager"' : 'fetchpriority="low" loading="lazy"'; ?>
                                    src="<?= teakwave_escape($banner['url']); ?>" width="1672" height="941">
                            </picture>
                        </div>
                        <?php if ($bannerLink !== ''): ?></a><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button aria-label="Slide sebelumnya" class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#heroCarousel" type="button"><span aria-hidden="true" class="carousel-control-prev-icon"></span></button>
                <button aria-label="Slide berikutnya" class="carousel-control-next" data-bs-slide="next" data-bs-target="#heroCarousel" type="button"><span aria-hidden="true" class="carousel-control-next-icon"></span></button>
                <div class="hero-stats" data-content-slug="index-hero-stats">
                    <div class="row text-center g-0">
                        <div class="col-12 col-md-4"><div class="stat-number">10+ Tahun</div><div class="stat-text">Pengalaman</div></div>
                        <div class="col-12 col-md-4"><div class="stat-number">15.000+</div><div class="stat-text">Produk Terdistribusi</div></div>
                        <div class="col-12 col-md-4"><div class="stat-number">Distributor Resmi</div><div class="stat-text">Brand Global</div></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-space" id="about">
        <div class="container">
            <div class="row align-items-center profil-index">
                <div class="col-lg-8 reveal slide-left" data-content-slug="index-about">
                    <span class="section-label">About Us</span>
                    <h2 class="section-title mb-3">Mengenal Teakwave</h2>
                    <p>Teakwave adalah distributor perangkat jaringan yang berdiri sejak tahun 2014 dan berpusat di Jakarta.</p>
                    <p>Kami menyediakan berbagai produk jaringan berkualitas dari brand terpercaya seperti Ubiquiti dan MikroTik. Hingga saat ini, Teakwave telah mendistribusikan lebih dari 15.000 perangkat jaringan ke berbagai wilayah di Indonesia.</p>
                </div>
                <div class="col-lg-4 reveal slide-right">
                    <div class="about-logo-box">
                        <div class="small fw-bold text-primary mb-1">Authorized Distributor of:</div>
                        <div class="brand-mini">
                            <span><img src="assets/img/logo-voltech.png" alt="VOL.TECH" loading="lazy" decoding="async"></span>
                            <span><img src="assets/img/logo-vsol.png" alt="V-SOL" loading="lazy" decoding="async"></span>
                            <span><img src="assets/img/logo-ubiquiti.png" alt="Ubiquiti" loading="lazy" decoding="async"></span>
                            <span><img src="assets/img/logo-mikrotik.png" alt="MikroTik" loading="lazy" decoding="async"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5" aria-labelledby="why-title">
        <div class="container">
            <div class="feature-panel reveal" data-content-slug="index-why-choose">
                <h2 class="section-title text-center mb-5" id="why-title">Mengapa Memilih Teakwave?</h2>
                <div class="row g-4">
                    <?php
                    $features = [
                        ['produk-original.png', 'Produk Original', 'Semua perangkat jaringan yang kami distribusikan merupakan produk asli dari brand resmi.'],
                        ['harga-kompetitif.png', 'Harga Kompetitif', 'Kami menyediakan harga terbaik bagi reseller, perusahaan, maupun pengguna akhir.'],
                        ['garansi-resmi.png', 'Garansi Resmi', 'Setiap produk dilengkapi garansi dengan aturan dan ketentuan yang jelas.'],
                        ['distribusi-nasional.png', 'Distribusi Nasional', 'Pengiriman produk menjangkau seluruh wilayah Indonesia.']
                    ];
                    foreach ($features as $index => $feature): ?>
                    <div class="col-md-6 reveal <?= $index % 2 === 0 ? 'slide-left' : 'slide-right'; ?>">
                        <div class="feature-item">
                            <div class="feature-icon"><img src="assets/img/<?= teakwave_escape($feature[0]); ?>" alt="" width="160" height="160" loading="lazy" decoding="async"></div>
                            <div><h3 class="h5"><?= teakwave_escape($feature[1]); ?></h3><p><?= teakwave_escape($feature[2]); ?></p></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space pt-4" id="produk" aria-labelledby="best-seller-title">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between mb-4 reveal">
                <h2 class="section-title mb-0" data-content-slug="index-products-title" id="best-seller-title">Produk Best Seller</h2>
                <div class="product-controls d-flex gap-2">
                    <button aria-label="Produk sebelumnya" class="btn" data-bs-slide="prev" data-bs-target="#productCarousel" type="button"><i class="bi bi-arrow-left"></i></button>
                    <button aria-label="Produk berikutnya" class="btn" data-bs-slide="next" data-bs-target="#productCarousel" type="button"><i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
            <div class="carousel slide reveal" data-bs-ride="false" id="productCarousel" data-best-seller-products="true">
                <div class="carousel-inner">
                    <?php foreach (array_chunk($bestSellerProducts, 5) as $chunkIndex => $chunk): ?>
                    <div class="carousel-item<?= $chunkIndex === 0 ? ' active' : ''; ?>">
                        <div class="best-seller-grid">
                            <?php foreach ($chunk as $product): ?>
                            <div class="best-seller-item">
                                <a class="best-seller-link" href="<?= teakwave_escape(teakwave_absolute_url('produk-' . $product['slug'])); ?>" aria-label="Lihat detail <?= teakwave_escape($product['name']); ?>">
                                    <article class="product-card">
                                        <div class="product-img"><img class="best-seller-product-photo" src="<?= teakwave_escape(teakwave_product_asset_url($product['image'] ?? '')); ?>" data-fallback-src="<?= teakwave_escape(teakwave_asset_url('produk/1.png')); ?>" alt="<?= teakwave_escape($product['name']); ?>" width="720" height="720" loading="lazy" decoding="async"></div>
                                        <h3 class="product-name"><?= teakwave_escape($product['name']); ?></h3>
                                    </article>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <script type="application/json" id="initialBestSellerData"><?= teakwave_json($bestSellerProducts); ?></script>
        </div>
    </section>

    <section class="pb-5" aria-labelledby="testimonial-title">
        <div class="container">
            <div class="testimonial-panel reveal" data-content-slug="index-testimonials">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-4 reveal slide-left"><span class="section-label">Testimonials</span><h2 class="section-title mb-3" id="testimonial-title">Apa Kata Mereka</h2><p>Banyak pelanggan telah mempercayakan kebutuhan perangkat jaringan mereka kepada Teakwave.</p></div>
                    <div class="col-lg-4 reveal"><blockquote class="testimonial-card"><p>“Pelayanan cepat, RMA bagus, harga kompetitif.”</p><footer>Bapak Donny — Gloria Net</footer><div class="stars mt-2" aria-label="5 dari 5 bintang"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div></blockquote></div>
                    <div class="col-lg-4 reveal slide-right"><blockquote class="testimonial-card"><p>“Selain harga yang bersaing, RMA pun cepat dan bagus.”</p><footer>Bapak David — DS Comp</footer><div class="stars mt-2" aria-label="5 dari 5 bintang"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div></blockquote></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space pt-4" id="marketplace" aria-labelledby="marketplace-title">
        <div class="container text-center" data-content-slug="index-marketplace">
            <span class="section-label reveal slide-left">Marketplace</span>
            <h2 class="section-title mb-4 reveal slide-right" id="marketplace-title">Beli Produk Teakwave Secara Online</h2>
            <div class="marketplace-list text-start">
                <div class="market-row reveal slide-left"><div>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></div><a aria-label="Toko Teakwave di Tokopedia" class="market-btn tokopedia" data-external-url="tokopedia" href="https://www.tokopedia.com/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-tokopedia.png" alt="Tokopedia" width="640" height="200" loading="lazy" decoding="async"></a></div>
                <div class="market-row reveal slide-right"><div>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></div><a aria-label="Toko Teakwave di Shopee" class="market-btn shopee" data-external-url="shopee" href="https://shopee.co.id/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-shopee.png" alt="Shopee" width="640" height="200" loading="lazy" decoding="async"></a></div>
                <div class="market-row reveal slide-left"><div>Ingin harga yang lebih kompetitif? Order melalui <strong>WhatsApp.</strong></div><a aria-label="Hubungi Teakwave lewat WhatsApp" class="market-btn whatsapp" data-external-url="whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="assets/img/logo-whatsapp.png" alt="WhatsApp" width="640" height="200" loading="lazy" decoding="async"></a></div>
            </div>
        </div>
    </section>
</main>
<?php
$footerClass = 'pb-5';
$footerContainerClass = 'container';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>
