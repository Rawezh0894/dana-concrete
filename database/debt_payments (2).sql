-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 12:35 PM
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
-- Table structure for table `debt_payments`
--

CREATE TABLE `debt_payments` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `amount_usd` decimal(14,2) DEFAULT 0.00,
  `amount_iqd` decimal(20,2) DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `dollar_rate` decimal(10,2) DEFAULT 150000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `debt_payments`
--

INSERT INTO `debt_payments` (`id`, `company_id`, `date`, `amount_usd`, `amount_iqd`, `note`, `created_by`, `dollar_rate`) VALUES
(1, 1, '2025-07-02', 188.00, 0.00, '', 1, 150000.00),
(2, 1, '2025-07-01', 0.00, 50000.00, '', 1, 150000.00),
(3, 1, '2025-07-16', 50.00, 50000.00, '', 1, 150000.00),
(6, 1, '2025-07-03', 50.00, 0.00, '', 1, 150000.00),
(7, 1, '2025-07-03', 1.00, 0.00, '', 1, 150000.00),
(8, 1, '2025-07-04', 188.00, 0.00, '', 1, 150000.00),
(9, 1, '2025-07-04', 11.00, 0.00, '', 1, 150000.00),
(10, 1, '2025-07-06', 0.00, 100000.00, '', 1, 150000.00),
(12, 21, '2025-07-06', 200.00, 0.00, '', 1, 150000.00),
(13, 21, '2025-07-06', 200.00, 0.00, '', 1, 150000.00),
(14, 20, '2025-07-06', 0.00, 69000.00, '', 1, 150000.00),
(15, 20, '2025-07-06', 0.00, 550000.00, '', 1, 150000.00),
(21, 22, '2025-07-06', 600.00, 0.00, '', 1, 150000.00),
(22, 22, '2025-07-06', 50.00, 0.00, '', 1, 150000.00),
(24, 20, '2025-07-10', 0.00, 300000.00, '', 1, 150000.00),
(25, 21, '2025-07-10', 300.00, 0.00, '', 1, 150000.00),
(26, 20, '2025-07-10', 0.00, 249000.00, '', 1, 150000.00);

--
-- Triggers `debt_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_debt_given` AFTER INSERT ON `debt_payments` FOR EACH ROW BEGIN
    -- ئەگەر ئەم مامەڵەیە قەرز دانەوەیە (بە شێوەیەکی جیاواز تۆمار بکە لە note یان ستوونێکی تر)
    IF NEW.note = 'قەرز دانەوە' THEN
        IF NEW.amount_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('قەرز دانەوە بۆ کۆمپانیا: ', NEW.company_id), NEW.created_by);
        END IF;
        IF NEW.amount_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('قەرز دانەوە بۆ کۆمپانیا: ', NEW.company_id), NEW.created_by);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_debt_payments` AFTER INSERT ON `debt_payments` FOR EACH ROW BEGIN
    -- بۆ دۆلار
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    -- بۆ دینار
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_debt_payments` BEFORE DELETE ON `debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_debt_payments` BEFORE UPDATE ON `debt_payments` FOR EACH ROW BEGIN
    -- سڕینەوەی deposit کۆن
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'deposit' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    -- زیادکردنی deposit نوێ
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD CONSTRAINT `debt_payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
