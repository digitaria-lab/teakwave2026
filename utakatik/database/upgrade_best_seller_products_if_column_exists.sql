USE digitaria_dashboard;

-- Jalankan file ini HANYA jika kolom is_best_seller sudah ada
-- atau jika file utama muncul error Duplicate column name 'is_best_seller'.

UPDATE products
SET is_best_seller = 0;

UPDATE products
SET is_best_seller = 1
ORDER BY id ASC
LIMIT 10;

SELECT id, name, is_best_seller
FROM products
ORDER BY is_best_seller DESC, id ASC
LIMIT 20;
