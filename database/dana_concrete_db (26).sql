-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2025 at 08:39 PM
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
(1, 'چاوی ١', 'چاو', 'لمی ڕەش', 106020.00, 2120.40, 0.02),
(2, 'چاوی ٢', 'چاو', 'لمی کەسارە', 33170.00, 0.00, 0.00),
(3, 'چاوی ٣', 'چاو', 'چەو', 144695.00, 1956.95, 0.01),
(4, 'چاوی ٤', 'چاو', 'چەو', 12810.00, 0.00, 0.00),
(5, 'سایلۆی ١', 'سایلۆ', 'چیمەنتۆ', 43465.00, 0.00, 0.00),
(6, 'سایلۆی ٢', 'سایلۆ', 'چیمەنتۆ', 32395.00, 0.00, 0.00),
(7, 'تەنکی دەرمان ١', 'تەنکی', 'دەرمان', 49810.00, 0.00, 0.00),
(8, 'تەکی گاز ١', 'تەنکی', 'گاز', 50000.00, 0.00, 0.00);

--
-- Triggers `bins_silos`
--
DELIMITER $$
CREATE TRIGGER `bins_silos_no_negative_stock` BEFORE UPDATE ON `bins_silos` FOR EACH ROW BEGIN
    IF NEW.amount < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock cannot be negative!';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_bins_silos_no_negative_amount` BEFORE UPDATE ON `bins_silos` FOR EACH ROW BEGIN
  IF NEW.amount < 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'بڕی ستۆک نابێت منفی بێت!';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `name`) VALUES
(4, ' p2'),
(5, 'm2');

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
(6, '2025-07-10', 'deposit', 0.00, 300.00, 'دۆلار', '', 1, '2025-07-09 10:21:56'),
(7, '2025-07-08', 'withdraw', 0.00, 900.00, 'دۆلار', 'کڕین: invoice 465', 12, '2025-07-09 13:36:33'),
(8, '2025-07-08', 'withdraw', 0.00, 0.00, 'دۆلار', 'کڕین: invoice 66', 12, '2025-07-09 13:44:41'),
(9, '2025-07-09', 'deposit', 0.00, 500.00, 'دۆلار', '', 1, '2025-07-09 14:05:26'),
(10, '2025-07-09', 'deposit', 0.00, 200.00, 'دۆلار', '', 1, '2025-07-09 14:10:24'),
(11, '2025-07-09', 'deposit', 0.00, 200.00, 'دۆلار', '', 1, '2025-07-09 14:11:19'),
(12, '2025-07-09', 'withdraw', 0.00, 720.00, 'دۆلار', 'کڕین: invoice 3652', NULL, '2025-07-10 10:28:24'),
(13, '2025-07-09', 'withdraw', 0.00, 250.00, 'دۆلار', 'کڕین: invoice 463', NULL, '2025-07-10 11:05:59');

--
-- Triggers `cash_box`
--
DELIMITER $$
CREATE TRIGGER `trg_before_withdraw_cash_box` BEFORE INSERT ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_to_check DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (هەموو مامەڵەکان)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / (dollar_rate / 100)
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / (dollar_rate / 100)
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی نوێ بە دۆلار
    IF NEW.currency = 'دۆلار' THEN
        SET usd_to_check = NEW.amount_usd;
    ELSE
        SET usd_to_check = NEW.amount_iqd / (dollar_rate / 100);
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار
    IF NEW.type = 'withdraw' AND usd_to_check > total_usd THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'بڕی پێویست لە قاسەی گشتی (دۆلار) نییە!';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `debt_usd` decimal(14,2) DEFAULT 0.00,
  `debt_iqd` decimal(20,2) DEFAULT 0.00,
  `opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `opening_debt_iqd` decimal(20,2) DEFAULT 0.00,
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `name`, `debt_usd`, `debt_iqd`, `opening_debt_usd`, `opening_debt_iqd`, `currency_type`) VALUES
(1, 'ماس', 0.00, 0.00, 0.00, 0.00, 'دینار'),
(12, 'ڕاوێژ', 0.00, 0.00, 0.00, 0.00, 'دینار'),
(14, 'test', -750.00, 0.00, 1000.00, 0.00, 'دۆلار'),
(15, 'test2', 0.00, 0.00, 400.00, 0.00, 'دۆلار'),
(18, 'TEST3', 20.00, 0.00, 500.00, 0.00, 'دۆلار'),
(20, 'TEST4', 0.00, 0.00, 0.00, 549990.00, 'دینار'),
(21, 'test56', 4850.00, 0.00, 300.00, 0.00, 'دۆلار'),
(22, 'test55', 0.00, 0.00, 0.00, 0.00, 'دۆلار');

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

-- --------------------------------------------------------

--
-- Table structure for table `concrete_receipts`
--

CREATE TABLE `concrete_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(100) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `formulas_id` int(11) NOT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concrete_receipts`
--

INSERT INTO `concrete_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `formulas_id`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `created_at`, `updated_at`) VALUES
(2, 'A-0001', 4, 'پیرەمەگروون ', 20.00, 23, 4, 4, 5, 4, '2025-07-05 18:21:27', NULL),
(3, 'A-0002', 9, 'پیرەمەگروون ', 20.00, 27, 4, 4, 5, 4, '2025-07-05 18:22:52', NULL),
(4, 'A-0003', 4, 'هەولێر', 10.00, 27, 4, 4, 5, 4, '2025-07-06 09:33:57', NULL),
(5, 'A-0004', 4, 'سلێمانی', 20.00, 30, 4, 36, 5, 34, '2025-07-07 13:09:21', NULL),
(6, 'A-0005', NULL, 'کوردتان', 20.00, 30, 4, 34, 5, 37, '2025-07-10 11:35:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile1` varchar(20) NOT NULL,
  `mobile2` varchar(20) DEFAULT NULL,
  `debt_iqd` decimal(20,2) DEFAULT 0.00,
  `debt_usd` decimal(14,2) DEFAULT 0.00,
  `opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `opening_debt_iqd` decimal(20,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `mobile1`, `mobile2`, `debt_iqd`, `debt_usd`, `opening_debt_usd`, `opening_debt_iqd`) VALUES
(4, 'حاجی', '07709240895', '', 0.00, 442.50, 100.00, 0.00),
(8, 'ڕاوێژ', '07709240894', '', 0.00, 0.00, 490.00, 0.00),
(9, 'محمد ', '07709240897', '', 0.00, 750.00, 500.00, 0.00),
(10, 'test', '07709240892', '', 0.00, 750.00, 960.00, 0.00),
(11, 'test2', '07501211541', '', 0.00, 200.00, 466.67, 0.00),
(12, 'حاجی عەطا ', '07709245656', '', 0.00, 400.00, 0.00, 0.00),
(13, 'fff', '07709245646', '', 0.00, 50.00, 0.00, 0.00),
(14, 'test65', '07709245455', '', 0.00, 50.00, 0.00, 0.00),
(15, 'sds', '07709245623', '', 0.00, 0.00, 0.00, 0.00);

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
(11, 8, '2025-07-05', 150000.00, 500.00, 0.00, 0.00, '', 0.00, 0.00),
(25, 11, '2025-07-05', 150000.00, 50.00, 0.00, 0.00, '', 0.00, 50.00),
(26, 11, '2025-07-05', 150000.00, 0.00, 50000.00, 0.00, '', 33.33, 0.00),
(30, 12, '2025-07-06', 150000.00, 500.00, 0.00, 0.00, '', 500.00, 0.00),
(32, 14, '2025-07-09', 150000.00, 50.00, 0.00, 0.00, '', 0.00, 50.00),
(33, 4, '2025-07-10', 150000.00, 65.00, 0.00, 0.00, '', 0.00, 65.00);

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
(22, 22, '2025-07-06', 50.00, 0.00, '', 1, 150000.00);

--
-- Triggers `debt_payments`
--
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

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`) VALUES
(1, 'ئەیوب'),
(2, 'دارا');

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
(3, 'ڕاوێژ', '07709240894', 'موحاسیب', 800000.00),
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
(3, 3, 800000.00, '50000', 10000.00, '2025-04', '2025-07-03 10:04:35', '2025-07-07 14:52:38', 860000.00),
(5, 24, 750000.00, '100000', 100000.00, '2025-01', '2025-07-03 10:40:13', '2025-07-07 14:53:17', 950000.00);

