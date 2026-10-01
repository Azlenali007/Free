-- Migration for Free Fire Store Phase 2 to Phase 8
-- Preserves all existing tables and data

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Alter users table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(50) NULL UNIQUE AFTER `wallet_balance`,
  ADD COLUMN IF NOT EXISTS `referred_by` INT NULL AFTER `referral_code`,
  ADD COLUMN IF NOT EXISTS `is_reseller` TINYINT(1) NOT NULL DEFAULT 0 AFTER `referred_by`,
  ADD COLUMN IF NOT EXISTS `reseller_level` VARCHAR(20) NOT NULL DEFAULT 'main' AFTER `is_reseller`,
  ADD COLUMN IF NOT EXISTS `reseller_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `reseller_level`,
  ADD COLUMN IF NOT EXISTS `two_factor_secret` VARCHAR(255) NULL AFTER `reseller_balance`,
  ADD COLUMN IF NOT EXISTS `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`,
  ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL AFTER `two_factor_enabled`;

-- 2. Alter admins table
ALTER TABLE `admins`
  ADD COLUMN IF NOT EXISTS `two_factor_secret` VARCHAR(255) NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`;

-- 3. Alter products table
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `badge`,
  ADD COLUMN IF NOT EXISTS `flash_sale_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN IF NOT EXISTS `flash_sale_price` DECIMAL(10,2) NULL AFTER `flash_sale_enabled`,
  ADD COLUMN IF NOT EXISTS `flash_sale_start` DATETIME NULL AFTER `flash_sale_price`,
  ADD COLUMN IF NOT EXISTS `flash_sale_end` DATETIME NULL AFTER `flash_sale_start`;

-- 4. Alter orders table
ALTER TABLE `orders`
  MODIFY COLUMN `order_status` ENUM('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `variant_id` INT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `variant_name` VARCHAR(150) NULL AFTER `variant_id`,
  ADD COLUMN IF NOT EXISTS `original_amount` DECIMAL(10,2) NULL AFTER `variant_name`,
  ADD COLUMN IF NOT EXISTS `coupon_code` VARCHAR(50) NULL AFTER `total_amount`,
  ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`,
  ADD COLUMN IF NOT EXISTS `provider_id` INT NULL AFTER `admin_notes`,
  ADD COLUMN IF NOT EXISTS `provider_order_id` VARCHAR(100) NULL AFTER `provider_id`,
  ADD COLUMN IF NOT EXISTS `provider_response` TEXT NULL AFTER `provider_order_id`,
  ADD COLUMN IF NOT EXISTS `failure_reason` TEXT NULL AFTER `provider_response`;

-- 5. Alter wallet_transactions table
ALTER TABLE `wallet_transactions`
  MODIFY COLUMN `status` ENUM('pending','completed','failed','cancelled','refunded','rejected') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `previous_balance` DECIMAL(10,2) NULL AFTER `amount`,
  ADD COLUMN IF NOT EXISTS `new_balance` DECIMAL(10,2) NULL AFTER `previous_balance`;

-- 6. Table: payment_gateways
CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `instructions` TEXT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `credentials` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payment_id` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `order_id` INT NULL,
  `wallet_transaction_id` INT NULL,
  `gateway_code` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `status` ENUM('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `gateway_txn_id` VARCHAR(100) NULL,
  `gateway_response` TEXT NULL,
  `error_reason` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pay_user` (`user_id`),
  INDEX `idx_pay_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table: coupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(10,2) NULL,
  `start_date` DATETIME NULL,
  `expiry_date` DATETIME NULL,
  `usage_limit` INT NOT NULL DEFAULT 0,
  `used_count` INT NOT NULL DEFAULT 0,
  `per_user_limit` INT NOT NULL DEFAULT 1,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table: coupon_usage
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `coupon_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cu_user` (`user_id`, `coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table: product_variants
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) NULL,
  `diamonds_amount` INT NOT NULL DEFAULT 0,
  `bonus_diamonds` INT NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table: banners
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `subtitle` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `badge` VARCHAR(50) NULL,
  `image` VARCHAR(255) NULL,
  `button_text` VARCHAR(50) NULL,
  `button_url` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Table: reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `rating` TINYINT NOT NULL DEFAULT 5,
  `review_text` TEXT NOT NULL,
  `status` ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_prod_review` (`product_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Table: notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'order',
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_notif` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Table: refunds
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `refund_number` VARCHAR(50) NOT NULL UNIQUE,
  `order_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `admin_id` INT NULL,
  `admin_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_refund_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Table: admin_activity_logs
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NULL,
  `admin_username` VARCHAR(50) NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_act_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Table: security_logs
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `user_id` INT NULL,
  `details` TEXT NULL,
  `severity` ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sec_event` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Table: login_attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 1,
  `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` DATETIME NULL,
  UNIQUE KEY `idx_ident_ip` (`identifier`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Table: referral_records
CREATE TABLE IF NOT EXISTS `referral_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `order_id` INT NULL,
  `reward_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','rewarded','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Table: reseller_pricing
CREATE TABLE IF NOT EXISTS `reseller_pricing` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `variant_id` INT NULL,
  `reseller_level` VARCHAR(20) NOT NULL DEFAULT 'main',
  `reseller_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Table: api_keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `api_key` VARCHAR(64) NOT NULL UNIQUE,
  `api_secret` VARCHAR(64) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `rate_limit_per_minute` INT NOT NULL DEFAULT 60,
  `last_used_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Table: api_logs
CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `api_key_id` INT NOT NULL,
  `endpoint` VARCHAR(150) NOT NULL,
  `request_method` VARCHAR(10) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `request_body` TEXT NULL,
  `response_code` INT NOT NULL DEFAULT 200,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Table: providers
CREATE TABLE IF NOT EXISTS `providers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key` VARCHAR(255) NULL,
  `api_secret` VARCHAR(255) NULL,
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Table: provider_services
CREATE TABLE IF NOT EXISTS `provider_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `provider_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `variant_id` INT NULL,
  `provider_service_id` VARCHAR(100) NOT NULL,
  `provider_service_name` VARCHAR(150) NOT NULL,
  `provider_price` DECIMAL(10,2) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
