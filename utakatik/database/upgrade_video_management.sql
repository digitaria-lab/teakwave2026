-- Upgrade: Video Management
-- Jalankan satu kali pada database lama. Aman dijalankan ulang.

CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `url` varchar(500) NOT NULL,
  `youtube_id` varchar(11) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `tag` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `youtube_id_unique` (`youtube_id`),
  KEY `title_index` (`title`),
  KEY `created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default access mengikuti modul Content: Super Admin, Admin, dan Editor.
INSERT IGNORE INTO `role_permissions` (`role_id`, `page_key`)
SELECT `id`, 'videos-list' FROM `roles` WHERE `id` IN (1,2,3);

INSERT IGNORE INTO `role_permissions` (`role_id`, `page_key`)
SELECT `id`, 'videos-add' FROM `roles` WHERE `id` IN (1,2,3);

INSERT IGNORE INTO `role_permissions` (`role_id`, `page_key`)
SELECT `id`, 'videos-edit' FROM `roles` WHERE `id` IN (1,2,3);
