-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 04, 2025 at 04:13 AM
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
-- Dumping data for table `purchase_materials`
--

INSERT INTO `purchase_materials` (`id`, `receipt_number`, `material_id`, `person_id`, `unit_type`, `quantity`, `transfer_loss`, `other_loss`, `usd_to_iqd_rate`, `price_per_unit_usd`, `price_per_unit_iqd`, `total_price_usd`, `total_price_iqd`, `currency_type`, `purchase_date`, `notes`, `created_by`, `created_at`, `updated_at`, `base_quantity`, `base_price_per_unit_usd`, `base_price_per_unit_iqd`, `payment_type`, `paid_amount_usd`, `paid_amount_iqd`, `remaining_amount_usd`, `remaining_amount_iqd`) VALUES
(15, 'KR-0001', 6, 14, 'کارتۆن', 1.00, 0.00, 0.00, 139400.00, 0.00, 9000.00, 0.00, 9000.00, 'دینار', '2025-08-04', '', 1, '2025-08-04 02:13:37', '2025-08-04 02:13:37', 12.00, 0.00, 750.00, 'قەرز', 0.00, 0.00, 0.00, 9.00);

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
CREATE TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW 
BEGIN
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
CREATE TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW 
BEGIN
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

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_materials`
--
ALTER TABLE `purchase_materials`
  ADD CONSTRAINT `purchase_materials_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `list_materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_materials_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
