-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 06:36 AM
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
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `salary` decimal(15,2) NOT NULL,
  `karwanhisabi` varchar(255) NOT NULL,
  `bonus` decimal(15,2) DEFAULT 0.00,
  `pay_month` varchar(7) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_payments`
--

INSERT INTO `employee_payments` (`id`, `employee_id`, `salary`, `karwanhisabi`, `bonus`, `pay_month`, `created_at`, `updated_at`, `total`) VALUES
(4, 33, 750000.00, '0', 0.00, '2025-07', '2025-07-12 07:10:39', '2025-07-12 07:10:39', 750000.00);

--
-- Triggers `employee_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at AND `type` = 'withdraw' AND amount_iqd = OLD.total AND currency = 'دینار' AND note = CONCAT('پارەدان بە کارمەند: ', OLD.employee_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی مامەڵەی کۆن
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at AND `type` = 'withdraw' AND amount_iqd = OLD.total AND currency = 'دینار' AND note = CONCAT('پارەدان بە کارمەند: ', OLD.employee_id);

    -- زیادکردنی مامەڵەی نوێ
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
