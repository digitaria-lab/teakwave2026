USE digitaria_dashboard;

-- RESET PRODUK, BRAND, DAN KATEGORI
-- Script ini menghapus semua produk, kategori, dan brand lama,
-- lalu memasukkan ulang 55 produk sesuai listing frontend produk.html/assets/js/script.js.
-- Jalankan setelah backup database.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM product_images;
DELETE FROM products;
DELETE FROM brands;
DELETE FROM categories;

ALTER TABLE product_images AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE brands AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

SET @db_name = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'products'
  AND COLUMN_NAME = 'is_best_seller';

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE products ADD COLUMN is_best_seller TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
    'SELECT "Column is_best_seller already exists" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO brands (name, slug, description, status) VALUES
('Ubiquiti','ubiquiti','','active'),
('V-SOL','v-sol','','active'),
('Mikrotik','mikrotik','','active'),
('VOL.TECH','vol-tech','','active');

INSERT INTO categories (name, slug, description, status) VALUES
('Access Point','access-point','','active'),
('CCTV','cctv','','active'),
('Fiber Accessories','fiber-accessories','','active'),
('Media Converter','media-converter','','active'),
('OLT','olt','','active'),
('ONU / ONT','onu-ont','','active'),
('Perangkat Jaringan','perangkat-jaringan','','active'),
('Rack Accessories','rack-accessories','','active'),
('Router / Gateway','router-gateway','','active'),
('SFP Module','sfp-module','','active'),
('Switch','switch','','active'),
('Wireless Outdoor','wireless-outdoor','','active');

