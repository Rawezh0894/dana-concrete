-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2025 at 09:21 AM
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
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_id`) VALUES
(9, 'admin', 6),
(11, 'admin', 3),
(12, 'admin', 2),
(13, 'admin', 1),
(14, 'admin', 7),
(15, 'admin', 8),
(18, 'admin', 9),
(19, 'admin', 13),
(20, 'admin', 12),
(21, 'admin', 10),
(22, 'admin', 16),
(23, 'admin', 15),
(24, 'admin', 14),
(26, 'admin', 17),
(28, 'admin', 18),
(31, 'admin', 21),
(32, 'admin', 19),
(33, 'admin', 20),
(34, 'admin', 4),
(35, 'admin', 23),
(36, 'admin', 24),
(37, 'admin', 25),
(38, 'admin', 26),
(39, 'admin', 28),
(40, 'admin', 27),
(41, 'admin', 29),
(42, 'admin', 11),
(43, 'admin', 30),
(44, 'admin', 31),
(45, 'admin', 37),
(46, 'admin', 36),
(47, 'admin', 35),
(48, 'admin', 34),
(49, 'admin', 33),
(50, 'admin', 32),
(51, 'admin', 38),
(52, 'admin', 39),
(55, 'admin', 5),
(56, 'admin', 55),
(58, 'admin', 56),
(59, 'admin', 57),
(60, 'admin', 22),
(61, 'admin', 54),
(62, 'admin', 58),
(63, 'admin', 59),
(64, 'admin', 60),
(65, 'admin', 61),
(69, 'admin', 64),
(70, 'admin', 65),
(71, 'admin', 66),
(72, 'admin', 67),
(73, 'admin', 68),
(81, 'admin', 62),
(82, 'admin', 70),
(83, 'admin', 69),
(86, 'admin', 71),
(87, 'admin', 72),
(88, 'admin', 73),
(89, 'admin', 74),
(90, 'admin', 75),
(93, 'user', 13),
(97, 'user', 71),
(98, 'user', 72),
(99, 'user', 75);

-- Add new permission for income from cars
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES (76, 'view_income_from_cars', 'بینینی داهاتی سەیارەکان');

-- Add permission to admin role
INSERT INTO `role_permissions` (`id`, `role`, `permission_id`) VALUES (100, 'admin', 76);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
