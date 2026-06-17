DIGITARIA ADMIN DASHBOARD - PHP MYSQL BOOTSTRAP

Fitur:
- Login dan logout
- Dashboard statistik seperti referensi
- User management
- User level / role management
- Content management
- Product catalog management
- File / image management
- Banner ads management
- Bootstrap 5 responsive dashboard

Cara install:
1. Buat database MySQL: digitaria_dashboard
2. Import: database/schema.sql
3. Edit koneksi: config/database.php
4. Jalankan via localhost, contoh:
   http://localhost/digitaria_admin_dashboard_php_mysql/

Default login:
Email    : admin@digitaria.id
Password : admin123


PERBAIKAN LOGIN:
Jika email/password tetap salah setelah import database lama:
1. Buka browser:
   http://localhost/digitaria_admin_dashboard_php_mysql/reset-admin-password.php

2. Setelah muncul pesan berhasil, login dengan:
   Email    : admin@digitaria.id
   Password : admin123

3. Hapus file reset-admin-password.php setelah digunakan demi keamanan.

Atau jalankan SQL manual:
UPDATE users
SET password = '$2y$10$ZrV1OuhN5SxHQm7QpWbqeuYP4Z2xClL35P6u2Nr.TgF1iGhNtYIXK',
    status = 'active'
WHERE email = 'admin@digitaria.id';


UPDATE PRODUCT MODULE:
- Product bisa upload lebih dari 1 gambar melalui Gallery Images.
- Product memiliki Brand dan Category.
- List product memiliki search berdasarkan nama produk, brand, atau kategori.
- Product bisa edit dari list.
- Description product memakai WYSIWYG editor TinyMCE.
- Tambahan menu Brand Management: add/edit/delete brand.
- Tambahan menu Category Management: add/edit/delete kategori.

Jika sudah pernah import database lama, jalankan:
database/upgrade_product_module.sql


UPDATE PRODUCT PAGE SPLIT:
- products.php sekarang hanya untuk List Product.
- product-add.php untuk Add Product.
- product-edit.php untuk Edit Product.
- Sidebar Product memiliki submenu:
  1. List Product
  2. Add Product
  3. Edit Product
- Tombol Edit di list product langsung menuju product-edit.php?id=ID.


UPDATE WYSIWYG EDITOR:
- TinyMCE sudah diganti dengan QuillJS.
- QuillJS digunakan melalui CDN:
  https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js
- Field deskripsi product tetap tersimpan sebagai HTML ke database.


UPDATE ROLE PERMISSIONS & SIDEBAR:
- Sidebar sekarang memiliki scroll jika menu melebihi tinggi layar.
- User Level bisa add/edit akses halaman.
- Hak akses disimpan di table role_permissions.
- Menu sidebar otomatis hanya menampilkan halaman yang diizinkan.
- Halaman yang tidak diizinkan akan menampilkan halaman "Akses Ditolak".

Jika sudah pakai database lama, jalankan:
database/upgrade_role_permissions.sql


UPDATE PRODUCT & CONTENT LIST:
- Product list sekarang 3 kolom.
- Product list memakai paging dengan limit 12 produk per halaman.
- Content management dipisah seperti Product:
  1. contents.php = List Content
  2. content-add.php = Add Content
  3. content-edit.php = Edit Content
- Sidebar Content sekarang memiliki submenu seperti Product.
- Permission User Level ikut diperbarui:
  contents-list, contents-add, contents-edit.

Jika menggunakan database lama, jalankan:
database/upgrade_content_split_and_paging.sql


UPDATE SIDEBAR & SECURITY:
- Sidebar bisa di-minimise dan expand dari tombol di topbar.
- State minimise tersimpan di localStorage.
- Add Product dan Edit Product disempurnakan layoutnya.
- Form product menggunakan CSRF token.
- Submit product menggunakan PDO prepared statement dan transaksi database.
- Upload file divalidasi extension, MIME type, ukuran maksimal 5MB, dan validitas image.
- Deskripsi produk disanitasi untuk mengurangi risiko XSS.
- Delete product dan delete gallery image sekarang memakai POST + CSRF, bukan GET link.

