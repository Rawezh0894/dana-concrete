-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2025 at 07:14 PM
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
-- Table structure for table `concrete_receipts`
--

CREATE TABLE `concrete_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(100) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `price_per_meter` decimal(10,2) DEFAULT 0.00,
  `formulas_id` int(11) NOT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `receiver_name` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concrete_receipts`
--

INSERT INTO `concrete_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `price_per_meter`, `formulas_id`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `created_at`, `updated_at`, `receiver_name`, `notes`) VALUES
(38, 'A-0001', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 19, 35, '2025-07-30 09:28:28', NULL, 'محمد', NULL),
(39, 'A-0002', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 22, 36, '2025-07-30 09:54:44', NULL, 'محمد', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formulas_id` (`formulas_id`),
  ADD KEY `pump_car_id` (`pump_car_id`),
  ADD KEY `pump_driver_id` (`pump_driver_id`),
  ADD KEY `mixer_car_id` (`mixer_car_id`),
  ADD KEY `mixer_driver_id` (`mixer_driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  ADD CONSTRAINT `concrete_receipts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_2` FOREIGN KEY (`formulas_id`) REFERENCES `concrete_formulas` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_7` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_8` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
