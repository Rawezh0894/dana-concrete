-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 08:41 PM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `formula_id` (`formula_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

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