--
-- Triggers `employee_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.salary, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`) VALUES
(1, 'سلێمانی');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('black_sand','brown_sand','gravel','cement','medicine','gas') NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `name`, `type`, `unit`) VALUES
(1, 'لمی ڕەش', 'black_sand', 'کیلۆگرام'),
(2, 'چەو', 'gravel', 'کیلۆگرام'),
(3, 'چیمەنتۆ', 'cement', 'کیلۆگرام'),
(4, 'دەرمان', 'medicine', 'کیلۆگرام'),
(5, 'لمی کەسارە', 'brown_sand', 'کیلۆگرام'),
(6, 'گاز', 'gas', 'کیلۆگرام');

-- --------------------------------------------------------

--
-- Table structure for table `other_expenses`
--

CREATE TABLE `other_expenses` (
  `id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `person_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') NOT NULL,
  `currency_type` enum('دینار','دۆلار') NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `amount_iqd` decimal(20,2) DEFAULT 0.00,
  `amount_usd` decimal(14,2) DEFAULT 0.00,
  `paid_iqd` decimal(20,2) DEFAULT 0.00,
  `paid_usd` decimal(14,2) DEFAULT 0.00,
  `exchange_rate` decimal(10,2) DEFAULT 150000.00,
  `remaining_iqd` decimal(20,2) DEFAULT 0.00,
  `remaining_usd` decimal(14,2) DEFAULT 0.00,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `other_expenses`
--

INSERT INTO `other_expenses` (`id`, `purpose`, `person_id`, `employee_id`, `car_id`, `payment_type`, `currency_type`, `invoice_number`, `amount_iqd`, `amount_usd`, `paid_iqd`, `paid_usd`, `exchange_rate`, `remaining_iqd`, `remaining_usd`, `date`) VALUES
(22, 'ڕۆن گۆڕین', 5, 32, 4, 'قەرز', 'دینار', '4523', 150000.00, 0.00, 99000.00, 0.00, 150000.00, 51000.00, 0.00, '2025-07-07'),
(24, 'ەرر', 5, 33, 4, 'قەرز', 'دۆلار', '123654', 0.00, 150.00, 0.00, 75.00, 150000.00, 0.00, 75.00, '2025-07-07'),
(25, 'sd', 6, 25, 5, 'قەرز', 'دۆلار', '554', 0.00, 150.00, 0.00, 150.00, 150000.00, 0.00, 0.00, '2025-07-07'),
(26, 'l', 6, 22, 4, 'قەرز', 'دۆلار', '5455', 0.00, 50.00, 0.00, 25.00, 150000.00, 0.00, 25.00, '2025-07-07'),
(27, 'د', 7, 23, 4, 'قەرز', 'دۆلار', '46', 0.00, 150.00, 0.00, 0.00, 150000.00, 0.00, 100.00, '2025-07-07'),
(28, 's', 8, 28, 4, 'قەرز', 'دۆلار', '', 0.00, 150.00, 0.00, 0.00, 150000.00, 0.00, 650.00, '2025-07-07'),
(30, 'ج', 11, 22, 4, 'قەرز', 'دۆلار', '135', 0.00, 100.00, 0.00, 0.00, 150000.00, 0.00, 100.00, '2025-07-08'),
(32, 'ج', 11, 32, 5, 'قەرز', 'دینار', '455', 50000.00, 0.00, 0.00, 0.00, 150000.00, 50000.00, 0.00, '2025-07-08');

--
-- Triggers `other_expenses`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses` FOR EACH ROW BEGIN
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.currency_type = 'دۆلار' AND NEW.paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.currency_type = 'دینار' AND NEW.paid_iqd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('خەرجی تر: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `other_expense_persons`
--

CREATE TABLE `other_expense_persons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `expense_usd` decimal(15,2) DEFAULT 0.00,
  `expense_iqd` decimal(15,2) DEFAULT 0.00,
  `opening_debt_usd` decimal(15,2) DEFAULT 0.00,
  `opening_debt_iqd` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `other_expense_persons`
--

INSERT INTO `other_expense_persons` (`id`, `name`, `expense_usd`, `expense_iqd`, `opening_debt_usd`, `opening_debt_iqd`) VALUES
(2, 'عسمان', 0.00, 0.00, 0.00, 0.00),
(5, 'ڕاوێژ', 475.00, 50000.00, 0.00, 0.00),
(6, 'test', 150.00, 0.00, 500.00, 0.00),
(7, 'test2', 125.00, 0.00, 0.00, 0.00),
(8, 'test3', 650.00, 0.00, 0.00, 0.00),
(11, 'ڕاوێژ2', 100.00, 50000.00, 375.00, 200000.00);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'add_user', 'زیادکردنی بەکارهێنەر'),
(2, 'edit_user', 'دەستکاری بەکارهێنەر'),
(3, 'delete_user', 'سڕینەوەی بەکارهێنەر'),
(4, 'view_users', 'بینینی لیستی بەکارهێنەران'),
(5, 'view_dashboard', 'بینینی داشبۆرد'),
(6, 'add_material', 'زیادکردنی مەواد'),
(7, 'add_company', 'زیادکردنی کۆمپانیا'),
(8, 'edit_company', 'دەسکاری کۆمپانیا'),
(9, 'delete_company', 'سڕینەوەی کۆمپانیا'),
(10, 'add_purchase', 'زیادکردنی کڕین'),
(11, 'edit_purchase', 'دەستکاری کڕین'),
(12, 'delete_purchase', 'سڕینەوەی کڕین'),
(13, 'view_purchase', 'بینینی کڕینەکان'),
(14, 'add_debt', 'زیادکردنی دانەوەی قەرز'),
(15, 'update_debt', 'دەستکاری دانەوەی قەرز'),
(16, 'delete_debt', 'سڕینەوەی دانەوەی قەرز'),
(17, 'view_debt', 'بینینی مێژووی دانەوەی قەرز'),
(18, 'add_employee', 'زیادکردنی کارمەند'),
(19, 'edit_employee', 'دەستکاری کارمەند'),
(20, 'delete_employee', 'سڕینەوەی کارمەند'),
(21, 'view_employee', 'بینینی لیستی کارمەندەکان'),
(22, 'view_employee_payment', 'بینینی پارەدان بە کارمەند'),
(23, 'add_payment', 'زیادکردنی پارەدان بە کارمەند'),
(24, 'delete_payment', 'سڕینەوەی پارەدان بە کارمەند'),
(25, 'edit_payment', 'دەستکاری پارەدان بە کارمەند'),
(26, 'view_car', 'بینینی سەیارەکان'),
(27, 'edit_car', 'دەستکاری سەیارەکان'),
(28, 'delete_car', 'سڕینەوەی سەیارەکان'),
(29, 'add_car', 'زیادکردنی سەیارە'),
(30, 'view_person_other_expenses', 'بینینی لیستی کەسانی خەرجی تر'),
(31, 'delete_person_other_expenses', 'سڕینەوەی کەسانی خەرجی تر'),
(32, 'edit_person_other_expenses', 'دەستکاری کەسانی خەرجی تر'),
(33, 'update_person_other_expenses', 'نوێکردنەوەی کەسانی خەرجی تر'),
(34, 'view_person_other_expenses_profile', 'بینینی پرۆفایلی کەس'),
(35, 'delete_person_other_expenses_profile', 'سڕینەوەی پرۆفایلی کەس'),
(36, 'edit_person_other_expenses_profile', 'دەستکاری پرۆفایلی کەس'),
(37, 'update_person_other_expenses_profile', 'نوێکردنەوەی پرۆفایلی کەس'),
(38, 'view_materials', 'بینینی لیستی مەواد'),
(39, 'view_accounts', 'بینینی هەژمارەکان'),
(40, 'view_vouchers', 'بینینی پسوڵەکان'),
(54, 'view_other_expenses', 'بینینی لیستی خەرجی تر'),
(55, 'add_other_expenses', 'زیادکردنی خەرجی تر'),
(56, 'edit_other_expenses', 'دەستکاری خەرجی تر'),
(57, 'delete_other_expenses', 'سڕینەوەی خەرجی تر'),
(58, 'view_concrete_formulas', 'بینینی فۆرمولای کۆنکرێت'),
(59, 'add_concrete_formulas', 'زیادکردنی فۆرمولای کۆنکرێت'),
(60, 'edit_concrete_formulas', 'دەستکاری فۆرمولای کۆنکرێت'),
(61, 'delete_concrete_formulas', 'سڕینەوەی فۆرمولای کۆنکرێت'),
(62, 'view_sale', 'بینینی فرۆشتن'),
(63, 'edit_sale', 'دەستکاری فرۆشتن'),
(64, 'delete_sale', 'سڕینەوەی فرۆشتن'),
(65, 'update_sale', 'نوێکردنەوەی فرۆشتن'),
(66, 'view_customer', 'بینینی کڕیار'),
(67, 'add_customer', 'زیادکردنی کڕیار'),
(68, 'delete_customer', 'سڕینەوەی کڕیار'),
(69, 'update_customer', 'نوێکردنەوەی کڕیار'),
(70, 'add_sale', 'زیادکردنی فرۆشتن'),
(71, 'view_concrete_receipts', 'بینینی پسوڵەی کۆنکرێت'),
(72, 'add_concrete_receipts', 'زیادکردنی پسوڵەی کۆنکرێت'),
(73, 'edit_concrete_receipts', 'دەستکاری پسوڵەی کۆنکرێت'),
(74, 'delete_concrete_receipts', 'سڕینەوەی پسوڵەی کۆنکرێت'),
(75, 'print_concrete_receipts', 'چاپکردنی پسوڵەی کۆنکرێت'),
(77, 'view_reports', 'بینینی راپۆرتەکان');

