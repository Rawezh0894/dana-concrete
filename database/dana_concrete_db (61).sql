-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 10, 2025 at 09:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00"; -- Iraq timezone (UTC+3)


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dana_concrete_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_activity_summary` (IN `p_user_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    SELECT 
        activity_type,
        module,
        COUNT(*) as activity_count,
        DATE(created_at) as activity_date
    FROM user_activity_log 
    WHERE user_id = p_user_id 
        AND DATE(created_at) BETWEEN p_start_date AND p_end_date
    GROUP BY activity_type, module, DATE(created_at)
    ORDER BY activity_date DESC, activity_count DESC;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `create_detailed_notification` (`p_user_id` INT, `p_action` VARCHAR(50), `p_table_name` VARCHAR(50), `p_record_id` INT, `p_description` TEXT, `p_old_values` TEXT, `p_new_values` TEXT, `p_additional_info` TEXT, `p_ip_address` VARCHAR(45)) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    INSERT INTO notifications (
        user_id, action, table_name, record_id, description, 
        old_values, new_values, additional_info, ip_address
    ) VALUES (
        p_user_id, p_action, p_table_name, p_record_id, p_description,
        p_old_values, p_new_values, p_additional_info, p_ip_address
    );
    
    RETURN LAST_INSERT_ID();
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `log_user_activity` (`p_user_id` INT, `p_username` VARCHAR(100), `p_activity_type` ENUM('login','logout','create','update','delete','view','export','import','print'), `p_module` VARCHAR(50), `p_action_description` TEXT, `p_record_id` INT, `p_table_name` VARCHAR(50), `p_old_values` TEXT, `p_new_values` TEXT, `p_ip_address` VARCHAR(45)) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    INSERT INTO user_activity_log (
        user_id, username, activity_type, module, action_description, 
        record_id, table_name, old_values, new_values, ip_address
    ) VALUES (
        p_user_id, p_username, p_activity_type, p_module, p_action_description,
        p_record_id, p_table_name, p_old_values, p_new_values, p_ip_address
    );
    
    RETURN LAST_INSERT_ID();
END$$

