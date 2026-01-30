-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2026 at 01:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `millet_marketplace`
--
Create Database if not exists `millet_marketplace` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `millet_marketplace`;

-- --------------------------------------------------------

--
-- Table structure for table `consumer_profiles`
--

CREATE TABLE `consumer_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consumer_profiles`
--

INSERT INTO `consumer_profiles` (`id`, `user_id`, `name`, `street`, `city`, `pincode`, `created_at`, `updated_at`) VALUES
(1, 4, 'karthik', 'no 50 shewag street, vimal nagar', 'Tiruvannamalai', '606597', '2026-01-29 05:23:05', '2026-01-29 05:23:05'),
(4, 7, 'vishnu', 'no 14 Fernandez colony, jj nagar', 'chennai', '606213', '2026-01-29 12:36:46', '2026-01-29 12:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_profiles`
--

CREATE TABLE `farmer_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `farm_location` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_ifsc_code` varchar(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_profiles`
--

INSERT INTO `farmer_profiles` (`id`, `user_id`, `name`, `street`, `city`, `pincode`, `farm_location`, `bank_account_number`, `bank_ifsc_code`, `created_at`, `updated_at`) VALUES
(1, 1, 'yuvaraji', 'no 30 old indian bank street', 'chennai', '606755', 'chennai', '192228377', 'SBIN0001234', '2026-01-29 04:34:15', '2026-01-29 04:34:15'),
(2, 2, 'yuvaraji', 'no 30 old indian bank street', 'Tiruvannamalai', '606755', 'tiruvannamalai', '19220787', 'SBIN0001234', '2026-01-29 04:39:50', '2026-01-29 04:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_supplies`
--

CREATE TABLE `farmer_supplies` (
  `id` int(10) UNSIGNED NOT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `millet_type` varchar(100) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `quality_grade` enum('A','B','C') NOT NULL,
  `harvest_date` date NOT NULL,
  `packaging_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `collection_by` varchar(255) DEFAULT NULL,
  `collection_date` date DEFAULT NULL,
  `status` enum('pending','accepted','completed') DEFAULT 'pending',
  `shg_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_supplies`
--

INSERT INTO `farmer_supplies` (`id`, `farmer_id`, `millet_type`, `quantity_kg`, `quality_grade`, `harvest_date`, `packaging_date`, `location`, `collection_by`, `collection_date`, `status`, `shg_id`, `created_at`, `updated_at`, `payment_status`) VALUES
(1, 2, 'Sorghum (Jowar)', 50.00, 'A', '2026-01-29', '2026-01-30', 'Tiruvannamalai', 'vishnu', '2026-01-30', 'completed', 3, '2026-01-29 04:45:40', '2026-01-29 12:07:08', 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `consumer_id` int(10) UNSIGNED NOT NULL,
  `shg_id` int(10) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('order_placed','confirmed','picked_up','in_transit','delivered','cancelled') DEFAULT 'order_placed',
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `consumer_id`, `shg_id`, `total_amount`, `status`, `pickup_location`, `dropoff_location`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20260129-374D85', 4, 3, 50.00, 'delivered', '', 'no 50 shewag street, vimal nagar, Tiruvannamalai', '2026-01-29 05:43:08', '2026-01-29 06:29:37'),
(2, 'ORD-20260129-4194AC', 4, 3, 25.00, 'in_transit', '', 'no 50 shewag street, vimal nagar, Tiruvannamalai', '2026-01-29 05:43:40', '2026-01-29 06:29:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity_kg`, `price_per_kg`, `total_price`, `created_at`) VALUES
(1, 1, 1, 2.00, 25.00, 50.00, '2026-01-29 05:43:08'),
(2, 2, 1, 1.00, 25.00, 25.00, '2026-01-29 05:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` enum('order_placed','confirmed','picked_up','in_transit','delivered','cancelled') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `created_at`) VALUES
(1, 1, 'order_placed', 'Order placed successfully', '2026-01-29 05:43:08'),
(2, 1, 'confirmed', 'Payment received and verified', '2026-01-29 05:43:19'),
(3, 2, 'order_placed', 'Order placed successfully', '2026-01-29 05:43:40'),
(4, 2, 'confirmed', 'Payment received and verified', '2026-01-29 05:44:03'),
(5, 2, 'picked_up', NULL, '2026-01-29 06:29:25'),
(6, 2, 'in_transit', NULL, '2026-01-29 06:29:27'),
(7, 1, 'picked_up', NULL, '2026-01-29 06:29:34'),
(8, 1, 'in_transit', NULL, '2026-01-29 06:29:36'),
(9, 1, 'delivered', NULL, '2026-01-29 06:29:37');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('registration','forgot_password') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_id`, `email`, `otp`, `purpose`, `is_used`, `expires_at`, `created_at`) VALUES
(1, 1, 'yuvifarm11@gmail.com', '917279', 'registration', 0, '2026-01-29 04:39:15', '2026-01-29 04:34:15'),
(2, 2, 'yuvi25082004@gmail.com', '534685', 'registration', 1, '2026-01-29 04:40:54', '2026-01-29 04:39:50'),
(3, 3, 'shg123@gmail.com', '239376', 'registration', 1, '2026-01-29 05:05:39', '2026-01-29 05:04:23'),
(4, 4, 'karthi1234@gmail.com', '520495', 'registration', 0, '2026-01-29 05:28:05', '2026-01-29 05:23:05'),
(7, 7, 'vishnu123@gmail.com', '643293', 'registration', 1, '2026-01-29 12:37:17', '2026-01-29 12:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `farmer_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_type` enum('consumer_order','farmer_payment') NOT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `farmer_id`, `payment_type`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `amount`, `status`, `payment_method`, `created_at`, `updated_at`) VALUES
(7, 1, NULL, 'consumer_order', 'order_demo_d37e1870', 'pay_demo_1769665466477', 'mock_signature', 70.00, 'success', NULL, '2026-01-29 05:43:19', '2026-01-29 05:43:19'),
(8, 2, NULL, 'consumer_order', 'order_demo_602c0389', 'pay_demo_1769665510567', 'mock_signature', 45.00, 'success', NULL, '2026-01-29 05:44:03', '2026-01-29 05:44:03'),
(16, NULL, 2, 'farmer_payment', NULL, NULL, NULL, 1000.00, 'success', 'Cash', '2026-01-29 12:07:08', '2026-01-29 12:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `shg_id` int(10) UNSIGNED NOT NULL,
  `supply_id` int(10) UNSIGNED DEFAULT NULL,
  `millet_type` varchar(100) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `quality_grade` enum('A','B','C') NOT NULL,
  `packaging_date` date NOT NULL,
  `source_farmer_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shg_id`, `supply_id`, `millet_type`, `quantity_kg`, `price_per_kg`, `quality_grade`, `packaging_date`, `source_farmer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 'Finger Millet (Ragi)', 47.00, 25.00, 'A', '2026-01-31', 'yuvaraji', 'good', 1, '2026-01-29 05:16:05', '2026-01-29 05:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `shg_profiles`
--

CREATE TABLE `shg_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `warehouse_location` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_ifsc_code` varchar(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shg_profiles`
--

INSERT INTO `shg_profiles` (`id`, `user_id`, `organization_name`, `contact_person_name`, `street`, `city`, `pincode`, `warehouse_location`, `bank_account_number`, `bank_ifsc_code`, `created_at`, `updated_at`) VALUES
(1, 3, 'Ramesh shg', 'ramesh s', 'no 40 shg street , kk nagar', 'erode', '606744', '', NULL, NULL, '2026-01-29 05:04:23', '2026-01-29 05:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `traceability_records`
--

CREATE TABLE `traceability_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `traceability_id` varchar(50) NOT NULL,
  `supply_id` int(10) UNSIGNED NOT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `millet_type` varchar(100) NOT NULL,
  `farmer_name` varchar(255) NOT NULL,
  `harvest_date` date NOT NULL,
  `packaging_date` date NOT NULL,
  `quality_grade` enum('A','B','C') NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `traceability_records`
--

INSERT INTO `traceability_records` (`id`, `traceability_id`, `supply_id`, `farmer_id`, `millet_type`, `farmer_name`, `harvest_date`, `packaging_date`, `quality_grade`, `qr_code_path`, `created_at`) VALUES
(1, 'TR-2026-001', 1, 2, 'Sorghum (Jowar)', 'yuvaraji', '2026-01-29', '2026-01-30', 'A', NULL, '2026-01-29 05:10:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('farmer','shg','consumer') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `mobile`, `password`, `role`, `is_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'yuvifarm11@gmail.com', '9944269416', '$2y$10$agb.vHH1/dvSnMv7vcPky.rugf8k8U4RHeFLXSH4iwWI2bJfiQ/6C', 'farmer', 0, 1, '2026-01-29 04:34:15', '2026-01-29 04:34:15'),
(2, 'yuvi25082004@gmail.com', '9944269416', '$2y$10$RAml53fDfmGQXuMb2upXjuV.8Ag71P6VOcjqCTJG0wP3pP9aJTNkm', 'farmer', 1, 1, '2026-01-29 04:39:50', '2026-01-29 04:40:54'),
(3, 'shg123@gmail.com', '9568906215', '$2y$10$mQypJ81rRIsbj.PiVNYBfe59PcOSrB.gJAnqN6Vl5Ch31.y.1qeaO', 'shg', 1, 1, '2026-01-29 05:04:23', '2026-01-29 05:05:39'),
(4, 'karthi1234@gmail.com', '9566089351', '$2y$10$iXcy7jckYMLl9VmCBJIEdeu/W3lFF.ldzgdVTOs.zkzFmom6q3ZHO', 'consumer', 1, 1, '2026-01-29 05:23:05', '2026-01-29 05:41:13'),
(7, 'vishnu123@gmail.com', '9955223636', '$2y$10$JvY8nBFCe1BSoKo1RhToqOd7zKWUXilML5bgm5T6GKe7syjZS9/FO', 'consumer', 1, 1, '2026-01-29 12:36:46', '2026-01-29 12:37:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consumer_profiles`
--
ALTER TABLE `consumer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `farmer_supplies`
--
ALTER TABLE `farmer_supplies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmer_id` (`farmer_id`),
  ADD KEY `idx_shg_id` (`shg_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_consumer_id` (`consumer_id`),
  ADD KEY `idx_shg_id` (`shg_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_email_otp` (`email`,`otp`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_farmer_id` (`farmer_id`),
  ADD KEY `idx_razorpay_order_id` (`razorpay_order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_id` (`supply_id`),
  ADD KEY `idx_shg_id` (`shg_id`),
  ADD KEY `idx_millet_type` (`millet_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `shg_profiles`
--
ALTER TABLE `shg_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `traceability_records`
--
ALTER TABLE `traceability_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `traceability_id` (`traceability_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_traceability_id` (`traceability_id`),
  ADD KEY `idx_supply_id` (`supply_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consumer_profiles`
--
ALTER TABLE `consumer_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `farmer_supplies`
--
ALTER TABLE `farmer_supplies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shg_profiles`
--
ALTER TABLE `shg_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `traceability_records`
--
ALTER TABLE `traceability_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consumer_profiles`
--
ALTER TABLE `consumer_profiles`
  ADD CONSTRAINT `consumer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD CONSTRAINT `farmer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_supplies`
--
ALTER TABLE `farmer_supplies`
  ADD CONSTRAINT `farmer_supplies_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `farmer_supplies_ibfk_2` FOREIGN KEY (`shg_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shg_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD CONSTRAINT `otp_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shg_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supply_id`) REFERENCES `farmer_supplies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shg_profiles`
--
ALTER TABLE `shg_profiles`
  ADD CONSTRAINT `shg_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `traceability_records`
--
ALTER TABLE `traceability_records`
  ADD CONSTRAINT `traceability_records_ibfk_1` FOREIGN KEY (`supply_id`) REFERENCES `farmer_supplies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `traceability_records_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
