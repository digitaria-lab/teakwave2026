<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/public-products.php';

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$productSlug = trim((string) ($_GET['slug'] ?? ''));
if ($productSlug !== '' && !preg_match('/^[a-z0-9-]{1,180}$/', $productSlug)) {
    $productSlug = '';
}

$product = null;
try {
    require_once __DIR__ . '/utakatik/config/database.php';
    $product = teakwave_public_product($pdo, $productId, $productSlug);
} catch (Throwable $ignored) {
    $product = null;
}

$activePage = 'product';
$metaType = 'product';

if ($product) {
    $canonicalPath = 'produk-' . $product['slug'];
    $pageTitle = $product['name'] . ' | Produk Teakwave';
    $metaDescription = teakwave_excerpt($product['description'], 160);
    if ($metaDescription === '') {
        $metaDescription = $product['name'] . ' dari ' . ($product['brand'] ?: 'Teakwave') . '. Tanyakan stok, spesifikasi, dan penawaran terbaru melalui tim Teakwave.';
    }
    $metaImage = $product['image'];
    $preloadImage = !preg_match('#^https?://#i', $product['image']) ? $product['image'] : '';

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => teakwave_excerpt($product['description'], 500),
        'image' => array_map('teakwave_absolute_url', $product['images']),
        'sku' => $product['sku'] ?: null,
        'brand' => ['@type' => 'Brand', 'name' => $product['brand'] ?: 'Teakwave'],
        'category' => $product['category'] ?: 'Perangkat Jaringan',
        'url' => teakwave_absolute_url($canonicalPath)
    ];
    $productSchema = array_filter($productSchema, static fn($value) => $value !== null && $value !== '');
    if ((float) $product['price'] > 0) {
        $productSchema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => 'IDR',
            'price' => (string) (float) $product['price'],
            'availability' => (int) $product['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
            'url' => teakwave_absolute_url($canonicalPath)
        ];
    }
    $structuredData = $productSchema;
} else {
    http_response_code(404);
    $pageTitle = 'Produk Tidak Ditemukan | Teakwave';
    $metaDescription = 'Produk yang Anda cari tidak ditemukan. Silakan kembali ke katalog produk Teakwave.';
    $canonicalPath = 'produk.php';
    $robots = 'noindex,follow';
}

require __DIR__ . '/includes/header.php';

