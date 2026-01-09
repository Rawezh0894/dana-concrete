-- ================================================================
-- Raw Material Sales System
-- Following ERP standards (Odoo, SAP, Oracle)
-- ================================================================

-- جدولی فرۆشتنی مەوادی خام
-- Supports: چەو (gravel), لم (sand), چیمەنتۆ (cement), دەرمان (additive), گاز (gas)
-- Currency: چەو، لم، گاز = IQD | چیمەنتۆ، دەرمان = USD

CREATE TABLE IF NOT EXISTS `raw_material_sales` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    
    -- Invoice Information (Unique identifier like SAP document number)
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `sale_date` DATE NOT NULL,
    
    -- Buyer Type (customer, company, external) - ERP Multi-party support
    `buyer_type` ENUM('کڕیار', 'کۆمپانیا', 'دەرەوە') NOT NULL DEFAULT 'دەرەوە',
    `customer_id` INT(11) DEFAULT NULL,        -- FK to customers table
    `company_id` INT(11) DEFAULT NULL,         -- FK to company table
    `external_buyer_name` VARCHAR(255) DEFAULT NULL,  -- For walk-in customers
    `external_buyer_phone` VARCHAR(20) DEFAULT NULL,
    
    -- Material & Inventory (bins_silos integration)
    `bin_id` INT(11) NOT NULL,                 -- FK to bins_silos
    `material_type` VARCHAR(50) NOT NULL,      -- Material type from bins_silos
    `quantity_kg` DECIMAL(15,4) NOT NULL,      -- Quantity sold in KG
    
    -- Pricing Information (Multi-currency support like Oracle)
    `currency_type` ENUM('دینار', 'دۆلار') NOT NULL,
    `unit_price` DECIMAL(15,6) NOT NULL,       -- Price per KG
    `total_price` DECIMAL(18,4) NOT NULL,      -- quantity_kg * unit_price
    `cost_price` DECIMAL(15,6) DEFAULT 0,      -- Average cost from purchases (for profit calculation)
    `profit_amount` DECIMAL(18,4) DEFAULT 0,   -- Calculated profit
    
    -- Payment Information (ERP Payment terms)
    `payment_type` ENUM('نەقد', 'قەرز') NOT NULL DEFAULT 'نەقد',
    `paid_amount` DECIMAL(18,4) DEFAULT 0,
    `remaining_amount` DECIMAL(18,4) DEFAULT 0,
    
    -- Exchange Rate (for USD items when paid in IQD)
    `exchange_rate` DECIMAL(10,2) DEFAULT 150000, -- Rate per 100 USD
    
    -- Notes
    `notes` TEXT DEFAULT NULL,
    
    -- Audit Trail (ERP standard)
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_by` INT(11) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_by` INT(11) DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    INDEX `idx_invoice_number` (`invoice_number`),
    INDEX `idx_sale_date` (`sale_date`),
    INDEX `idx_buyer_type` (`buyer_type`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_bin_id` (`bin_id`),
    INDEX `idx_material_type` (`material_type`),
    INDEX `idx_is_deleted` (`is_deleted`),
    
    -- Foreign Keys
    CONSTRAINT `fk_rms_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rms_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rms_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rms_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rms_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rms_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Inventory Movement Log (Like SAP Material Document)
-- For tracking all inventory changes with full audit trail
-- ================================================================
CREATE TABLE IF NOT EXISTS `raw_material_inventory_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bin_id` INT(11) NOT NULL,
    `sale_id` INT(11) DEFAULT NULL,
    `movement_type` ENUM('sale', 'return', 'adjustment') NOT NULL,
    `quantity_change` DECIMAL(15,4) NOT NULL,  -- Negative for sales, positive for returns
    `quantity_before` DECIMAL(15,4) NOT NULL,
    `quantity_after` DECIMAL(15,4) NOT NULL,
    `reference_doc` VARCHAR(50) DEFAULT NULL,  -- Invoice number or adjustment reference
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_bin_id` (`bin_id`),
    INDEX `idx_sale_id` (`sale_id`),
    INDEX `idx_movement_type` (`movement_type`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_rmil_bin` FOREIGN KEY (`bin_id`) REFERENCES `bins_silos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rmil_sale` FOREIGN KEY (`sale_id`) REFERENCES `raw_material_sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Add Permissions for Raw Material Sales
-- ================================================================
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_raw_material_sales', 'بینینی فرۆشتنی مەوادی خام'),
('add_raw_material_sales', 'زیادکردنی فرۆشتنی مەوادی خام'),
('update_raw_material_sales', 'نوێکردنەوەی فرۆشتنی مەوادی خام'),
('delete_raw_material_sales', 'سڕینەوەی فرۆشتنی مەوادی خام');

-- ================================================================
-- Grant permissions to admin role
-- ================================================================
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`)
SELECT 'admin', `id` FROM `permissions` 
WHERE `name` IN ('view_raw_material_sales', 'add_raw_material_sales', 'update_raw_material_sales', 'delete_raw_material_sales');
