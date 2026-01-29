-- Millet Supply Chain & Marketplace Database Schema
-- MySQL 8.0+ / InnoDB Engine

SET FOREIGN_KEY_CHECKS=0;

-- Drop existing tables
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `traceability_records`;
DROP TABLE IF EXISTS `farmer_supplies`;
DROP TABLE IF EXISTS `consumer_profiles`;
DROP TABLE IF EXISTS `shg_profiles`;
DROP TABLE IF EXISTS `farmer_profiles`;
DROP TABLE IF EXISTS `otp_verifications`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS=1;

-- ========================================
-- USERS TABLE (Authentication)
-- ========================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mobile` VARCHAR(10) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('farmer', 'shg', 'consumer') NOT NULL,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_mobile` (`mobile`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- OTP VERIFICATIONS
-- ========================================
CREATE TABLE `otp_verifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `otp` VARCHAR(6) NOT NULL,
  `purpose` ENUM('registration', 'forgot_password') NOT NULL,
  `is_used` BOOLEAN DEFAULT FALSE,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_email_otp` (`email`, `otp`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- FARMER PROFILES
-- ========================================
CREATE TABLE `farmer_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255),
  `city` VARCHAR(100),
  `pincode` VARCHAR(6),
  `farm_location` VARCHAR(255),
  `bank_account_number` VARCHAR(50),
  `bank_ifsc_code` VARCHAR(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SHG/FPO PROFILES
-- ========================================
CREATE TABLE `shg_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `organization_name` VARCHAR(255) NOT NULL,
  `contact_person_name` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255),
  `city` VARCHAR(100),
  `pincode` VARCHAR(6),
  `warehouse_location` VARCHAR(255),
  `bank_account_number` VARCHAR(50),
  `bank_ifsc_code` VARCHAR(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CONSUMER PROFILES
-- ========================================
CREATE TABLE `consumer_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `street` VARCHAR(255),
  `city` VARCHAR(100),
  `pincode` VARCHAR(6),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- FARMER SUPPLIES
-- ========================================
CREATE TABLE `farmer_supplies` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `farmer_id` INT UNSIGNED NOT NULL,
  `millet_type` VARCHAR(100) NOT NULL,
  `quantity_kg` DECIMAL(10,2) NOT NULL,
  `quality_grade` ENUM('A', 'B', 'C') NOT NULL,
  `harvest_date` DATE NOT NULL,
  `packaging_date` DATE NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `collection_by` VARCHAR(255),
  `collection_date` DATE,
  `status` ENUM('pending', 'accepted', 'completed') DEFAULT 'pending',
  `shg_id` INT UNSIGNED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shg_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_farmer_id` (`farmer_id`),
  INDEX `idx_shg_id` (`shg_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TRACEABILITY RECORDS
-- ========================================
CREATE TABLE `traceability_records` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `traceability_id` VARCHAR(50) NOT NULL UNIQUE,
  `supply_id` INT UNSIGNED NOT NULL,
  `farmer_id` INT UNSIGNED NOT NULL,
  `millet_type` VARCHAR(100) NOT NULL,
  `farmer_name` VARCHAR(255) NOT NULL,
  `harvest_date` DATE NOT NULL,
  `packaging_date` DATE NOT NULL,
  `quality_grade` ENUM('A', 'B', 'C') NOT NULL,
  `qr_code_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`supply_id`) REFERENCES `farmer_supplies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_traceability_id` (`traceability_id`),
  INDEX `idx_supply_id` (`supply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PRODUCTS
-- ========================================
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shg_id` INT UNSIGNED NOT NULL,
  `supply_id` INT UNSIGNED,
  `millet_type` VARCHAR(100) NOT NULL,
  `quantity_kg` DECIMAL(10,2) NOT NULL,
  `price_per_kg` DECIMAL(10,2) NOT NULL,
  `quality_grade` ENUM('A', 'B', 'C') NOT NULL,
  `packaging_date` DATE NOT NULL,
  `source_farmer_name` VARCHAR(255),
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`shg_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supply_id`) REFERENCES `farmer_supplies`(`id`) ON DELETE SET NULL,
  INDEX `idx_shg_id` (`shg_id`),
  INDEX `idx_millet_type` (`millet_type`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ORDERS
-- ========================================
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `consumer_id` INT UNSIGNED NOT NULL,
  `shg_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('order_placed', 'confirmed', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'order_placed',
  `pickup_location` VARCHAR(255),
  `dropoff_location` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`consumer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shg_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_number` (`order_number`),
  INDEX `idx_consumer_id` (`consumer_id`),
  INDEX `idx_shg_id` (`shg_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ORDER ITEMS
-- ========================================
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity_kg` DECIMAL(10,2) NOT NULL,
  `price_per_kg` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ORDER STATUS HISTORY
-- ========================================
CREATE TABLE `order_status_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `status` ENUM('order_placed', 'confirmed', 'picked_up', 'in_transit', 'delivered', 'cancelled') NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PAYMENTS
-- ========================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED,
  `farmer_id` INT UNSIGNED,
  `payment_type` ENUM('consumer_order', 'farmer_payment') NOT NULL,
  `razorpay_order_id` VARCHAR(100),
  `razorpay_payment_id` VARCHAR(100),
  `razorpay_signature` VARCHAR(255),
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending',
  `payment_method` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_farmer_id` (`farmer_id`),
  INDEX `idx_razorpay_order_id` (`razorpay_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA (Optional)
-- ========================================
-- Insert sample millet types for reference
-- You can create a separate millet_types table if needed
