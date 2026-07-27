SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `link_metrics`;
DROP TABLE IF EXISTS `shortened_urls`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `stripe_customer_id` VARCHAR(255) DEFAULT NULL,
  `stripe_subscription_id` VARCHAR(255) DEFAULT NULL,
  `plan_status` ENUM('free', 'pro', 'enterprise') DEFAULT 'free',
  `plan_expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_email` (`email`),
  INDEX `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shortened_urls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `original_url` TEXT NOT NULL,
  `short_code` VARCHAR(12) NOT NULL UNIQUE,
  `custom_domain` VARCHAR(255) DEFAULT NULL,
  `qr_code_path` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_url_code` (`short_code`),
  INDEX `idx_url_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `link_metrics` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `url_id` INT NOT NULL,
  `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `country_code` VARCHAR(3) DEFAULT 'UNK',
  `country_name` VARCHAR(100) DEFAULT 'Unknown',
  `city` VARCHAR(100) DEFAULT 'Unknown',
  `device_type` ENUM('desktop', 'mobile', 'tablet', 'bot') DEFAULT 'desktop',
  `browser` VARCHAR(100) DEFAULT 'Unknown',
  `referrer_domain` VARCHAR(255) DEFAULT 'Direct',
  FOREIGN KEY (`url_id`) REFERENCES `shortened_urls`(`id`) ON DELETE CASCADE,
  INDEX `idx_metrics_url_date` (`url_id`, `clicked_at`),
  INDEX `idx_metrics_geo` (`country_code`),
  INDEX `idx_metrics_device` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
