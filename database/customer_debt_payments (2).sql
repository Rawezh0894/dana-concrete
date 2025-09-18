-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 01:59 PM
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
(4, 32, '2025-07-28', 139000.00, 50.00, 50000.00, 0.00, '', 85.97, 0.00);

--
-- Triggers `customer_debt_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_customer_debt_payments` AFTER INSERT ON `customer_debt_payments` FOR EACH ROW BEGIN
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_customer_debt_payments` BEFORE DELETE ON `customer_debt_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی deposit لە cash_box
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_customer_debt_payments` BEFORE UPDATE ON `customer_debt_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی deposit کۆن
    IF OLD.paid_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    IF OLD.paid_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = 'گەڕاندنەوەی قەرزی کڕیار';
    END IF;
    -- زیادکردنی deposit نوێ
    IF NEW.paid_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.paid_usd, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
    IF NEW.paid_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.paid_iqd, 0, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL);
    END IF;
END
$$
DELIMITER ;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
