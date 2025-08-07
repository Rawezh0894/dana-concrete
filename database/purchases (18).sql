-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2025 at 11:17 AM
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
(58, '2025-07-27', 'A-0140', 'test', 'سلێانی', 1, 36000.00, 0.00, 'قەرز', 139050, 29, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00);

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bin_id` (`bin_id`),
  ADD KEY `fk_purchases_company` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchases_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`),
  ADD CONSTRAINT `fk_purchases_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