Catatan:
- Query sudah memakai prepared statement untuk menghindari SQL injection.
- Untuk keamanan produksi, tetap aktifkan HTTPS, permission folder upload yang benar, dan nonaktifkan display_errors di server.


UPDATE PROFILE & CONTENT EDITOR:
- Ditambahkan halaman profile.php untuk edit nama dan photo profil.
- Sidebar menampilkan link Edit Profile.
- Avatar user tersimpan di kolom users.avatar.
- Add/Edit Content sekarang memakai WYSIWYG editor QuillJS seperti product.
- Body content disanitasi sebelum disimpan ke database.
- Form add/edit content memakai CSRF token.

Jika memakai database lama, jalankan:
database/upgrade_profile_content_editor.sql


UPDATE CONTENT MULTIPLE IMAGES:
- Add Content sekarang bisa upload multiple image.
- Edit Content bisa tambah multiple image.
- Edit Content bisa delete image satu per satu.
- Content list menampilkan jumlah images.
- Gambar konten disimpan di table content_images.
- Upload image memakai validasi yang sama dengan upload product: extension, MIME type, ukuran maksimal, dan validitas image.

Jika menggunakan database lama, jalankan:
database/upgrade_content_multiple_images.sql


UPDATE ACTIVITY LOGS:
- Ditambahkan halaman logs.php untuk melihat semua aktivitas user.
- Ditambahkan table activity_logs.
- Sidebar menampilkan menu Activity Logs jika user level memiliki permission logs.
- Aktivitas yang dicatat meliputi:
  login, logout, create/update/delete product, content, user, role, brand, category, file, banner, dan edit profile.
- Logs memiliki search dan paging 30 data per halaman.

Jika memakai database lama, jalankan:
database/upgrade_activity_logs.sql

Jika permission belum muncul, pastikan user level memiliki akses "Activity Logs" dari menu User Level.


UPDATE SAMPLE DATA & PAGING:
- Database fresh install sekarang berisi:
  - 20 sample content
  - 100 sample product
- Ditambahkan file sample untuk database lama:
  database/sample_data_20_contents_100_products.sql
- List Product sudah memakai paging limit 12 produk per halaman.
- List Content sudah memakai paging limit 12 content per halaman.

Cara update database lama:
1. Import database/upgrade_activity_logs.sql dan upgrade lain jika belum.
2. Import database/sample_data_20_contents_100_products.sql.


UPDATE PRODUCT VIEW & MOBILE SIDEBAR:
- List product card view sekarang 4 kolom pada layar besar.
- Limit product per halaman menjadi 20.
- Ditambahkan pilihan view:
  1. Card 4 kolom
  2. Table listing
- Paging product tetap aktif dan mempertahankan pilihan view/search.
- Sidebar mobile disempurnakan:
  - tombol buka sidebar
  - tombol tutup sidebar di dalam sidebar
  - klik overlay untuk menutup
  - tombol Escape untuk menutup
  - sidebar otomatis tertutup setelah klik menu pada mobile


UPDATE CONTENT VIEW & UI COMPACT:
- List Content sekarang memiliki pilihan view:
  1. Card
  2. Table
- View dan paging pada Content mengikuti pola List Product.
- Tampilan keseluruhan dibuat lebih compact dengan font size dikurangi sekitar 2px.
- Ditambahkan animasi hover untuk button, link, sidebar menu, pagination, product card, dan content card.


UPDATE VISIBLE FORM SUBMIT:
- Halaman add/edit product dan add/edit content disempurnakan agar tombol Save/Submit terlihat.
- Tombol Save ditambahkan di bagian header form.
- Tombol Save/Submit bagian bawah dibuat sticky action bar.
- Tinggi Quill editor dikurangi agar form tidak terlalu panjang.
- Form action dibuat responsive untuk mobile.