-- --------------------------------------------------------

--
-- Table structure for table `person_other_expenses_debt_payments`
--

CREATE TABLE `person_other_expenses_debt_payments` (
  `id` int(11) NOT NULL,
  `person_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount_usd` decimal(15,2) DEFAULT 0.00,
  `amount_iqd` decimal(15,2) DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `person_other_expenses_debt_payments`
--

INSERT INTO `person_other_expenses_debt_payments` (`id`, `person_id`, `date`, `amount_usd`, `amount_iqd`, `note`, `created_at`) VALUES
(46, 7, '2025-07-07', 25.00, 0.00, '', '2025-07-07 18:47:55'),
(47, 7, '2025-07-07', 25.00, 0.00, '', '2025-07-07 18:49:43'),
(48, 7, '2025-07-07', 375.00, 0.00, '', '2025-07-07 18:54:57'),
(51, 7, '2025-07-07', 25.00, 0.00, '', '2025-07-07 19:12:44'),
(53, 7, '2025-07-07', 525.00, 0.00, '', '2025-07-07 19:17:42'),
(54, 7, '2025-07-07', 25.00, 0.00, '', '2025-07-07 19:19:41');

--
-- Triggers `person_other_expenses_debt_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_person_other_expenses_debt_payments` AFTER INSERT ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    -- بۆ دۆلار
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    -- بۆ دینار
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'deposit', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `driver` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `material_id` int(11) NOT NULL,
  `kg` decimal(10,2) DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `payment_type` enum('نەقد','قەرز') NOT NULL,
  `exchange_rate` decimal(10,0) DEFAULT 150000,
  `company_id` int(11) NOT NULL,
  `type` enum('دینار','دۆلار') NOT NULL,
  `paid_usd` decimal(10,0) DEFAULT 0,
  `paid_iqd` decimal(10,0) DEFAULT 0,
  `remaining_usd` decimal(10,2) DEFAULT 0.00,
  `remaining_iqd` decimal(10,0) DEFAULT NULL,
  `bin_id` int(11) DEFAULT NULL,
  `amount_iqd` decimal(12,2) DEFAULT 0.00,
  `price_per_kg_iqd` decimal(10,2) DEFAULT 0.00,
  `price_per_kg_usd` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `date`, `invoice_number`, `driver`, `location`, `material_id`, `kg`, `price`, `payment_type`, `exchange_rate`, `company_id`, `type`, `paid_usd`, `paid_iqd`, `remaining_usd`, `remaining_iqd`, `bin_id`, `amount_iqd`, `price_per_kg_iqd`, `price_per_kg_usd`) VALUES
