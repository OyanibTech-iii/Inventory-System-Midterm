-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2025 at 11:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'Pacifico Oyanib III', 'pacifico@example.com', '555-123-4567', '123 Main Street, Apt 4B, New York, NY 10001', '2025-04-23 05:53:51'),
(2, 'Pacifico Rodriguez', 'pacifico@example.com', '555-123-4567', '123 Main Street, Apt 4B, New York, NY 10001', '2025-04-23 05:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `minimum_stock_level` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `category`, `price`, `sku`, `minimum_stock_level`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Running Shoes', 'Comfortable running shoes with cushioned sole and breathable material', 'Shoes', 79.99, 'SHO-1234', 15, 1, '2025-04-22 22:19:33', '2025-04-22 22:19:33'),
(2, 'Formal Shoes', 'Use in formal event', 'Shoes', 700.00, 'SHO - 1235', 10, 1, '2025-04-22 16:32:26', '0000-00-00 00:00:00'),
(3, 'Airforce 1', 'For school porpuses', 'Shoes', 122.00, 'PRD-3531', 10, 1, '2025-04-22 16:46:35', '0000-00-00 00:00:00'),
(4, 'Nike Air Force 1', 'Classic white Nike Air Force 1 sneakers with iconic design and air cushioning.', 'Footwear', 110.00, 'NIKE-AF1-WHT', 5, 1, '2025-04-23 05:55:39', '2025-04-23 05:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON format to store permission flags'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `permissions`) VALUES
(1, 'Admin', 'System administrator with full access', '{\"all\": true}'),
(2, 'Manager', 'Store manager with limited administrative rights', '{\"view_dashboard\": true, \"view_products\": true, \"add_product\": true, \"edit_product\": true, \"view_sales\": true, \"add_sale\": true, \"view_reports\": true, \"view_suppliers\": true, \"edit_suppliers\": true}'),
(3, 'Sales Staff', 'Staff responsible for processing sales', '{\"view_products\": true, \"view_product_details\": true, \"add_sale\": true, \"view_own_sales\": true}'),
(4, 'Inventory Staff', 'Staff responsible for inventory management', '{\"view_products\": true, \"add_product\": true, \"edit_product\": true, \"view_stock\": true, \"add_stock\": true, \"view_suppliers\": true}'),
(5, 'Users', 'Limited access to the system.', '{\"all\": false}');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `invoice_number`, `customer_id`, `user_id`, `sale_date`, `total_amount`, `payment_method`, `notes`) VALUES
(1, 'INV-2025-04-0001', 2, 1, '2025-04-23 05:55:39', 110.00, 'Credit Card', 'Generated from order');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `product_id`, `quantity_sold`, `unit_price`, `discount`, `subtotal`) VALUES
(1, 1, 4, 1, 110.00, 0.00, 110.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stock_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `location_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`stock_id`, `product_id`, `supplier_id`, `quantity_added`, `current_quantity`, `unit_cost`, `batch_number`, `date_added`, `expiry_date`, `location_code`) VALUES
(1, 1, 1, 50, 50, 45.50, 'BT20250422', '2025-04-22 22:19:33', NULL, 'WHSE-B3-S4'),
(2, 2, 1, 1, 1, 700.00, NULL, '2025-04-22 22:33:06', NULL, NULL),
(3, 4, 7, 20, 18, 85.00, 'AF1-BATCH-2025-04', '2025-04-23 05:55:39', NULL, 'WH-A3-15');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Pacifico Oyanib', 'Pacifico Oyanib', 'pacifico@example.com', '555-123-4567', '123 Supplier Street, Tech City, TC 12345', 1, '2025-04-22 22:19:32', '2025-04-22 22:19:32'),
(2, 'Nike', 'John Parker', 'wholesale@nike.com', '503-671-6453', '1 Bowerman Dr, Beaverton, OR 97005, USA', 1, '2025-04-22 22:38:33', '2025-04-22 22:38:33'),
(3, 'Adidas', 'Sarah Mueller', 'b2b@adidas.com', '971-234-5678', '5055 N Greeley Ave, Portland, OR 97217, USA', 1, '2025-04-22 22:38:33', '2025-04-22 22:38:33'),
(4, 'Jordan Brand', 'Michael Wilson', 'jordan.wholesale@nike.com', '503-671-8900', '1 Bowerman Dr, Beaverton, OR 97005, USA', 1, '2025-04-22 22:38:33', '2025-04-22 22:38:33'),
(5, 'Puma', 'Thomas Schmitt', 'suppliers@puma.com', '617-866-3990', '10 Liberty Way, Westford, MA 01886, USA', 1, '2025-04-22 22:38:33', '2025-04-22 22:38:33'),
(6, 'Under Armour', 'David Jacobs', 'retail.support@underarmour.com', '410-454-6428', '1020 Hull St, Baltimore, MD 21230, USA', 1, '2025-04-22 22:38:33', '2025-04-22 22:38:33'),
(7, 'Nike Distribution', 'John Smith', 'wholesale@nike.example.com', '800-555-NIKE', '1 Nike Way, Beaverton, OR 97005', 1, '2025-04-23 05:55:39', '2025-04-23 05:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_products`
--

CREATE TABLE `supplier_products` (
  `supplier_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_product_code` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `lead_time_days` int(11) DEFAULT NULL,
  `min_order_quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role_id`, `email`, `created_at`, `last_login`) VALUES
(1, 'PacificoOyaniblll', '$2y$10$yfVgf3CZ9yXPLt6lKeLleeI3lgVn83zu.i0rE0vhSTbGQjm9sPQ5W', 2, 'PacificoOyaniblll@gmail.com', '2025-04-09 17:32:08', '2025-04-22 09:00:06'),
(2, 'tester', '$2y$10$Cd0KXnDH5qEVUx/2Z5etwu5aNCkVLh9vrBRcuSc1Vm4TqDezKrVNG', 5, 'tester@gmail.com', '2025-04-18 22:04:30', '2025-04-23 00:32:30'),
(3, 'user123', '$2y$10$pb36vDitEcMtAEnkE1fmtOGRgVWjt7rqRxf1RPqikAC3dbQhjnv5m', 5, 'user123@gmail.com', '2025-04-19 14:39:13', '2025-04-19 14:40:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD PRIMARY KEY (`supplier_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `stock_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `supplier_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
