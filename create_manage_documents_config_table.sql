-- Create manage_documents_config table for storing user configuration settings
CREATE TABLE IF NOT EXISTS `manage_documents_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `quarter` int(1) NOT NULL CHECK (`quarter` BETWEEN 1 AND 4),
  `main_category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_config` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_year_quarter` (`year`, `quarter`),
  KEY `idx_main_category` (`main_category_id`),
  KEY `idx_sub_category` (`sub_category_id`),
  CONSTRAINT `fk_config_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_config_main_category` FOREIGN KEY (`main_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_config_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;