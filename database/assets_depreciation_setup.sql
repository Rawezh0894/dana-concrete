-- ============================================
-- Assets and Depreciation System Setup
-- ============================================
-- This script creates tables for managing assets and depreciation
-- Following international standards (Odoo, SAP, Oracle style)

-- Table: asset_categories (جۆرەکانی ئامێر)
CREATE TABLE IF NOT EXISTS `asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'ناوی جۆر',
  `code` varchar(50) DEFAULT NULL COMMENT 'کۆدی جۆر',
  `description` text DEFAULT NULL COMMENT 'وەسف',
  `default_depreciation_method` enum('straight_line','declining_balance','units_of_production') DEFAULT 'straight_line' COMMENT 'شێوازی داخورانی بنەڕەتی',
  `default_useful_life_years` int(11) DEFAULT 5 COMMENT 'ماوەی بەکارهێنانی بنەڕەتی (بە ساڵ)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='جۆرەکانی ئامێر';

-- Insert default asset categories
INSERT INTO `asset_categories` (`name`, `code`, `description`, `default_depreciation_method`, `default_useful_life_years`) VALUES
('پەمپ', 'PUMP', 'پەمپەکانی کۆنکرێت', 'straight_line', 10),
('میکسەر', 'MIXER', 'میکسەرەکانی کۆنکرێت', 'straight_line', 8),
('مەکینەی تیکەڵکردن', 'BATCHING', 'مەکینەی تیکەڵکردنی کۆنکرێت', 'straight_line', 12),
('تییتا پیکاب', 'TITAN_PICKUP', 'تییتا پیکاب', 'straight_line', 7);

-- Table: assets (ئامێرەکان)
CREATE TABLE IF NOT EXISTS `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(100) NOT NULL COMMENT 'کۆدی ئامێر',
  `name` varchar(255) NOT NULL COMMENT 'ناوی ئامێر',
  `category_id` int(11) NOT NULL COMMENT 'جۆری ئامێر',
  `serial_number` varchar(100) DEFAULT NULL COMMENT 'ژمارەی سیریاڵ',
  `purchase_date` date NOT NULL COMMENT 'بەرواری کڕین',
  `purchase_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'نرخی کڕین',
  `salvage_value` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی کۆتایی (salvage value)',
  `useful_life_years` int(11) NOT NULL DEFAULT 5 COMMENT 'ماوەی بەکارهێنان (بە ساڵ)',
  `useful_life_units` decimal(15,2) DEFAULT NULL COMMENT 'ماوەی بەکارهێنان (بە یەکە - بۆ units of production)',
  `depreciation_method` enum('straight_line','declining_balance','units_of_production') NOT NULL DEFAULT 'straight_line' COMMENT 'شێوازی داخوران',
  `depreciation_rate` decimal(5,2) DEFAULT NULL COMMENT 'ڕێژەی داخوران (بۆ declining balance)',
  `current_value` decimal(15,2) NOT NULL COMMENT 'نرخی ئێستا',
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00 COMMENT 'کۆی داخوران',
  `status` enum('active','inactive','disposed','under_maintenance') DEFAULT 'active' COMMENT 'دۆخی ئامێر',
  `location` varchar(255) DEFAULT NULL COMMENT 'شوێن',
  `supplier` varchar(255) DEFAULT NULL COMMENT 'دابینکەر',
  `warranty_expiry` date DEFAULT NULL COMMENT 'بەرواری بەسەرچوونی گارانتی',
  `notes` text DEFAULT NULL COMMENT 'تێبینی',
  `created_by` int(11) DEFAULT NULL COMMENT 'دروستکراو لەلایەن',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ئامێرەکان';

-- Table: depreciation_schedules (کاتی داخوران)
CREATE TABLE IF NOT EXISTS `depreciation_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL COMMENT 'ئامێر',
  `period_start` date NOT NULL COMMENT 'دەستپێکردنی ماوە',
  `period_end` date NOT NULL COMMENT 'کۆتایی ماوە',
  `depreciation_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'بڕی داخوران',
  `accumulated_depreciation` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'کۆی داخوران',
  `book_value` decimal(15,2) NOT NULL COMMENT 'نرخی کتێب',
  `is_posted` tinyint(1) DEFAULT 0 COMMENT 'پۆست کراوە',
  `posted_at` timestamp NULL DEFAULT NULL COMMENT 'کاتی پۆستکردن',
  `posted_by` int(11) DEFAULT NULL COMMENT 'پۆستکراو لەلایەن',
  `notes` text DEFAULT NULL COMMENT 'تێبینی',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `period_start` (`period_start`),
  KEY `period_end` (`period_end`),
  KEY `is_posted` (`is_posted`),
  CONSTRAINT `depreciation_schedules_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='کاتی داخوران';

-- Table: asset_maintenance (نوێکردنەوە و چاککردن)
CREATE TABLE IF NOT EXISTS `asset_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL COMMENT 'ئامێر',
  `maintenance_type` enum('preventive','corrective','inspection') NOT NULL COMMENT 'جۆری چاککردن',
  `maintenance_date` date NOT NULL COMMENT 'بەرواری چاککردن',
  `cost` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخ',
  `description` text DEFAULT NULL COMMENT 'وەسف',
  `technician` varchar(255) DEFAULT NULL COMMENT 'تەکنیشن',
  `next_maintenance_date` date DEFAULT NULL COMMENT 'بەرواری چاککردنی داهاتوو',
  `created_by` int(11) DEFAULT NULL COMMENT 'دروستکراو لەلایەن',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `maintenance_date` (`maintenance_date`),
  CONSTRAINT `asset_maintenance_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='نوێکردنەوە و چاککردنی ئامێر';

-- Table: asset_disposals (فڕێدانی ئامێر)
CREATE TABLE IF NOT EXISTS `asset_disposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL COMMENT 'ئامێر',
  `disposal_date` date NOT NULL COMMENT 'بەرواری فڕێدان',
  `disposal_method` enum('sale','scrap','donation','write_off') NOT NULL COMMENT 'شێوازی فڕێدان',
  `disposal_proceeds` decimal(15,2) DEFAULT 0.00 COMMENT 'داهاتی فڕێدان',
  `loss_or_gain` decimal(15,2) DEFAULT 0.00 COMMENT 'زەرەر یان قازانج',
  `notes` text DEFAULT NULL COMMENT 'تێبینی',
  `created_by` int(11) DEFAULT NULL COMMENT 'دروستکراو لەلایەن',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `asset_disposals_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='فڕێدانی ئامێر';

-- Table: asset_usage_log (تۆماری بەکارهێنان - بۆ units of production)
CREATE TABLE IF NOT EXISTS `asset_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL COMMENT 'ئامێر',
  `usage_date` date NOT NULL COMMENT 'بەرواری بەکارهێنان',
  `units_used` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'یەکەکانی بەکارهاتوو',
  `description` text DEFAULT NULL COMMENT 'وەسف',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `usage_date` (`usage_date`),
  CONSTRAINT `asset_usage_log_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='تۆماری بەکارهێنانی ئامێر';

-- ============================================
-- Indexes for better performance
-- ============================================
CREATE INDEX idx_assets_purchase_date ON assets(purchase_date);
CREATE INDEX idx_assets_status ON assets(status);
CREATE INDEX idx_depreciation_schedules_period ON depreciation_schedules(period_start, period_end);
CREATE INDEX idx_asset_maintenance_date ON asset_maintenance(maintenance_date);
