-- Tambahkan akses Backup & Restore Database untuk Super Admin.
-- Aman dijalankan berulang kali karena menggunakan INSERT IGNORE.

INSERT IGNORE INTO role_permissions (role_id, page_key)
SELECT id, 'backup-restore'
FROM roles
WHERE id = 1 OR slug = 'super-admin';
