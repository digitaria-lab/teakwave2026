FITUR BACKUP & RESTORE DATABASE
=================================

File utama:
- backup-restore.php
- includes/database-backup.php
- database/upgrade_backup_restore_permission.sql

INSTALASI PADA DATABASE YANG SUDAH BERJALAN
1. Upload semua file proyek yang telah diperbarui.
2. Login ke dashboard utakatik sebagai Super Admin.
3. Buka menu Backup & Restore.
4. Pada panel "Upgrade Izin Backup & Restore", klik "Jalankan Upgrade Sekarang".
5. Dashboard akan menjalankan file berikut memakai koneksi database aplikasi:
   utakatik/database/upgrade_backup_restore_permission.sql
6. Tidak diperlukan akses phpMyAdmin, Adminer, terminal, atau MySQL client.

CARA KERJA UPGRADE DASHBOARD
- Hanya Super Admin yang dapat menjalankan upgrade.
- Form dilindungi token CSRF.
- Sistem hanya menerima file upgrade lokal yang sudah disertakan dalam proyek.
- Isi file diperiksa agar hanya menambahkan izin backup-restore.
- Query memakai INSERT IGNORE sehingga aman dijalankan berulang kali.
- Status pemasangan diperiksa dan ditampilkan langsung pada halaman.

CATATAN KEAMANAN
- Super Admin selalu dapat mengakses halaman ini, termasuk sebelum izin database ditambahkan.
- Role lain hanya dapat mengakses jika izin "Backup & Restore Database" dicentang pada User Level.
- Setiap restore membuat backup pengaman otomatis sebelum data diubah.
- File backup server disimpan di utakatik/storage/database-backups dan dilindungi .htaccess.
- Restore menerima file .sql maksimal 64 MB dan memblokir perintah lintas database/administrasi server.
- Untuk database besar, sesuaikan upload_max_filesize, post_max_size, max_execution_time, dan memory_limit pada server.
