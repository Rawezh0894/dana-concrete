-- Revolutionary Warehouse Unit System Migration
-- This migration implements a new unit-based warehouse system for Dana Concrete

-- Create new unit types table
CREATE TABLE `unit_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `name_ku` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert unit types
INSERT INTO `unit_types` (`name`, `name_ku`, `description`) VALUES
('carton', 'کارتۆن', 'کارتۆن کە چەند دانەیەکی تێدایە'),
('piece', 'دانە', 'دانە بەتەنیا'),
('barrel', 'بەرمیل', 'بەرمیل کە هەر بەرمیلێک چەند دەبەیە و هەر دەبەیەک چەن لیتر'),
('bucket', 'دەبە', 'دەبە بەتەنیا و هەر دەبەیەک چەن لیتر'),
('liter', 'لیتر', 'لیتر بە تەنیا');

-- Create new warehouse materials table
CREATE TABLE `warehouse_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_ku` varchar(255) NOT NULL,
  `type` enum('black_sand','brown_sand','gravel','cement','medicine','gas','other') NOT NULL,
  `unit_type_id` int(11) NOT NULL,
  `base_unit` varchar(50) NOT NULL COMMENT 'Smallest unit (piece, liter, kg)',
  `conversion_factor` decimal(15,4) DEFAULT 1.0000 COMMENT 'Conversion to base unit',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `unit_type_id` (`unit_type_id`),
  CONSTRAINT `fk_warehouse_materials_unit_type` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create warehouse inventory table
CREATE TABLE `warehouse_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Quantity in base units',
  `available_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Available quantity in base units',
  `total_value_usd` decimal(15,4) DEFAULT 0.0000,
  `total_value_iqd` decimal(15,4) DEFAULT 0.0000,
  `average_price_usd` decimal(15,4) DEFAULT 0.0000 COMMENT 'Average price per base unit in USD',
  `average_price_iqd` decimal(15,4) DEFAULT 0.0000 COMMENT 'Average price per base unit in IQD',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_id` (`material_id`),
  CONSTRAINT `fk_warehouse_inventory_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create warehouse purchases table
CREATE TABLE `warehouse_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `currency_type` enum('دینار','دۆلار') NOT NULL DEFAULT 'دینار',
  `exchange_rate` decimal(15,4) DEFAULT 139250.0000,
  `transfer_cost` decimal(15,4) DEFAULT 0.0000,
  `other_costs` decimal(15,4) DEFAULT 0.0000,
  `total_amount_usd` decimal(15,4) DEFAULT 0.0000,
  `total_amount_iqd` decimal(15,4) DEFAULT 0.0000,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `fk_warehouse_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `other_expense_persons` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create warehouse purchase items table
CREATE TABLE `warehouse_purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `purchase_quantity` decimal(15,4) NOT NULL COMMENT 'Quantity in purchase unit',
  `purchase_unit` varchar(50) NOT NULL COMMENT 'Unit used for purchase (carton, piece, barrel, etc.)',
  `purchase_price_usd` decimal(15,4) DEFAULT 0.0000 COMMENT 'Price per purchase unit in USD',
  `purchase_price_iqd` decimal(15,4) DEFAULT 0.0000 COMMENT 'Price per purchase unit in IQD',
  `total_price_usd` decimal(15,4) DEFAULT 0.0000,
  `total_price_iqd` decimal(15,4) DEFAULT 0.0000,
  `converted_quantity` decimal(15,4) NOT NULL COMMENT 'Quantity converted to base units',
  `unit_conversion_details` json DEFAULT NULL COMMENT 'JSON with conversion details',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `fk_warehouse_purchase_items_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `warehouse_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_warehouse_purchase_items_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create unit conversion rules table
CREATE TABLE `unit_conversion_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_type_id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_description` text DEFAULT NULL,
  `conversion_logic` json NOT NULL COMMENT 'JSON structure defining conversion logic',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `unit_type_id` (`unit_type_id`),
  CONSTRAINT `fk_unit_conversion_rules_unit_type` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert conversion rules
INSERT INTO `unit_conversion_rules` (`unit_type_id`, `rule_name`, `rule_description`, `conversion_logic`) VALUES
(1, 'carton_to_pieces', 'Convert carton to pieces', '{"input_fields": ["pieces_per_carton"], "output_fields": ["total_pieces"], "formula": "carton_quantity * pieces_per_carton"}'),
(2, 'piece_direct', 'Direct piece conversion', '{"input_fields": [], "output_fields": ["total_pieces"], "formula": "piece_quantity"}'),
(3, 'barrel_to_buckets_to_liters', 'Convert barrel to buckets to liters', '{"input_fields": ["buckets_per_barrel", "liters_per_bucket"], "output_fields": ["total_buckets", "total_liters"], "formula": "barrel_quantity * buckets_per_barrel * liters_per_bucket"}'),
(4, 'bucket_to_liters', 'Convert bucket to liters', '{"input_fields": ["liters_per_bucket"], "output_fields": ["total_liters"], "formula": "bucket_quantity * liters_per_bucket"}'),
(5, 'liter_direct', 'Direct liter conversion', '{"input_fields": [], "output_fields": ["total_liters"], "formula": "liter_quantity"}');