(69, '2025-07-01', '888', 'ئەیوب', 'سلێمانی', 2, 36000.00, 1188.00, 'قەرز', 150000, 1, 'دۆلار', 0, 0, 701.00, 0, 3, 0.00, 10.00, 33.00),
(70, '2025-07-02', '55', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 1088000, 1, 1188000.00, 33000.00, 0.00),
(71, '2025-07-03', '1546', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00),
(78, '2025-07-06', '46', 'ئەیوب', 'سلێمانی', 2, 20000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 40000, 3, 40000.00, 2000.00, 0.00),
(79, '2025-07-02', '55', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 1088000, 1, 1188000.00, 33000.00, 0.00),
(80, '2025-07-08', '569', 'ئەیوب', 'سلێمانی', 1, 36000.00, 900.00, 'نەقد', 150000, 1, 'دۆلار', 900, 0, 0.00, 0, 1, 0.00, 0.00, 25.00),
(81, '2025-07-08', '465', 'ئەیوب', 'سلێمانی', 2, 25000.00, 900.00, 'نەقد', 150000, 12, 'دۆلار', 900, 0, 0.00, 0, 3, 0.00, 0.00, 36.00),
(82, '2025-07-08', '66', 'دارا', 'سلێمانی', 2, 41000.00, 984.00, 'قەرز', 150000, 12, 'دۆلار', 0, 0, 984.00, 0, 3, 0.00, 0.00, 24.00),
(83, '2025-07-02', '55', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 1088000, 1, 1188000.00, 33000.00, 0.00),
(89, '2025-07-09', '3652', 'دارا', 'سلێمانی', 2, 36000.00, 720.00, 'نەقد', 150000, 12, 'دۆلار', 720, 0, 0.00, 0, 3, 0.00, 0.00, 20.00),
(99, '2025-07-09', '463', 'دارا', 'سلێمانی', 2, 10000.00, 250.00, 'نەقد', 150000, 12, 'دۆلار', 250, 0, 0.00, 0, 3, 0.00, 0.00, 25.00);

