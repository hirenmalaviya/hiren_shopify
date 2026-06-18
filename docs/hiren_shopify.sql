-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 12:18 PM
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
-- Database: `hiren_shopify`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_logs`
--

CREATE TABLE `import_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `upload_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` varchar(255) NOT NULL DEFAULT 'info',
  `message` varchar(255) NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `import_logs`
--

INSERT INTO `import_logs` (`id`, `upload_id`, `product_id`, `level`, `message`, `context`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/9325221740783\",\"sku\":\"MDL-001\",\"handle\":\"modern-desk-lamp\",\"row\":1}', '2026-06-18 01:10:55', '2026-06-18 01:10:55'),
(2, 1, 2, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102333679\",\"sku\":\"EOC-001\",\"handle\":\"ergonomic-office-chair\",\"row\":2}', '2026-06-18 01:10:59', '2026-06-18 01:10:59'),
(3, 1, 3, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102366447\",\"sku\":\"WBS-001\",\"handle\":\"wireless-bluetooth-speaker\",\"row\":3}', '2026-06-18 01:11:03', '2026-06-18 01:11:03'),
(4, 1, 4, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102399215\",\"sku\":\"PYM-001\",\"handle\":\"premium-yoga-mat\",\"row\":4}', '2026-06-18 01:11:07', '2026-06-18 01:11:07'),
(5, 1, 5, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102431983\",\"sku\":\"SSWB-001\",\"handle\":\"stainless-steel-water-bottle\",\"row\":5}', '2026-06-18 01:11:11', '2026-06-18 01:11:11'),
(6, 1, 6, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102464751\",\"sku\":\"HCM-001\",\"handle\":\"handcrafted-ceramic-mug\",\"row\":6}', '2026-06-18 01:11:15', '2026-06-18 01:11:15'),
(7, 1, 7, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102497519\",\"sku\":\"OCTS-001\",\"handle\":\"organic-cotton-t-shirt\",\"row\":7}', '2026-06-18 01:11:19', '2026-06-18 01:11:19'),
(8, 1, 8, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102530287\",\"sku\":\"SFT-001\",\"handle\":\"smart-fitness-tracker\",\"row\":8}', '2026-06-18 01:11:23', '2026-06-18 01:11:23'),
(9, 1, 9, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102595823\",\"sku\":\"PCB-001\",\"handle\":\"premium-coffee-beans\",\"row\":9}', '2026-06-18 01:11:27', '2026-06-18 01:11:27'),
(10, 1, 10, 'info', 'Product imported (update)', '{\"shopify_product_id\":\"gid:\\/\\/shopify\\/Product\\/8922102628591\",\"sku\":\"MWC-001\",\"handle\":\"minimalist-wall-clock\",\"row\":10}', '2026-06-18 01:11:31', '2026-06-18 01:11:31');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_06_15_085329_create_uploads_table', 1),
(5, '2026_06_15_085330_create_products_table', 1),
(6, '2026_06_15_085331_create_import_logs_table', 1),
(7, '2026_06_15_085331_create_notifications_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('2a3888d6-d649-4516-84db-8c409142d4e3', 'App\\Notifications\\ImportFailedNotification', 'App\\Models\\User', 1, '{\"type\":\"import_failed\",\"upload_id\":2,\"filename\":\"bad-products.csv\",\"failed_rows\":3,\"total_rows\":3,\"message\":\"3 of 3 products failed to import in \\\"bad-products.csv\\\".\"}', NULL, '2026-06-18 01:12:01', '2026-06-18 01:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `upload_id` bigint(20) UNSIGNED NOT NULL,
  `row_number` int(10) UNSIGNED NOT NULL,
  `handle` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body_html` longtext DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `sku` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `requires_shipping` tinyint(1) NOT NULL DEFAULT 1,
  `taxable` tinyint(1) NOT NULL DEFAULT 1,
  `inventory_tracker` varchar(255) DEFAULT NULL,
  `inventory_qty` int(11) NOT NULL DEFAULT 0,
  `inventory_policy` varchar(255) DEFAULT NULL,
  `fulfillment_service` varchar(255) DEFAULT NULL,
  `weight` decimal(8,3) DEFAULT NULL,
  `weight_unit` varchar(255) DEFAULT NULL,
  `image_src` varchar(1024) DEFAULT NULL,
  `image_position` int(10) UNSIGNED DEFAULT NULL,
  `image_alt_text` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `action` varchar(255) DEFAULT NULL,
  `shopify_product_id` varchar(255) DEFAULT NULL,
  `shopify_variant_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `upload_id`, `row_number`, `handle`, `title`, `body_html`, `vendor`, `product_type`, `tags`, `published`, `sku`, `price`, `compare_at_price`, `requires_shipping`, `taxable`, `inventory_tracker`, `inventory_qty`, `inventory_policy`, `fulfillment_service`, `weight`, `weight_unit`, `image_src`, `image_position`, `image_alt_text`, `status`, `action`, `shopify_product_id`, `shopify_variant_id`, `error_message`, `attempts`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'modern-desk-lamp', 'Modern Desk Lamp', '<p>A sleek and contemporary desk lamp with adjustable brightness settings. Perfect for your home office.</p>', 'LightCraft', 'Lighting', 'desk,lamp,office,modern', 1, 'MDL-001', 39.99, 49.99, 1, 1, 'shopify', 25, 'deny', 'manual', 1.200, 'kg', 'https://images.unsplash.com/photo-1534109287734-9595ff4428d5', 1, 'Modern desk lamp with adjustable arm', 'successful', 'update', 'gid://shopify/Product/9325221740783', 'gid://shopify/ProductVariant/48200378515695', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:10:55'),
(2, 1, 2, 'ergonomic-office-chair', 'Ergonomic Office Chair', '<p>Premium ergonomic office chair with lumbar support and adjustable height. Designed for maximum comfort during long work hours.</p>', 'ComfortSeating', 'Furniture', 'chair,office,ergonomic,furniture', 1, 'EOC-001', 189.99, 229.99, 1, 1, 'shopify', 12, 'deny', 'manual', 15.500, 'kg', 'https://images.unsplash.com/photo-1505797149-35ebcb016d74', 1, 'Ergonomic office chair with mesh back', 'successful', 'update', 'gid://shopify/Product/8922102333679', 'gid://shopify/ProductVariant/48200378548463', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:10:59'),
(3, 1, 3, 'wireless-bluetooth-speaker', 'Wireless Bluetooth Speaker', '<p>Portable Bluetooth speaker with superior sound quality and 12-hour battery life. Waterproof design makes it perfect for outdoor use.</p>', 'SoundWave', 'Electronics', 'speaker,bluetooth,wireless,audio', 1, 'WBS-001', 79.99, 99.99, 1, 1, 'shopify', 30, 'deny', 'manual', 0.500, 'kg', 'https://images.unsplash.com/photo-1589003077984-894e133dabab', 1, 'Portable Bluetooth speaker', 'successful', 'update', 'gid://shopify/Product/8922102366447', 'gid://shopify/ProductVariant/48200378581231', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:03'),
(4, 1, 4, 'premium-yoga-mat', 'Premium Yoga Mat', '<p>Extra thick yoga mat made from eco-friendly materials. Non-slip surface provides excellent grip during practice.</p>', 'ZenFitness', 'Fitness', 'yoga,mat,fitness,exercise', 1, 'PYM-001', 45.99, 59.99, 1, 1, 'shopify', 40, 'deny', 'manual', 1.800, 'kg', 'https://images.unsplash.com/photo-1518611012118-696072aa579a', 1, 'Premium yoga mat', 'successful', 'update', 'gid://shopify/Product/8922102399215', 'gid://shopify/ProductVariant/48200378613999', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:07'),
(5, 1, 5, 'stainless-steel-water-bottle', 'Stainless Steel Water Bottle', '<p>Double-walled insulated water bottle that keeps drinks cold for 24 hours or hot for 12 hours. BPA-free and environmentally friendly.</p>', 'EcoHydrate', 'Kitchen', 'water bottle,stainless steel,eco-friendly,hydration', 1, 'SSWB-001', 24.99, 29.99, 1, 1, 'shopify', 50, 'deny', 'manual', 0.350, 'kg', 'https://images.unsplash.com/photo-1602143407151-7111542de6e8', 1, 'Stainless steel water bottle', 'successful', 'update', 'gid://shopify/Product/8922102431983', 'gid://shopify/ProductVariant/48200378646767', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:11'),
(6, 1, 6, 'handcrafted-ceramic-mug', 'Handcrafted Ceramic Mug', '<p>Artisan-made ceramic mug, perfect for your morning coffee or tea. Each piece is unique with slight variations in the glaze.</p>', 'ArtisanWares', 'Kitchen', 'mug,ceramic,handcrafted,coffee', 1, 'HCM-001', 18.99, 24.99, 1, 1, 'shopify', 20, 'deny', 'manual', 0.400, 'kg', 'https://images.unsplash.com/photo-1577937927533-addefece22c3', 1, 'Handcrafted ceramic mug', 'successful', 'update', 'gid://shopify/Product/8922102464751', 'gid://shopify/ProductVariant/48200378679535', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:15'),
(7, 1, 7, 'organic-cotton-t-shirt', 'Organic Cotton T-Shirt', '<p>Ultra-soft organic cotton t-shirt. Ethically sourced and manufactured. Classic fit suitable for everyday wear.</p>', 'EcoClothing', 'Apparel', 't-shirt,organic,cotton,clothing', 1, 'OCTS-001', 29.99, 34.99, 1, 1, 'shopify', 30, 'deny', 'manual', 0.200, 'kg', 'https://images.unsplash.com/photo-1581655353564-df123a1eb820', 1, 'Organic cotton t-shirt', 'successful', 'update', 'gid://shopify/Product/8922102497519', 'gid://shopify/ProductVariant/48200378712303', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:19'),
(8, 1, 8, 'smart-fitness-tracker', 'Smart Fitness Tracker', '<p>Advanced fitness tracker with heart rate monitoring, sleep tracking, and smartphone notifications. Water-resistant design for all-day wear.</p>', 'TechFit', 'Electronics', 'fitness,tracker,smartwatch,health', 1, 'SFT-001', 89.99, 109.99, 1, 1, 'shopify', 15, 'deny', 'manual', 0.050, 'kg', 'https://images.unsplash.com/photo-1575311373937-040b8e1fd5b6', 1, 'Smart fitness tracker', 'successful', 'update', 'gid://shopify/Product/8922102530287', 'gid://shopify/ProductVariant/48200378745071', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:23'),
(9, 1, 9, 'premium-coffee-beans', 'Premium Coffee Beans', '<p>Freshly roasted specialty coffee beans sourced from sustainable farms. Rich flavor profile with notes of chocolate and caramel.</p>', 'BeanMaster', 'Food', 'coffee,beans,organic,gourmet', 1, 'PCB-001', 14.99, 17.99, 1, 1, 'shopify', 50, 'deny', 'manual', 0.250, 'kg', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e', 1, 'Premium coffee beans package', 'successful', 'update', 'gid://shopify/Product/8922102595823', 'gid://shopify/ProductVariant/48200378777839', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:27'),
(10, 1, 10, 'minimalist-wall-clock', 'Minimalist Wall Clock', '<p>Elegant wall clock with a minimalist design. Silent movement ensures no ticking noise. Perfect for any modern interior.</p>', 'ModernDecor', 'Home Decor', 'clock,wall,minimalist,home decor', 1, 'MWC-001', 49.99, 59.99, 1, 1, 'shopify', 18, 'deny', 'manual', 0.800, 'kg', 'https://images.unsplash.com/photo-1507985622960-4ce3dce7c7a9', 1, 'Minimalist wall clock', 'successful', 'update', 'gid://shopify/Product/8922102628591', 'gid://shopify/ProductVariant/48200378843375', NULL, 1, '2026-06-18 01:10:49', '2026-06-18 01:11:31'),
(11, 2, 1, 'broken-1', 'Broken One', NULL, NULL, NULL, NULL, 1, 'BRK-1', NULL, NULL, 1, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'failed', NULL, NULL, NULL, 'Variant Price must be a number ≥ 0 (got \"not-a-number\").', 0, '2026-06-18 01:12:00', '2026-06-18 01:12:00'),
(12, 2, 2, NULL, 'Missing Handle', NULL, NULL, NULL, NULL, 1, 'BRK-2', 9.99, NULL, 1, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'failed', NULL, NULL, NULL, 'Handle is required.', 0, '2026-06-18 01:12:00', '2026-06-18 01:12:00'),
(13, 2, 3, 'broken-3', NULL, NULL, NULL, NULL, NULL, 1, 'BRK-3', 12.50, NULL, 1, 1, NULL, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'failed', NULL, NULL, NULL, 'Title is required.', 0, '2026-06-18 01:12:00', '2026-06-18 01:12:00');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Rj05s49VzHOgU2GLGwHlGz0lsqSObYDDLOjqKMQt', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3NvTmZiYkljdlJaSGtQVGd0NVZZd2ZQYzFwRGxkZTNlV0VZNlpjRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC91cGxvYWRzIjtzOjU6InJvdXRlIjtzOjEzOiJ1cGxvYWRzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1781764934);

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `successful_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `skipped_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `original_filename`, `stored_path`, `status`, `total_rows`, `processed_rows`, `successful_rows`, `failed_rows`, `skipped_rows`, `started_at`, `finished_at`, `created_at`, `updated_at`) VALUES
(1, 'shopify-products-csv (5) (1) (1).csv', 'uploads/RkMFLsetaxyA9e7swVhFiSLVSPXRUqlMxfVc1QXj.csv', 'completed', 10, 10, 10, 0, 0, '2026-06-18 01:10:51', '2026-06-18 01:11:31', '2026-06-18 01:10:49', '2026-06-18 01:11:31'),
(2, 'bad-products.csv', 'uploads/wLHJsdFz6Pa4Im3E3jvfqdsYE6mU2zRQQgotaT4I.csv', 'failed', 3, 3, 0, 3, 0, '2026-06-18 01:12:01', '2026-06-18 01:12:01', '2026-06-18 01:12:00', '2026-06-18 01:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'System', 'system@import.local', NULL, '$2y$12$aqk9kBlODpz8uulUd53UBuXl4N/FHVFGo1UlslV64wEW8SRTOqvZa', NULL, '2026-06-18 01:08:37', '2026-06-18 01:08:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_logs_product_id_foreign` (`product_id`),
  ADD KEY `import_logs_upload_id_level_index` (`upload_id`,`level`),
  ADD KEY `import_logs_level_index` (`level`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_upload_id_status_index` (`upload_id`,`status`),
  ADD KEY `products_sku_index` (`sku`),
  ADD KEY `products_handle_index` (`handle`),
  ADD KEY `products_status_index` (`status`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploads_status_index` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD CONSTRAINT `import_logs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `import_logs_upload_id_foreign` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_upload_id_foreign` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
