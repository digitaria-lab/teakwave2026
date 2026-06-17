USE digitaria_dashboard;

-- FIXED SQL TANPA information_schema
-- Jalankan SQL ini jika versi sebelumnya error:
-- #1044 - Access denied for user ... to database 'information_schema'

-- 1. Tambahkan kolom best seller.
-- Jika kolom is_best_seller sudah pernah dibuat, lewati/baris ini jangan dijalankan lagi.
ALTER TABLE products
ADD COLUMN is_best_seller TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

-- 2. Reset semua produk menjadi bukan best seller.
UPDATE products
SET is_best_seller = 0;

-- 3. Jadikan 10 produk pertama sebagai best seller awal.
UPDATE products
SET is_best_seller = 1
ORDER BY id ASC
LIMIT 10;

-- 4. Cek hasil.
SELECT id, name, is_best_seller
FROM products
ORDER BY is_best_seller DESC, id ASC
LIMIT 20;