UPDATE WYSIWYG FULLSCREEN:
- WYSIWYG editor QuillJS pada Add/Edit Product dan Add/Edit Content sekarang memiliki tombol Fullscreen.
- Tombol Fullscreen berada di atas editor.
- Editor bisa keluar fullscreen dengan tombol Exit Fullscreen atau tombol ESC.
- Fullscreen responsive untuk desktop dan mobile.


UPDATE VIEW MODE COOKIE:
- List Product sekarang menyimpan pilihan view Card/Table ke cookie `product_view_mode`.
- List Content sekarang menyimpan pilihan view Card/Table ke cookie `content_view_mode`.
- Jika user kembali ke halaman tersebut, tampilan terakhir otomatis digunakan.
- Masa simpan cookie: 30 hari.


UPDATE MAXIMUM HTML CONTENT SECURITY:
- Sanitasi HTML diperkuat secara signifikan.
- Jika Composer/HTML Purifier tersedia, sistem otomatis memakai HTML Purifier.
- Jika HTML Purifier belum diinstall, sistem memakai fallback DOMDocument whitelist sanitizer.
- Tag HTML yang diizinkan dibatasi untuk konten WYSIWYG:
  p, br, strong, b, em, i, u, s, ul, ol, li, blockquote, pre, code,
  h1-h4, table, thead, tbody, tr, th, td, a.
- Atribut yang diizinkan hanya atribut aman untuk link:
  href, title, target, rel.
- Event handler seperti onclick/onerror, style inline, script, iframe,
  object, embed, form, input, meta, link, dan javascript: diblokir.
- Payload HTML dibatasi maksimal 250KB.
- Text biasa seperti title, SKU, dan name memakai sanitize_plain_text().
- Session cookie diperkuat:
  HttpOnly, SameSite=Lax, Secure otomatis saat HTTPS, strict mode.
- Security headers ditambahkan:
  X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
  Permissions-Policy, HSTS saat HTTPS.
- Folder uploads dilindungi dengan .htaccess agar file script tidak dieksekusi.
- Upload file tetap divalidasi extension, MIME type, ukuran, dan validitas image.

REKOMENDASI PRODUKSI:
1. Jalankan Composer:
   composer install

2. Pastikan folder berikut writable:
   cache/htmlpurifier
   assets/uploads

3. Gunakan HTTPS.

4. Matikan display_errors di production.

5. Tetap gunakan prepared statement seperti yang sudah diterapkan.


UPDATE WEBSITE FRONT SETTINGS:
- Ditambahkan halaman website-settings.php untuk konfigurasi website front.
- Field yang bisa diatur:
  1. Nama Web
  2. Meta Title
  3. Meta Description
  4. Meta Keyword
  5. Upload/Edit Favicon
  6. Timezone / Setting Jam
  7. Date Format
  8. Time Format
- Setting disimpan di table website_settings.
- Ditambahkan permission baru: website-settings.
- Ditambahkan contoh pemakaian meta frontend:
  includes/front-meta-example.php

Jika memakai database lama, jalankan:
database/upgrade_front_website_settings.sql


UPDATE SEO FRIENDLY IMAGE FILE NAME:
- Upload image Product sekarang memakai nama file berdasarkan judul produk.
  Contoh:
  website-company-profile-product-main-20260515123010-a1b2c3.jpg
  website-company-profile-product-gallery-1-20260515123011-d4e5f6.jpg
- Upload image Content sekarang memakai nama file berdasarkan judul content.
  Contoh:
  artikel-digital-marketing-content-image-1-20260515123012-a1b2c3.jpg
- Nama file tetap diberi timestamp dan token unik agar tidak overwrite.
- Fungsi baru:
  seo_file_name()
- Fungsi upload_file() dan upload_multiple_files() sekarang mendukung parameter judul SEO.


