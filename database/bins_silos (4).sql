-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 01:54 PM
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
-- Table structure for table `bins_silos`
--

CREATE TABLE `bins_silos` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `type` enum('چاو','سایلۆ','تەنکی','عەمبار') DEFAULT NULL,
  `material_type` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `total_value` decimal(12,2) DEFAULT 0.00,
  `average_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bins_silos`
--

INSERT INTO `bins_silos` (`id`, `name`, `type`, `material_type`, `amount`, `total_value`, `average_price`) VALUES
(1, 'چاوی ١', 'چاو', 'لمی ڕەش', 39080.00, 390.80, 0.01),
(2, 'چاوی ٢', 'چاو', 'لمی کەسارە', 33870.00, 0.00, 0.00),
(3, 'چاوی ٣', 'چاو', 'چەو', 64840.00, -1296.80, -0.02),
(4, 'چاوی ٤', 'چاو', 'چەو', 64945.00, 0.00, 0.00),
(5, 'سایلۆی ١', 'سایلۆ', 'چیمەنتۆ', 43690.00, 0.00, 0.00),
(6, 'سایلۆی ٢', 'سایلۆ', 'چیمەنتۆ', 33430.00, 0.00, 0.00),
(7, 'تەنکی دەرمان ١', 'تەنکی', 'دەرمان', 49819.00, 0.00, 0.00),
(8, 'تەکی گاز ١', 'تەنکی', 'گاز', 50000.00, 0.00, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bins_silos`
--
ALTER TABLE `bins_silos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bins_silos`
--
ALTER TABLE `bins_silos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
