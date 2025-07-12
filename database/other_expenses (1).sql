-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 09:14 PM
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
(27, 'د', 7, 23, 4, 'قەرز', 'دۆلار', '46', 0.00, 150.00, 0.00, 0.00, 150000.00, 0.00, 150.00, '2025-07-07');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