UPDATE SEO FILE NAME ON EDIT:
- Saat update/edit Product, main image baru maupun existing akan di-rename mengikuti nama produk terbaru.
- Saat update/edit Product, seluruh gallery image existing juga ikut di-rename mengikuti nama produk terbaru.
- Saat update/edit Content, seluruh image content existing ikut di-rename mengikuti judul content terbaru.
- Upload image baru pada edit Product/Content tetap memakai nama SEO-friendly.
- Rename hanya berlaku untuk file lokal di folder assets/uploads/ agar aman.


UPDATE PRODUCT CARD IMAGE:
- Pada list product tampilan Card, gambar produk sekarang ditampilkan penuh.
- Image memakai object-fit: contain agar foto tidak terpotong.
- Tinggi area gambar disesuaikan agar foto terlihat lebih jelas.


UPDATE PROFILE PASSWORD & SUPER ADMIN RESET:
- Halaman Edit Profile sekarang memiliki form Edit Password.
- User dapat mengganti password sendiri dengan validasi password lama.
- Password baru minimal 8 karakter.
- Super Admin dapat reset password user lain yang levelnya di bawah Super Admin.
- Super Admin tidak bisa reset password Super Admin lain dari User Management.
- Reset password user lain memakai modal Bootstrap, CSRF token, password_hash(), dan activity log.


UPDATE ACTIVITY LOG PAGING:
- Activity Logs sekarang memakai paging dengan limit 25 data per halaman.
- Pagination dibuat lebih ringkas dengan format angka awal/akhir dan ellipsis.


UPDATE SHOW/HIDE PASSWORD:
- Semua field password sekarang memiliki tombol lihat/sembunyikan password.
- Berlaku untuk:
  1. Login page
  2. Add User
  3. Edit Profile / Edit Password
  4. Reset Password user oleh Super Admin
- Toggle memakai ikon eye / eye-slash Bootstrap Icons.


FIX LOGOUT REDIRECT LOOP:
- logout.php diperbaiki agar benar-benar menghapus session server dan cookie session browser.
- Setelah logout diarahkan ke login.php?logged_out=1.
- login.php disesuaikan agar parameter logged_out tidak memicu redirect loop ke dashboard.
- Jika browser masih menyimpan cookie lama, cukup refresh sekali atau clear cookie localhost.


LOGOUT LOOP FIX V2:
- Redirect diperbaiki dengan app_url(), sehingga auth/check.php tidak lagi mengarah ke /auth/login.php.
- logout.php sekarang membersihkan session cookie di beberapa kemungkinan path.
- login.php tidak akan redirect ke dashboard saat ada parameter logged_out.
- Jika masih loop, buka debug-session.php untuk melihat session/cookie aktif.
- Setelah update, clear cookie localhost sekali saja jika browser masih menyimpan cookie dari versi lama.


UPDATE FASTO-LIKE LAYOUT:
- Layout dashboard diubah mengikuti referensi Fasto:
  1. Sidebar kiri berwarna ungu.
  2. Brand/merk ditampilkan di kiri atas sidebar.
  3. Tombol + New Project ditambahkan di sidebar.
  4. Profil user dipindahkan ke topbar kanan atas.
  5. Topbar dibuat putih, sticky, dengan search, message, notification, dan profile dropdown.
  6. Sidebar tetap bisa minimize/expand.
  7. Mobile sidebar tetap bisa buka/tutup dengan overlay.
- Nama brand sidebar otomatis mengambil Website Settings `site_name` jika tersedia.


FIX REDIRECT LOOP AFTER FASTO LAYOUT:
- login.php diperbaiki. Sebelumnya redirect ke index.php berjalan tanpa pengecekan session karena blok if kehilangan kurung.
- Login page sekarang hanya redirect ke dashboard jika session user benar-benar aktif.
- Setelah logout, login.php?logged_out=1 membersihkan session dan tidak memicu redirect loop.


