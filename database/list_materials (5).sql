-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2025 at 08:05 AM
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
-- Table structure for table `list_materials`
--

CREATE TABLE `list_materials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') NOT NULL DEFAULT 'دانە',
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار',
  `purchase_price_usd` decimal(15,2) DEFAULT 0.00,
  `purchase_price_iqd` decimal(15,2) DEFAULT 0.00,
  -- Conversion fields for different unit types
  `pieces_per_carton` int(11) DEFAULT NULL COMMENT 'ژمارەی دانە لە کارتۆن',
  `buckets_per_barrel` int(11) DEFAULT NULL COMMENT 'ژمارەی دەبە لە بەرمیل',
  `liters_per_bucket` decimal(10,2) DEFAULT NULL COMMENT 'ژمارەی لیتر لە دەبە',
  `liters_per_barrel` decimal(10,2) DEFAULT NULL COMMENT 'کۆی لیتر لە بەرمیل',
  -- Calculated prices for different units
  `price_per_piece_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دۆلار',
  `price_per_piece_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دینار',
  `price_per_bucket_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دۆلار',
  `price_per_bucket_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دینار',
  `price_per_liter_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دۆلار',
  `price_per_liter_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دینار'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `list_materials`
--
ALTER TABLE `list_materials`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `list_materials`
--
ALTER TABLE `list_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
