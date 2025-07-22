-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 08:18 AM
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
  `formulas_id` int(11) NOT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concrete_receipts`
--

INSERT INTO `concrete_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `formulas_id`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `created_at`, `updated_at`) VALUES
(2, 'A-0002', 21, 'سلێمانی', 20.00, 30, 8, 38, 9, 38, '2025-07-17 13:05:21', NULL),
(3, 'A-0003', 21, 'سلێمانی', 20.00, 30, 8, 37, 9, 38, '2025-07-17 13:25:29', NULL),
(4, 'A-0004', NULL, 'سلێمانی', 20.00, 27, 11, 38, 10, 37, '2025-07-17 13:30:39', NULL),
(5, 'A-0005', 21, 'سلێمانی', 20.00, 27, 11, 34, 9, 35, '2025-07-17 13:35:25', NULL),
(6, 'A-0006', 21, 'سلێمانی', 20.00, 26, 11, 36, 10, 34, '2025-07-17 13:36:42', NULL),
(7, 'A-0007', 21, 'سلێمانی', 20.00, 30, 8, 37, 9, 38, '2025-07-17 14:09:54', NULL),
(8, 'A-0008', 21, 'سلێمانی', 20.00, 31, 8, 38, 9, 38, '2025-07-18 20:20:03', NULL),
(9, 'A-0009', 21, 'سلێمانی', 20.00, 27, 8, 37, 10, 38, '2025-07-18 21:03:21', NULL),
(10, 'A-0010', NULL, 'سلێمانی', 20.00, 30, 11, 38, 9, 38, '2025-07-18 21:03:42', NULL),
(11, 'A-0011', 21, 'سلێمانی', 20.00, 30, 8, 38, 9, 37, '2025-07-19 17:50:36', NULL),
(12, 'A-0012', 21, 'سلێمانی', 20.00, 30, 8, 38, 9, 38, '2025-07-19 17:58:10', NULL),
(13, 'A-0013', 21, 'سلێمانی', 20.00, 30, 11, 38, 9, 36, '2025-07-19 18:01:04', NULL),
(14, 'A-0014', 21, 'سلێمانی', 12.00, 27, 11, 27, 19, 34, '2025-07-20 08:43:28', NULL),
(15, 'A-0015', 21, 'سلێمانی', 12.00, 26, 11, 26, 20, 37, '2025-07-20 08:44:48', NULL),
(16, 'A-0016', 21, 'سلێمانی', 12.00, 27, 8, 27, 20, 37, '2025-07-20 08:51:17', NULL),
(17, 'A-0017', 21, 'سلێمانی', 12.00, 26, 11, 25, 21, 34, '2025-07-20 08:59:18', NULL),
(18, 'A-0018', 21, 'سلێمانی', 12.00, 30, 8, 27, 21, 38, '2025-07-20 09:00:59', NULL),
(19, 'A-0019', 21, 'سلێمانی', 12.00, 30, 11, 27, 21, 32, '2025-07-20 09:02:33', NULL),
(20, 'A-0020', 21, 'سلێمانی', 12.00, 27, 8, 27, 22, 34, '2025-07-20 09:04:58', NULL),
(21, 'A-0021', 21, 'سلێمانی', 12.00, 30, 8, 27, 22, 34, '2025-07-20 09:08:09', NULL),
(23, 'A-0022', 21, 'سلێمانی', 12.00, 30, 8, 26, 20, 37, '2025-07-20 09:13:54', NULL),
(24, 'A-0023', 22, 'سلێمانی', 12.00, 31, 11, 27, 22, 38, '2025-07-20 11:47:07', NULL),
(25, 'A-0024', 21, 'سلێمانی', 12.00, 31, 8, 26, 22, 33, '2025-07-20 11:48:00', NULL),
(26, 'A-0025', 22, 'سلێمانی', 12.00, 27, 8, 26, 22, 34, '2025-07-20 11:49:13', NULL),
(27, 'A-0026', 22, 'سلێمانی', 12.00, 30, 8, 27, 22, 37, '2025-07-20 11:58:19', NULL),
(28, 'A-0027', 22, 'سلێمانی', 12.00, 27, 8, 27, 22, 38, '2025-07-20 11:59:18', NULL),
(29, 'A-0028', 22, 'Iraq-Kurdistan, Sulaymaniyah ', 12.00, 27, 8, 26, 20, 37, '2025-07-21 09:36:31', NULL),
(30, 'A-0029', 21, 'سلێمانی', 12.00, 31, 8, 26, 22, 37, '2025-07-21 09:38:51', NULL),
(31, 'A-0030', 22, 'سلێمانی', 12.00, 30, 11, 26, 22, 37, '2025-07-21 09:39:13', NULL),
(32, 'A-0031', 21, 'سلێمانی', 12.00, 30, 8, 26, 22, 37, '2025-07-21 09:41:56', NULL),
(33, 'A-0032', 21, 'سلێمانی', 12.00, 30, 8, 26, 21, 38, '2025-07-21 09:52:25', NULL),
(34, 'A-0033', 21, 'سلێمانی', 2.00, 26, 8, 25, 20, 33, '2025-07-21 09:53:42', NULL),
(35, 'A-0034', 21, 'Iraq-Kurdistan, Sulaymaniyah ', 2.00, 27, 8, 26, 21, 38, '2025-07-21 09:54:37', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

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