UPDATE TOPBAR SEARCH & SIDEBAR REFINEMENT:
- Menu Edit Profile di sidebar dihilangkan.
- Tombol New Project diganti menjadi New Content dan diarahkan ke content-add.php.
- Search topbar diarahkan ke search.php.
- Search menampilkan hasil dari Product dan Content berdasarkan keyword pada nama/judul, isi/deskripsi, SKU, brand, kategori, slug, dan type.
- Icon email dan alert di sebelah search dihilangkan.
- Hamburger menu topbar diperbaiki agar tidak double.
- Saat sidebar diperkecil, nama menu muncul sebagai tooltip saat hover icon.


UPDATE SIDEBAR TOGGLE ICON:
- Icon hamburger di topbar berubah menjadi icon panah kanan saat sidebar dikecilkan.
- Saat sidebar dibuka/expand, icon kembali menjadi hamburger.
- Ditambahkan animasi hover pada tombol hamburger/panah.


UPDATE CSRF, DATATABLES, SIDEBAR:
- Error CSRF setelah logout diperbaiki. login.php?logged_out=1 tidak lagi menghapus cookie session baru yang dibutuhkan token CSRF.
- List Product dan List Content mode table memakai DataTables untuk search dan sorting.
- Menu Edit Profile di sidebar kiri dihapus.
- Urutan sidebar diubah: Dashboard, Content, Product, Brand, Category, lalu menu lain.
- Submenu memakai animasi slide saat dibuka/ditutup.
- New Project tetap menjadi New Content dan mengarah ke content-add.php.


FIX DATATABLES TN/4:
- Error DataTables "Requested unknown parameter '1' for row 0, column 1" diperbaiki.
- Penyebabnya adalah row kosong memakai <td colspan="8"> di dalam <tbody>.
- DataTables tidak cocok dengan colspan row di tbody saat search/sorting.
- Empty state sekarang ditampilkan di luar table.
- Script DataTables juga membersihkan invalid row sebelum inisialisasi.


UPDATE TABLE ROW HOVER:
- Ditambahkan efek hover pada row tabel List Product dan List Content.
- Row hover memiliki background gradient tipis, shadow, efek naik, radius cell kiri/kanan, dan aksen kiri.
- Icon/gambar dalam row ikut diberi animasi scale saat hover.
- Kompatibel dengan DataTables.


UPDATE UPLOAD SETTINGS & PROFILE ANIMATION:
- Website Settings sekarang memiliki pengaturan:
  1. Maksimal file size upload dalam MB.
  2. Extension yang boleh diupload.
- Setting upload disimpan di table website_settings:
  upload_max_filesize_mb
  upload_allowed_extensions
- Validasi server-side upload mengikuti setting tersebut.
- Validasi client-side menampilkan alert jika file terlalu besar atau extension tidak sesuai.
- Alert berlaku untuk form upload product, content, media, banner, avatar, dan favicon.
- Menu profile di topbar diberi animasi dropdown, hover, dan avatar scale.
- Ditambahkan SQL upgrade:
  database/upgrade_upload_settings.sql


UPDATE PROFILE UPLOAD ALERT & PROFILE MENU ANIMATION:
- Alert validasi upload sekarang dipastikan aktif pada Edit Profile/avatar.
- Input avatar diberi class upload-validate-image agar validasi extension dan filesize mengikuti Website Settings.
- Alert upload dibuat lebih jelas dengan animasi masuk.
- Profile kanan atas diberi animasi hover.
- Profile kanan atas diberi animasi klik.
- Dropdown submenu profile diberi animasi keluar/masuk dan stagger pada item menu.


UPDATE LOGIN ANIMATED GRADIENT:
- Login page sekarang memiliki animasi background gradasi.
- Ditambahkan animated gradient, floating blob, dan glass effect pada login card.
- Animasi otomatis berhenti jika user mengaktifkan reduced motion pada sistem.
