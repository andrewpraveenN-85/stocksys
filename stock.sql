-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 01, 2025 at 11:38 AM
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
-- Database: `stocksys`
--

-- --------------------------------------------------------

--
-- Table structure for table `grns`
--

CREATE TABLE `grns` (
  `id` int(11) NOT NULL,
  `grn_no` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `grn_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `total_cost` decimal(18,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn_items`
--

CREATE TABLE `grn_items` (
  `id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `raw_material_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty` decimal(18,3) NOT NULL,
  `unit_cost` decimal(18,3) NOT NULL,
  `total_cost` decimal(18,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productions`
--

CREATE TABLE `productions` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `batches` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `total_output_qty` decimal(18,3) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `opening_qty` decimal(18,3) DEFAULT 0.000,
  `current_qty` decimal(18,3) DEFAULT 0.000,
  `selling_price` decimal(18,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `unit_id`, `opening_qty`, `current_qty`, `selling_price`, `created_at`) VALUES
(1, 'Devilled Beef', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(2, 'Pepper Beef', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(3, 'Pork Stew', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(4, 'Mixed Grill', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(5, 'Butter Prawns', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(6, 'Seafood Platter', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(7, 'Devilled Cuttlefish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(8, 'Hot Butter Cuttlefish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(9, 'Hadallo', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(10, 'Fried Lake Fish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(11, 'Fried Chicken', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(12, 'Chicken Stew', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(13, 'Kankun Beef', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(14, 'Grilled Whole Fish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(15, 'Devilled Chicken', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(16, 'Devilled Fish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(17, 'Fried Fish', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(18, 'Fried Fish with Kankun', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18'),
(19, 'Devilled Prawns', 3, 0.000, 0.000, 0.00, '2025-08-31 17:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `opening_qty` decimal(18,3) DEFAULT 0.000,
  `current_qty` decimal(18,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `name`, `unit_id`, `opening_qty`, `current_qty`, `created_at`) VALUES
(1, 'Beef', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(2, 'Pork', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(3, 'Chicken', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(4, 'Fish', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(5, 'Cuttle fish', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(6, 'Prawns', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(7, 'Crab', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(8, 'Tomato', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(9, 'Onion', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(10, 'Capsicum', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(11, 'Carrot', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(12, 'Celery', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(13, 'Parsley', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(14, 'Green chilli', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(15, 'Kankun', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(16, 'Veges (mix)', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(17, 'Mix salad', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(18, 'Cucumber', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(19, 'Iceberg', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(20, 'Lettuce', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(21, 'Lemon/Lime', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(22, 'Spring onion', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(23, 'Ginger/Garlic (paste)', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(24, 'Ginger', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(25, 'Garlic', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(26, 'Salt', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(27, 'Pepper', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(28, 'Black Pepper', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(29, 'Chilli flakes', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(30, 'Chilli powder', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(31, 'Turmeric powder', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(32, 'Baking soda', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(33, 'Corn flour', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(34, 'Flour', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(35, 'Tomato sauce', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(36, 'BBQ sauce', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(37, 'Tartar sauce', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(38, 'Soya sauce', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(39, 'Dark soya sauce', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(40, 'Oyster sauce', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(41, 'Sesame oil', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(42, 'Chinese cooking wine/oil', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(43, 'Water', 2, 0.000, 0.000, '2025-08-31 17:54:18'),
(44, 'Oil', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(45, 'Butter', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(46, 'Egg', 3, 0.000, 0.000, '2025-08-31 17:54:18'),
(47, 'Sugar', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(48, 'Gas', 1, 0.000, 0.000, '2025-08-31 17:54:18'),
(49, 'potato', 1, 0.000, 0.000, '2025-08-31 17:54:41'),
(50, 'Sausage', 1, 0.000, 0.000, '2025-08-31 17:54:41'),
(51, 'hadallo', 1, 0.000, 0.000, '2025-08-31 17:54:41'),
(52, 'Curry leaves', 1, 0.000, 0.000, '2025-08-31 17:54:41'),
(53, 'dry chilli', 1, 0.000, 0.000, '2025-08-31 17:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `yield_qty` decimal(18,3) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `product_id`, `name`, `yield_qty`, `notes`, `created_at`) VALUES
(1, 1, 'Devilled Beef Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(2, 2, 'Pepper Beef Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(3, 3, 'Pork Stew Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(4, 4, 'Mixed Grill Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(5, 5, 'Butter Prawns Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(6, 6, 'Seafood Platter Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(7, 7, 'Devilled Cuttlefish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(8, 8, 'Hot Butter Cuttlefish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(9, 9, 'Hadallo Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(10, 10, 'Fried Lake Fish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(11, 11, 'Fried Chicken Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(12, 12, 'Chicken Stew Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(13, 13, 'Kankun Beef Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(14, 14, 'Grilled Whole Fish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(15, 15, 'Devilled Chicken Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(16, 16, 'Devilled Fish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(17, 17, 'Fried Fish Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(18, 18, 'Fried Fish with Kankun Recipe', 1.000, NULL, '2025-08-31 17:54:18'),
(19, 19, 'Devilled Prawns Recipe', 1.000, NULL, '2025-08-31 17:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_items`
--

CREATE TABLE `recipe_items` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `qty` decimal(18,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipe_items`
--

INSERT INTO `recipe_items` (`id`, `recipe_id`, `raw_material_id`, `qty`) VALUES
(1, 1, 1, 200.000),
(2, 1, 8, 100.000),
(3, 1, 9, 100.000),
(4, 1, 23, 20.000),
(5, 1, 26, 6.000),
(6, 1, 27, 6.000),
(7, 1, 35, 200.000),
(8, 1, 38, 20.000),
(9, 1, 29, 25.000),
(10, 1, 44, 125.000),
(11, 1, 10, 100.000),
(12, 1, 48, 250.000),
(13, 2, 44, 125.000),
(14, 2, 1, 200.000),
(15, 2, 9, 200.000),
(16, 2, 10, 125.000),
(17, 2, 47, 10.000),
(18, 2, 38, 20.000),
(19, 2, 39, 5.000),
(20, 2, 43, 10.000),
(21, 2, 32, 5.000),
(22, 2, 33, 25.000),
(23, 2, 28, 12.000),
(24, 2, 26, 6.000),
(25, 2, 48, 250.000),
(37, 3, 2, 200.000),
(38, 3, 49, 150.000),
(39, 3, 11, 65.000),
(40, 3, 9, 200.000),
(41, 3, 12, 13.000),
(42, 3, 31, 6.000),
(43, 3, 26, 6.000),
(44, 3, 27, 6.000),
(45, 3, 33, 35.000),
(46, 3, 10, 100.000),
(47, 3, 48, 250.000);

-- --------------------------------------------------------

--
-- Table structure for table `stock_ledger`
--

CREATE TABLE `stock_ledger` (
  `id` int(11) NOT NULL,
  `item_type` enum('raw','product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `ref_type` enum('OPENING','GRN','PROD_CONS','PROD_OUT','ADJUST') NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `entry_date` datetime NOT NULL,
  `qty_in` decimal(18,3) DEFAULT 0.000,
  `qty_out` decimal(18,3) DEFAULT 0.000,
  `balance_after` decimal(18,3) DEFAULT 0.000,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `symbol`) VALUES
(1, 'Gram', 'g'),
(2, 'Milliliter', 'ml'),
(3, 'Piece', 'pc');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grns`
--
ALTER TABLE `grns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grn_no` (`grn_no`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `raw_material_id` (`raw_material_id`),
  ADD KEY `fk_grn_items_product` (`product_id`);

--
-- Indexes for table `productions`
--
ALTER TABLE `productions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `recipe_items`
--
ALTER TABLE `recipe_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `raw_material_id` (`raw_material_id`);

--
-- Indexes for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grns`
--
ALTER TABLE `grns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn_items`
--
ALTER TABLE `grn_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `recipe_items`
--
ALTER TABLE `recipe_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `stock_ledger`
--
ALTER TABLE `stock_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grns`
--
ALTER TABLE `grns`
  ADD CONSTRAINT `grns_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD CONSTRAINT `fk_grn_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `grn_items_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grn_items_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`);

--
-- Constraints for table `productions`
--
ALTER TABLE `productions`
  ADD CONSTRAINT `productions_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `recipe_items`
--
ALTER TABLE `recipe_items`
  ADD CONSTRAINT `recipe_items_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_items_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
