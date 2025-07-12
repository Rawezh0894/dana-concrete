-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 03:20 PM
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
-- Table structure for table `cash_box`
--

CREATE TABLE `cash_box` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount_iqd` decimal(20,2) DEFAULT 0.00,
  `amount_usd` decimal(14,2) DEFAULT 0.00,
  `currency` enum('دینار','دۆلار') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_box`
--

INSERT INTO `cash_box` (`id`, `date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`, `created_at`) VALUES
(3, '2025-07-09', 'deposit', 0.00, 500.00, 'دۆلار', '', 1, '2025-07-09 10:14:49'),
(5, '2025-07-09', 'deposit', 300000.00, 0.00, 'دینار', '', 1, '2025-07-09 10:21:34'),
(6, '2025-07-10', 'deposit', 0.00, 300.00, 'دۆلار', '', 1, '2025-07-09 10:21:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cash_box`
--
ALTER TABLE `cash_box`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cash_box`
--
ALTER TABLE `cash_box`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
