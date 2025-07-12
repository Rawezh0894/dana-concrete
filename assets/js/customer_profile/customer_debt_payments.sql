-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2025 at 02:39 PM
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
-- Table structure for table `customer_debt_payments`
--

CREATE TABLE `customer_debt_payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `dolar_rate` decimal(10,2) DEFAULT 0.00,
  `paid_usd` decimal(14,2) DEFAULT 0.00,
  `paid_iqd` decimal(20,2) DEFAULT 0.00,
  `discount` decimal(14,2) DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `from_opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `from_sales_usd` decimal(14,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_debt_payments`
--

INSERT INTO `customer_debt_payments` (`id`, `customer_id`, `date`, `dolar_rate`, `paid_usd`, `paid_iqd`, `discount`, `note`, `from_opening_debt_usd`, `from_sales_usd`) VALUES
(1, 4, '2025-07-04', 150000.00, 0.00, 150000.00, 0.00, '', 0.00, 0.00),
(2, 4, '2025-07-04', 150000.00, 0.00, 150000.00, 0.00, '', 0.00, 0.00),
(3, 4, '2025-07-05', 150000.00, 57.00, 0.00, 0.50, '', 0.00, 0.00),
(4, 4, '2025-07-05', 150000.00, 100.00, 0.00, 0.00, '', 0.00, 0.00),
(11, 8, '2025-07-05', 150000.00, 500.00, 0.00, 0.00, '', 0.00, 0.00),
(24, 11, '2025-07-05', 150000.00, 200.00, 0.00, 0.00, '', 0.00, 200.00),
(25, 11, '2025-07-05', 150000.00, 50.00, 0.00, 0.00, '', 0.00, 50.00),
(26, 11, '2025-07-05', 150000.00, 0.00, 50000.00, 0.00, '', 33.33, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  ADD CONSTRAINT `customer_debt_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