$formatPrice = static function ($price) {
    return 'Rp' . number_format((float) $price, 0, ',', '.');
};
?>
<main class="product-detail-page" id="main-content">
    <section class="produk-hero-section" aria-label="Header produk">
        <div class="container">
            <div class="produk-hero-card reveal">
                <div class="produk-hero-visual" data-banner-placement="product"
                    style="background-image:url('assets/img/banner-profil.webp');background-size:cover;background-position:center"></div>
                <div class="produk-hero-title"><p class="h1 mb-0">Produk <em>Teakwave</em></p></div>
            </div>
        </div>
    </section>

    <section class="product-detail-section">
        <div class="container">
            <?php if (!$product): ?>
                <div class="product-detail-card reveal">
                    <article class="product-detail-content w-100 text-center">
                        <h1>Produk tidak ditemukan</h1>
                        <div class="detail-divider"></div>
                        <p>Alamat produk tidak valid atau produk sudah tidak tersedia.</p>
                        <div class="detail-actions justify-content-center"><a class="detail-action-btn secondary" href="produk"><i class="bi bi-grid"></i> Kembali ke Produk</a></div>
                    </article>
                </div>
            <?php else: ?>
                <nav class="mb-3" aria-label="Breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= teakwave_escape(teakwave_absolute_url()); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="produk">Produk</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= teakwave_escape($product['name']); ?></li>
                    </ol>
                </nav>
                <div class="product-detail-card reveal">
                    <div class="product-gallery">
                        <button aria-label="Perbesar foto <?= teakwave_escape($product['name']); ?>" class="main-photo-btn" id="mainPhotoButton" type="button">
                            <img alt="<?= teakwave_escape($product['name']); ?>" id="mainProductImage" src="<?= teakwave_escape($product['images'][0]); ?>" width="720" height="720" fetchpriority="high" decoding="async">
                        </button>
                        <div aria-label="Thumbnail foto produk" class="thumb-row" id="productThumbs" role="list">
                            <?php foreach ($product['images'] as $index => $image): ?>
                                <button type="button" class="thumb-btn<?= $index === 0 ? ' active' : ''; ?>" aria-label="Lihat foto <?= $index + 1; ?> <?= teakwave_escape($product['name']); ?>" aria-selected="<?= $index === 0 ? 'true' : 'false'; ?>">
                                    <img src="<?= teakwave_escape($image); ?>" alt="<?= teakwave_escape($product['name']); ?> foto <?= $index + 1; ?>" width="720" height="720" loading="lazy" decoding="async">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <article class="product-detail-content">
                        <h1 id="detailProductName"><?= teakwave_escape($product['name']); ?></h1>
                        <div class="detail-divider"></div>
                        <div class="product-description-from-db"><?= $product['description']; ?></div>
                        <div class="detail-subtitle">Spesifikasi Singkat</div>
                        <ul>
                            <li>Brand: <?= teakwave_escape($product['brand'] ?: '-'); ?></li>
                            <li>Kategori: <?= teakwave_escape($product['category'] ?: '-'); ?></li>
                            <li>SKU: <?= teakwave_escape($product['sku'] ?: '-'); ?></li>
                            <li>Stok: <?= teakwave_escape((string) $product['stock']); ?></li>
                        </ul>
                        <?php if ((float) $product['price'] > 0): ?>
                        <div class="detail-price-card" aria-label="Harga produk">
                            <span class="detail-price-label">Harga</span>
                            <strong class="detail-price-value"><?= teakwave_escape($formatPrice($product['price'])); ?></strong>
                            <span class="detail-price-note">Cek stok dan penawaran terbaru via WhatsApp.</span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-actions">
                            <a class="detail-action-btn primary" data-external-url="whatsapp"
                               data-whatsapp-message="<?= teakwave_escape('Halo, saya tertarik dengan produk ' . $product['name']); ?>"
                               href="<?= teakwave_escape($defaultWhatsappUrl . '?text=' . rawurlencode('Halo, saya tertarik dengan produk ' . $product['name'])); ?>" rel="noopener" target="_blank"><i class="bi bi-whatsapp"></i> Tanya via WhatsApp</a>
                            <a class="detail-action-btn secondary" href="produk"><i class="bi bi-grid"></i> Kembali ke Produk</a>
                        </div>
                    </article>
                </div>
                <script type="application/json" id="initialProductData"><?= teakwave_json($product); ?></script>
            <?php endif; ?>
        </div>
    </section>

    <section class="section-space pt-3" id="marketplace" aria-labelledby="detail-marketplace-title">
        <div class="container text-center" data-content-slug="produk-detail-marketplace">
            <span class="market-title-pill reveal">Marketplace</span>
            <h2 class="section-title mb-4 reveal" id="detail-marketplace-title">Beli Produk Teakwave Secara Online</h2>
            <div class="market-mini-wrap"><div class="row g-3">
                <div class="col-md-4 reveal slide-left"><div class="market-card-mini"><p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p><a aria-label="Tokopedia Teakwave" class="market-btn" data-external-url="tokopedia" href="https://www.tokopedia.com/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-tokopedia.png" alt="Tokopedia" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
                <div class="col-md-4 reveal"><div class="market-card-mini"><p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></p><a aria-label="Shopee Teakwave" class="market-btn" data-external-url="shopee" href="https://shopee.co.id/teakwave" rel="noopener" target="_blank"><img src="assets/img/logo-shopee.png" alt="Shopee" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
                <div class="col-md-4 reveal slide-right"><div class="market-card-mini"><p>Ingin harga yang lebih kompetitif? Order melalui <strong>WhatsApp.</strong></p><a aria-label="WhatsApp Teakwave" class="market-btn" data-external-url="whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="assets/img/logo-whatsapp.png" alt="WhatsApp" width="640" height="200" loading="lazy" decoding="async"></a></div></div>
            </div></div>
        </div>
    </section>
</main>
<?php
$footerClass = 'pb-5 produk-footer-space';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
?>
<?php if ($product): ?>
<div aria-hidden="true" aria-labelledby="productImageModalTitle" class="modal fade product-modal" id="productImageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content"><div class="modal-body">
        <div class="modal-product-shell">
            <button aria-label="Tutup" class="btn-close" data-bs-dismiss="modal" type="button"></button>
            <h2 class="modal-product-title" id="productImageModalTitle"><?= teakwave_escape($product['name']); ?></h2>
            <div class="modal-product-stage">
                <button aria-label="Foto sebelumnya" class="modal-gallery-nav modal-gallery-prev" id="modalProductPrev" type="button"><i class="bi bi-chevron-left"></i></button>
                <img alt="Foto <?= teakwave_escape($product['name']); ?> diperbesar" class="modal-product-image" id="modalProductImage" src="<?= teakwave_escape($product['images'][0]); ?>" width="720" height="720">
                <button aria-label="Foto berikutnya" class="modal-gallery-nav modal-gallery-next" id="modalProductNext" type="button"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="modal-product-counter" id="modalProductCounter"></div>
        </div>
    </div></div></div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