DELIMITER ;

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
  `average_price` decimal(15,10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bins_silos`
--

INSERT INTO `bins_silos` (`id`, `name`, `type`, `material_type`, `amount`, `total_value`, `average_price`) VALUES
(1, 'چاوی ١', 'چاو', 'لمی کەسارە', 32000.00, 328000.00, 10.2500000000),
(2, 'چاوی ٢', 'چاو', 'لمی ڕەش', 0.00, 0.00, 0.0000000000),
(3, 'چاوی ٣', 'چاو', 'چەو', 0.00, 0.00, 0.0000000000),
(4, 'چاوی ٤', 'چاو', 'چەو', 0.00, 0.00, 0.0000000000),
(5, 'سایلۆی ١', 'سایلۆ', 'چیمەنتۆ', 0.00, 0.00, 0.0000000000),
(6, 'سایلۆی ٢', 'سایلۆ', 'چیمەنتۆ', 19992640.00, 0.00, 0.0000000000),
(7, 'تەنکی دەرمان ١', 'تەنکی', 'دەرمان', 19999929.00, 0.00, 0.0000000000),
(8, 'تەکی گاز ١', 'تەنکی', 'گاز', 10000.00, 80000.00, 8.0000000000),
(11, 'تەنکی گازی 2', 'تەنکی', 'گاز', 0.00, 0.00, 0.0000000000);

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
(8, 'p1'),
(9, 'm1'),
(10, 'm3'),
(11, 'p7'),
(12, 'M10'),
(13, 'M11'),
(14, 'M12'),
(15, 'M13'),
(16, 'M14'),
(17, 'M15'),
(18, 'M16'),
(19, 'M17'),
(20, 'M18'),
(21, 'M19'),
(22, 'M20');

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
(34, '2025-07-17', 'deposit', 150000.00, 0.00, 'دینار', '', 1, '2025-07-29 09:46:49'),
(37, '2025-07-28', 'deposit', 0.00, 50.00, 'دۆلار', 'گەڕاندنەوەی قەرزی کڕیار', NULL, '2025-07-30 06:29:20'),
(38, '2025-07-28', 'deposit', 50000.00, 0.00, 'دینار', 'گەڕاندنەوەی قەرزی کڕیار', NULL, '2025-07-30 06:29:20'),
(40, '2025-08-06', 'deposit', 0.00, 500.00, 'دۆلار', '', 1, '2025-08-04 02:03:03'),
(41, '2025-08-04', 'withdraw', 0.00, 140.00, 'دۆلار', 'پارەدانی کڕینی KR-0001', 1, '2025-08-04 02:26:01'),
(42, '2025-08-04', 'withdraw', 9000.00, 0.00, 'دینار', 'پارەدانی کڕینی KR-0002', 1, '2025-08-04 05:03:49'),
(47, '2025-08-04', 'deposit', 0.00, 140.00, 'دۆلار', 'گەڕاندنەوەی پارەی کڕینی KR-0001', 1, '2025-08-04 05:59:03'),
(48, '2025-08-04', 'deposit', 9000.00, 0.00, 'دینار', 'گەڕاندنەوەی پارەی کڕینی KR-0002', 1, '2025-08-04 05:59:03'),
(73, '2025-08-07', 'deposit', 0.00, 10000.00, 'دۆلار', '', 1, '2025-08-09 07:39:40'),
(76, '2025-08-09', 'withdraw', 0.00, 4000.00, 'دۆلار', 'گەڕاندنەوەی قەرزی کۆمپانیا: 30', 1, '2025-08-09 07:41:13');

--
-- Triggers `cash_box`
--
DELIMITER $$
CREATE TRIGGER `trg_before_delete_cash_box` BEFORE DELETE ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_to_remove DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (پێش سڕینەوە)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی سڕدرێنەوە بە دۆلار
    IF OLD.currency = 'دۆلار' THEN
        SET usd_to_remove = 
            CASE WHEN OLD.type = 'deposit' THEN -OLD.amount_usd ELSE OLD.amount_usd END;
    ELSE
        SET usd_to_remove = 
            CASE WHEN OLD.type = 'deposit' THEN -OLD.amount_iqd / dollar_rate ELSE OLD.amount_iqd / dollar_rate END;
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار پاش سڕینەوە
    IF (total_usd + usd_to_remove) < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'سڕینەوەی ئەم مامەڵەیە قاسەی گشتی منفی دەکات!';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_cash_box` BEFORE UPDATE ON `cash_box` FOR EACH ROW BEGIN
    DECLARE total_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE usd_old DECIMAL(20,2) DEFAULT 0;
    DECLARE usd_new DECIMAL(20,2) DEFAULT 0;

    -- وەرگرتنی نرخەکەی دۆلار لە settings
    SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;

    -- هەژمارکردنی قاسەی گشتی بە دۆلار (پێش update)
    SELECT
        IFNULL(SUM(
            CASE
                WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                ELSE 0
            END
        ), 0)
    INTO total_usd
    FROM cash_box;

    -- بڕی مامەڵەی کۆن بە دۆلار
    IF OLD.currency = 'دۆلار' THEN
        SET usd_old = 
            CASE WHEN OLD.type = 'deposit' THEN OLD.amount_usd ELSE -OLD.amount_usd END;
    ELSE
        SET usd_old = 
            CASE WHEN OLD.type = 'deposit' THEN OLD.amount_iqd / dollar_rate ELSE -OLD.amount_iqd / dollar_rate END;
    END IF;

    -- بڕی مامەڵەی نوێ بە دۆلار
    IF NEW.currency = 'دۆلار' THEN
        SET usd_new = 
            CASE WHEN NEW.type = 'deposit' THEN NEW.amount_usd ELSE -NEW.amount_usd END;
    ELSE
        SET usd_new = 
            CASE WHEN NEW.type = 'deposit' THEN NEW.amount_iqd / dollar_rate ELSE -NEW.amount_iqd / dollar_rate END;
    END IF;

    -- چێککردنی قاسەی گشتی بە دۆلار پاش update
    IF (total_usd - usd_old + usd_new) < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'نوێکردنەوەی ئەم مامەڵەیە قاسەی گشتی منفی دەکات!';
    END IF;
END
$$
DELIMITER ;
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
  `opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `opening_debt_iqd` decimal(20,2) DEFAULT 0.00,
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `name`, `opening_debt_usd`, `opening_debt_iqd`, `currency_type`) VALUES
(27, 'Rawezh', 0.00, 150000.00, 'دینار'),
(28, 'محمد', 0.00, 0.00, 'دۆلار'),
(29, 'test', 0.00, 50000.00, 'دینار'),
(30, 'دەرمان MHA', 0.00, 0.00, 'دۆلار');

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
  `price_per_meter` decimal(10,2) DEFAULT 0.00,
  `formulas_id` int(11) NOT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `receiver_name` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concrete_receipts`
--

INSERT INTO `concrete_receipts` (`id`, `receipt_number`, `customer_id`, `location`, `meter_amount`, `price_per_meter`, `formulas_id`, `pump_car_id`, `pump_driver_id`, `mixer_car_id`, `mixer_driver_id`, `created_at`, `updated_at`, `receiver_name`, `notes`, `payment_status`) VALUES
(38, 'A-0001', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 19, 35, '2025-07-30 09:28:28', NULL, 'محمد', NULL, 'unpaid'),
(39, 'A-0002', 32, 'سلێمانی ', 10.00, 0.00, 17, 8, 27, 22, 36, '2025-07-30 09:54:44', NULL, 'محمد', NULL, 'unpaid'),
(40, 'A-0003', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 36, '2025-08-07 15:01:13', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(41, 'A-0004', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 37, '2025-08-07 15:03:27', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(42, 'A-0005', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 27, 22, 38, '2025-08-07 15:07:20', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(43, 'A-0006', 32, 'پیرەمەگرون', 12.00, 0.00, 19, 8, 27, 21, 38, '2025-08-07 15:10:47', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(44, 'A-0007', 32, 'پیرەمەگرون', 12.00, 0.00, 18, 8, 27, 22, 38, '2025-08-07 15:15:02', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(45, 'A-0008', 32, 'پیرەمەگرون', 12.00, 0.00, 17, 8, 26, 21, 38, '2025-08-07 15:15:23', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(46, 'A-0009', 32, 'پیرەمەگرون', 12.00, 0.00, 18, NULL, 25, 20, 36, '2025-08-07 15:19:20', NULL, 'ڕاوێژ', NULL, 'unpaid'),
(47, 'A-0010', 32, 'سلێمانی', 12.00, 0.00, 21, 8, NULL, 22, 37, '2025-08-07 15:20:38', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(48, 'A-0011', 32, 'سلێمانی', 12.00, 0.00, 19, 8, 27, 21, 36, '2025-08-07 15:21:12', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(49, 'A-0012', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 27, 22, 38, '2025-08-07 15:22:22', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(50, 'A-0013', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 26, 22, 38, '2025-08-07 15:23:56', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(51, 'A-0014', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 27, 21, 38, '2025-08-07 15:25:56', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(52, 'A-0015', 32, 'سلێمانی', 12.00, 0.00, 17, 8, 26, 22, 37, '2025-08-07 15:28:58', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(53, 'A-0016', 32, 'سلێمانی', 10.00, 0.00, 17, 8, 26, 20, 35, '2025-08-07 15:30:35', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(54, 'A-0017', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 20, 35, '2025-08-07 15:31:00', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(55, 'A-0018', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 22, 36, '2025-08-07 15:32:50', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(56, 'A-0019', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 22, 36, '2025-08-07 15:36:10', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(57, 'A-0020', 32, 'سلێمانی', 5.00, 0.00, 18, NULL, 26, 20, 36, '2025-08-07 15:36:45', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(58, 'A-0021', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 22, 37, '2025-08-07 15:39:29', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(59, 'A-0022', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 36, '2025-08-07 15:40:19', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(60, 'A-0022', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 36, '2025-08-07 15:40:28', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(61, 'A-0023', 32, 'سلێمانی', 5.00, 0.00, 19, 8, 26, 20, 38, '2025-08-07 15:42:25', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(62, 'A-0023', 32, 'سلێمانی', 5.00, 0.00, 19, 8, 26, 20, 38, '2025-08-07 15:42:38', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(63, 'A-0024', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 22, 37, '2025-08-07 15:52:09', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(64, 'A-0025', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 26, 22, 36, '2025-08-07 15:54:57', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(65, 'A-0026', 32, 'سلێمانی', 5.00, 0.00, 18, 8, 27, 21, 37, '2025-08-07 15:55:17', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(66, 'A-0027', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 21, 37, '2025-08-07 16:20:08', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(67, 'A-0028', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 25, 19, 35, '2025-08-07 16:22:43', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(68, 'A-0029', 32, 'سلێمانی', 5.00, 0.00, 18, NULL, 26, 21, 37, '2025-08-07 16:25:14', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid'),
(69, 'A-0030', 32, 'سلێمانی', 5.00, 0.00, 17, 8, 27, 20, 37, '2025-08-07 16:43:34', NULL, 'کاک ڕاوێژ ', NULL, 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile1` varchar(20) NOT NULL,
  `mobile2` varchar(20) DEFAULT NULL,
  `opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `opening_debt_iqd` decimal(20,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `mobile1`, `mobile2`, `opening_debt_usd`, `opening_debt_iqd`) VALUES
(32, 'test', '07709245644', '', 14.03, 0.00);

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
  `discount_usd` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `dollar_rate` decimal(10,2) DEFAULT 150000.00,
  `from_opening_debt_usd` decimal(14,2) DEFAULT 0.00 COMMENT 'Amount paid from opening debt USD (FIFO tracking)',
  `from_opening_debt_iqd` decimal(20,2) DEFAULT 0.00 COMMENT 'Amount paid from opening debt IQD (FIFO tracking)',
  `from_purchases_usd` decimal(14,2) DEFAULT 0.00 COMMENT 'Amount paid from purchases USD (FIFO tracking)',
  `from_purchases_iqd` decimal(20,2) DEFAULT 0.00 COMMENT 'Amount paid from purchases IQD (FIFO tracking)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `debt_payments`
--

INSERT INTO `debt_payments` (`id`, `company_id`, `date`, `amount_usd`, `amount_iqd`, `discount_usd`, `note`, `created_by`, `dollar_rate`, `from_opening_debt_usd`, `from_opening_debt_iqd`, `from_purchases_usd`, `from_purchases_iqd`) VALUES
(29, 30, '2025-08-09', 4000.00, 0.00, 8.00, '', 1, 139250.00, 0.00, 0.00, 0.00, 0.00);

--
-- Triggers `debt_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_debt_payments` AFTER INSERT ON `debt_payments` FOR EACH ROW BEGIN
    -- هەرکاتێک پارە دەگێڕیتەوە بۆ کۆمپانیا، پارە لە قاسە دەکەیتەوە
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_debt_payments` BEFORE DELETE ON `debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_debt_payments` BEFORE UPDATE ON `debt_payments` FOR EACH ROW BEGIN
    -- DELETE مامەڵەی کۆن
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', OLD.company_id);
    END IF;

    -- INSERT مامەڵەی نوێ
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کۆمپانیا: ', NEW.company_id), NEW.created_by);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dollar_rates`
--

CREATE TABLE `dollar_rates` (
  `id` int(11) NOT NULL,
  `rate_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dollar_rates`
--

INSERT INTO `dollar_rates` (`id`, `rate_value`, `created_at`) VALUES
(1, 131073.00, '2025-07-30 05:03:29'),
(2, 131073.00, '2025-07-30 05:03:33'),
(3, 131073.00, '2025-07-30 05:05:27'),
(4, 131073.00, '2025-07-30 05:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `load_capacity` decimal(10,2) DEFAULT NULL COMMENT 'بەتاڵەی بارهەڵگر بە کیلۆگرام'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `load_capacity`) VALUES
(4, '5', 1733.00);

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
-- Triggers `employee_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_employee_payment_cash_box` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بۆ کارمەند: ', NEW.employee_id), NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_employee_payments` AFTER INSERT ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NEW.created_at, 'withdraw', NEW.total, 0, 'دینار', CONCAT('پارەدان بە کارمەند: ', NEW.employee_id), NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_employee_payment_cash_box` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    DELETE FROM cash_box
    WHERE `date` = OLD.created_at
      AND `type` = 'withdraw'
      AND amount_iqd = OLD.total
      AND currency = 'دینار'
      AND note = CONCAT('پارەدان بۆ کارمەند: ', OLD.employee_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_employee_payments` BEFORE DELETE ON `employee_payments` FOR EACH ROW BEGIN
    INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
    VALUES (NOW(), 'deposit', OLD.total, 0, 'دینار', CONCAT('گەڕانەوەی پارەدان بە کارمەند: ', OLD.employee_id), NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_employee_payment_cash_box` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    
    -- Calculate the difference
    SET difference = NEW.total - OLD.total;
    
    -- If there's a difference, handle it
    IF difference != 0 THEN
        IF difference > 0 THEN
            -- New amount is higher - withdraw the difference
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            -- New amount is lower - deposit the difference (return money)
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_employee_payments` BEFORE UPDATE ON `employee_payments` FOR EACH ROW BEGIN
    DECLARE difference DECIMAL(15,2);
    
    -- Calculate the difference
    SET difference = NEW.total - OLD.total;
    
    -- If there's a difference, handle it
    IF difference != 0 THEN
        IF difference > 0 THEN
            -- New amount is higher - withdraw the difference
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'withdraw', difference, 0, 'دینار', CONCAT('زیادکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        ELSE
            -- New amount is lower - deposit the difference (return money)
            INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
            VALUES (NOW(), 'deposit', ABS(difference), 0, 'دینار', CONCAT('کەمکردنی پارەدان بە کارمەند: ', NEW.employee_id), NULL);
        END IF;
    END IF;
END
$$
DELIMITER ;

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
  `pieces_per_carton` int(11) DEFAULT NULL COMMENT 'ژمارەی دانە لە کارتۆن',
  `buckets_per_barrel` int(11) DEFAULT NULL COMMENT 'ژمارەی دەبە لە بەرمیل',
  `liters_per_bucket` decimal(10,2) DEFAULT NULL COMMENT 'ژمارەی لیتر لە دەبە',
  `liters_per_barrel` decimal(10,2) DEFAULT NULL COMMENT 'کۆی لیتر لە بەرمیل',
  `price_per_piece_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دۆلار',
  `price_per_piece_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دینار',
  `price_per_bucket_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دۆلار',
  `price_per_bucket_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دینار',
  `price_per_liter_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دۆلار',
  `price_per_liter_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دینار'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'سلێانی');

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
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `customer_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `meter_amount` decimal(10,2) NOT NULL,
  `formula_id` int(11) NOT NULL,
  `mixer_car_id` int(11) DEFAULT NULL,
  `mixer_driver_id` int(11) DEFAULT NULL,
  `pump_car_id` int(11) DEFAULT NULL,
  `pump_driver_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `date`, `time`, `customer_id`, `location`, `recipient`, `meter_amount`, `formula_id`, `mixer_car_id`, `mixer_driver_id`, `pump_car_id`, `pump_driver_id`, `is_read`, `created_at`, `updated_at`) VALUES
(1, '2025-07-29', '15:00:00', 32, 'سلێمانی', 'کاک ڕاوێژ ', 50.00, 21, 22, 37, 8, 34, 1, '2025-07-28 10:48:54', '2025-07-28 11:50:35'),
(2, '2025-07-28', '15:15:00', 32, 'سلێمانی', 'کاک ڕاوێژ ', 50.00, 42, 10, 30, 8, 25, 1, '2025-07-28 11:15:41', '2025-07-28 11:53:27'),
(3, '2025-07-28', '14:18:00', 32, 'پیرەمەگرون', '', 50.00, 39, 22, 36, 8, NULL, 1, '2025-07-28 11:16:06', '2025-08-07 13:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `seen` tinyint(1) DEFAULT 0,
  `old_values` text DEFAULT NULL COMMENT 'JSON format of old values before change',
  `new_values` text DEFAULT NULL COMMENT 'JSON format of new values after change',
  `additional_info` text DEFAULT NULL COMMENT 'Additional context information',
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `created_at`, `seen`, `old_values`, `new_values`, `additional_info`, `ip_address`) VALUES
(206, 1, 'insert', 'other_expenses', 12, 'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی گاز, کەس: هیچ کەسێک نییە, کارمەند: احمد(ابو روەیدا), سەیارە: M10)', '2025-07-30 10:14:14', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"32\",\"employee_name\":\"احمد(ابو روەیدا)\",\"car_id\":\"12\",\"car_name\":\"M10\",\"gas_liters\":1000,\"expense_type\":\"بەکارهێنانی گاز\",\"material_id\":null,\"material_name\":\"هیچ مادەیەک نییە\",\"material_quantity\":0,\"material_purchase_price_iqd\":0,\"material_purchase_price_usd\":0,\"material_total_cost\":0,\"gas_purchase_price_input\":8,\"gas_total_cost\":8000,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139000,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-07-30\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی گاز\"}', '::1'),
(207, 1, 'insert', 'customer_debt_payments', 6, 'پارەدان بۆ قەرزی کڕیار زیادکرا (کڕیار: test, تەلەفۆن: 07709245644)', '2025-07-30 10:53:13', 0, NULL, '{\"customer_id\":\"32\",\"customer_name\":\"test\",\"customer_phone\":\"07709245644\",\"date\":\"2025-07-30\",\"dolar_rate\":139000,\"paid_usd\":14.03,\"paid_iqd\":0,\"discount\":0,\"note\":\"\",\"from_opening_debt_usd\":14.03,\"from_sales_usd\":0}', '{\"action_type\":\"customer_debt_payment\",\"payment_method\":\"USD\",\"total_paid_usd_equivalent\":14.03,\"debt_reduction_type\":\"opening_debt\"}', '::1'),
(208, 1, 'delete', 'customer_debt_payments', 6, 'پارەدانی قەرزی کڕیار سڕایەوە (کڕیار: test, تەلەفۆن: 07709245644)', '2025-07-30 10:54:56', 0, '{\"customer_id\":32,\"customer_name\":\"test\",\"customer_phone\":\"07709245644\",\"date\":\"2025-07-30\",\"dolar_rate\":139000,\"paid_usd\":14.03,\"paid_iqd\":0,\"discount\":0,\"note\":\"\",\"from_opening_debt_usd\":14.03,\"from_sales_usd\":0}', NULL, '{\"action_type\":\"customer_debt_payment_deletion\",\"payment_method\":\"USD\",\"total_paid_usd_equivalent\":14.03,\"debt_reduction_type\":\"opening_debt\"}', '::1'),
(209, 1, 'insert', 'sales', 13, 'فرۆشتنێکی نوێ زیادکرا (invoice: 12, کڕیار: test, فۆرمۆلا: SAQF 350, بڕ: 10 م³)', '2025-08-03 11:05:46', 0, NULL, '{\"customer_id\":\"32\",\"customer_name\":\"test\",\"formula_id\":\"17\",\"formula_name\":\"SAQF 350\",\"recipient\":\"\",\"location\":\"پیرەمەگرون\",\"quantity\":\"10\",\"price_per_unit\":\"45\",\"total_price\":\"450.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"12\",\"notes\":\"\"}', '{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}', '::1'),
(210, 1, 'insert', 'sales', 14, 'فرۆشتنێکی نوێ زیادکرا (invoice: 12, کڕیار: test, فۆرمۆلا: SAQF 300, بڕ: 10 م³)', '2025-08-03 12:47:46', 0, NULL, '{\"customer_id\":\"32\",\"customer_name\":\"test\",\"formula_id\":\"18\",\"formula_name\":\"SAQF 300\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"10\",\"price_per_unit\":\"45\",\"total_price\":\"450.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"450.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"12\",\"notes\":\"\"}', '{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}', '::1'),
(211, 1, 'insert', 'sales', 15, 'فرۆشتنێکی نوێ زیادکرا (invoice: 125, کڕیار: test, فۆرمۆلا: PAYA 30 MEGA, بڕ: 10 م³)', '2025-08-03 12:48:26', 0, NULL, '{\"customer_id\":\"32\",\"customer_name\":\"test\",\"formula_id\":\"22\",\"formula_name\":\"PAYA 30 MEGA\",\"recipient\":\"وستا ازاد\",\"location\":\"پیرەمەگرون\",\"quantity\":\"10\",\"price_per_unit\":\"45\",\"total_price\":\"450.0000\",\"payment_type\":\"قەرز\",\"amount_paid_usd\":\"0\",\"amount_paid_iq\":\"0\",\"remaining_amount\":\"450.0000\",\"dolar_rate\":\"139450\",\"discount\":\"0\",\"order_date\":\"2025-08-02\",\"invoice_number\":\"125\",\"notes\":\"\"}', '{\"action_type\":\"sale_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0}', '::1'),
(212, 1, 'delete', 'other_expenses', 12, 'خەرجی تر سڕایەوە (ID: 12, جۆر: بەکارهێنانی گاز, کەس: هیچ کەسێک نییە, کارمەند: احمد(ابو روەیدا), سەیارە: M10)', '2025-08-03 13:43:29', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":32,\"employee_name\":\"احمد(ابو روەیدا)\",\"car_id\":12,\"car_name\":\"M10\",\"gas_liters\":\"1000.00\",\"expense_type\":\"بەکارهێنانی گاز\",\"material_id\":null,\"material_name\":\"هیچ مادەیەک نییە\",\"material_quantity\":\"0.00\",\"material_purchase_price_iqd\":\"0.00\",\"material_purchase_price_usd\":\"0.00\",\"material_total_cost\":\"0.00\",\"gas_purchase_price_input\":\"8.00\",\"gas_total_cost\":\"8000.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"139000.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-07-30\"}', NULL, '{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی گاز\"}', '::1'),
(213, 1, 'delete', 'other_expenses', 10, 'خەرجی تر سڕایەوە (ID: 10, جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: M10)', '2025-08-03 13:47:10', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":33,\"employee_name\":\"ئامانج\",\"car_id\":12,\"car_name\":\"M10\",\"gas_liters\":\"0.00\",\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":5,\"material_name\":\"لمی کەسارە\",\"material_quantity\":\"3.00\",\"material_purchase_price_iqd\":\"0.00\",\"material_purchase_price_usd\":\"100.00\",\"material_total_cost\":\"300.00\",\"gas_purchase_price_input\":\"0.00\",\"gas_total_cost\":\"0.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"150000.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-07-28\"}', NULL, '{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(214, 1, 'delete', 'other_expenses', 8, 'خەرجی تر سڕایەوە (ID: 8, جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: بازیان, سەیارە: M11)', '2025-08-03 13:47:14', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":22,\"employee_name\":\"بازیان\",\"car_id\":13,\"car_name\":\"M11\",\"gas_liters\":\"0.00\",\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":5,\"material_name\":\"لمی کەسارە\",\"material_quantity\":\"2.00\",\"material_purchase_price_iqd\":\"0.00\",\"material_purchase_price_usd\":\"100.00\",\"material_total_cost\":\"200.00\",\"gas_purchase_price_input\":\"0.00\",\"gas_total_cost\":\"0.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"150000.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-07-27\"}', NULL, '{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(215, 1, 'delete', 'other_expenses', 7, 'خەرجی تر سڕایەوە (ID: 7, جۆر: بەکارهێنانی گاز, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: M11)', '2025-08-03 13:47:16', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":33,\"employee_name\":\"ئامانج\",\"car_id\":13,\"car_name\":\"M11\",\"gas_liters\":\"2000.00\",\"expense_type\":\"بەکارهێنانی گاز\",\"material_id\":null,\"material_name\":\"هیچ مادەیەک نییە\",\"material_quantity\":\"0.00\",\"material_purchase_price_iqd\":\"0.00\",\"material_purchase_price_usd\":\"0.00\",\"material_total_cost\":\"0.00\",\"gas_purchase_price_input\":\"8.00\",\"gas_total_cost\":\"16000.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"150000.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-07-27\"}', NULL, '{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی گاز\"}', '::1'),
(216, 1, 'insert', 'other_expenses', 13, 'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: m1)', '2025-08-03 17:22:32', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"33\",\"employee_name\":\"ئامانج\",\"car_id\":\"9\",\"car_name\":\"m1\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"6\",\"material_name\":\"گاز\",\"material_quantity\":2,\"material_purchase_price_iqd\":750,\"material_purchase_price_usd\":0,\"material_total_cost\":1500,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139450,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-03\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(217, 1, 'delete', 'other_expenses', 13, 'خەرجی تر سڕایەوە (ID: 13, جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: m1)', '2025-08-03 17:38:29', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":33,\"employee_name\":\"ئامانج\",\"car_id\":9,\"car_name\":\"m1\",\"gas_liters\":\"0.00\",\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":6,\"material_name\":\"گاز\",\"material_quantity\":\"2.00\",\"material_purchase_price_iqd\":\"750.00\",\"material_purchase_price_usd\":\"0.00\",\"material_total_cost\":\"1500.00\",\"gas_purchase_price_input\":\"0.00\",\"gas_total_cost\":\"0.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"139450.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-08-03\"}', NULL, '{\"action_type\":\"other_expense_deletion\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(218, 1, 'insert', 'other_expenses', 14, 'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: احمد(ابو روەیدا), سەیارە: M10)', '2025-08-03 17:44:16', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"32\",\"employee_name\":\"احمد(ابو روەیدا)\",\"car_id\":\"12\",\"car_name\":\"M10\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"6\",\"material_name\":\"گاز\",\"material_quantity\":2,\"material_purchase_price_iqd\":750,\"material_purchase_price_usd\":0,\"material_total_cost\":1500,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139450,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-03\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(219, 1, 'insert', 'other_expenses', 15, 'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: هیچ کارمەندێک نییە, سەیارە: هیچ سەیارەیەک نییە)', '2025-08-03 17:45:30', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":null,\"employee_name\":\"هیچ کارمەندێک نییە\",\"car_id\":null,\"car_name\":\"هیچ سەیارەیەک نییە\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"7\",\"material_name\":\"Unknown\",\"material_quantity\":1,\"material_purchase_price_iqd\":0,\"material_purchase_price_usd\":3.5,\"material_total_cost\":3.5,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139450,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-03\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(220, 1, 'update', 'other_expenses', 15, 'خەرجی تر نوێکرایەوە (ID: 15, جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: ڕزگار, سەیارە: M10)', '2025-08-03 17:46:44', 0, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":null,\"employee_name\":\"هیچ کارمەندێک نییە\",\"car_id\":null,\"car_name\":\"هیچ سەیارەیەک نییە\",\"gas_liters\":\"0.00\",\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":7,\"material_name\":\"Unknown\",\"material_quantity\":\"1.00\",\"material_purchase_price_iqd\":\"0.00\",\"material_purchase_price_usd\":\"3.50\",\"material_total_cost\":\"3.50\",\"gas_purchase_price_input\":\"0.00\",\"gas_total_cost\":\"0.00\",\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":\"0.00\",\"amount_usd\":\"0.00\",\"paid_iqd\":\"0.00\",\"paid_usd\":\"0.00\",\"exchange_rate\":\"139450.00\",\"remaining_iqd\":\"0.00\",\"remaining_usd\":\"0.00\",\"date\":\"2025-08-03\"}', '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"38\",\"employee_name\":\"ڕزگار\",\"car_id\":\"12\",\"car_name\":\"M10\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"7\",\"material_name\":\"Unknown\",\"material_quantity\":1,\"material_purchase_price_iqd\":0,\"material_purchase_price_usd\":3.5,\"material_total_cost\":3.5,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139450,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-03\"}', '{\"action_type\":\"other_expense_update\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(221, 1, 'insert', 'other_expenses', 16, 'خەرجی تر زیادکرا (invoice: , جۆر: بەکارهێنانی کاڵای کۆگا, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: M10)', '2025-08-04 08:10:02', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"33\",\"employee_name\":\"ئامانج\",\"car_id\":\"12\",\"car_name\":\"M10\",\"gas_liters\":0,\"expense_type\":\"بەکارهێنانی کاڵای کۆگا\",\"material_id\":\"6\",\"material_name\":\"گاز\",\"material_quantity\":1,\"material_purchase_price_iqd\":750,\"material_purchase_price_usd\":0,\"material_total_cost\":750,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"\",\"invoice_number\":\"\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139400,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-04\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"بەکارهێنانی کاڵای کۆگا\"}', '::1'),
(222, 1, 'insert', 'other_expenses', 0, 'خەرجی تر زیادکرا (invoice: 1236, جۆر: خەرجی تر, کەس: test, کارمەند: شاخەوان, سەیارە: M10)', '2025-08-04 08:34:35', 0, NULL, '{\"person_id\":\"14\",\"person_name\":\"test\",\"employee_id\":\"4\",\"employee_name\":\"شاخەوان\",\"car_id\":\"12\",\"car_name\":\"M10\",\"gas_liters\":0,\"expense_type\":\"خەرجی تر\",\"material_id\":null,\"material_name\":\"هیچ مادەیەک نییە\",\"material_quantity\":0,\"material_purchase_price_iqd\":0,\"material_purchase_price_usd\":0,\"material_total_cost\":0,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"قەرز\",\"currency_type\":\"دینار\",\"invoice_number\":\"1236\",\"amount_iqd\":21000,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139400,\"remaining_iqd\":21000,\"remaining_usd\":0,\"date\":\"2025-08-04\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":21000,\"expense_category\":\"خەرجی تر\"}', '::1'),
(223, 1, 'insert', 'person_other_expenses_debt_payments', 4, 'پارەدان بۆ قەرزی کەسانی تر زیادکرا (کەس: 14)', '2025-08-04 08:56:26', 0, NULL, NULL, NULL, NULL),
(224, 1, 'delete', 'person_other_expenses_debt_payments', 4, 'پارەدانی قەرزی کەسانی تر سڕایەوە (کەس: 14)', '2025-08-04 08:56:44', 0, NULL, NULL, NULL, NULL),
(225, 1, 'insert', 'person_other_expenses_debt_payments', 5, 'پارەدان بۆ قەرزی کەسانی تر زیادکرا (کەس: 14)', '2025-08-04 08:57:15', 0, NULL, NULL, NULL, NULL),
(226, 1, 'delete', 'person_other_expenses_debt_payments', 5, 'پارەدانی قەرزی کەسانی تر سڕایەوە (کەس: 14)', '2025-08-04 08:57:24', 0, NULL, NULL, NULL, NULL),
(227, 1, 'insert', 'person_other_expenses_debt_payments', 6, 'پارەدان بۆ قەرزی کەسانی تر زیادکرا (کەس: 14)', '2025-08-04 08:57:58', 0, NULL, NULL, NULL, NULL),
(228, 1, 'update', 'person_other_expenses_debt_payments', 6, 'پارەدانی قەرزی کەسانی تر نوێکرایەوە (کەس: 14)', '2025-08-04 08:58:14', 0, NULL, NULL, NULL, NULL),
(229, 1, 'delete', 'person_other_expenses_debt_payments', 7, 'پارەدانی قەرزی کەسانی تر سڕایەوە (کەس: 14)', '2025-08-04 08:58:24', 0, NULL, NULL, NULL, NULL),
(230, 1, 'insert', 'debt_payments', 7, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:21:54', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":50000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":50000}', '::1'),
(231, 1, 'delete', 'debt_payments', 7, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:22:08', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":50000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":50000}', '::1'),
(232, 1, 'insert', 'debt_payments', 8, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:22:15', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":50000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":50000}', '::1'),
(233, 1, 'delete', 'debt_payments', 8, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:23:05', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":50000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":50000}', '::1'),
(234, 1, 'insert', 'debt_payments', 9, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:23:16', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(235, 1, 'delete', 'debt_payments', 9, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:26:25', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":170000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":170000}', '::1'),
(236, 1, 'insert', 'debt_payments', 10, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:26:58', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":50000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":50000}', '::1'),
(237, 1, 'update', 'debt_payments', 10, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:27:05', 0, '{\"company_id\":27,\"date\":\"2025-08-07\",\"dollar_rate\":\"139250.00\",\"amount_usd\":\"0.00\",\"amount_iqd\":\"60000.00\",\"note\":\"\"}', '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"dollar_rate\":139250,\"amount_usd\":0,\"amount_iqd\":60000,\"note\":\"\"}', '{\"action_type\":\"company_debt_payment_update\",\"payment_method\":\"IQD\",\"total_paid_usd_equivalent\":0.43087971274685816,\"debt_reduction_type\":\"mixed\"}', '::1'),
(238, 1, 'delete', 'debt_payments', 10, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:27:16', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":60000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":60000}', '::1'),
(239, 1, 'insert', 'debt_payments', 11, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:40:18', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(240, 1, 'delete', 'debt_payments', 11, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:40:45', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(241, 1, 'insert', 'debt_payments', 12, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:55:38', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(242, 1, 'delete', 'debt_payments', 12, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:56:34', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(243, 1, 'insert', 'debt_payments', 13, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 10:57:52', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(244, 1, 'insert', 'debt_payments', 14, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:10:53', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(245, 1, 'delete', 'debt_payments', 14, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:18:24', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(246, 1, 'delete', 'debt_payments', 13, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:18:27', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(247, 1, 'insert', 'debt_payments', 15, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:18:54', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(248, 1, 'delete', 'debt_payments', 15, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:20:45', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(249, 1, 'insert', 'debt_payments', 16, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:23:21', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(250, 1, 'delete', 'debt_payments', 16, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:23:46', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(251, 1, 'insert', 'debt_payments', 17, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 11:48:33', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(252, 1, 'delete', 'debt_payments', 17, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 12:16:01', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(253, 1, 'insert', 'debt_payments', 18, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 12:16:14', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(254, 1, 'delete', 'debt_payments', 18, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 12:21:37', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(255, 1, 'insert', 'debt_payments', 19, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh, تەلەفۆن: هیچ ژمارەیەک نییە)', '2025-08-07 12:21:50', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"company_phone\":\"هیچ ژمارەیەک نییە\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(256, 1, 'delete', 'debt_payments', 19, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:00:43', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(257, 1, 'insert', 'debt_payments', 20, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:00:55', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(258, 1, 'delete', 'debt_payments', 20, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:04:05', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(259, 1, 'insert', 'debt_payments', 21, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:08:22', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(260, 1, 'delete', 'debt_payments', 21, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:09:15', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(262, 1, 'insert', 'debt_payments', 23, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:19:48', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":150000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":150000}', '::1'),
(263, 1, 'delete', 'debt_payments', 23, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:20:12', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":150000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":150000}', '::1'),
(264, 1, 'insert', 'debt_payments', 24, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:20:20', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(265, 1, 'delete', 'debt_payments', 24, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:21:08', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":160000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(266, 1, 'insert', 'debt_payments', 25, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:21:25', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":170000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":170000}', '::1'),
(267, 1, 'update', 'debt_payments', 25, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:21:38', 0, '{\"company_id\":27,\"date\":\"2025-08-07\",\"dollar_rate\":\"139250.00\",\"amount_usd\":\"0.00\",\"amount_iqd\":\"180000.00\",\"note\":\"\"}', '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"dollar_rate\":139250,\"amount_usd\":0,\"amount_iqd\":180000,\"note\":\"\"}', '{\"action_type\":\"company_debt_payment_update\",\"payment_method\":\"IQD\",\"total_amount\":180000}', '::1'),
(268, 1, 'delete', 'debt_payments', 25, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:21:53', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":180000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":180000}', '::1'),
(269, 1, 'insert', 'debt_payments', 26, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: Rawezh)', '2025-08-07 14:26:23', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"amount_usd\":0,\"amount_iqd\":160000,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"IQD\",\"total_amount\":160000}', '::1'),
(270, 1, 'update', 'debt_payments', 26, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:26:32', 0, '{\"company_id\":27,\"date\":\"2025-08-07\",\"dollar_rate\":\"139250.00\",\"amount_usd\":\"0.00\",\"amount_iqd\":\"170000.00\",\"note\":\"\"}', '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"date\":\"2025-08-07\",\"dollar_rate\":139250,\"amount_usd\":0,\"amount_iqd\":170000,\"note\":\"\"}', '{\"action_type\":\"company_debt_payment_update\",\"payment_method\":\"IQD\",\"total_amount\":170000,\"difference_usd\":0,\"difference_iqd\":10000}', '::1'),
(271, 1, 'delete', 'debt_payments', 26, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: Rawezh)', '2025-08-07 14:26:37', 0, '{\"company_id\":27,\"company_name\":\"Rawezh\",\"amount_usd\":0,\"amount_iqd\":170000,\"date\":\"2025-08-07\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"IQD\",\"total_amount\":170000}', '::1'),
(272, 1, 'insert', 'purchases', 60, 'کڕینێکی نوێ زیادکرا (invoice: 408, کۆمپانیا: Rawezh, مادە: لمی ڕەش, بڕ: 32000 کگم)', '2025-08-07 14:27:45', 0, NULL, '{\"company_id\":\"27\",\"company_name\":\"Rawezh\",\"driver\":\"5\",\"location\":\"سلێانی\",\"material_id\":\"1\",\"material_name\":\"لمی ڕەش\",\"amount_iqd\":\"328000.00\",\"kg\":\"32000\",\"price\":\"0\",\"payment_type\":\"قەرز\",\"exchange_rate\":\"139400\",\"type\":\"دینار\",\"paid_usd\":\"0\",\"paid_iqd\":\"0\",\"remaining_usd\":\"0\",\"remaining_iqd\":\"328000.00\",\"bin_id\":\"1\",\"price_per_kg_iqd\":\"10250\",\"price_per_kg_usd\":\"0\",\"invoice_number\":\"408\",\"date\":\"2025-08-06\"}', '{\"action_type\":\"purchase_creation\",\"payment_status\":\"credit\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":328000}', '::1'),
(273, 1, 'insert', 'debt_payments', 28, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: دەرمان MHA)', '2025-08-09 10:40:13', 0, NULL, '{\"company_id\":\"30\",\"company_name\":\"دەرمان MHA\",\"date\":\"2025-08-09\",\"amount_usd\":4000,\"amount_iqd\":0,\"discount_usd\":8,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"USD\",\"total_amount\":4000}', '::1'),
(274, 1, 'delete', 'debt_payments', 28, 'پارەدانی قەرزی کۆمپانیا سڕایەوە (کۆمپانیا: دەرمان MHA)', '2025-08-09 10:40:20', 0, '{\"company_id\":30,\"company_name\":\"دەرمان MHA\",\"amount_usd\":4000,\"amount_iqd\":0,\"date\":\"2025-08-09\",\"note\":\"\"}', NULL, '{\"action_type\":\"company_debt_payment_deletion\",\"payment_method\":\"USD\",\"total_amount\":4000,\"discount_usd\":8}', '::1'),
(275, 1, 'insert', 'debt_payments', 29, 'پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: دەرمان MHA)', '2025-08-09 10:41:02', 0, NULL, '{\"company_id\":\"30\",\"company_name\":\"دەرمان MHA\",\"date\":\"2025-08-09\",\"amount_usd\":4000,\"amount_iqd\":0,\"discount_usd\":5,\"dollar_rate\":139250,\"note\":\"\",\"created_by\":1}', '{\"action_type\":\"company_debt_payment\",\"payment_method\":\"USD\",\"total_amount\":4000}', '::1'),
(276, 1, 'update', 'debt_payments', 29, 'پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: دەرمان MHA)', '2025-08-09 10:41:13', 0, '{\"company_id\":30,\"date\":\"2025-08-09\",\"dollar_rate\":\"139250.00\",\"amount_usd\":\"4000.00\",\"amount_iqd\":\"0.00\",\"note\":\"\",\"discount_usd\":\"8.00\"}', '{\"company_id\":\"30\",\"company_name\":\"دەرمان MHA\",\"date\":\"2025-08-09\",\"dollar_rate\":139250,\"amount_usd\":4000,\"amount_iqd\":0,\"discount_usd\":8,\"note\":\"\"}', '{\"action_type\":\"company_debt_payment_update\",\"payment_method\":\"USD\",\"total_amount\":4000,\"difference_usd\":0,\"difference_iqd\":0,\"difference_discount_usd\":3}', '::1'),
(277, 1, 'insert', 'other_expenses', 18, 'خەرجی تر زیادکرا (invoice: 123, جۆر: خەرجی تر, کەس: هیچ کەسێک نییە, کارمەند: ئامانج, سەیارە: M15)', '2025-08-10 10:00:24', 0, NULL, '{\"person_id\":null,\"person_name\":\"هیچ کەسێک نییە\",\"employee_id\":\"33\",\"employee_name\":\"ئامانج\",\"car_id\":\"17\",\"car_name\":\"M15\",\"gas_liters\":0,\"expense_type\":\"خەرجی تر\",\"material_id\":null,\"material_name\":\"هیچ مادەیەک نییە\",\"material_quantity\":0,\"material_purchase_price_iqd\":0,\"material_purchase_price_usd\":0,\"material_total_cost\":0,\"gas_purchase_price_input\":0,\"gas_total_cost\":0,\"payment_type\":\"نەقد\",\"currency_type\":\"دۆلار\",\"invoice_number\":\"123\",\"amount_iqd\":0,\"amount_usd\":0,\"paid_iqd\":0,\"paid_usd\":0,\"exchange_rate\":139800,\"remaining_iqd\":0,\"remaining_usd\":0,\"date\":\"2025-08-10\"}', '{\"action_type\":\"other_expense_creation\",\"payment_status\":\"paid\",\"currency_used\":\"none\",\"total_paid\":0,\"remaining_debt\":0,\"expense_category\":\"خەرجی تر\"}', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `other_expenses`
--

CREATE TABLE `other_expenses` (
  `id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `person_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `gas_liters` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') DEFAULT NULL,
  `currency_type` enum('دینار','دۆلار') DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `amount_iqd` decimal(20,2) DEFAULT 0.00,
  `amount_usd` decimal(14,2) DEFAULT 0.00,
  `paid_iqd` decimal(20,2) DEFAULT 0.00,
  `paid_usd` decimal(14,2) DEFAULT 0.00,
  `exchange_rate` decimal(10,2) DEFAULT 150000.00,
  `remaining_iqd` decimal(20,2) DEFAULT 0.00,
  `remaining_usd` decimal(14,2) DEFAULT 0.00,
  `date` date NOT NULL,
  `expense_type` enum('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر','خواردنگە','ئۆفیس') DEFAULT 'خەرجی تر',
  `material_quantity` decimal(10,2) DEFAULT NULL COMMENT 'بڕی عەدەدی کاڵا',
  `gas_purchase_price_input` decimal(15,2) DEFAULT NULL COMMENT 'ئینپوتی نرخی کڕینی گاز',
  `material_purchase_price_iqd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دینار',
  `material_purchase_price_usd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دۆلار',
  `material_id` int(11) DEFAULT NULL COMMENT 'ناسنامەی کاڵا لە کۆگا',
  `material_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی کاڵای بەکارهاتوو',
  `gas_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی گازی بەکارهاتوو',
  `base_material_quantity` decimal(15,2) DEFAULT NULL COMMENT 'بڕی بنەڕەتی کاڵا (دانە/لیتر)',
  `usage_unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') DEFAULT NULL COMMENT 'یەکەی بەکارهێنان',
  `created_by` int(11) DEFAULT NULL COMMENT 'ناسنامەی کارمەندێک کە خەرجییەکەی تۆمارکردووە'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `other_expenses`
--

INSERT INTO `other_expenses` (`id`, `purpose`, `person_id`, `employee_id`, `car_id`, `gas_liters`, `payment_type`, `currency_type`, `invoice_number`, `amount_iqd`, `amount_usd`, `paid_iqd`, `paid_usd`, `exchange_rate`, `remaining_iqd`, `remaining_usd`, `date`, `expense_type`, `material_quantity`, `gas_purchase_price_input`, `material_purchase_price_iqd`, `material_purchase_price_usd`, `material_id`, `material_total_cost`, `gas_total_cost`, `base_material_quantity`, `usage_unit_type`, `created_by`) VALUES
(18, '', NULL, 33, 17, 0.00, 'نەقد', 'دۆلار', '123', 0.00, 0.00, 0.00, 0.00, 139800.00, 0.00, 0.00, '2025-08-10', 'خەرجی تر', 0.00, 0.00, 0.00, 0.00, NULL, 0.00, 0.00, NULL, NULL, NULL);

--
-- Triggers `other_expenses`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for cash payments
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
    
    -- Handle gas consumption for gas usage expenses
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material consumption for warehouse material usage
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_update_other_expenses` AFTER UPDATE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for new record
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
    
    -- Handle new gas consumption
    IF NEW.expense_type = 'بەکارهێنانی گاز' AND NEW.gas_liters IS NOT NULL AND NEW.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount - NEW.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle new material consumption
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.base_material_quantity IS NOT NULL AND NEW.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.base_material_quantity
        WHERE id = NEW.material_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_other_expenses` BEFORE DELETE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations reversal
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas restoration
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material restoration
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_other_expenses` BEFORE UPDATE ON `other_expenses` FOR EACH ROW BEGIN
    -- Handle cash box operations for old record
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.currency_type = 'دۆلار' AND OLD.paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.currency_type = 'دینار' AND OLD.paid_iqd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('خەرجی تر: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    
    -- Handle gas changes
    IF OLD.expense_type = 'بەکارهێنانی گاز' AND OLD.gas_liters IS NOT NULL AND OLD.gas_liters > 0 THEN
        -- Restore old gas amount
        UPDATE bins_silos
        SET amount = amount + OLD.gas_liters
        WHERE type = 'تەنکی' AND material_type = 'گاز'
        LIMIT 1;
    END IF;
    
    -- Handle material changes
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.base_material_quantity IS NOT NULL AND OLD.base_material_quantity > 0 THEN
        -- Restore old material quantity
        UPDATE list_materials
        SET quantity = quantity + OLD.base_material_quantity
        WHERE id = OLD.material_id;
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
(14, 'test', 0.00, 31000.00, 500.00, 50000.00);

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
(77, 'view_reports', 'بینینی راپۆرتەکان'),
(78, 'view_notifications', 'بینینی ئاگادارکردنەوەکان'),
(79, 'view_summery_concrete_receipts', 'بینینی پوختەی پسووڵەکانی کۆنکرێت'),
(80, 'view_bins_silos', 'بینینی بین/سایلۆکان'),
(81, 'view_cash_box', 'بینینی قاسەکە'),
(82, 'view_notes', 'بینینی تێبینیەکان'),
(83, 'add_notes', 'زیادکردنی تێبینیەکان'),
(84, 'update_notes', 'نوێکردنەوەی تێبینیەکان'),
(85, 'delete_notes', 'سڕینەوەی تێبینیەکان'),
(86, 'mark_notes_read', 'خوێندنی تێبینیەکان'),
(87, 'view_concrete_prices', 'بینینی نرخەکانی کۆنکرێت'),
(88, 'set_concrete_prices', 'دانانی نرخی کۆنکرێت'),
(89, 'edit_concrete_prices', 'دەستکاری نرخی کۆنکرێت');

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
-- Triggers `person_other_expenses_debt_payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_person_other_expenses_debt_payments` AFTER INSERT ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    -- بۆ دۆلار
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    -- بۆ دینار
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_delete_person_other_expenses_debt_payments` BEFORE DELETE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_person_other_expenses_debt_payments` BEFORE UPDATE ON `person_other_expenses_debt_payments` FOR EACH ROW BEGIN
    IF OLD.amount_usd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.amount_usd AND currency = 'دۆلار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF OLD.amount_iqd > 0 THEN
        DELETE FROM cash_box
        WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.amount_iqd AND currency = 'دینار' AND note = CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', OLD.person_id);
    END IF;
    IF NEW.amount_usd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', 0, NEW.amount_usd, 'دۆلار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
    END IF;
    IF NEW.amount_iqd > 0 THEN
        INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
        VALUES (NEW.date, 'withdraw', NEW.amount_iqd, 0, 'دینار', CONCAT('گەڕاندنەوەی قەرزی کەسانی تر: ', NEW.person_id), NULL);
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
(57, '2025-07-24', '123654', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 360000, 1, 360000.00, 10000.00, 0.00),
(58, '2025-07-27', 'A-0140', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139050, 29, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00),
(60, '2025-08-06', '408', '5', 'سلێانی', 1, 32000.00, 0.00, 'قەرز', 139400, 27, 'دینار', 0, 0, 0.00, 328000, 1, 328000.00, 10250.00, 0.00);

--
-- Triggers `purchases`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_delete` AFTER DELETE ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_value_sub DECIMAL(20,6);

  IF OLD.type = 'دینار' THEN
    SET total_value_sub = (OLD.kg / 1000) * OLD.price_per_kg_iqd;
  ELSEIF OLD.type = 'دۆلار' THEN
    SET total_value_sub = (OLD.kg / 1000) * OLD.price_per_kg_usd;
  ELSE
    SET total_value_sub = 0;
  END IF;

  IF OLD.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount - OLD.kg,
      total_value = total_value - total_value_sub
    WHERE id = OLD.bin_id;

    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = OLD.bin_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_insert` AFTER INSERT ON `purchases` FOR EACH ROW BEGIN
  DECLARE total_value_add DECIMAL(20,6);

  IF NEW.type = 'دینار' THEN
    SET total_value_add = (NEW.kg / 1000) * NEW.price_per_kg_iqd;
  ELSEIF NEW.type = 'دۆلار' THEN
    SET total_value_add = (NEW.kg / 1000) * NEW.price_per_kg_usd;
  ELSE
    SET total_value_add = 0;
  END IF;

  IF NEW.bin_id IS NOT NULL THEN
    UPDATE bins_silos
    SET
      amount = amount + NEW.kg,
      total_value = total_value + total_value_add
    WHERE id = NEW.bin_id;

    -- average_price بۆ هەر ١kg
    UPDATE bins_silos
    SET
      average_price = 
        CASE 
          WHEN amount > 0 
          THEN total_value / amount
          ELSE 0
        END
    WHERE id = NEW.bin_id;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_update` AFTER UPDATE ON `purchases` FOR EACH ROW BEGIN
  DECLARE old_total_value DECIMAL(20,6);
  DECLARE new_total_value DECIMAL(20,6);

  -- Only process if kg field changed (not remaining_iqd or other fields)
  IF OLD.kg != NEW.kg THEN
    -- هەژمارکردنی بڕی کۆن
    IF OLD.type = 'دینار' THEN
      SET old_total_value = (OLD.kg / 1000) * OLD.price_per_kg_iqd;
    ELSEIF OLD.type = 'دۆلار' THEN
      SET old_total_value = (OLD.kg / 1000) * OLD.price_per_kg_usd;
    ELSE
      SET old_total_value = 0;
    END IF;

    -- هەژمارکردنی بڕی نوێ
    IF NEW.type = 'دینار' THEN
      SET new_total_value = (NEW.kg / 1000) * NEW.price_per_kg_iqd;
    ELSEIF NEW.type = 'دۆلار' THEN
      SET new_total_value = (NEW.kg / 1000) * NEW.price_per_kg_usd;
    ELSE
      SET new_total_value = 0;
    END IF;

    -- گەڕاندنەوەی بڕی کۆن بۆ stock
    IF OLD.bin_id IS NOT NULL THEN
      UPDATE bins_silos
      SET
        amount = amount - OLD.kg,
        total_value = total_value - old_total_value
      WHERE id = OLD.bin_id;

      UPDATE bins_silos
      SET
        average_price = 
          CASE 
            WHEN amount > 0 
            THEN total_value / amount
            ELSE 0
          END
      WHERE id = OLD.bin_id;
    END IF;

    -- زیادکردنی بڕی نوێ بۆ stock
    IF NEW.bin_id IS NOT NULL THEN
      UPDATE bins_silos
      SET
        amount = amount + NEW.kg,
        total_value = total_value + new_total_value
      WHERE id = NEW.bin_id;

      UPDATE bins_silos
      SET
        average_price = 
          CASE 
            WHEN amount > 0 
            THEN total_value / amount
            ELSE 0
          END
      WHERE id = NEW.bin_id;
    END IF;
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
DELIMITER $$
CREATE TRIGGER `trg_before_delete_purchase_cash_box` BEFORE DELETE ON `purchases` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        ELSE
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_purchase_cash_box` BEFORE UPDATE ON `purchases` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.type = 'دۆلار' THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_usd = OLD.paid_usd AND currency = 'دۆلار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        ELSE
            DELETE FROM cash_box
            WHERE `date` = OLD.date AND `type` = 'withdraw' AND amount_iqd = OLD.paid_iqd AND currency = 'دینار' AND note = CONCAT('کڕین: invoice ', OLD.invoice_number);
        END IF;
    END IF;
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
-- Table structure for table `purchase_materials`
--

CREATE TABLE `purchase_materials` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(20) NOT NULL,
  `material_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') NOT NULL DEFAULT 'دانە',
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `transfer_loss` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_loss` decimal(15,2) NOT NULL DEFAULT 0.00,
  `usd_to_iqd_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_per_unit_usd` decimal(15,2) DEFAULT 0.00,
  `price_per_unit_iqd` decimal(15,2) DEFAULT 0.00,
  `total_price_usd` decimal(15,2) DEFAULT 0.00,
  `total_price_iqd` decimal(15,2) DEFAULT 0.00,
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار',
  `purchase_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `base_quantity` decimal(15,2) DEFAULT 0.00 COMMENT 'بڕی بنەڕەتی بە دانە',
  `base_price_per_unit_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دۆلار',
  `base_price_per_unit_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دینار',
  `payment_type` enum('نەقد','قەرز') DEFAULT 'نەقد',
  `paid_amount_usd` decimal(15,2) DEFAULT 0.00,
  `paid_amount_iqd` decimal(15,2) DEFAULT 0.00,
  `remaining_amount_usd` decimal(15,2) DEFAULT 0.00,
  `remaining_amount_iqd` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `purchase_materials`
--
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_delete` AFTER DELETE ON `purchase_materials` FOR EACH ROW BEGIN
    -- Update the quantity in list_materials by subtracting the deleted base quantity
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
    
    -- If it was a cash payment, return the money to cash_box (use total price, not paid amount)
    IF OLD.payment_type = 'نەقد' THEN
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            OLD.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', OLD.receipt_number),
            OLD.created_by
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;
    
    -- Update the quantity in list_materials by adding the purchased base quantity
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
    
    -- If it's a cash payment, withdraw money from cash_box
    IF NEW.payment_type = 'نەقد' THEN
        -- Check if there's enough money in cash_box before withdrawing
        -- Get dollar rate from settings
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        
        -- Calculate current cash box balance in USD
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;
        
        -- Calculate the withdrawal amount in USD (use total price, not paid amount)
        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;
        
        -- Check if there's enough balance
        IF (current_balance_usd - withdrawal_usd) < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ ئەم کڕینە!';
        END IF;
        
        -- Proceed with withdrawal (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number),
            NEW.created_by
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW BEGIN
    DECLARE current_balance_usd DECIMAL(20,2) DEFAULT 0;
    DECLARE dollar_rate DECIMAL(20,2) DEFAULT 150000;
    DECLARE withdrawal_usd DECIMAL(20,2) DEFAULT 0;
    
    -- First, subtract the old base quantity from the material
    UPDATE list_materials 
    SET quantity = quantity - OLD.base_quantity
    WHERE id = OLD.material_id;
    
    -- Then, add the new base quantity to the material
    UPDATE list_materials 
    SET quantity = quantity + NEW.base_quantity
    WHERE id = NEW.material_id;
    
    -- Handle cash box changes based on payment type changes
    IF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'قەرز' THEN
        -- Changed from cash to credit, return money to cash_box (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'deposit',
            OLD.total_price_usd,
            OLD.total_price_iqd,
            OLD.currency_type,
            CONCAT('گەڕاندنەوەی پارەی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ قەرز)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'قەرز' AND NEW.payment_type = 'نەقد' THEN
        -- Changed from credit to cash, check balance and withdraw money from cash_box
        -- Get dollar rate from settings
        SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
        
        -- Calculate current cash box balance in USD
        SELECT
            IFNULL(SUM(
                CASE
                    WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                    WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                    WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                    WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                    ELSE 0
                END
            ), 0)
        INTO current_balance_usd
        FROM cash_box;
        
        -- Calculate the withdrawal amount in USD (use total price, not paid amount)
        IF NEW.currency_type = 'دۆلار' THEN
            SET withdrawal_usd = NEW.total_price_usd;
        ELSE
            SET withdrawal_usd = NEW.total_price_iqd / dollar_rate;
        END IF;
        
        -- Check if there's enough balance
        IF (current_balance_usd - withdrawal_usd) < 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ ئەم کڕینە!';
        END IF;
        
        -- Proceed with withdrawal (use total price, not paid amount)
        INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
        VALUES (
            NEW.purchase_date,
            'withdraw',
            NEW.total_price_usd,
            NEW.total_price_iqd,
            NEW.currency_type,
            CONCAT('پارەدانی کڕینی ', NEW.receipt_number, ' (گۆڕانکاری بۆ نەقد)'),
            NEW.created_by
        );
    ELSEIF OLD.payment_type = 'نەقد' AND NEW.payment_type = 'نەقد' THEN
        -- Both are cash, check if paid amounts changed
        IF OLD.paid_amount_usd != NEW.paid_amount_usd OR OLD.paid_amount_iqd != NEW.paid_amount_iqd THEN
            -- Calculate the difference
            IF (NEW.paid_amount_usd - OLD.paid_amount_usd) > 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) > 0 THEN
                -- More money was paid, check balance and withdraw the difference
                -- Get dollar rate from settings
                SELECT CAST(value AS DECIMAL(20,2)) INTO dollar_rate FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1;
                
                -- Calculate current cash box balance in USD
                SELECT
                    IFNULL(SUM(
                        CASE
                            WHEN currency = 'دۆلار' AND type = 'deposit' THEN amount_usd
                            WHEN currency = 'دۆلار' AND type = 'withdraw' THEN -amount_usd
                            WHEN currency = 'دینار' AND type = 'deposit' THEN amount_iqd / dollar_rate
                            WHEN currency = 'دینار' AND type = 'withdraw' THEN -amount_iqd / dollar_rate
                            ELSE 0
                        END
                    ), 0)
                INTO current_balance_usd
                FROM cash_box;
                
                -- Calculate the additional withdrawal amount in USD
                IF NEW.currency_type = 'دۆلار' THEN
                    SET withdrawal_usd = (NEW.paid_amount_usd - OLD.paid_amount_usd);
                ELSE
                    SET withdrawal_usd = (NEW.paid_amount_iqd - OLD.paid_amount_iqd) / dollar_rate;
                END IF;
                
                -- Check if there's enough balance
                IF (current_balance_usd - withdrawal_usd) < 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'قاسەی گشتی بەردەست نییە بۆ زیادکردنی پارەدانی!';
                END IF;
                
                -- Proceed with withdrawal
                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'withdraw',
                    (NEW.paid_amount_usd - OLD.paid_amount_usd),
                    (NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('زیادکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            ELSEIF (NEW.paid_amount_usd - OLD.paid_amount_usd) < 0 OR (NEW.paid_amount_iqd - OLD.paid_amount_iqd) < 0 THEN
                -- Less money was paid, return the difference
                INSERT INTO cash_box (date, type, amount_usd, amount_iqd, currency, note, created_by)
                VALUES (
                    NEW.purchase_date,
                    'deposit',
                    ABS(NEW.paid_amount_usd - OLD.paid_amount_usd),
                    ABS(NEW.paid_amount_iqd - OLD.paid_amount_iqd),
                    NEW.currency_type,
                    CONCAT('کەمکردنی پارەدانی کڕینی ', NEW.receipt_number),
                    NEW.created_by
                );
            END IF;
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
(2, 2, '2025-07-23', '123', 'test', 'سلێانی', 6, 2000000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 20500000, 8, 20500000.00, 10250.00, 0.00, '2025-07-24 15:31:57'),
(3, 2, '2025-07-23', '123', 'test', 'سلێانی', 6, 2000000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 20500000, 8, 20500000.00, 10250.00, 0.00, '2025-07-24 15:32:16'),
(4, 2, '2025-07-23', '123', 'test', 'سلێانی', 6, 2000000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 20500000, 8, 20500000.00, 10250.00, 0.00, '2025-07-24 15:35:20'),
(5, 2, '2025-07-23', '123', 'test', 'سلێانی', 6, 2000000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 20500000, 8, 20500000.00, 10250.00, 0.00, '2025-07-24 15:35:33'),
(6, 2, '2025-07-23', '123', 'test', 'سلێانی', 6, 2000000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 20500000, 8, 20500000.00, 10250.00, 0.00, '2025-07-24 15:38:13'),
(7, 4, '2025-07-24', '12354', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 27, 'دینار', 0, 0, 0.00, 360000, 1, 360000.00, 10000.00, 0.00, '2025-07-24 15:59:23'),
(8, 3, '2025-07-23', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 15:59:26'),
(9, 5, '2025-07-23', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:20:09'),
(10, 6, '2025-07-23', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:22:33'),
(11, 7, '2025-07-23', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:26:39'),
(12, 8, '2025-07-23', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:34:58'),
(13, 9, '2025-07-23', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:37:27'),
(14, 10, '2025-07-23', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:42:17'),
(15, 11, '2025-07-24', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-24 16:52:25'),
(16, 12, '2025-07-23', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 360000, 1, 360000.00, 10000.00, 0.00, '2025-07-24 16:52:27'),
(17, 14, '2025-07-24', '1235', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 360000, 1, 360000.00, 10000.00, 0.00, '2025-07-25 07:52:27'),
(18, 13, '2025-07-23', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 07:52:29'),
(19, 16, '2025-07-25', '12365', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:03:05'),
(20, 15, '2025-07-24', '1236', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:03:08'),
(21, 18, '2025-07-25', '134', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:09:58'),
(22, 17, '2025-07-24', '1236', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:10:01'),
(23, 19, '2025-07-25', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:25:20'),
(24, 20, '2025-07-25', '1254', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:25:23'),
(25, 22, '2025-07-25', '1254', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:31:00'),
(26, 21, '2025-07-24', '1236', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:31:03'),
(27, 23, '2025-07-24', '1236', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:42:10'),
(28, 24, '2025-07-25', '12', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:42:13'),
(29, 26, '2025-07-25', '12354', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 139000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:44:40'),
(30, 25, '2025-07-11', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 13900, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:44:42'),
(31, 28, '2025-07-25', '1234', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 0, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:52:52'),
(32, 29, '2025-07-25', '12', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 08:52:55'),
(33, 27, '2025-07-24', '1236', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 0, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 08:52:57'),
(34, 30, '2025-07-24', '1237', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:01:03'),
(35, 31, '2025-07-25', '123', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 09:01:06'),
(36, 32, '2025-07-24', '1237', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:03:57'),
(37, 33, '2025-07-24', '1237', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:08:00'),
(38, 34, '2025-07-24', '1230', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:17:21'),
(39, 35, '2025-07-24', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:21:04'),
(40, 35, '2025-07-24', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:21:25'),
(41, 36, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:34:41'),
(42, 37, '2025-07-25', '1230', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 09:34:43'),
(43, 40, '2025-07-25', '16', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 315000, 1, 315000.00, 10500.00, 0.00, '2025-07-25 09:49:37'),
(44, 38, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:49:39'),
(45, 39, '2025-07-24', '134', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 360000, 1, 360000.00, 10000.00, 0.00, '2025-07-25 09:49:41'),
(46, 41, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:53:55'),
(47, 42, '2025-07-24', '134', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 09:53:57'),
(48, 43, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:55:06'),
(49, 44, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:56:17'),
(50, 45, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 09:57:16'),
(51, 47, '2025-07-24', '134', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 10:06:59'),
(52, 46, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 10:07:02'),
(53, 49, '2025-07-25', '45', 'test', 'سلێانی', 2, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 3, 369000.00, 10250.00, 0.00, '2025-07-25 10:07:05'),
(54, 48, '2025-07-25', '123654', 'test', 'سلێانی', 3, 36000.00, 522.00, 'قەرز', 150000, 27, 'دۆلار', 0, 0, 522.00, 0, 5, 0.00, 0.00, 14.50, '2025-07-25 10:07:07'),
(55, 51, '2025-07-25', '12', 'test', 'سلێانی', 3, 36000.00, 522.00, 'قەرز', 150000, 27, 'دۆلار', 0, 0, 522.00, 0, 5, 0.00, 0.00, 14.50, '2025-07-25 10:13:08'),
(56, 50, '2025-07-24', '13', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 10:13:10'),
(57, 52, '2025-07-24', '135', 'test', 'سلێانی', 3, 30000.00, 375.00, 'قەرز', 150000, 28, 'دۆلار', 0, 0, 375.00, 0, 5, 0.00, 0.00, 12.50, '2025-07-25 10:13:12'),
(58, 55, '2025-07-24', '12345', 'test', 'سلێانی', 3, 36000.00, 522.00, 'قەرز', 150000, 28, 'دۆلار', 0, 0, 522.00, 0, 5, 0.00, 0.00, 14.50, '2025-07-25 10:26:21'),
(59, 56, '2025-07-24', '1234598', 'test', 'سلێانی', 3, 30000.00, 390.00, 'قەرز', 150000, 28, 'دۆلار', 0, 0, 390.00, 0, 5, 0.00, 0.00, 13.00, '2025-07-25 10:26:24'),
(60, 54, '2025-07-24', '1234', 'test', 'سلێانی', 1, 30000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 300000, 1, 300000.00, 10000.00, 0.00, '2025-07-25 10:26:26'),
(61, 53, '2025-07-24', '123', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 150000, 27, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00, '2025-07-25 10:26:28'),
(62, 59, '2025-07-27', 'A-01404', 'test', 'سلێانی', 1, 40000.00, 0.00, 'قەرز', 150000, 28, 'دینار', 0, 0, 0.00, 240000, 1, 240000.00, 6000.00, 0.00, '2025-07-28 11:05:09');

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
(741, 'user', 71),
(742, 'user', 75),
(743, 'admin', 77),
(744, 'user', 39),
(745, 'admin', 40),
(746, 'user', 72),
(747, 'admin', 78),
(748, 'user', 62),
(749, 'user', 70),
(750, 'user', 73),
(751, 'user', 74),
(752, 'admin', 79),
(753, 'accountant', 79),
(754, 'accountant', 80),
(755, 'admin', 80),
(756, 'admin', 81),
(757, 'accountant', 81),
(758, 'user', 86),
(759, 'user', 82);

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
  `invoice_number` varchar(500) NOT NULL,
  `order_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `formula_id` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `recipient`, `location`, `quantity`, `price_per_unit`, `total_price`, `payment_type`, `amount_paid_usd`, `amount_paid_iq`, `dolar_rate`, `remaining_amount`, `invoice_number`, `order_date`, `notes`, `formula_id`, `discount`) VALUES
(12, 32, '', 'سلێمانی', 10.00, 45.00, 450.00, 'قەرز', 0.00, 0.00, 139050.00, 450.00, 'A-0140', '2025-07-27', '', 17, 0.00),
(13, 32, '', 'پیرەمەگرون', 10.00, 45.00, 450.00, 'قەرز', 0.00, 0.00, NULL, NULL, '12', '2025-08-02', '', 17, 0.00),
(14, 32, 'وستا ازاد', 'پیرەمەگرون', 10.00, 45.00, 450.00, 'قەرز', 0.00, 0.00, 139450.00, 450.00, '12', '2025-08-02', '', 18, 0.00),
(15, 32, 'وستا ازاد', 'پیرەمەگرون', 10.00, 45.00, 450.00, 'قەرز', 0.00, 0.00, 139450.00, 450.00, '125', '2025-08-02', '', 22, 0.00);

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
DELIMITER $$
CREATE TRIGGER `trg_before_delete_sale_cash_box` BEFORE DELETE ON `sales` FOR EACH ROW BEGIN
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_usd = OLD.amount_paid_usd AND currency = 'دۆلار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_iqd = OLD.amount_paid_iq AND currency = 'دینار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_update_sale_cash_box` BEFORE UPDATE ON `sales` FOR EACH ROW BEGIN
    -- سڕینەوەی مامەڵەی کۆن
    IF OLD.payment_type = 'نەقد' THEN
        IF OLD.amount_paid_usd > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_usd = OLD.amount_paid_usd AND currency = 'دۆلار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
        IF OLD.amount_paid_iq > 0 THEN
            DELETE FROM cash_box
            WHERE `date` = OLD.order_date AND `type` = 'deposit' AND amount_iqd = OLD.amount_paid_iq AND currency = 'دینار' AND note = CONCAT('فرۆشتن: invoice ', OLD.invoice_number);
        END IF;
    END IF;
    -- زیادکردنی مامەڵەی نوێ
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

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `bin_id`, `adjustment`, `reason`, `created_at`, `user_id`, `price`, `price_usd`, `price_iqd`) VALUES
(1, 7, 2000.00, 'س', '2025-07-14 17:33:42', 1, 0.00, 0.00, 0.00),
(2, 8, 2000.00, '.', '2025-07-14 17:33:52', 1, 0.00, 0.00, 0.00),
(3, 5, 2000.00, 'اس', '2025-07-14 17:34:02', 1, 0.00, 0.00, 0.00),
(4, 6, 20000.00, 'ا', '2025-07-14 17:34:10', 1, 0.00, 0.00, 0.00),
(5, 1, 2000.00, 'س', '2025-07-14 17:34:24', 1, 0.00, 0.00, 0.00),
(6, 2, 2000.00, 'ل', '2025-07-14 17:34:33', 1, 0.00, 0.00, 0.00),
(7, 3, 3000.00, '\'؛', '2025-07-14 17:34:46', 1, 0.00, 0.00, 0.00),
(8, 4, 2000.00, 'ل؛', '2025-07-14 17:34:53', 1, 0.00, 0.00, 0.00),
(9, 7, 500000.00, 'ل؛', '2025-07-14 17:35:54', 1, 0.00, 0.00, 0.00),
(10, 8, 65000.00, 'سو', '2025-07-14 17:36:02', 1, 0.00, 0.00, 0.00),
(11, 7, 20000.00, 'ڕژان', '2025-07-14 17:36:13', 1, 0.00, 0.00, 0.00),
(12, 1, 12000.00, '55', '2025-07-14 17:36:21', 1, 0.00, 0.00, 0.00),
(13, 5, 120000.00, 'خس', '2025-07-14 17:36:36', 1, 0.00, 0.00, 0.00),
(14, 6, 200000.00, 'ل؛', '2025-07-14 17:36:45', 1, 0.00, 0.00, 0.00),
(15, 2, 52000.00, 'ل؛', '2025-07-14 17:36:54', 1, 0.00, 0.00, 0.00),
(16, 3, 25250.00, 'ئح', '2025-07-14 17:37:23', 1, 0.00, 0.00, 0.00),
(17, 4, 30000.00, 'ئ', '2025-07-14 17:37:43', 1, 0.00, 0.00, 0.00);

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
(4, 'test', '$2y$10$xWN316OpnqZt0Z02NePmruAFKm6cHO.c8TUJMPK6XhCLJEILmirma', 'user');

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
-- Indexes for table `dollar_rates`
--
ALTER TABLE `dollar_rates`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `list_materials`
--
ALTER TABLE `list_materials`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formula_id` (`formula_id`),
  ADD KEY `mixer_car_id` (`mixer_car_id`),
  ADD KEY `mixer_driver_id` (`mixer_driver_id`),
  ADD KEY `pump_car_id` (`pump_car_id`),
  ADD KEY `pump_driver_id` (`pump_driver_id`),
  ADD KEY `date` (`date`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `other_expenses_ibfk_4` (`created_by`);

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
-- Indexes for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_receipt_number` (`receipt_number`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cash_box`
--
ALTER TABLE `cash_box`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `concrete_formulas`
--
ALTER TABLE `concrete_formulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `concrete_receipts`
--
ALTER TABLE `concrete_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `customer_debt_payments`
--
ALTER TABLE `customer_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `debt_payments`
--
ALTER TABLE `debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `dollar_rates`
--
ALTER TABLE `dollar_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `list_materials`
--
ALTER TABLE `list_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `other_expenses`
--
ALTER TABLE `other_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `other_expense_persons`
--
ALTER TABLE `other_expense_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `recycle_bin_purchases`
--
ALTER TABLE `recycle_bin_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `recycle_bin_sales`
--
ALTER TABLE `recycle_bin_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=760;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`formula_id`) REFERENCES `concrete_formulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`mixer_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`mixer_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_5` FOREIGN KEY (`pump_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notes_ibfk_6` FOREIGN KEY (`pump_driver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD CONSTRAINT `other_expenses_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `other_expenses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `other_expenses_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `other_expenses_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  ADD CONSTRAINT `purchase_materials_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `list_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
