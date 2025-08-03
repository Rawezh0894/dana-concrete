-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2025 at 04:43 AM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
