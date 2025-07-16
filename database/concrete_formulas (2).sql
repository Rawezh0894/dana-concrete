-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2025 at 06:43 AM
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
-- Table structure for table `concrete_formulas`
--

CREATE TABLE `concrete_formulas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('عەرزی تێکەڵ','عەرزی سادە','سەقف','پایە') NOT NULL,
  `strength_type` enum('kg','mpa') NOT NULL,
  `strength_kg` enum('150','200','250','300','350','400','450') NOT NULL,
  `strength_mpa` enum('15','18','21','25','30','35','40','45','50','55') NOT NULL,
  `black_sand_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `brown_sand_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gravel_bin3_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gravel_bin4_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cement_cem1_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cement_cem2_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `water_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `additive_kg` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concrete_formulas`
--

INSERT INTO `concrete_formulas` (`id`, `name`, `type`, `strength_type`, `strength_kg`, `strength_mpa`, `black_sand_kg`, `brown_sand_kg`, `gravel_bin3_kg`, `gravel_bin4_kg`, `cement_cem1_kg`, `cement_cem2_kg`, `water_kg`, `additive_kg`) VALUES
(17, 'SAQF 350', 'سەقف', 'kg', '350', '', 1140.00, 0.00, 430.00, 430.00, 0.00, 270.00, 110.00, 2.00),
(18, 'SAQF 300', 'سەقف', 'kg', '300', '', 1170.00, 0.00, 425.00, 425.00, 0.00, 250.00, 110.00, 1.50),
(19, 'SAQF 400 ', 'سەقف', 'kg', '400', '', 1100.00, 0.00, 450.00, 440.00, 0.00, 285.00, 100.00, 2.50),
(21, 'PAYA 25 MEGA', 'پایە', 'kg', '', '25', 670.00, 650.00, 350.00, 330.00, 265.00, 0.00, 115.00, 2.00),
(22, 'PAYA 30 MEGA', 'پایە', 'kg', '', '30', 670.00, 650.00, 335.00, 325.00, 285.00, 0.00, 115.00, 3.00),
(23, 'PAYA 35 MEGA', 'پایە', 'kg', '', '35', 600.00, 660.00, 350.00, 345.00, 300.00, 0.00, 120.00, 3.00),
(24, 'ARZY 15 MEGA', 'عەرزی سادە', 'kg', '', '15', 0.00, 1290.00, 375.00, 375.00, 150.00, 0.00, 160.00, 1.50),
(25, 'ARZY 18 MEGA', 'عەرزی تێکەڵ', 'kg', '', '18', 500.00, 640.00, 450.00, 440.00, 200.00, 0.00, 120.00, 1.00),
(26, 'ARZY 21 MEGA', 'عەرزی تێکەڵ', 'kg', '', '21', 450.00, 700.00, 420.00, 420.00, 225.00, 0.00, 135.00, 2.00),
(27, 'ARZY 25 MEGA', 'عەرزی تێکەڵ', 'kg', '', '25', 505.00, 640.00, 410.00, 420.00, 245.00, 0.00, 130.00, 2.00),
(30, 'ARZY 30 MEGA', 'عەرزی تێکەڵ', 'kg', '', '30', 500.00, 635.00, 400.00, 400.00, 280.00, 0.00, 135.00, 2.50),
(31, 'ARZY 35 MEGA', 'عەرزی تێکەڵ', 'kg', '', '35', 530.00, 560.00, 420.00, 415.00, 310.00, 0.00, 120.00, 3.00),
(32, 'SAQF 40 M', 'سەقف', 'kg', '', '40', 1050.00, 0.00, 425.00, 425.00, 0.00, 370.00, 95.00, 4.00),
(33, 'SAQF 35 MEGA', 'سەقف', 'kg', '', '35', 1150.00, 0.00, 410.00, 410.00, 0.00, 310.00, 90.00, 3.50),
(34, 'ARZY 18 M', 'عەرزی تێکەڵ', 'kg', '', '18', 400.00, 720.00, 450.00, 440.00, 225.00, 0.00, 120.00, 2.50),
(35, 'ARZY 21 M', 'عەرزی تێکەڵ', 'kg', '', '21', 430.00, 700.00, 420.00, 420.00, 250.00, 0.00, 130.00, 2.50),
(36, 'ARZY 25 M', 'عەرزی تێکەڵ', 'kg', '', '25', 500.00, 620.00, 410.00, 410.00, 280.00, 0.00, 130.00, 3.00),
(37, 'ARZY 30 M', 'عەرزی تێکەڵ', 'kg', '', '30', 710.00, 380.00, 420.00, 410.00, 0.00, 320.00, 100.00, 3.50),
(38, 'ARZY 35 M', 'عەرزی تێکەڵ', 'kg', '', '35', 700.00, 330.00, 420.00, 410.00, 0.00, 370.00, 120.00, 4.00),
(39, 'PAYA 25 M', 'عەرزی تێکەڵ', 'kg', '', '25', 600.00, 600.00, 375.00, 375.00, 0.00, 290.00, 140.00, 3.00),
(40, 'PAYA 30 M', 'عەرزی تێکەڵ', 'kg', '', '30', 590.00, 590.00, 385.00, 380.00, 0.00, 300.00, 140.00, 3.30),
(41, 'PAYA 35 M', 'عەرزی تێکەڵ', 'kg', '', '35', 560.00, 555.00, 388.00, 385.00, 0.00, 385.00, 110.00, 4.00),
(42, 'Chawy lws saqf 350', 'سەقف', 'kg', '350', '', 1100.00, 0.00, 910.00, 0.00, 0.00, 270.00, 100.00, 2.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `concrete_formulas`
--
ALTER TABLE `concrete_formulas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `concrete_formulas`
--
ALTER TABLE `concrete_formulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
