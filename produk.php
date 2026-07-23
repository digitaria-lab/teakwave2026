<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/public-products.php';

$products = [];
try {
    require_once __DIR__ . '/utakatik/config/database.php';
    $products = teakwave_public_products($pdo);
} catch (Throwable $ignored) {
    $products = [];
}

if (!$products) {
    $fallbackNames = [
        'UniFi U6 Lite Access Point', 'UniFi U6 Plus Indoor AP', 'UniFi U7 Pro WiFi AP',
        'UniFi Dream Router', 'UniFi Cloud Gateway Ultra', 'UniFi Switch Lite 8 PoE', 'UniFi Switch 24 PoE'
    ];
    foreach ($fallbackNames as $index => $name) {
        $products[] = [
            'id' => $index + 1, 'name' => $name, 'slug' => teakwave_product_slug($name),
            'brand' => 'Ubiquiti', 'category' => 'Perangkat Jaringan', 'sku' => '',
            'price' => 0, 'stock' => 0, 'is_best_seller' => true, 'description' => '',
            'image' => 'produk/' . (($index % 7) + 1) . '.png'
        ];
    }
}

$brands = array_values(array_unique(array_filter(array_map(static fn($item) => $item['brand'] ?? '', $products))));
sort($brands, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = 'Produk Teakwave | Access Point, Router, Switch & Fiber Optic';
$metaDescription = 'Jelajahi katalog perangkat jaringan Teakwave: access point, router, switch, wireless outdoor, OLT, ONU/ONT, dan aksesoris fiber dari brand terpercaya.';
$canonicalPath = 'produk';
$activePage = 'product';
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Katalog Produk Teakwave',
    'itemListElement' => array_map(static function ($product, $index) {
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => teakwave_absolute_url('produk-' . $product['slug']),
            'name' => $product['name']
        ];
    }, array_slice($products, 0, 20), array_keys(array_slice($products, 0, 20)))
];
require __DIR__ . '/includes/header.php';
?>
<main class="produk-page" id="main-content">
    <section class="produk-hero-section" aria-labelledby="product-page-title">
        <div class="container">
            <div class="produk-hero-card">
                <div class="produk-hero-visual" data-banner-placement="product"
                    style="background-image: url('<?= teakwave_escape(teakwave_asset_url('assets/img/banner-profil.webp')); ?>'); background-size: cover; background-position: center center;"></div>
                <div class="produk-hero-title"><h1 id="product-page-title">Produk <em>Teakwave</em></h1></div>
            </div>
        </div>
    </section>

    <section class="catalog-section pb-5" aria-label="Katalog produk jaringan">
        <div class="container">
            <div class="product-filterbar reveal">
                <div aria-label="Filter brand produk" class="brand-tabs" role="group">
                    <button class="filter-pill active" data-filter-brand="all" type="button">Semua</button>
                    <?php foreach ($brands as $brand): ?>
                    <button class="filter-pill" data-filter-brand="<?= teakwave_escape($brand); ?>" type="button"><?= teakwave_escape($brand); ?></button>
                    <?php endforeach; ?>
                </div>
                <label class="product-search" for="productSearch">
                    <span class="visually-hidden">Cari produk</span>
                    <input id="productSearch" placeholder="Cari produk" type="search" autocomplete="off">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </label>
            </div>
            <div class="catalog-grid-wrap reveal">
                <div class="row g-3" id="catalogGrid" aria-live="polite">
                    <?php foreach (array_slice($products, 0, 10) as $product): ?>
                    <?= teakwave_render_product_card($product); ?>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state" id="emptyProductState">Produk tidak ditemukan. Coba gunakan kata kunci lain.</div>
            </div>
            <div aria-label="Navigasi halaman produk" class="product-pagination reveal" id="productPagination"></div>
            <noscript><p class="text-center mt-4">Aktifkan JavaScript untuk menggunakan pencarian dan pagination. Daftar produk utama tetap dapat dibuka melalui kartu di atas.</p></noscript>
            <script type="application/json" id="initialCatalogData"><?= teakwave_json(['products' => $products, 'brands' => $brands]); ?></script>
        </div>
    </section>

    <section class="section-space pt-3" id="marketplace" aria-labelledby="product-marketplace-title">
        <div class="container text-center" data-content-slug="produk-marketplace">
            <span class="market-title-pill reveal">Marketplace</span>
            <h2 class="section-title mb-4 reveal" id="product-marketplace-title">Beli Produk Teakwave Secara Online</h2>
            <div class="market-mini-wrap"><div class="row g-3">
                <div class="col-md-4 reveal slide-left"><div class="market-card-mini"><p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p><a aria-label="Tokopedia Teakwave" class="market-btn" data-external-url="tokopedia" href="https://www.tokopedia.com/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-tokopedia.png" alt="Tokopedia" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
                <div class="col-md-4 reveal"><div class="market-card-mini"><p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></p><a aria-label="Shopee Teakwave" class="market-btn" data-external-url="shopee" href="https://shopee.co.id/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-shopee.png" alt="Shopee" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
                <div class="col-md-4 reveal slide-right"><div class="market-card-mini"><p>Ingin harga yang lebih kompetitif? Order melalui <strong>WhatsApp.</strong></p><a aria-label="WhatsApp Teakwave" class="market-btn" data-external-url="whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="assets/img/logo-whatsapp.png" alt="WhatsApp" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
            </div></div>
        </div>
    </section>

    <section class="brand-section" aria-labelledby="brands-title">
        <div class="container text-center" data-content-slug="produk-brands">
            <span class="brand-title-pill reveal" id="brands-title">Our Brands</span>
            <div class="brand-grid"><div class="row g-3">
                <?php
                $brandInfo = [
                    ['logo-ubiquiti.png', 'Ubiquiti', 'Brand global yang dikenal dengan performa tinggi dan stabilitas untuk jaringan wireless, banyak digunakan oleh ISP dan enterprise.'],
                    ['logo-vsol.png', 'V-SOL', 'Solusi perangkat GPON dan fiber optic yang handal dan efisien, cocok untuk kebutuhan ISP dan pengembangan jaringan FTTH.'],
                    ['logo-mikrotik.png', 'MikroTik', 'Perangkat jaringan dengan fleksibilitas tinggi, fitur lengkap, dan harga kompetitif, menjadi pilihan utama para profesional jaringan.'],
                    ['logo-voltech.png', 'VOL.TECH', 'Brand perangkat jaringan yang menghadirkan solusi modern untuk kebutuhan konektivitas.']
                ];
                foreach ($brandInfo as $index => $brand): ?>
                <div class="col-md-6 reveal <?= $index % 2 === 0 ? 'slide-left' : 'slide-right'; ?>">
                    <article class="brand-card"><div class="brand-logo-text"><img src="assets/img/<?= teakwave_escape($brand[0]); ?>" alt="<?= teakwave_escape($brand[1]); ?>" width="640" height="200" loading="lazy" decoding="async"></div><p><?= teakwave_escape($brand[2]); ?></p></article>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </section>
</main>
<?php
$footerClass = 'pb-5 produk-footer-space';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>