INSERT INTO products (brand_id, category_id, name, sku, price, image, stock, status, is_best_seller, description) VALUES
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='access-point' LIMIT 1),'UniFi U6 Lite Access Point','TW-UBIQUITI-001',0,'../produk/1.png',0,'active','<p>UniFi U6 Lite Access Point adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='access-point' LIMIT 1),'UniFi U6 Plus Indoor AP','TW-UBIQUITI-002',0,'../produk/2.png',0,'active','<p>UniFi U6 Plus Indoor AP adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='access-point' LIMIT 1),'UniFi U7 Pro WiFi AP','TW-UBIQUITI-003',0,'../produk/3.png',0,'active','<p>UniFi U7 Pro WiFi AP adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'UniFi Dream Router','TW-UBIQUITI-004',0,'../produk/4.png',0,'active','<p>UniFi Dream Router adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'UniFi Cloud Gateway Ultra','TW-UBIQUITI-005',0,'../produk/5.png',0,'active','<p>UniFi Cloud Gateway Ultra adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'UniFi Switch Lite 8 PoE','TW-UBIQUITI-006',0,'../produk/6.png',0,'active','<p>UniFi Switch Lite 8 PoE adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'UniFi Switch 24 PoE','TW-UBIQUITI-007',0,'../produk/7.png',0,'active','<p>UniFi Switch 24 PoE adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'EdgeRouter X Gigabit','TW-UBIQUITI-008',0,'../produk/1.png',0,'active','<p>EdgeRouter X Gigabit adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'LiteBeam 5AC Gen2','TW-UBIQUITI-009',0,'../produk/2.png',0,'active','<p>LiteBeam 5AC Gen2 adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'NanoBeam 5AC Bridge','TW-UBIQUITI-010',0,'../produk/3.png',0,'active','<p>NanoBeam 5AC Bridge adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'PowerBeam 5AC ISO','TW-UBIQUITI-011',0,'../produk/4.png',0,'active','<p>PowerBeam 5AC ISO adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'Rocket Prism AC Radio','TW-UBIQUITI-012',0,'../produk/5.png',0,'active','<p>Rocket Prism AC Radio adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'airMAX Sector Antenna','TW-UBIQUITI-013',0,'../produk/6.png',0,'active','<p>airMAX Sector Antenna adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='cctv' LIMIT 1),'UniFi Protect G5 Dome','TW-UBIQUITI-014',0,'../produk/7.png',0,'active','<p>UniFi Protect G5 Dome adalah produk cctv dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='ubiquiti' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'UniFi Flex Mini Switch','TW-UBIQUITI-015',0,'../produk/5.png',0,'active','<p>UniFi Flex Mini Switch adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL GPON ONU 1GE','TW-VSOL-016',0,'../produk/4.png',0,'active','<p>V-SOL GPON ONU 1GE adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL ONU WiFi AC1200','TW-VSOL-017',0,'../produk/7.png',0,'active','<p>V-SOL ONU WiFi AC1200 adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='perangkat-jaringan' LIMIT 1),'V-SOL HG323AC Dual Band','TW-VSOL-018',0,'../produk/5.png',0,'active','<p>V-SOL HG323AC Dual Band adalah produk perangkat jaringan dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL V2802RH Optical Unit','TW-VSOL-019',0,'../produk/4.png',0,'active','<p>V-SOL V2802RH Optical Unit adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL V2801SG Mini ONU','TW-VSOL-020',0,'../produk/2.png',0,'active','<p>V-SOL V2801SG Mini ONU adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='olt' LIMIT 1),'V-SOL OLT 4 Port GPON','TW-VSOL-021',0,'../produk/1.png',0,'active','<p>V-SOL OLT 4 Port GPON adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='olt' LIMIT 1),'V-SOL OLT 8 Port GPON','TW-VSOL-022',0,'../produk/2.png',0,'active','<p>V-SOL OLT 8 Port GPON adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='olt' LIMIT 1),'V-SOL OLT 16 Port Rack','TW-VSOL-023',0,'../produk/3.png',0,'active','<p>V-SOL OLT 16 Port Rack adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'V-SOL XPON Router WiFi','TW-VSOL-024',0,'../produk/4.png',0,'active','<p>V-SOL XPON Router WiFi adalah produk router / gateway dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL Fiber ONT Voice','TW-VSOL-025',0,'../produk/5.png',0,'active','<p>V-SOL Fiber ONT Voice adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'V-SOL PoE ONU Outdoor','TW-VSOL-026',0,'../produk/6.png',0,'active','<p>V-SOL PoE ONU Outdoor adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='perangkat-jaringan' LIMIT 1),'V-SOL CATV Optical Node','TW-VSOL-027',0,'../produk/7.png',0,'active','<p>V-SOL CATV Optical Node adalah produk perangkat jaringan dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='sfp-module' LIMIT 1),'V-SOL SFP GPON Module','TW-VSOL-028',0,'../produk/5.png',0,'active','<p>V-SOL SFP GPON Module adalah produk sfp module dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='v-sol' LIMIT 1),(SELECT id FROM categories WHERE slug='fiber-accessories' LIMIT 1),'V-SOL Optical Splitter 1:8','TW-VSOL-029',0,'../produk/4.png',0,'active','<p>V-SOL Optical Splitter 1:8 adalah produk fiber accessories dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'MikroTik hAP ax2 Router','TW-MIKROTIK-030',0,'../produk/2.png',0,'active','<p>MikroTik hAP ax2 Router adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'MikroTik hAP ax3 WiFi 6','TW-MIKROTIK-031',0,'../produk/1.png',0,'active','<p>MikroTik hAP ax3 WiFi 6 adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'MikroTik RB750Gr3 hEX','TW-MIKROTIK-032',0,'../produk/2.png',0,'active','<p>MikroTik RB750Gr3 hEX adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'MikroTik RB5009 Router','TW-MIKROTIK-033',0,'../produk/3.png',0,'active','<p>MikroTik RB5009 Router adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'MikroTik CRS326 Switch','TW-MIKROTIK-034',0,'../produk/4.png',0,'active','<p>MikroTik CRS326 Switch adalah produk switch dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'MikroTik CSS610 8G Switch','TW-MIKROTIK-035',0,'../produk/5.png',0,'active','<p>MikroTik CSS610 8G Switch adalah produk switch dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='access-point' LIMIT 1),'MikroTik cAP ax Ceiling','TW-MIKROTIK-036',0,'../produk/6.png',0,'active','<p>MikroTik cAP ax Ceiling adalah produk access point dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'MikroTik SXT LTE Kit','TW-MIKROTIK-037',0,'../produk/7.png',0,'active','<p>MikroTik SXT LTE Kit adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'MikroTik LHG 5 Antenna','TW-MIKROTIK-038',0,'../produk/5.png',0,'active','<p>MikroTik LHG 5 Antenna adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'MikroTik mANTBox 15s','TW-MIKROTIK-039',0,'../produk/4.png',0,'active','<p>MikroTik mANTBox 15s adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='perangkat-jaringan' LIMIT 1),'MikroTik NetMetal ac²','TW-MIKROTIK-040',0,'../produk/2.png',0,'active','<p>MikroTik NetMetal ac² adalah produk perangkat jaringan dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'MikroTik wAP ac Outdoor','TW-MIKROTIK-041',0,'../produk/1.png',0,'active','<p>MikroTik wAP ac Outdoor adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='mikrotik' LIMIT 1),(SELECT id FROM categories WHERE slug='router-gateway' LIMIT 1),'MikroTik RouterBOARD PoE','TW-MIKROTIK-042',0,'../produk/2.png',0,'active','<p>MikroTik RouterBOARD PoE adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'VOL.TECH GPON ONT 1GE','TW-VOLTECH-043',0,'../produk/3.png',0,'active','<p>VOL.TECH GPON ONT 1GE adalah produk onu / ont dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='onu-ont' LIMIT 1),'VOL.TECH ONT Dual Band','TW-VOLTECH-044',0,'../produk/4.png',0,'active','<p>VOL.TECH ONT Dual Band adalah produk onu / ont dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='olt' LIMIT 1),'VOL.TECH OLT 4 Port','TW-VOLTECH-045',0,'../produk/5.png',0,'active','<p>VOL.TECH OLT 4 Port adalah produk olt dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='olt' LIMIT 1),'VOL.TECH OLT 8 Port','TW-VOLTECH-046',0,'../produk/6.png',0,'active','<p>VOL.TECH OLT 8 Port adalah produk olt dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'VOL.TECH PoE Switch 8 Port','TW-VOLTECH-047',0,'../produk/7.png',0,'active','<p>VOL.TECH PoE Switch 8 Port adalah produk switch dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='switch' LIMIT 1),'VOL.TECH Gigabit Switch 16','TW-VOLTECH-048',0,'../produk/5.png',0,'active','<p>VOL.TECH Gigabit Switch 16 adalah produk switch dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='media-converter' LIMIT 1),'VOL.TECH Media Converter','TW-VOLTECH-049',0,'../produk/4.png',0,'active','<p>VOL.TECH Media Converter adalah produk media converter dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='sfp-module' LIMIT 1),'VOL.TECH SFP Module 1.25G','TW-VOLTECH-050',0,'../produk/2.png',0,'active','<p>VOL.TECH SFP Module 1.25G adalah produk sfp module dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='fiber-accessories' LIMIT 1),'VOL.TECH Fiber Patch Cord','TW-VOLTECH-051',0,'../produk/1.png',0,'active','<p>VOL.TECH Fiber Patch Cord adalah produk fiber accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='fiber-accessories' LIMIT 1),'VOL.TECH Optical Splitter 1:16','TW-VOLTECH-052',0,'../produk/2.png',0,'active','<p>VOL.TECH Optical Splitter 1:16 adalah produk fiber accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='wireless-outdoor' LIMIT 1),'VOL.TECH Outdoor CPE AC','TW-VOLTECH-053',0,'../produk/3.png',0,'active','<p>VOL.TECH Outdoor CPE AC adalah produk wireless outdoor dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='access-point' LIMIT 1),'VOL.TECH Access Point Pro','TW-VOLTECH-054',0,'../produk/4.png',0,'active','<p>VOL.TECH Access Point Pro adalah produk access point dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>'),
((SELECT id FROM brands WHERE slug='vol-tech' LIMIT 1),(SELECT id FROM categories WHERE slug='rack-accessories' LIMIT 1),'VOL.TECH Rackmount PDU','TW-VOLTECH-055',0,'../produk/5.png',0,'active','<p>VOL.TECH Rackmount PDU adalah produk rack accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>');

-- Simpan main image juga sebagai product gallery agar detail produk bisa menampilkan thumbnail.
INSERT INTO product_images (product_id, image_path, is_primary)
SELECT id, image, 1
FROM products
WHERE image IS NOT NULL AND image <> '';

-- Ringkasan hasil
SELECT 'brands' AS table_name, COUNT(*) AS total FROM brands
UNION ALL
SELECT 'categories' AS table_name, COUNT(*) AS total FROM categories
UNION ALL
SELECT 'products' AS table_name, COUNT(*) AS total FROM products
UNION ALL
SELECT 'product_images' AS table_name, COUNT(*) AS total FROM product_images;