-- Create warehouse transactions log
CREATE TABLE `warehouse_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','sale','adjustment','transfer') NOT NULL,
  `quantity` decimal(15,4) NOT NULL COMMENT 'Quantity in base units',
  `unit_price_usd` decimal(15,4) DEFAULT 0.0000,
  `unit_price_iqd` decimal(15,4) DEFAULT 0.0000,
  `total_value_usd` decimal(15,4) DEFAULT 0.0000,
  `total_value_iqd` decimal(15,4) DEFAULT 0.0000,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related record (purchase, sale, etc.)',
  `reference_table` varchar(50) DEFAULT NULL COMMENT 'Table name of related record',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `reference_id` (`reference_id`),
  CONSTRAINT `fk_warehouse_transactions_material` FOREIGN KEY (`material_id`) REFERENCES `warehouse_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create triggers for automatic inventory updates
DELIMITER $$

CREATE TRIGGER `after_warehouse_purchase_items_insert` 
AFTER INSERT ON `warehouse_purchase_items` 
FOR EACH ROW 
BEGIN
    DECLARE current_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_iqd DECIMAL(15,4) DEFAULT 0;
    
    -- Get current inventory values
    SELECT quantity, total_value_usd, total_value_iqd 
    INTO current_quantity, current_value_usd, current_value_iqd
    FROM warehouse_inventory 
    WHERE material_id = NEW.material_id;
    
    -- Calculate new values
    SET new_quantity = IFNULL(current_quantity, 0) + NEW.converted_quantity;
    SET new_value_usd = IFNULL(current_value_usd, 0) + NEW.total_price_usd;
    SET new_value_iqd = IFNULL(current_value_iqd, 0) + NEW.total_price_iqd;
    
    -- Calculate new average prices
    IF new_quantity > 0 THEN
        SET new_avg_price_usd = new_value_usd / new_quantity;
        SET new_avg_price_iqd = new_value_iqd / new_quantity;
    END IF;
    
    -- Insert or update inventory
    INSERT INTO warehouse_inventory (material_id, quantity, available_quantity, total_value_usd, total_value_iqd, average_price_usd, average_price_iqd)
    VALUES (NEW.material_id, new_quantity, new_quantity, new_value_usd, new_value_iqd, new_avg_price_usd, new_avg_price_iqd)
    ON DUPLICATE KEY UPDATE
        quantity = new_quantity,
        available_quantity = new_quantity,
        total_value_usd = new_value_usd,
        total_value_iqd = new_value_iqd,
        average_price_usd = new_avg_price_usd,
        average_price_iqd = new_avg_price_iqd;
    
    -- Log transaction
    INSERT INTO warehouse_transactions (material_id, transaction_type, quantity, unit_price_usd, unit_price_iqd, total_value_usd, total_value_iqd, reference_id, reference_table, notes, created_by)
    VALUES (NEW.material_id, 'purchase', NEW.converted_quantity, 
            CASE WHEN NEW.converted_quantity > 0 THEN NEW.total_price_usd / NEW.converted_quantity ELSE 0 END,
            CASE WHEN NEW.converted_quantity > 0 THEN NEW.total_price_iqd / NEW.converted_quantity ELSE 0 END,
            NEW.total_price_usd, NEW.total_price_iqd, NEW.purchase_id, 'warehouse_purchases', 
            CONCAT('Purchase: ', NEW.purchase_quantity, ' ', NEW.purchase_unit), 
            (SELECT created_by FROM warehouse_purchases WHERE id = NEW.purchase_id));
END$$

