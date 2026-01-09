-- Add tables for raw material sales and other sales
-- This migration adds support for:
-- 1. Raw material sales (gravel, sand, cement, medicine, gas)
-- 2. Other sales (iron, leftovers, and other items)

-- Table for raw material sales
CREATE TABLE IF NOT EXISTS `raw_material_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `material_type` enum('black_sand','brown_sand','gravel','cement','medicine','gas') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'کیلۆگرام',
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `discount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `material_type` (`material_type`),
  KEY `order_date` (`order_date`),
  CONSTRAINT `raw_material_sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for other sales (iron, leftovers, etc.)
CREATE TABLE IF NOT EXISTS `other_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `item_type` enum('iron','leftovers','other') COLLATE utf8mb4_general_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'کیلۆگرام',
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('نەقد','قەرز') COLLATE utf8mb4_general_ci NOT NULL,
  `amount_paid_usd` decimal(10,2) DEFAULT NULL,
  `amount_paid_iq` decimal(10,2) DEFAULT NULL,
  `dolar_rate` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `discount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `item_type` (`item_type`),
  KEY `order_date` (`order_date`),
  CONSTRAINT `other_sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
