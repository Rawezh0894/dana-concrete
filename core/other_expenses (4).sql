-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 27, 2025 at 07:49 PM
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
  `expense_type` enum('بەکارهێنانی کاڵای کۆگا','بەکارهێنانی گاز','خەرجی تر') DEFAULT 'خەرجی تر',
  `material_quantity` decimal(10,2) DEFAULT NULL COMMENT 'بڕی عەدەدی کاڵا',
  `gas_purchase_price_input` decimal(15,2) DEFAULT NULL COMMENT 'ئینپوتی نرخی کڕینی گاز',
  `material_purchase_price_iqd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دینار',
  `material_purchase_price_usd` decimal(15,2) DEFAULT NULL COMMENT 'نرخی کڕینی کاڵا بە دۆلار',
  `material_id` int(11) DEFAULT NULL COMMENT 'ناسنامەی کاڵا لە کۆگا',
  `material_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی کاڵای بەکارهاتوو',
  `gas_total_cost` decimal(15,2) DEFAULT NULL COMMENT 'کۆی نرخی گازی بەکارهاتوو'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `other_expenses`
--

INSERT INTO `other_expenses` (`id`, `purpose`, `person_id`, `employee_id`, `car_id`, `gas_liters`, `payment_type`, `currency_type`, `invoice_number`, `amount_iqd`, `amount_usd`, `paid_iqd`, `paid_usd`, `exchange_rate`, `remaining_iqd`, `remaining_usd`, `date`, `expense_type`, `material_quantity`, `gas_purchase_price_input`, `material_purchase_price_iqd`, `material_purchase_price_usd`, `material_id`, `material_total_cost`, `gas_total_cost`) VALUES
(1, 'c', 13, 33, 9, NULL, 'نەقد', 'دینار', '', 500000.00, 0.00, 0.00, 0.00, 150000.00, 500000.00, 0.00, '2025-07-17', 'خەرجی تر', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'گاز تێکردن بۆ سەیارەی M1', 13, 23, 18, 12000.00, 'قەرز', 'دۆلار', '123', 0.00, 0.00, 0.00, 0.00, 150000.00, 0.00, 0.00, '2025-07-23', 'خەرجی تر', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '', NULL, 33, 13, 2000.00, 'نەقد', '', '', 0.00, 0.00, 0.00, 0.00, 150000.00, 0.00, 0.00, '2025-07-27', 'بەکارهێنانی گاز', 0.00, 8.00, 0.00, 0.00, NULL, 0.00, 16000.00);

--
-- Triggers `other_expenses`
--
DELIMITER $$

-- Trigger for INSERT: Handle cash box and gas consumption
CREATE TRIGGER `trg_after_insert_other_expenses` AFTER INSERT ON `other_expenses` FOR EACH ROW 
BEGIN
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
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

-- Trigger for DELETE: Handle cash box reversal and gas/material restoration
CREATE TRIGGER `trg_before_delete_other_expenses` BEFORE DELETE ON `other_expenses` FOR EACH ROW 
BEGIN
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
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity + OLD.material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

-- Trigger for UPDATE: Handle cash box, gas, and material changes
CREATE TRIGGER `trg_before_update_other_expenses` BEFORE UPDATE ON `other_expenses` FOR EACH ROW 
BEGIN
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
    IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
        -- Restore old material quantity
        UPDATE list_materials
        SET quantity = quantity + OLD.material_quantity
        WHERE id = OLD.material_id;
    END IF;
END$$

-- Trigger for UPDATE: Handle new values after old values are processed
CREATE TRIGGER `trg_after_update_other_expenses` AFTER UPDATE ON `other_expenses` FOR EACH ROW 
BEGIN
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
    IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
        UPDATE list_materials
        SET quantity = quantity - NEW.material_quantity
        WHERE id = NEW.material_id;
    END IF;
END$$

DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `car_id` (`car_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `other_expenses`
--
ALTER TABLE `other_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD CONSTRAINT `other_expenses_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `other_expenses_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `other_expenses_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