CREATE TRIGGER `after_warehouse_purchase_items_delete` 
AFTER DELETE ON `warehouse_purchase_items` 
FOR EACH ROW 
BEGIN
    DECLARE current_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE current_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_quantity DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_value_iqd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_usd DECIMAL(15,4) DEFAULT 0;
    DECLARE new_avg_price_iqd DECIMAL(15,4) DEFAULT 0;
    
    -- Get current inventory values
    SELECT quantity, total_value_usd, total_value_iqd 
    INTO current_quantity, current_value_usd, current_value_iqd
    FROM warehouse_inventory 
    WHERE material_id = OLD.material_id;
    
    -- Calculate new values (subtract deleted values)
    SET new_quantity = IFNULL(current_quantity, 0) - OLD.converted_quantity;
    SET new_value_usd = IFNULL(current_value_usd, 0) - OLD.total_price_usd;
    SET new_value_iqd = IFNULL(current_value_iqd, 0) - OLD.total_price_iqd;
    
    -- Ensure quantities don't go negative
    IF new_quantity < 0 THEN
        SET new_quantity = 0;
    END IF;
    IF new_value_usd < 0 THEN
        SET new_value_usd = 0;
    END IF;
    IF new_value_iqd < 0 THEN
        SET new_value_iqd = 0;
    END IF;
    
    -- Calculate new average prices
    IF new_quantity > 0 THEN
        SET new_avg_price_usd = new_value_usd / new_quantity;
        SET new_avg_price_iqd = new_value_iqd / new_quantity;
    END IF;
    
    -- Update inventory
    UPDATE warehouse_inventory 
    SET quantity = new_quantity,
        available_quantity = new_quantity,
        total_value_usd = new_value_usd,
        total_value_iqd = new_value_iqd,
        average_price_usd = new_avg_price_usd,
        average_price_iqd = new_avg_price_iqd
    WHERE material_id = OLD.material_id;
    
    -- Log transaction
    INSERT INTO warehouse_transactions (material_id, transaction_type, quantity, unit_price_usd, unit_price_iqd, total_value_usd, total_value_iqd, reference_id, reference_table, notes, created_by)
    VALUES (OLD.material_id, 'adjustment', -OLD.converted_quantity, 
            CASE WHEN OLD.converted_quantity > 0 THEN OLD.total_price_usd / OLD.converted_quantity ELSE 0 END,
            CASE WHEN OLD.converted_quantity > 0 THEN OLD.total_price_iqd / OLD.converted_quantity ELSE 0 END,
            -OLD.total_price_usd, -OLD.total_price_iqd, OLD.purchase_id, 'warehouse_purchases', 
            CONCAT('Purchase deletion: ', OLD.purchase_quantity, ' ', OLD.purchase_unit), 
            (SELECT created_by FROM warehouse_purchases WHERE id = OLD.purchase_id));
END$$

DELIMITER ;

-- Insert sample materials for testing
INSERT INTO `warehouse_materials` (`name`, `name_ku`, `type`, `unit_type_id`, `base_unit`, `conversion_factor`, `description`) VALUES
('Cement Bags', 'چیمەنتۆ', 'cement', 1, 'kg', 50.0000, 'Cement in cartons of 50kg bags'),
('Sand Bags', 'لمی ڕەش', 'black_sand', 1, 'kg', 25.0000, 'Black sand in cartons of 25kg bags'),
('Gravel Bags', 'چەو', 'gravel', 1, 'kg', 30.0000, 'Gravel in cartons of 30kg bags'),
('Medicine Bottles', 'دەرمان', 'medicine', 2, 'piece', 1.0000, 'Medicine bottles sold individually'),
('Gas Barrels', 'گاز', 'gas', 3, 'liter', 200.0000, 'Gas barrels containing 200 liters each'),
('Water Buckets', 'ئاو', 'other', 4, 'liter', 20.0000, 'Water buckets containing 20 liters each'),
('Oil Liters', 'ڕۆن', 'other', 5, 'liter', 1.0000, 'Oil sold by liter');

-- Create indexes for better performance
CREATE INDEX `idx_warehouse_materials_type` ON `warehouse_materials` (`type`);
CREATE INDEX `idx_warehouse_materials_unit_type` ON `warehouse_materials` (`unit_type_id`);
CREATE INDEX `idx_warehouse_purchases_date` ON `warehouse_purchases` (`purchase_date`);
CREATE INDEX `idx_warehouse_purchases_supplier` ON `warehouse_purchases` (`supplier_id`);
CREATE INDEX `idx_warehouse_transactions_material_date` ON `warehouse_transactions` (`material_id`, `created_at`);

-- Add comments to tables
ALTER TABLE `warehouse_materials` COMMENT = 'Warehouse materials with unit conversion support';
ALTER TABLE `warehouse_inventory` COMMENT = 'Current inventory levels for warehouse materials';
ALTER TABLE `warehouse_purchases` COMMENT = 'Purchase records for warehouse materials';
ALTER TABLE `warehouse_purchase_items` COMMENT = 'Individual items in warehouse purchases';
ALTER TABLE `unit_conversion_rules` COMMENT = 'Rules for converting between different unit types';
ALTER TABLE `warehouse_transactions` COMMENT = 'Transaction log for all warehouse activities'; 