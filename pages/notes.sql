-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2025 at 06:47 AM
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
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `customer_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `formula_id` int(11) NOT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `date`, `time`, `customer_id`, `location`, `recipient`, `meter_amount`, `formula_id`, `mixer_car_id`, `mixer_driver_id`, `pump_car_id`, `pump_driver_id`, `is_read`, `created_at`, `updated_at`) VALUES
(1, '2025-07-29', '15:00:00', 32, 'سلێمانی', 'کاک ڕاوێژ ', 50.00, 21, 22, 37, 8, 34, 1, '2025-07-28 10:48:54', '2025-07-28 11:50:35'),
(2, '2025-07-28', '15:15:00', 32, 'سلێمانی', 'کاک ڕاوێژ ', 50.00, 42, 10, 30, 8, 25, 1, '2025-07-28 11:15:41', '2025-07-28 11:53:27'),
(3, '2025-07-28', '14:18:00', 32, 'سلێمانی', 'کاک ڕاوێژ ', 50.00, 39, 22, 36, 8, 36, 1, '2025-07-28 11:16:06', '2025-07-28 11:55:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formula_id` (`formula_id`),
  ADD KEY `mixer_car_id` (`mixer_car_id`),
  ADD KEY `mixer_driver_id` (`mixer_driver_id`),
  ADD KEY `pump_car_id` (`pump_car_id`),
  ADD KEY `pump_driver_id` (`pump_driver_id`),
  ADD KEY `date` (`date`),
  ADD KEY `is_read` (`is_read`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`formula_id`) REFERENCES `concrete_formulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
