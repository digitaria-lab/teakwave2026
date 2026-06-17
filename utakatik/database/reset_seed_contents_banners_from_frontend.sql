USE digitaria_dashboard;

-- RESET CONTENT DAN BANNER
-- Script ini menghapus semua content dan banner lama,
-- lalu memasukkan text frontend dan banner frontend ke database.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM content_images;
DELETE FROM contents;
DELETE FROM banners;

ALTER TABLE content_images AUTO_INCREMENT = 1;
ALTER TABLE contents AUTO_INCREMENT = 1;
ALTER TABLE banners AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO contents (title, slug, type, body, status) VALUES
('Index - Hero Statistik','index-hero-stats','section','<div class="row text-center g-0">
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
</div>','published'),
('Index - About Us','index-about','section','<span class="section-label">About Us</span>
<h2 class="section-title mb-3">Mengenal Teakwave</h2>
<p>Teakwave adalah distributor perangkat jaringan yang berdiri sejak tahun 2014 dan berpusat di
                        Jakarta.</p>
<p>Kami menyediakan berbagai produk jaringan berkualitas dari brand terpercaya seperti Ubiquiti dan
                        MikroTik. Hingga saat ini, Teakwave telah mendistribusikan lebih dari 15.000 perangkat jaringan
                        ke berbagai wilayah di Indonesia.</p>','published'),
('Index - Mengapa Memilih Teakwave','index-why-choose','section','<h2 class="section-title text-center mb-5">Mengapa Memilih Teakwave?</h2>
<div class="row g-4">
<div class="col-md-6 reveal slide-left">
<div class="feature-item">
<div class="feature-icon"><img src="../assets/img/produk-original.png"/></div>
<div>
<h5>Produk Original</h5>
<p>Semua perangkat jaringan yang kami distribusikan merupakan produk asli dari brand
                                    resmi.</p>
</div>
</div>
</div>
<div class="col-md-6 reveal slide-right">
<div class="feature-item">
<div class="feature-icon"><img src="../assets/img/harga-kompetitif.png"/></div>
<div>
<h5>Harga Kompetitif</h5>
<p>Kami menyediakan harga terbaik bagi reseller, perusahaan, maupun pengguna akhir.</p>
</div>
</div>
</div>
<div class="col-md-6 reveal slide-left">
<div class="feature-item">
<div class="feature-icon"><img src="../assets/img/garansi-resmi.png"/></div>
<div>
<h5>Garansi Resmi</h5>
<p>Setiap produk dilengkapi garansi dengan aturan dan ketentuan yang jelas.</p>
</div>
</div>
</div>
<div class="col-md-6 reveal slide-right">
<div class="feature-item">
<div class="feature-icon"><img src="../assets/img/distribusi-nasional.png"/></div>
<div>
<h5>Distribusi Nasional</h5>
<p>Pengiriman produk menjangkau seluruh wilayah Indonesia.</p>
</div>
</div>
</div>
</div>','published'),
('Index - Judul Produk Best Seller','index-products-title','section','Produk Best Seller','published'),
('Index - Testimonials','index-testimonials','section','<div class="row g-4 align-items-center">
<div class="col-lg-4 reveal slide-left">
<span class="section-label">Testimonials</span>
<h2 class="section-title mb-3">Apa Kata Mereka</h2>
<p>Banyak pelanggan telah mempercayakan kebutuhan perangkat jaringan mereka kepada Teakwave.</p>
</div>
<div class="col-lg-4 reveal">
<div class="testimonial-card">
<p>“Pelayanan cepat, RMA bagus, harga kompetitif.”</p>
<small>Bapak Donny — Gloria Net</small>
<div class="stars mt-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
</div>
</div>
<div class="col-lg-4 reveal slide-right">
<div class="testimonial-card">
<p>“Selain harga yang bersaing, RMA pun cepat dan bagus.”</p>
<small>Bapak David — DS Comp</small>
<div class="stars mt-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
</div>
</div>
</div>','published'),
('Index - Marketplace','index-marketplace','section','<span class="section-label reveal slide-left">Marketplace</span>
<h2 class="section-title mb-4 reveal slide-right">Beli Produk Teakwave Secara Online</h2>
<div class="marketplace-list text-start">
<div class="market-row reveal slide-left">
<div>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></div>
<a class="market-btn tokopedia" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-tokopedia.png"/></a>
</div>
<div class="market-row reveal slide-right">
<div>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></div>
<a class="market-btn shopee" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-shopee.png"/></a>
</div>
<div class="market-row reveal slide-left">
<div>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></div>
<a class="market-btn whatsapp" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="../assets/img/logo-whatsapp.png"/></a>
</div>
</div>','published'),
('Footer - Contact','footer-contact','footer','<span class="section-label">Contact</span>
<h2 class="fw-bold mb-3">Hubungi Kami</h2>
<p class="mb-2"><i class="bi bi-geo-alt-fill contact-icon"></i>Kompleks Harco Elektronik Mangga
                            Dua Blok H-6 Raya Jl. Mangga Dua Dalam Jakarta, DKI Jakarta 10730</p>
<p class="mb-2"><i class="bi bi-envelope-fill contact-icon"></i><a href="mailto:sales@teakwave.com">sales@teakwave.com</a></p>
<p class="mb-0"><i class="bi bi-telephone-fill contact-icon"></i><a href="tel:0216121005">(021)
                                6121 005</a></p>','published'),
('Footer - Company','footer-company','footer','<h2 class="fw-bold mb-3">Teakwave</h2>
<p>Distributor perangkat jaringan nirkabel dan internet berkualitas untuk berbagai kebutuhan
                            jaringan di Indonesia.</p>
<div class="social mt-3">
<a aria-label="Instagram" href="#"><i class="bi bi-instagram"></i></a>
<a aria-label="Facebook" href="#"><i class="bi bi-facebook"></i></a>
</div>','published'),
('Profil - Tentang Kami','tentang-kami','page','<p>Didirikan pada tahun 2014, Teakwave adalah perusahaan spesialis di bidang perangkat jaringan dan
                        nirkabel yang berbasis di Jakarta. Sebagai distributor resmi berbagai produk wireless, kami
                        menyediakan beragam perangkat jaringan berkualitas tinggi dengan harga yang terjangkau.</p>
<p>Selain itu, kami juga memberikan layanan konsultasi teknis untuk membantu pelanggan memahami
                        fitur dan manfaat dari produk yang kami tawarkan. Tim kami siap membantu dalam proses
                        troubleshooting apabila dibutuhkan. Untuk memastikan solusi yang tepat, kami juga dapat
                        melakukan pengecekan awal menggunakan link planner guna menilai kesesuaian produk dengan
                        kebutuhan sistem Anda.</p>
<p>Dengan komitmen untuk menghadirkan solusi terbaik bagi kebutuhan jaringan dan nirkabel, Teakwave
                        menjadi pilihan yang tepat untuk mengoptimalkan performa infrastruktur Anda.</p>','published'),
('Profil - Authorized Distributor','profile-authorized','section','<div class="auth-label">Authorized Distributor of:</div>
<div aria-label="Brand distributor resmi" class="auth-brand-row">
<span class="auth-logo voltech"><img src="../assets/img/logo-voltech.png"/></span>
<span class="auth-logo voltech"><img src="../assets/img/logo-vsol.png"/></span>
<span class="auth-logo voltech"><img src="../assets/img/logo-ubiquiti.png"/></span>
<span class="auth-logo voltech"><img src="../assets/img/logo-mikrotik.png"/></span>
</div>','published'),
('Kontak - Informasi Kontak','kontak','page','<h2 class="contact-info-title">Kami Siap Membantu Kebutuhan Jaringan Anda</h2>
<p>Punya pertanyaan, butuh konsultasi produk, atau ingin mendapatkan penawaran terbaik?<br/>Tim
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
<a href="https://wa.me/6289527932474" rel="noopener" target="_blank">+6289527932474</a>
</div>
</div>
<div class="contact-method reveal slide-right">
<div class="contact-method-icon"><i class="bi bi-instagram"></i></div>
<div>
<strong>Instagram</strong>
<a href="https://instagram.com/teak.wave" rel="noopener" target="_blank">teak.wave</a>
</div>
</div>
</div>','published'),
('Produk - Marketplace','produk-marketplace','section','<span class="market-title-pill reveal">Marketplace</span>
<h2 class="section-title mb-4 reveal">Beli Produk Teakwave Secara Online</h2>
<div class="market-mini-wrap">
<div class="row g-3">
<div class="col-md-4 reveal slide-left">
<div class="market-card-mini">
<p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>
<a class="market-btn" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-tokopedia.png"/></a>
</div>
</div>
<div class="col-md-4 reveal">
<div class="market-card-mini">
<p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>
</p>
<a class="market-btn" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-shopee.png"/></a>
</div>
</div>
<div class="col-md-4 reveal slide-right">
<div class="market-card-mini">
<p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>
<a class="market-btn" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="../assets/img/logo-whatsapp.png"/></a>
</div>
</div>
</div>
</div>','published'),
('Produk - Brands','produk-brands','section','<span class="brand-title-pill reveal">Our Brands</span>
<div class="brand-grid">
<div class="row g-3">
<div class="col-md-6 reveal slide-left">
<div class="brand-card">
<div class="brand-logo-text ubiquiti"><img src="../assets/img/logo-ubiquiti.png"/>
</div>
<p>Brand global yang dikenal dengan performa tinggi dan stabilitas untuk jaringan
                                    wireless, banyak digunakan oleh ISP dan enterprise.</p>
</div>
</div>
<div class="col-md-6 reveal slide-right">
<div class="brand-card">
<div class="brand-logo-text vsol"><img src="../assets/img/logo-vsol.png"/></div>
<p>Solusi perangkat GPON dan fiber optic yang handal dan efisien, cocok untuk kebutuhan
                                    ISP dan pengembangan jaringan FTTH.</p>
</div>
</div>
<div class="col-md-6 reveal slide-left">
<div class="brand-card">
<div class="brand-logo-text mikrotik"><img src="../assets/img/logo-mikrotik.png"/></div>
<p>Perangkat jaringan dengan fleksibilitas tinggi, fitur lengkap, dan harga kompetitif,
                                    menjadi pilihan utama para profesional jaringan.</p>
</div>
</div>
<div class="col-md-6 reveal slide-right">
<div class="brand-card">
<div class="brand-logo-text voltech"><img src="../assets/img/logo-voltech.png"/></div>
<p>Brand perangkat jaringan yang menghadirkan solusi modern untuk kebutuhan
                                    konektivitas.</p>
</div>
</div>
</div>
</div>','published'),
('Produk Detail - Marketplace','produk-detail-marketplace','section','<span class="market-title-pill reveal">Marketplace</span>
<h2 class="section-title mb-4 reveal">Beli Produk Teakwave Secara Online</h2>
<div class="market-mini-wrap">
<div class="row g-3">
<div class="col-md-4 reveal slide-left">
<div class="market-card-mini">
<p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>
<a class="market-btn" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-tokopedia.png"/></a>
</div>
</div>
<div class="col-md-4 reveal">
<div class="market-card-mini">
<p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>
</p>
<a class="market-btn" href="#" rel="noopener" target="_blank"><img src="../assets/img/logo-shopee.png"/></a>
</div>
</div>
<div class="col-md-4 reveal slide-right">
<div class="market-card-mini">
<p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>
<a class="market-btn" href="https://wa.me/6282112345678" rel="noopener" target="_blank"><img src="../assets/img/logo-whatsapp.png"/></a>
</div>
</div>
</div>
</div>','published');

INSERT INTO banners (title, image, link_url, placement, status, start_date, end_date) VALUES
('Homepage Banner 1','../assets/img/banner-home-1.png','','homepage','active',NULL,NULL),
('Homepage Banner 2','../assets/img/banner-home-2.png','','homepage','active',NULL,NULL),
('Homepage Banner 3','../assets/img/banner-home-3.png','','homepage','active',NULL,NULL),
('Profile Header Banner','../assets/img/banner-profil.png','','profile','active',NULL,NULL),
('Product Header Banner','../assets/img/banner-profil.png','','product','active',NULL,NULL),
('Contact Header Banner','../assets/img/banner-kontak.png','','contact','active',NULL,NULL);

SELECT 'contents' AS table_name, COUNT(*) AS total FROM contents
UNION ALL
SELECT 'banners' AS table_name, COUNT(*) AS total FROM banners;
