-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2025 at 07:06 PM
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
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `role` enum('شۆفێر','موحاسیب','وەکیل') NOT NULL,
  `salary` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `mobile`, `role`, `salary`) VALUES
(4, 'شاخەوان', '07709245698', 'شۆفێر', 500000.00),
(22, 'بازیان', '07731445414', 'شۆفێر', 2000000.00),
(23, 'دانا', '07729950101', 'موحاسیب', 1150000.00),
(24, 'شاخەوان', '07736943309', 'شۆفێر', 750000.00),
(25, 'بەرزان', '07719702022', 'شۆفێر', 1150000.00),
(26, 'شاڵاو', '07748168525', 'شۆفێر', 1150000.00),
(27, 'سەربەست', '07769716198', 'شۆفێر', 1150000.00),
(28, 'سەردار', '07732708916', 'شۆفێر', 1000000.00),
(29, 'طارق', '07701967088', 'شۆفێر', 750000.00),
(30, 'عماد', '07824865268', 'شۆفێر', 750000.00),
(31, 'علاوی', '07824864286', 'شۆفێر', 750000.00),
(32, 'احمد(ابو روەیدا)', '07824872364', 'شۆفێر', 750000.00),
(33, 'ئامانج', '07748214162', 'شۆفێر', 750000.00),
(34, 'وشیار', '07741599776', 'شۆفێر', 750000.00),
(35, 'شڤان', '07719682166', 'شۆفێر', 1000000.00),
(36, 'هاوکار میکسەر', '07740823861', 'شۆفێر', 750000.00),
(37, 'عادل', '07701790359', 'شۆفێر', 750000.00),
(38, 'ڕزگار', '07738887562', 'شۆفێر', 750000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
