-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 08:54 AM
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
-- Indexes for dumped tables
--

--
-- Indexes for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `person_id` (`person_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `person_other_expenses_debt_payments`
--
ALTER TABLE `person_other_expenses_debt_payments`
  ADD CONSTRAINT `person_other_expenses_debt_payments_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
