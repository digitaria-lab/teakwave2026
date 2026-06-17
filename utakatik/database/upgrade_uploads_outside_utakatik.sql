USE digitaria_dashboard;

-- Pindahkan path file upload lama dari /utakatik/assets/uploads ke /uploads di root project.
-- Setelah menjalankan SQL ini, pastikan file fisik sudah berada di folder root: uploads/

UPDATE products
SET image = REPLACE(image, 'utakatik/assets/uploads/', '../uploads/')
WHERE image LIKE 'utakatik/assets/uploads/%';

UPDATE products
SET image = REPLACE(image, 'assets/uploads/', '../uploads/')
WHERE image LIKE 'assets/uploads/%';

UPDATE product_images
SET image_path = REPLACE(image_path, 'utakatik/assets/uploads/', '../uploads/')
WHERE image_path LIKE 'utakatik/assets/uploads/%';

UPDATE product_images
SET image_path = REPLACE(image_path, 'assets/uploads/', '../uploads/')
WHERE image_path LIKE 'assets/uploads/%';

UPDATE content_images
SET image_path = REPLACE(image_path, 'utakatik/assets/uploads/', '../uploads/')
WHERE image_path LIKE 'utakatik/assets/uploads/%';

UPDATE content_images
SET image_path = REPLACE(image_path, 'assets/uploads/', '../uploads/')
WHERE image_path LIKE 'assets/uploads/%';

UPDATE brands
SET logo = REPLACE(logo, 'utakatik/assets/uploads/', '../uploads/')
WHERE logo LIKE 'utakatik/assets/uploads/%';

UPDATE brands
SET logo = REPLACE(logo, 'assets/uploads/', '../uploads/')
WHERE logo LIKE 'assets/uploads/%';

UPDATE banners
SET image = REPLACE(image, 'utakatik/assets/uploads/', '../uploads/')
WHERE image LIKE 'utakatik/assets/uploads/%';

UPDATE banners
SET image = REPLACE(image, 'assets/uploads/', '../uploads/')
WHERE image LIKE 'assets/uploads/%';

UPDATE media_files
SET file_path = REPLACE(file_path, 'utakatik/assets/uploads/', '../uploads/')
WHERE file_path LIKE 'utakatik/assets/uploads/%';

UPDATE media_files
SET file_path = REPLACE(file_path, 'assets/uploads/', '../uploads/')
WHERE file_path LIKE 'assets/uploads/%';

UPDATE users
SET avatar = REPLACE(avatar, 'utakatik/assets/uploads/', '../uploads/')
WHERE avatar LIKE 'utakatik/assets/uploads/%';

UPDATE users
SET avatar = REPLACE(avatar, 'assets/uploads/', '../uploads/')
WHERE avatar LIKE 'assets/uploads/%';

UPDATE website_settings
SET setting_value = REPLACE(setting_value, 'utakatik/assets/uploads/', '../uploads/')
WHERE setting_key = 'favicon' AND setting_value LIKE 'utakatik/assets/uploads/%';

UPDATE website_settings
SET setting_value = REPLACE(setting_value, 'assets/uploads/', '../uploads/')
WHERE setting_key = 'favicon' AND setting_value LIKE 'assets/uploads/%';
