-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 10:53 AM
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
(69, '2025-07-01', '888', 'ئەیوب', 'سلێمانی', 2, 36000.00, 1188.00, 'قەرز', 150000, 1, 'دۆلار', 0, 0, 701.00, 0, 3, 0.00, 10.00, 33.00),
(70, '2025-07-02', '55', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 1088000, 1, 1188000.00, 33000.00, 0.00),
(71, '2025-07-03', '1546', 'ئەیوب', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 1, 'دینار', 0, 0, 0.00, 369000, 1, 369000.00, 10250.00, 0.00),
(72, '2025-07-05', '1654', 'دارا', 'سلێمانی', 2, 50000.00, 750.00, 'قەرز', 150000, 14, 'دۆلار', 0, 0, 750.00, 0, 3, 0.00, 0.00, 15.00),
(73, '2025-07-05', '13658', 'دارا', 'سلێمانی', 1, 36000.00, 0.00, 'قەرز', 150000, 20, 'دینار', 0, 0, 0.00, 200010, 1, 369000.00, 10250.00, 0.00),
(74, '2025-07-05', '4375', 'دارا', 'سلێمانی', 2, 50000.00, 750.00, 'قەرز', 150000, 21, 'دۆلار', 0, 0, 750.00, 0, 3, 0.00, 0.00, 15.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