--
-- Triggers `purchases`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_delete` AFTER DELETE ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_usd DECIMAL(20,2);
  DECLARE dollar_rate DECIMAL(20,2);

  SET dollar_rate = OLD.exchange_rate / 100;

  IF OLD.type = 'دۆلار' THEN
    SET total_usd = OLD.paid_usd + IFNULL(OLD.remaining_usd, 0);
  ELSEIF OLD.type = 'دینار' THEN
    SET total_usd = (OLD.paid_iqd + IFNULL(OLD.remaining_iqd, 0)) / dollar_rate;
  ELSE
    SET total_usd = 0;
  END IF;

  IF OLD.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount - OLD.kg,
      total_value = total_value - total_usd,
      average_price = 
        CASE 
          WHEN (amount - OLD.kg) > 0 
          THEN (total_value - total_usd) / (amount - OLD.kg)
          ELSE 0
        END
    WHERE id = OLD.bin_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_insert` AFTER INSERT ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_usd DECIMAL(20,2);
  DECLARE dollar_rate DECIMAL(20,2);

  SET dollar_rate = NEW.exchange_rate / 100;

  IF NEW.type = 'دۆلار' THEN
    SET total_usd = NEW.paid_usd + IFNULL(NEW.remaining_usd, 0);
  ELSEIF NEW.type = 'دینار' THEN
    SET total_usd = (NEW.paid_iqd + IFNULL(NEW.remaining_iqd, 0)) / dollar_rate;
  ELSE
    SET total_usd = 0;
  END IF;

  IF NEW.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount + NEW.kg,
      total_value = total_value + total_usd,
      average_price = 
        CASE 
          WHEN (amount + NEW.kg) > 0 
          THEN (total_value + total_usd) / (amount + NEW.kg)
          ELSE 0
        END
    WHERE id = NEW.bin_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_update` AFTER UPDATE ON `purchases` FOR EACH ROW BEGIN
  DECLARE old_total_usd DECIMAL(20,2);
  DECLARE new_total_usd DECIMAL(20,2);
  DECLARE old_dollar_rate DECIMAL(20,2);
  DECLARE new_dollar_rate DECIMAL(20,2);

  SET old_dollar_rate = OLD.exchange_rate / 100;
  SET new_dollar_rate = NEW.exchange_rate / 100;

  -- هەژمارکردنی بڕی کۆن
  IF OLD.type = 'دۆلار' THEN
    SET old_total_usd = OLD.paid_usd + IFNULL(OLD.remaining_usd, 0);
  ELSEIF OLD.type = 'دینار' THEN
    SET old_total_usd = (OLD.paid_iqd + IFNULL(OLD.remaining_iqd, 0)) / old_dollar_rate;
  ELSE
    SET old_total_usd = 0;
  END IF;

  -- هەژمارکردنی بڕی نوێ
  IF NEW.type = 'دۆلار' THEN
    SET new_total_usd = NEW.paid_usd + IFNULL(NEW.remaining_usd, 0);
  ELSEIF NEW.type = 'دینار' THEN
    SET new_total_usd = (NEW.paid_iqd + IFNULL(NEW.remaining_iqd, 0)) / new_dollar_rate;
  ELSE
    SET new_total_usd = 0;
  END IF;

  -- گەڕاندنەوەی بڕی کۆن بۆ stock
  IF OLD.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount - OLD.kg,
      total_value = total_value - old_total_usd,
      average_price = 
        CASE 
          WHEN (amount - OLD.kg) > 0 
          THEN (total_value - old_total_usd) / (amount - OLD.kg)
          ELSE 0
        END
    WHERE id = OLD.bin_id;
  END IF;

  -- زیادکردنی بڕی نوێ بۆ stock
  IF NEW.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount + NEW.kg,
      total_value = total_value + new_total_usd,
      average_price = 
        CASE 
          WHEN (amount + NEW.kg) > 0 
          THEN (total_value + new_total_usd) / (amount + NEW.kg)
          ELSE 0
        END
    WHERE id = NEW.bin_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_purchase_cash_box` AFTER INSERT ON `purchases` FOR EACH ROW BEGIN
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.type = 'دۆلار' THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', 0, NEW.paid_usd, 'دۆلار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        ELSE
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.date, 'withdraw', NEW.paid_iqd, 0, 'دینار', CONCAT('کڕین: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin_purchases`
--

CREATE TABLE `recycle_bin_purchases` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `driver` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `material_id` int(11) NOT NULL,
  `kg` decimal(10,2) DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `payment_type` enum('نەقد','قەرز') NOT NULL,
  `exchange_rate` decimal(10,0) DEFAULT 150000,
  `company_id` int(11) NOT NULL,
  `type` enum('دینار','دۆلار') NOT NULL,
  `paid_usd` decimal(10,0) DEFAULT 0,
  `paid_iqd` decimal(10,0) DEFAULT 0,
  `remaining_usd` decimal(10,2) DEFAULT 0.00,
  `remaining_iqd` decimal(10,0) DEFAULT NULL,
  `bin_id` int(11) DEFAULT NULL,
  `amount_iqd` decimal(12,2) DEFAULT 0.00,
  `price_per_kg_iqd` decimal(10,2) DEFAULT 0.00,
  `price_per_kg_usd` decimal(10,2) DEFAULT 0.00,
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recycle_bin_purchases`
--

INSERT INTO `recycle_bin_purchases` (`id`, `original_id`, `date`, `invoice_number`, `driver`, `location`, `material_id`, `kg`, `price`, `payment_type`, `exchange_rate`, `company_id`, `type`, `paid_usd`, `paid_iqd`, `remaining_usd`, `remaining_iqd`, `bin_id`, `amount_iqd`, `price_per_kg_iqd`, `price_per_kg_usd`, `deleted_at`) VALUES
(2, 74, '2025-07-05', '4375', 'دارا', 'سلێمانی', 2, 50000.00, 750.00, 'قەرز', 150000, 21, 'دۆلار', 0, 0, 75.00, 0, 4, 0.00, 0.00, 15.00, '2025-07-08 16:15:52'),
(3, 72, '2025-07-05', '1654', 'دارا', 'سلێمانی', 2, 50000.00, 750.00, 'قەرز', 150000, 14, 'دۆلار', 0, 0, 750.00, 0, 3, 0.00, 0.00, 15.00, '2025-07-08 16:15:54'),
(4, 73, '2025-07-05', '13658', 'دارا', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 20, 'دینار', 0, 0, 0.00, 200010, 1, 369000.00, 10250.00, 0.00, '2025-07-08 16:15:56'),
(5, 71, '2025-07-03', '1546', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-08 16:15:58'),
(6, 71, '2025-07-03', '1546', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-08 16:16:00'),
(7, 69, '2025-07-01', '888', 'ئەیوب', 'سلێمانی', 2, 36000.00, 1188.00, 'قەرز', 150000, 1, 'دۆلار', 0, 0, 701.00, 0, 3, 0.00, 10.00, 33.00, '2025-07-08 16:16:13'),
(8, 70, '2025-07-02', '55', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 1088000, 1, 1188000.00, 33000.00, 0.00, '2025-07-08 16:16:16');

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin_sales`
--

CREATE TABLE `recycle_bin_sales` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `order_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `formula_id` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'ئەدمین'),
(2, 'user', 'بەکارهێنەر'),
(3, 'accountant', 'موحاسیب'),
(4, 'manager', 'بەڕێوەبەر');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','user','accountant','manager') NOT NULL,
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
(102, 'accountant', 29),
(103, 'accountant', 7),
(104, 'accountant', 59),
(105, 'accountant', 72),
(106, 'accountant', 67),
(107, 'accountant', 14),
(108, 'accountant', 18),
(109, 'accountant', 6),
(110, 'accountant', 55),
(111, 'accountant', 23),
(112, 'accountant', 10),
(113, 'accountant', 70),
(115, 'accountant', 28),
(116, 'accountant', 9),
(117, 'accountant', 61),
(118, 'accountant', 74),
(119, 'accountant', 68),
(120, 'accountant', 16),
(121, 'accountant', 20),
(122, 'accountant', 57),
(123, 'accountant', 24),
(124, 'accountant', 31),
(125, 'accountant', 35),
(126, 'accountant', 12),
(127, 'accountant', 64),
(128, 'accountant', 3),
(129, 'accountant', 27),
(130, 'accountant', 8),
(131, 'accountant', 60),
(132, 'accountant', 73),
(133, 'accountant', 19),
(134, 'accountant', 56),
(135, 'accountant', 25),
(136, 'accountant', 32),
(137, 'accountant', 36),
(138, 'accountant', 11),
(139, 'accountant', 63),
(140, 'accountant', 2),
(141, 'accountant', 75),
(142, 'accountant', 69),
(143, 'accountant', 15),
(144, 'accountant', 33),
(145, 'accountant', 37),
(146, 'accountant', 65),
(147, 'accountant', 39),
(148, 'accountant', 26),
(149, 'accountant', 58),
(150, 'accountant', 71),
(151, 'accountant', 66),
(152, 'accountant', 5),
(153, 'accountant', 17),
(154, 'accountant', 21),
(155, 'accountant', 22),
(156, 'accountant', 38),
(157, 'accountant', 54),
(158, 'accountant', 30),
(159, 'accountant', 34),
(160, 'accountant', 13),
(161, 'accountant', 62),
(162, 'accountant', 4),
(163, 'accountant', 40),
(356, 'accountant', 29),
(357, 'accountant', 7),
(358, 'accountant', 59),
(359, 'accountant', 72),
(360, 'accountant', 67),
(361, 'accountant', 14),
(362, 'accountant', 18),
(363, 'accountant', 6),
(364, 'accountant', 55),
(365, 'accountant', 23),
(366, 'accountant', 10),
(367, 'accountant', 70),
(369, 'accountant', 28),
(370, 'accountant', 9),
(371, 'accountant', 61),
(372, 'accountant', 74),
(373, 'accountant', 68),
(374, 'accountant', 16),
(375, 'accountant', 20),
(376, 'accountant', 57),
(377, 'accountant', 24),
(378, 'accountant', 31),
(379, 'accountant', 35),
(380, 'accountant', 12),
(381, 'accountant', 64),
(382, 'accountant', 3),
(383, 'accountant', 27),
(384, 'accountant', 8),
(385, 'accountant', 60),
(386, 'accountant', 73),
(387, 'accountant', 19),
(388, 'accountant', 56),
(389, 'accountant', 25),
(390, 'accountant', 32),
(391, 'accountant', 36),
(392, 'accountant', 11),
(393, 'accountant', 63),
(394, 'accountant', 2),
(395, 'accountant', 75),
(396, 'accountant', 69),
(397, 'accountant', 15),
(398, 'accountant', 33),
(399, 'accountant', 37),
(400, 'accountant', 65),
(401, 'accountant', 39),
(402, 'accountant', 26),
(403, 'accountant', 58),
(404, 'accountant', 71),
(405, 'accountant', 66),
(406, 'accountant', 5),
(407, 'accountant', 17),
(408, 'accountant', 21),
(409, 'accountant', 22),
(410, 'accountant', 38),
(411, 'accountant', 54),
(412, 'accountant', 30),
(413, 'accountant', 34),
(414, 'accountant', 13),
(415, 'accountant', 62),
(416, 'accountant', 4),
(417, 'accountant', 40),
(610, 'manager', 29),
(611, 'manager', 7),
(612, 'manager', 59),
(613, 'manager', 72),
(614, 'manager', 67),
(615, 'manager', 14),
(616, 'manager', 18),
(617, 'manager', 6),
(618, 'manager', 55),
(619, 'manager', 23),
(620, 'manager', 10),
(621, 'manager', 70),
(622, 'manager', 1),
(623, 'manager', 28),
(624, 'manager', 9),
(625, 'manager', 61),
(626, 'manager', 74),
(627, 'manager', 68),
(628, 'manager', 16),
(629, 'manager', 20),
(630, 'manager', 57),
(631, 'manager', 24),
(632, 'manager', 31),
(633, 'manager', 35),
(634, 'manager', 12),
(635, 'manager', 64),
(637, 'manager', 27),
(638, 'manager', 8),
(639, 'manager', 60),
(640, 'manager', 73),
(641, 'manager', 19),
(642, 'manager', 56),
(643, 'manager', 25),
(644, 'manager', 32),
(645, 'manager', 36),
(646, 'manager', 11),
(647, 'manager', 63),
(648, 'manager', 2),
(649, 'manager', 75),
(650, 'manager', 69),
(651, 'manager', 15),
(652, 'manager', 33),
(653, 'manager', 37),
(654, 'manager', 65),
(655, 'manager', 39),
(656, 'manager', 26),
(657, 'manager', 58),
(658, 'manager', 71),
(659, 'manager', 66),
(660, 'manager', 5),
(661, 'manager', 17),
(662, 'manager', 21),
(663, 'manager', 22),
(664, 'manager', 38),
(665, 'manager', 54),
(666, 'manager', 30),
(667, 'manager', 34),
(668, 'manager', 13),
(669, 'manager', 62),
(671, 'manager', 40),
(737, 'admin', 4),
(738, 'accountant', 1),
(739, 'user', 5),
(740, 'user', 77),
(741, 'user', 71),
(742, 'user', 75);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `order_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `formula_id` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `recipient`, `location`, `quantity`, `price_per_unit`, `total_price`, `payment_type`, `amount_paid_usd`, `amount_paid_iq`, `dolar_rate`, `remaining_amount`, `invoice_number`, `order_date`, `notes`, `formula_id`, `discount`) VALUES
(1287, 4, 'دانا ', 'پیرەمەگروون', 6.00, 43.00, 258.00, 'قەرز', 0.00, 0.00, 150000.00, 0.00, '9654', '2025-07-03', '', 22, 0.50),
(1295, NULL, '', 'سلێمای', 1.00, 1.00, 1.00, 'قەرز', 0.00, 0.00, 150000.00, 1.00, '5', '2025-07-03', '', 17, 0.00),
(1298, 4, '', 'سلیمانی', 5.00, 20.00, 100.00, 'قەرز', 0.00, 0.00, 150000.00, 0.00, '564', '2025-07-03', '', 17, 0.00),
(1301, NULL, '', 'سلێمانی', 5.00, 48.00, 240.00, 'قەرز', 0.00, 0.00, 150000.00, 240.00, '4585', '2025-07-04', '', 17, 0.00),
(1302, NULL, '', 'سلێمانی', 4.00, 48.00, 192.00, 'قەرز', 42.00, 75000.00, 150000.00, 160.00, '474', '2025-07-04', '', 17, 0.00),
(1303, 8, '', 'test', 5.00, 50.00, 250.00, 'قەرز', 0.00, 50000.00, 150000.00, 0.00, '454', '2025-07-04', '', 17, 0.00),
(1304, 8, '', 'test', 5.00, 48.00, 240.00, 'قەرز', 0.00, 0.00, 150000.00, 0.00, '45447', '2025-07-04', '', 17, 0.00),
(1305, 9, '', 'ژ', 2.00, 50.00, 100.00, 'قەرز', 0.00, 0.00, 150000.00, 100.00, '45', '2025-07-04', '', 17, 0.00),
(1306, 9, '', 'ژ', 3.00, 50.00, 150.00, 'قەرز', 0.00, 0.00, 150000.00, 150.00, '4577', '2025-07-04', '', 17, 0.00),
(1307, 10, '', 'k', 5.00, 50.00, 250.00, 'قەرز', 0.00, 0.00, 150000.00, 250.00, '364', '2025-07-04', '', 17, 0.00),
(1309, 10, '', 'k', 10.00, 48.00, 480.00, 'قەرز', 0.00, 0.00, 150000.00, 20.00, '364', '2025-07-04', '', 17, 0.00),
(1310, 11, '', 's', 3.00, 50.00, 150.00, 'قەرز', 0.00, 0.00, 150000.00, 150.00, '544885', '2025-07-04', '', 17, 0.00),
(1311, 11, '', 'س', 2.00, 50.00, 100.00, 'قەرز', 0.00, 0.00, 150000.00, 50.00, '45454', '2025-07-04', '', 19, 0.00),
(1312, 4, '', 'دهۆک ', 5.00, 50.00, 250.00, 'نەقد', 250.00, 0.00, 150000.00, 0.00, '2384', '2025-07-04', '', 23, 0.00),
(1313, 12, 'ڕاوێژ', 'سلێمانی', 10.00, 40.00, 400.00, 'قەرز', 0.00, 0.00, 150000.00, 400.00, '3654', '2025-07-05', '', 30, 0.00),
(1316, 4, 'سلێمانی', 'کوردتان', 1.00, 50.00, 50.00, 'قەرز', 0.00, 0.00, 150000.00, 0.00, '4375', '2025-07-06', '', 17, 0.00),
(1317, 4, 'سلێمانی', 'کوردتان', 1.00, 55.00, 55.00, 'قەرز', 0.00, 0.00, 150000.00, 40.00, '4374', '2025-07-06', '', 17, 0.00),
(1318, 4, 'سلێمانی', 'سلێمانی', 2.00, 55.00, 110.00, 'قەرز', 0.00, 0.00, 150000.00, 110.00, '22', '2025-07-06', '', 24, 0.00),
(1319, 4, 'سلێمانی', 'کوردتان', 1.00, 50.00, 50.00, 'قەرز', 0.00, 0.00, 150000.00, 50.00, '45', '2025-07-06', '', 19, 0.00),
(1320, 13, 'کوردستان', 'Iraq-Kurdistan, Sulaymaniyah ', 1.00, 50.00, 50.00, 'قەرز', 0.00, 0.00, 150000.00, 50.00, '453', '2025-07-06', '', 26, 0.00),
(1321, 4, 'سلێمانی', 'کوردتان', 10.00, 50.00, 500.00, 'قەرز', 0.00, 0.00, 150000.00, 500.00, '437', '2025-07-06', '', 18, 0.00),
(1322, 4, 'سلێمانی', 'سلێمانی', 1.00, 10.00, 10.00, 'نەقد', 10.00, 0.00, 150000.00, 0.00, '5475', '2025-07-08', '', 18, 0.00),
(1323, 14, 'سلێمانی', 'سلێمانی', 2.00, 50.00, 100.00, 'قەرز', 0.00, 0.00, 150000.00, 50.00, '469', '2025-07-08', '', 18, 0.00);

