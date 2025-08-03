-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2025 at 08:06 AM
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
-- Database: `dana_concrete_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `purchase_materials`
--

CREATE TABLE `purchase_materials` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') NOT NULL DEFAULT 'دانە',
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `transfer_loss` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_loss` decimal(15,2) NOT NULL DEFAULT 0.00,
  `usd_to_iqd_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_per_unit_usd` decimal(15,2) DEFAULT 0.00,
  `price_per_unit_iqd` decimal(15,2) DEFAULT 0.00,
  `total_price_usd` decimal(15,2) DEFAULT 0.00,
  `total_price_iqd` decimal(15,2) DEFAULT 0.00,
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار',
  `purchase_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  -- Conversion fields for tracking base units
  `base_quantity` decimal(15,2) DEFAULT 0.00 COMMENT 'بڕی بنەڕەتی بە دانە',
  `base_price_per_unit_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دۆلار',
  `base_price_per_unit_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دینار'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `purchase_materials`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_delete` AFTER DELETE ON `purchase_materials` FOR EACH ROW BEGIN
    -- Update the quantity in list_materials by subtracting the deleted base quantity
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW BEGIN
    -- Update the quantity in list_materials by adding the purchased base quantity
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW BEGIN
    -- First, subtract the old base quantity from the material
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
    
    -- Then, add the new base quantity to the material
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_receipt_number` (`receipt_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  ADD CONSTRAINT `purchase_materials_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `list_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
