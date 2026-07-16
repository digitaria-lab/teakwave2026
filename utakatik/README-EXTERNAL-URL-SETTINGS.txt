PENGATURAN URL FRONTEND
=======================

Lokasi dashboard:
  utakatik/website-settings.php

URL yang dapat diubah:
- Tokopedia
- Shopee
- WhatsApp
- Instagram
- Facebook

Tidak diperlukan ALTER TABLE atau akses phpMyAdmin. Saat tombol Save Settings ditekan,
key baru otomatis dibuat/diubah di tabel website_settings melalui INSERT ... ON DUPLICATE KEY UPDATE.

Frontend mengambil pengaturan melalui:
  api/settings.php

Setelah mengubah URL, lakukan hard refresh browser bila cache lama masih terlihat.
