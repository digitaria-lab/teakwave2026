<?php
$pageTitle = 'Kontak Teakwave | Konsultasi dan Penawaran Produk Jaringan';
$metaDescription = 'Hubungi tim Teakwave untuk konsultasi perangkat jaringan, informasi stok, kebutuhan proyek, dan penawaran produk melalui email, telepon, atau WhatsApp.';
$canonicalPath = 'kontak';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>
<main class="contact-page" id="main-content">
    <section class="contact-section">
        <div class="container contact-container">
            <div class="contact-hero-card">
                <div class="contact-hero-visual" data-banner-placement="contact"
                    style="background-image: url('<?= teakwave_escape(teakwave_asset_url('assets/img/banner-kontak.webp')); ?>'); background-size: cover; background-position: center center;">
                </div>
                <div class="contact-hero-title">
                    <h1>Kontak <em>Teakwave</em></h1>
                </div>
            </div>
            <div class="contact-info-block reveal slide-left" data-content-slug="kontak">
                <h2 class="contact-info-title">Kami Siap Membantu Kebutuhan Jaringan Anda</h2>
                <p>Punya pertanyaan, butuh konsultasi produk, atau ingin mendapatkan penawaran terbaik?<br />Tim
                    Teakwave siap membantu Anda dengan pelayanan yang cepat dan profesional.</p>
                <div class="contact-company">PT Makmur Jati Teknologi</div>
                <p>Kompleks Harco Elektronik Mangga Dua Blok H-6, Jl. Mangga Dua Raya, Jakarta Pusat 10730</p>
                <div class="contact-method-grid">
                    <div class="contact-method reveal slide-left">
                        <div class="contact-method-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <strong>Email</strong>
                            <a href="mailto:sales@teakwave.com">sales@teakwave.com</a>
                        </div>
                    </div>
                    <div class="contact-method reveal slide-right">
                        <div class="contact-method-icon"><i class="bi bi-globe2"></i></div>
                        <div>
                            <strong>Website</strong>
                            <a href="https://teakwave.com" rel="noopener" target="_blank">teakwave.com</a>
                        </div>
                    </div>
                    <div class="contact-method reveal slide-left">
                        <div class="contact-method-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <strong>WhatsApp Sales</strong>
                            <a data-external-url="whatsapp" href="https://wa.me/6289527932474" rel="noopener" target="_blank">+6289527932474</a>
                        </div>
                    </div>
                    <div class="contact-method reveal slide-right">
                        <div class="contact-method-icon"><i class="bi bi-instagram"></i></div>
                        <div>
                            <strong>Instagram</strong>
                            <a data-external-url="instagram" href="https://instagram.com/teak.wave" rel="noopener" target="_blank">teak.wave</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="purchase-card reveal slide-right" id="marketplace">
                <div>
                    <h2>Pembelian Produk</h2>
                    <p>Anda dapat membeli produk Teakwave melalui marketplace resmi atau langsung menghubungi tim
                        kami.</p>
                </div>
                <div aria-label="Link pembelian produk" class="purchase-actions">
                    <a aria-label="Tokopedia" class="purchase-action tokopedia" data-external-url="tokopedia" href="https://www.tokopedia.com/teakwave" rel="noopener"
                        target="_blank"><img src="assets/img/icon-tokopedia.png" alt="Tokopedia" width="320" height="320" loading="lazy" decoding="async"></a>
                    <a aria-label="Shopee" class="purchase-action shopee" data-external-url="shopee" href="https://shopee.co.id/teakwave" rel="noopener" target="_blank"><img src="assets/img/icon-shopee.png" alt="Shopee" width="320" height="320" loading="lazy" decoding="async"></a>
                    <a aria-label="WhatsApp" class="purchase-action whatsapp" data-external-url="whatsapp" href="https://wa.me/6289527932474"
                        rel="noopener" target="_blank"><img src="assets/img/icon-whatsapp.png" alt="WhatsApp" width="320" height="320" loading="lazy" decoding="async"></a>
                </div>
            </div>
            <div aria-hidden="true" class="contact-bottom-space"></div>
        </div>
    </section>
</main>
<?php
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>