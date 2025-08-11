-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2025 at 11:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00"; -- Iraq timezone (UTC+3)


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

INSERT INTO `concrete_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `price_per_meter`, `formulas_id`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `created_at`, `updated_at`, `receiver_name`, `notes`, `payment_status`) VALUES
(38, 'A-0001', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 19, 35, '2025-07-30 09:28:28', NULL, 'محمد', NULL, 'unpaid'),
(39, 'A-0002', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 22, 36, '2025-07-30 09:54:44', NULL, 'محمد', NULL, 'unpaid'),
(40, 'A-0003', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 36, '2025-08-07 15:01:13', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(41, 'A-0004', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 37, '2025-08-07 15:03:27', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(42, 'A-0005', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 38, '2025-08-07 15:07:20', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(43, 'A-0006', 32, 'پیرەمەگرون', 12.00, 0.00, 19, 8, 27, 21, 38, '2025-08-07 15:10:47', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(44, 'A-0007', 32, 'پیرەمەگرون', 12.00, 0.00, 18, 8, 27, 22, 38, '2025-08-07 15:15:02', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(45, 'A-0008', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 26, 21, 38, '2025-08-07 15:15:23', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(46, 'A-0009', 32, 'پیرەمەگرون', 12.00, 0.00, 18, NULL, 25, 20, 36, '2025-08-07 15:19:20', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(47, 'A-0010', 32, 'سلێمانی', 12.00, 0.00, 21, 8, NULL, 22, 37, '2025-08-07 15:20:38', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(48, 'A-0011', 32, 'سلێمانی', 12.00, 0.00, 19, 8, 27, 21, 36, '2025-08-07 15:21:12', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(49, 'A-0012', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 27, 22, 38, '2025-08-07 15:22:22', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(50, 'A-0013', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 26, 22, 38, '2025-08-07 15:23:56', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(51, 'A-0014', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 27, 21, 38, '2025-08-07 15:25:56', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(52, 'A-0015', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 26, 22, 37, '2025-08-07 15:28:58', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(53, 'A-0016', 32, 'سلێمانی', 10.00, 0.00, 17, 8, 26, 20, 35, '2025-08-07 15:30:35', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(54, 'A-0017', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 20, 35, '2025-08-07 15:31:00', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(55, 'A-0018', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 22, 36, '2025-08-07 15:32:50', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(56, 'A-0019', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 22, 36, '2025-08-07 15:36:10', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(57, 'A-0020', 32, 'سلێمانی', 5.00, 0.00, 18, NULL, 26, 20, 36, '2025-08-07 15:36:45', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(58, 'A-0021', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 22, 37, '2025-08-07 15:39:29', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(59, 'A-0022', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 36, '2025-08-07 15:40:19', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(60, 'A-0022', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 36, '2025-08-07 15:40:28', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(61, 'A-0023', 32, 'سلێمانی', 5.00, 0.00, 19, 8, 26, 20, 38, '2025-08-07 15:42:25', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(62, 'A-0023', 32, 'سلێمانی', 5.00, 0.00, 19, 8, 26, 20, 38, '2025-08-07 15:42:38', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(63, 'A-0024', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 22, 37, '2025-08-07 15:52:09', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(64, 'A-0025', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 22, 36, '2025-08-07 15:54:57', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(65, 'A-0026', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 21, 37, '2025-08-07 15:55:17', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(66, 'A-0027', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 37, '2025-08-07 16:20:08', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(67, 'A-0028', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 25, 19, 35, '2025-08-07 16:22:43', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(68, 'A-0029', 32, 'سلێمانی', 5.00, 0.00, 18, NULL, 26, 21, 37, '2025-08-07 16:25:14', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(69, 'A-0030', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 20, 37, '2025-08-07 16:43:34', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

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
