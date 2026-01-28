-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 11:18 AM
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
-- Table structure for table `service_receipts`
--

CREATE TABLE `service_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'The concrete company renting the equipment',
  `location` varchar(255) DEFAULT NULL,
  `meter_amount` decimal(10,2) DEFAULT NULL COMMENT 'Quantity in cubic meters',
  `price_per_meter` decimal(10,2) DEFAULT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `receiver_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_type` enum('cash','credit') DEFAULT 'credit',
  `paid_usd` decimal(15,4) DEFAULT 0.0000,
  `paid_iqd` decimal(15,4) DEFAULT 0.0000,
  `exchange_rate` decimal(15,4) DEFAULT 0.0000,
  `payment_status` enum('paid','unpaid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_price_computed` decimal(15,2) GENERATED ALWAYS AS (`meter_amount` * `price_per_meter`) VIRTUAL,
  `remaining_balance` decimal(15,2) GENERATED ALWAYS AS (`meter_amount` * `price_per_meter` - (`paid_usd` + `paid_iqd` / `exchange_rate`)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_receipts`
--

INSERT INTO `service_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `price_per_meter`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `receiver_name`, `notes`, `payment_type`, `paid_usd`, `paid_iqd`, `exchange_rate`, `payment_status`, `created_at`, `updated_at`) VALUES
(2, '20260128-001', 492, 'کارەبەکەی قەیوان', 198.00, 8.00, 12, 56, NULL, NULL, NULL, '', 'credit', 0.0000, 0.0000, 0.0000, 'unpaid', '2026-01-26 08:08:00', '2026-01-28 08:39:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `service_receipts`
--
ALTER TABLE `service_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `pump_car_id` (`pump_car_id`),
  ADD KEY `pump_driver_id` (`pump_driver_id`),
  ADD KEY `mixer_car_id` (`mixer_car_id`),
  ADD KEY `mixer_driver_id` (`mixer_driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `service_receipts`
--
ALTER TABLE `service_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_receipts`
--
ALTER TABLE `service_receipts`
  ADD CONSTRAINT `service_receipts_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_receipts_mixer_car_fk` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_receipts_mixer_driver_fk` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_receipts_pump_car_fk` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_receipts_pump_driver_fk` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
