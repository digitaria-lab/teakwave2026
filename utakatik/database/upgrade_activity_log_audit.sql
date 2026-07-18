-- Activity Log compatibility upgrade
-- Safe to run when activity_logs already exists.

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `module` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(80) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`),
  KEY `module_index` (`module`),
  KEY `action_index` (`action`),
  KEY `created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Older deployments sometimes used ENUM or short VARCHAR columns. Those
-- definitions reject action values such as video_create and restore_upload.
ALTER TABLE `activity_logs`
  MODIFY `action` varchar(120) NOT NULL,
  MODIFY `module` varchar(120) NOT NULL,
  MODIFY `description` text DEFAULT NULL,
  MODIFY `ip_address` varchar(80) DEFAULT NULL,
  MODIFY `user_agent` text DEFAULT NULL;