--
-- Triggers `sales`
--
DELIMITER $$
CREATE TRIGGER `trg_after_delete_sale` AFTER DELETE ON `sales` FOR EACH ROW BEGIN
    DECLARE v_black_sand_kg DECIMAL(10,2);
    DECLARE v_brown_sand_kg DECIMAL(10,2);
    DECLARE v_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_cement_kg DECIMAL(10,2);
    DECLARE v_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_additive_kg DECIMAL(10,2);
    DECLARE v_total_volume DECIMAL(10,2);

    -- هەژمارکردنی مەتری سێجا
    SET v_total_volume = OLD.quantity;

    -- وەرگرتنی ڕێژەکانی فۆرمۆلاکە
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_black_sand_kg, v_brown_sand_kg, v_gravel_bin3_kg, v_gravel_bin4_kg, v_cement_kg, v_cement_cem2_kg, v_additive_kg
    FROM concrete_formulas 
    WHERE id = OLD.formula_id;

    -- لێکدانی ڕێژەکان بۆ قەبارەی فرۆشراو
    SET v_black_sand_kg = v_black_sand_kg * v_total_volume;
    SET v_brown_sand_kg = v_brown_sand_kg * v_total_volume;
    SET v_gravel_bin3_kg = v_gravel_bin3_kg * v_total_volume;
    SET v_gravel_bin4_kg = v_gravel_bin4_kg * v_total_volume;
    SET v_cement_kg = v_cement_kg * v_total_volume;
    SET v_cement_cem2_kg = v_cement_cem2_kg * v_total_volume;
    SET v_additive_kg = v_additive_kg * v_total_volume;

    -- گەڕاندنەوەی ماتریاڵەکان (stock) بۆ bins_silos
    IF v_black_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_black_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 1;
    END IF;

    IF v_brown_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_brown_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 2;
    END IF;

    IF v_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin3_kg, 
            total_value = (amount * average_price)
        WHERE id = 3;
    END IF;

    IF v_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_gravel_bin4_kg, 
            total_value = (amount * average_price)
        WHERE id = 4;
    END IF;

    IF v_cement_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_kg, 
            total_value = (amount * average_price)
        WHERE id = 5;
    END IF;
    
    IF v_cement_cem2_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_cement_cem2_kg, 
            total_value = (amount * average_price)
        WHERE id = 6;
    END IF;
    
    IF v_additive_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount + v_additive_kg, 
            total_value = (amount * average_price)
        WHERE id = 7;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_sale` AFTER INSERT ON `sales` FOR EACH ROW BEGIN
    DECLARE v_black_sand_kg DECIMAL(10,2);
    DECLARE v_brown_sand_kg DECIMAL(10,2);
    DECLARE v_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_cement_kg DECIMAL(10,2);
    DECLARE v_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_additive_kg DECIMAL(10,2);
    DECLARE v_total_volume DECIMAL(10,2);
    DECLARE v_current_black_sand DECIMAL(10,2);
    DECLARE v_current_brown_sand DECIMAL(10,2);
    DECLARE v_current_gravel_bin3 DECIMAL(10,2);
    DECLARE v_current_gravel_bin4 DECIMAL(10,2);
    DECLARE v_current_cement DECIMAL(10,2);
    DECLARE v_current_cement2 DECIMAL(10,2);
    DECLARE v_current_additive DECIMAL(10,2);
    DECLARE v_error_message VARCHAR(255);

    -- هەژمارکردنی مەتری سێجا
    SET v_total_volume = NEW.quantity;

    -- وەرگرتنی ڕێژەکانی فۆرمۆلاکە
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_black_sand_kg, v_brown_sand_kg, v_gravel_bin3_kg, v_gravel_bin4_kg, v_cement_kg, v_cement_cem2_kg, v_additive_kg
    FROM concrete_formulas 
    WHERE id = NEW.formula_id;

    -- لێکدانی ڕێژەکان بۆ قەبارەی فرۆشراو
    SET v_black_sand_kg = v_black_sand_kg * v_total_volume;
    SET v_brown_sand_kg = v_brown_sand_kg * v_total_volume;
    SET v_gravel_bin3_kg = v_gravel_bin3_kg * v_total_volume;
    SET v_gravel_bin4_kg = v_gravel_bin4_kg * v_total_volume;
    SET v_cement_kg = v_cement_kg * v_total_volume;
    SET v_cement_cem2_kg = v_cement_cem2_kg * v_total_volume;
    SET v_additive_kg = v_additive_kg * v_total_volume;

    -- وەرگرتنی بڕی ئێستای ماتریاڵەکان (بە kg)
    SELECT amount INTO v_current_black_sand FROM bins_silos WHERE id = 1;
    SELECT amount INTO v_current_brown_sand FROM bins_silos WHERE id = 2;
    SELECT amount INTO v_current_gravel_bin3 FROM bins_silos WHERE id = 3;
    SELECT amount INTO v_current_gravel_bin4 FROM bins_silos WHERE id = 4;
    SELECT amount INTO v_current_cement FROM bins_silos WHERE id = 5;
    SELECT amount INTO v_current_cement2 FROM bins_silos WHERE id = 6;
    SELECT amount INTO v_current_additive FROM bins_silos WHERE id = 7;

    -- چێککردنی بڕی پێویست لە هەموو ماتریاڵەکان (بە kg)
    IF v_black_sand_kg > v_current_black_sand THEN
        SET v_error_message = CONCAT('بڕی پێویست لە لمی ڕەش نییە. بڕی پێویست: ', ROUND(v_black_sand_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_black_sand, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_brown_sand_kg > v_current_brown_sand THEN
        SET v_error_message = CONCAT('بڕی پێویست لە لمی کەسارە نییە. بڕی پێویست: ', ROUND(v_brown_sand_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_brown_sand, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_gravel_bin3_kg > v_current_gravel_bin3 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چەوی بینی ٣ نییە. بڕی پێویست: ', ROUND(v_gravel_bin3_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_gravel_bin3, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_gravel_bin4_kg > v_current_gravel_bin4 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چەوی بینی ٤ نییە. بڕی پێویست: ', ROUND(v_gravel_bin4_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_gravel_bin4, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    IF v_cement_kg > v_current_cement THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چیمەنتۆی سایلۆی ١ نییە. بڕی پێویست: ', ROUND(v_cement_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_cement, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;
    
    IF v_cement_cem2_kg > v_current_cement2 THEN
        SET v_error_message = CONCAT('بڕی پێویست لە چیمەنتۆی سایلۆی ٢ نییە. بڕی پێویست: ', ROUND(v_cement_cem2_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_cement2, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;
    
    IF v_additive_kg > v_current_additive THEN
        SET v_error_message = CONCAT('بڕی پێویست لە ماددەی زیادکراو نییە. بڕی پێویست: ', ROUND(v_additive_kg, 2), ' kg، بڕی بەردەست: ', ROUND(v_current_additive, 2), ' kg');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_message;
    END IF;

    -- کەمکردنەوەی ماتریاڵەکان (بە kg)
    IF v_black_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_black_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 1;
    END IF;

    IF v_brown_sand_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_brown_sand_kg, 
            total_value = (amount * average_price)
        WHERE id = 2;
    END IF;

    IF v_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_gravel_bin3_kg, 
            total_value = (amount * average_price)
        WHERE id = 3;
    END IF;

    IF v_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_gravel_bin4_kg, 
            total_value = (amount * average_price)
        WHERE id = 4;
    END IF;

    IF v_cement_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_cement_kg, 
            total_value = (amount * average_price)
        WHERE id = 5;
    END IF;
    
    IF v_cement_cem2_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_cement_cem2_kg, 
            total_value = (amount * average_price)
        WHERE id = 6;
    END IF;
    
    IF v_additive_kg > 0 THEN
        UPDATE bins_silos 
        SET amount = amount - v_additive_kg, 
            total_value = (amount * average_price)
        WHERE id = 7;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_sale_cash_box` AFTER INSERT ON `sales` FOR EACH ROW BEGIN
    IF NEW.payment_type = 'نەقد' THEN
        IF NEW.amount_paid_usd > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', 0, NEW.amount_paid_usd, 'دۆلار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
        IF NEW.amount_paid_iq > 0 THEN
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NEW.order_date, 'deposit', NEW.amount_paid_iq, 0, 'دینار', CONCAT('فرۆشتن: invoice ', NEW.invoice_number), NULL);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_update_sale` AFTER UPDATE ON `sales` FOR EACH ROW BEGIN
    -- Variables for old values
    DECLARE v_old_black_sand_kg DECIMAL(10,2);
    DECLARE v_old_brown_sand_kg DECIMAL(10,2);
    DECLARE v_old_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_old_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_old_cement_kg DECIMAL(10,2);
    DECLARE v_old_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_old_additive_kg DECIMAL(10,2);
    DECLARE v_old_total_volume DECIMAL(10,2);

    -- Variables for new values
    DECLARE v_new_black_sand_kg DECIMAL(10,2);
    DECLARE v_new_brown_sand_kg DECIMAL(10,2);
    DECLARE v_new_gravel_bin3_kg DECIMAL(10,2);
    DECLARE v_new_gravel_bin4_kg DECIMAL(10,2);
    DECLARE v_new_cement_kg DECIMAL(10,2);
    DECLARE v_new_cement_cem2_kg DECIMAL(10,2);
    DECLARE v_new_additive_kg DECIMAL(10,2);
    DECLARE v_new_total_volume DECIMAL(10,2);

    -- هەژمارکردنی بڕی کۆن
    SET v_old_total_volume = OLD.quantity;
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_old_black_sand_kg, v_old_brown_sand_kg, v_old_gravel_bin3_kg, v_old_gravel_bin4_kg, v_old_cement_kg, v_old_cement_cem2_kg, v_old_additive_kg
    FROM concrete_formulas 
    WHERE id = OLD.formula_id;

    SET v_old_black_sand_kg = v_old_black_sand_kg * v_old_total_volume;
    SET v_old_brown_sand_kg = v_old_brown_sand_kg * v_old_total_volume;
    SET v_old_gravel_bin3_kg = v_old_gravel_bin3_kg * v_old_total_volume;
    SET v_old_gravel_bin4_kg = v_old_gravel_bin4_kg * v_old_total_volume;
    SET v_old_cement_kg = v_old_cement_kg * v_old_total_volume;
    SET v_old_cement_cem2_kg = v_old_cement_cem2_kg * v_old_total_volume;
    SET v_old_additive_kg = v_old_additive_kg * v_old_total_volume;

    -- هەژمارکردنی بڕی نوێ
    SET v_new_total_volume = NEW.quantity;
    SELECT black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, additive_kg
    INTO v_new_black_sand_kg, v_new_brown_sand_kg, v_new_gravel_bin3_kg, v_new_gravel_bin4_kg, v_new_cement_kg, v_new_cement_cem2_kg, v_new_additive_kg
    FROM concrete_formulas 
    WHERE id = NEW.formula_id;

    SET v_new_black_sand_kg = v_new_black_sand_kg * v_new_total_volume;
    SET v_new_brown_sand_kg = v_new_brown_sand_kg * v_new_total_volume;
    SET v_new_gravel_bin3_kg = v_new_gravel_bin3_kg * v_new_total_volume;
    SET v_new_gravel_bin4_kg = v_new_gravel_bin4_kg * v_new_total_volume;
    SET v_new_cement_kg = v_new_cement_kg * v_new_total_volume;
    SET v_new_cement_cem2_kg = v_new_cement_cem2_kg * v_new_total_volume;
    SET v_new_additive_kg = v_new_additive_kg * v_new_total_volume;

    -- گەڕاندنەوەی بڕی کۆن بۆ stock
    IF v_old_black_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_black_sand_kg, total_value = (amount * average_price) WHERE id = 1;
    END IF;
    IF v_old_brown_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_brown_sand_kg, total_value = (amount * average_price) WHERE id = 2;
    END IF;
    IF v_old_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_gravel_bin3_kg, total_value = (amount * average_price) WHERE id = 3;
    END IF;
    IF v_old_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_gravel_bin4_kg, total_value = (amount * average_price) WHERE id = 4;
    END IF;
    IF v_old_cement_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_cement_kg, total_value = (amount * average_price) WHERE id = 5;
    END IF;
    IF v_old_cement_cem2_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_cement_cem2_kg, total_value = (amount * average_price) WHERE id = 6;
    END IF;
    IF v_old_additive_kg > 0 THEN
        UPDATE bins_silos SET amount = amount + v_old_additive_kg, total_value = (amount * average_price) WHERE id = 7;
    END IF;

    -- کەمکردنەوەی بڕی نوێ لە stock
    IF v_new_black_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_black_sand_kg, total_value = (amount * average_price) WHERE id = 1;
    END IF;
    IF v_new_brown_sand_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_brown_sand_kg, total_value = (amount * average_price) WHERE id = 2;
    END IF;
    IF v_new_gravel_bin3_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_gravel_bin3_kg, total_value = (amount * average_price) WHERE id = 3;
    END IF;
    IF v_new_gravel_bin4_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_gravel_bin4_kg, total_value = (amount * average_price) WHERE id = 4;
    END IF;
    IF v_new_cement_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_cement_kg, total_value = (amount * average_price) WHERE id = 5;
    END IF;
    IF v_new_cement_cem2_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_cement_cem2_kg, total_value = (amount * average_price) WHERE id = 6;
    END IF;
    IF v_new_additive_kg > 0 THEN
        UPDATE bins_silos SET amount = amount - v_new_additive_kg, total_value = (amount * average_price) WHERE id = 7;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `value` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'usd_iqd_rate', '150000');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL,
  `bin_id` int(11) NOT NULL,
  `adjustment` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `price_usd` decimal(12,2) DEFAULT 0.00,
  `price_iqd` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','accountant','manager') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'Dana', '$2y$10$zu.c2smtRIzusVGN2tUpwuc/C50zJx.pWJuMz0VyER84Sw0j9XbRe', 'admin'),
(2, 'rawezh', '$2y$10$.okWrqkFKbIoEN7LdwwApusaQV.SOJRYJx5Zfrn.OOk2ULmUFRor6', 'user'),
(4, 'test', '$2y$10$JgVyrys3Rz7X9nVN9iHKiey3HKzY.w.TU0XEE7/4/6oBRuV6BkaSq', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bins_silos`
--
ALTER TABLE `bins_silos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_box`
--
ALTER TABLE `cash_box`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `concrete_formulas`
--
ALTER TABLE `concrete_formulas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formulas_id` (`formulas_id`),
  ADD KEY `pump_car_id` (`pump_car_id`),
  ADD KEY `pump_driver_id` (`pump_driver_id`),
  ADD KEY `mixer_car_id` (`mixer_car_id`),
  ADD KEY `mixer_driver_id` (`mixer_driver_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `other_expense_persons`
--
ALTER TABLE `other_expense_persons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bin_id` (`bin_id`),
  ADD KEY `fk_purchases_company` (`company_id`);

--
-- Indexes for table `recycle_bin_purchases`
--
ALTER TABLE `recycle_bin_purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recycle_bin_sales`
--
ALTER TABLE `recycle_bin_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formula_id` (`formula_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bins_silos`
--
ALTER TABLE `bins_silos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cash_box`
--
ALTER TABLE `cash_box`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `concrete_formulas`
--
ALTER TABLE `concrete_formulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `other_expenses`
--
ALTER TABLE `other_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `other_expense_persons`
--
ALTER TABLE `other_expense_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `recycle_bin_purchases`
--
ALTER TABLE `recycle_bin_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `recycle_bin_sales`
--
ALTER TABLE `recycle_bin_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=743;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1324;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  ADD CONSTRAINT `concrete_receipts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_2` FOREIGN KEY (`formulas_id`) REFERENCES `concrete_formulas` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_7` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `concrete_receipts_ibfk_8` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  ADD CONSTRAINT `customer_debt_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `debt_payments`
--
ALTER TABLE `debt_payments`
  ADD CONSTRAINT `debt_payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD CONSTRAINT `employee_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD CONSTRAINT `other_expenses_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `other_expenses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `other_expenses_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  ADD CONSTRAINT `person_other_expenses_debt_payments_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchases_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`),
  ADD CONSTRAINT `fk_purchases_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`formula_id`) REFERENCES `concrete_formulas` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
