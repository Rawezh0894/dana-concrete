-- Database Migration for New Unit System
-- This migration creates a new inventory system with different unit types: Carton, Piece, Barrel, Bag, Liter

-- Create new inventory_materials table
CREATE TABLE `inventory_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece',
  `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
  `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
  `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
  `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel',
  `current_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار',
  `purchase_price_usd` decimal(15,2) DEFAULT 0.00,
  `purchase_price_iqd` decimal(15,2) DEFAULT 0.00,
  `price_per_piece` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per individual piece',
  `price_per_liter` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per liter',
  `price_per_bag` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per bag',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add new columns to purchase_materials table
ALTER TABLE `purchase_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece' AFTER `material_id`,
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel',
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per individual piece',
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per liter',
ADD COLUMN `price_per_bag` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per bag';

-- Create a view for material unit calculations
CREATE VIEW `inventory_unit_calculations` AS
SELECT 
    id,
    name,
    unit_type,
    pieces_per_carton,
    bags_per_barrel,
    liters_per_bag,
    liters_per_barrel,
    current_quantity,
    purchase_price_usd,
    purchase_price_iqd,
    price_per_piece,
    price_per_liter,
    price_per_bag,
    CASE 
        WHEN unit_type = 'carton' THEN current_quantity * pieces_per_carton
        WHEN unit_type = 'barrel' THEN current_quantity * bags_per_barrel * liters_per_bag
        WHEN unit_type = 'bag' THEN current_quantity * liters_per_bag
        ELSE current_quantity
    END as total_pieces_or_liters,
    CASE 
        WHEN unit_type = 'carton' THEN current_quantity * bags_per_barrel
        WHEN unit_type = 'barrel' THEN current_quantity * bags_per_barrel
        WHEN unit_type = 'bag' THEN current_quantity
        ELSE 0
    END as total_bags
FROM inventory_materials;

-- Create a function to calculate piece price from carton
DELIMITER $$
CREATE FUNCTION `calculate_piece_price_from_carton`(
    p_carton_price DECIMAL(15,2),
    p_pieces_per_carton INT
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    IF p_pieces_per_carton > 0 THEN
        RETURN p_carton_price / p_pieces_per_carton;
    ELSE
        RETURN 0;
    END IF;
END$$
DELIMITER ;

-- Create a function to calculate bag price from barrel
DELIMITER $$
CREATE FUNCTION `calculate_bag_price_from_barrel`(
    p_barrel_price DECIMAL(15,2),
    p_bags_per_barrel INT
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    IF p_bags_per_barrel > 0 THEN
        RETURN p_barrel_price / p_bags_per_barrel;
    ELSE
        RETURN 0;
    END IF;
END$$
DELIMITER ;

-- Create a function to calculate liter price from barrel
DELIMITER $$
CREATE FUNCTION `calculate_liter_price_from_barrel`(
    p_barrel_price DECIMAL(15,2),
    p_bags_per_barrel INT,
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_liters DECIMAL(15,2);
    SET total_liters = p_bags_per_barrel * p_liters_per_bag;
    
    IF total_liters > 0 THEN
        RETURN p_barrel_price / total_liters;
    ELSE
        RETURN 0;
    END IF;
END$$
DELIMITER ;

-- Create a function to calculate liter price from bag
DELIMITER $$
CREATE FUNCTION `calculate_liter_price_from_bag`(
    p_bag_price DECIMAL(15,2),
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    IF p_liters_per_bag > 0 THEN
        RETURN p_bag_price / p_liters_per_bag;
    ELSE
        RETURN 0;
    END IF;
END$$
DELIMITER ;

-- Create a function to calculate bag price from liter
DELIMITER $$
CREATE FUNCTION `calculate_bag_price_from_liter`(
    p_liter_price DECIMAL(15,2),
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    IF p_liters_per_bag > 0 THEN
        RETURN p_liter_price * p_liters_per_bag;
    ELSE
        RETURN 0;
    END IF;
END$$
DELIMITER ;

-- Update existing purchase_materials to have default unit type
UPDATE purchase_materials SET unit_type = 'piece' WHERE unit_type IS NULL;

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `after_purchase_materials_insert`;
DROP TRIGGER IF EXISTS `after_purchase_materials_update`;
DROP TRIGGER IF EXISTS `after_purchase_materials_delete`;

-- Create trigger for INSERT operations
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_insert` AFTER INSERT ON `purchase_materials` FOR EACH ROW 
BEGIN
    DECLARE material_unit_type VARCHAR(20);
    DECLARE pieces_per_carton_val INT;
    DECLARE bags_per_barrel_val INT;
    DECLARE liters_per_bag_val DECIMAL(10,2);
    DECLARE quantity_to_add DECIMAL(15,2);
    
    -- Get material details from inventory_materials
    SELECT unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag 
    INTO material_unit_type, pieces_per_carton_val, bags_per_barrel_val, liters_per_bag_val
    FROM inventory_materials 
    WHERE id = NEW.material_id;
    
    -- Calculate quantity to add based on purchase unit type
    SET quantity_to_add = NEW.quantity;
    
    -- Convert purchase quantity to base unit (piece/liter) for inventory tracking
    CASE NEW.unit_type
        WHEN 'carton' THEN
            -- Convert carton to pieces
            SET quantity_to_add = NEW.quantity * COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            -- Convert barrel to liters
            SET quantity_to_add = NEW.quantity * COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1);
        WHEN 'bag' THEN
            -- Convert bag to liters
            SET quantity_to_add = NEW.quantity * COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            -- Already in pieces
            SET quantity_to_add = NEW.quantity;
        WHEN 'liter' THEN
            -- Already in liters
            SET quantity_to_add = NEW.quantity;
        ELSE
            -- Default to original quantity
            SET quantity_to_add = NEW.quantity;
    END CASE;
    
    -- Update inventory quantity
    UPDATE inventory_materials 
    SET current_quantity = current_quantity + quantity_to_add,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.material_id;
    
    -- Log the operation
    INSERT INTO inventory_log (material_id, operation_type, quantity, unit_type, reference_id, reference_table, notes, created_at)
    VALUES (NEW.material_id, 'INSERT', quantity_to_add, NEW.unit_type, NEW.id, 'purchase_materials', 
            CONCAT('Added ', NEW.quantity, ' ', NEW.unit_type, ' (', quantity_to_add, ' base units)'), CURRENT_TIMESTAMP);
END$$
DELIMITER ;

-- Create trigger for UPDATE operations
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_update` AFTER UPDATE ON `purchase_materials` FOR EACH ROW 
BEGIN
    DECLARE material_unit_type VARCHAR(20);
    DECLARE pieces_per_carton_val INT;
    DECLARE bags_per_barrel_val INT;
    DECLARE liters_per_bag_val DECIMAL(10,2);
    DECLARE old_quantity_base DECIMAL(15,2);
    DECLARE new_quantity_base DECIMAL(15,2);
    DECLARE quantity_difference DECIMAL(15,2);
    
    -- Get material details from inventory_materials
    SELECT unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag 
    INTO material_unit_type, pieces_per_carton_val, bags_per_barrel_val, liters_per_bag_val
    FROM inventory_materials 
    WHERE id = NEW.material_id;
    
    -- Calculate old quantity in base units
    SET old_quantity_base = OLD.quantity;
    CASE OLD.unit_type
        WHEN 'carton' THEN
            SET old_quantity_base = OLD.quantity * COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            SET old_quantity_base = OLD.quantity * COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1);
        WHEN 'bag' THEN
            SET old_quantity_base = OLD.quantity * COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            SET old_quantity_base = OLD.quantity;
        WHEN 'liter' THEN
            SET old_quantity_base = OLD.quantity;
        ELSE
            SET old_quantity_base = OLD.quantity;
    END CASE;
    
    -- Calculate new quantity in base units
    SET new_quantity_base = NEW.quantity;
    CASE NEW.unit_type
        WHEN 'carton' THEN
            SET new_quantity_base = NEW.quantity * COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            SET new_quantity_base = NEW.quantity * COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1);
        WHEN 'bag' THEN
            SET new_quantity_base = NEW.quantity * COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            SET new_quantity_base = NEW.quantity;
        WHEN 'liter' THEN
            SET new_quantity_base = NEW.quantity;
        ELSE
            SET new_quantity_base = NEW.quantity;
    END CASE;
    
    -- Calculate the difference
    SET quantity_difference = new_quantity_base - old_quantity_base;
    
    -- Update inventory quantity
    UPDATE inventory_materials 
    SET current_quantity = current_quantity + quantity_difference,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.material_id;
    
    -- Log the operation
    INSERT INTO inventory_log (material_id, operation_type, quantity, unit_type, reference_id, reference_table, notes, created_at)
    VALUES (NEW.material_id, 'UPDATE', quantity_difference, NEW.unit_type, NEW.id, 'purchase_materials', 
            CONCAT('Updated from ', OLD.quantity, ' ', OLD.unit_type, ' to ', NEW.quantity, ' ', NEW.unit_type, 
                   ' (difference: ', quantity_difference, ' base units)'), CURRENT_TIMESTAMP);
END$$
DELIMITER ;

-- Create trigger for DELETE operations
DELIMITER $$
CREATE TRIGGER `after_purchase_materials_delete` AFTER DELETE ON `purchase_materials` FOR EACH ROW 
BEGIN
    DECLARE material_unit_type VARCHAR(20);
    DECLARE pieces_per_carton_val INT;
    DECLARE bags_per_barrel_val INT;
    DECLARE liters_per_bag_val DECIMAL(10,2);
    DECLARE quantity_to_subtract DECIMAL(15,2);
    
    -- Get material details from inventory_materials
    SELECT unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag 
    INTO material_unit_type, pieces_per_carton_val, bags_per_barrel_val, liters_per_bag_val
    FROM inventory_materials 
    WHERE id = OLD.material_id;
    
    -- Calculate quantity to subtract based on purchase unit type
    SET quantity_to_subtract = OLD.quantity;
    
    -- Convert purchase quantity to base unit (piece/liter) for inventory tracking
    CASE OLD.unit_type
        WHEN 'carton' THEN
            -- Convert carton to pieces
            SET quantity_to_subtract = OLD.quantity * COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            -- Convert barrel to liters
            SET quantity_to_subtract = OLD.quantity * COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1);
        WHEN 'bag' THEN
            -- Convert bag to liters
            SET quantity_to_subtract = OLD.quantity * COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            -- Already in pieces
            SET quantity_to_subtract = OLD.quantity;
        WHEN 'liter' THEN
            -- Already in liters
            SET quantity_to_subtract = OLD.quantity;
        ELSE
            -- Default to original quantity
            SET quantity_to_subtract = OLD.quantity;
    END CASE;
    
    -- Update inventory quantity
    UPDATE inventory_materials 
    SET current_quantity = current_quantity - quantity_to_subtract,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.material_id;
    
    -- Log the operation
    INSERT INTO inventory_log (material_id, operation_type, quantity, unit_type, reference_id, reference_table, notes, created_at)
    VALUES (OLD.material_id, 'DELETE', quantity_to_subtract, OLD.unit_type, OLD.id, 'purchase_materials', 
            CONCAT('Removed ', OLD.quantity, ' ', OLD.unit_type, ' (', quantity_to_subtract, ' base units)'), CURRENT_TIMESTAMP);
END$$
DELIMITER ;

-- Create inventory_log table for tracking inventory changes
CREATE TABLE IF NOT EXISTS `inventory_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `operation_type` ENUM('INSERT', 'UPDATE', 'DELETE', 'ADJUSTMENT') NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_table` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_material_id` (`material_id`),
  KEY `idx_operation_type` (`operation_type`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`material_id`) REFERENCES `inventory_materials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create function to get current inventory quantity in base units
DELIMITER $$
CREATE FUNCTION `get_inventory_quantity_base_units`(
    p_material_id INT
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE current_qty DECIMAL(15,2);
    
    SELECT current_quantity INTO current_qty
    FROM inventory_materials 
    WHERE id = p_material_id;
    
    RETURN COALESCE(current_qty, 0);
END$$
DELIMITER ;

-- Create function to convert quantity between units
DELIMITER $$
CREATE FUNCTION `convert_quantity_between_units`(
    p_quantity DECIMAL(15,2),
    p_from_unit ENUM('carton', 'piece', 'barrel', 'bag', 'liter'),
    p_to_unit ENUM('carton', 'piece', 'barrel', 'bag', 'liter'),
    p_material_id INT
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE pieces_per_carton_val INT;
    DECLARE bags_per_barrel_val INT;
    DECLARE liters_per_bag_val DECIMAL(10,2);
    DECLARE base_quantity DECIMAL(15,2);
    DECLARE converted_quantity DECIMAL(15,2);
    
    -- Get material conversion factors
    SELECT pieces_per_carton, bags_per_barrel, liters_per_bag 
    INTO pieces_per_carton_val, bags_per_barrel_val, liters_per_bag_val
    FROM inventory_materials 
    WHERE id = p_material_id;
    
    -- Convert to base units first
    CASE p_from_unit
        WHEN 'carton' THEN
            SET base_quantity = p_quantity * COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            SET base_quantity = p_quantity * COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1);
        WHEN 'bag' THEN
            SET base_quantity = p_quantity * COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            SET base_quantity = p_quantity;
        WHEN 'liter' THEN
            SET base_quantity = p_quantity;
        ELSE
            SET base_quantity = p_quantity;
    END CASE;
    
    -- Convert from base units to target unit
    CASE p_to_unit
        WHEN 'carton' THEN
            SET converted_quantity = base_quantity / COALESCE(pieces_per_carton_val, 1);
        WHEN 'barrel' THEN
            SET converted_quantity = base_quantity / (COALESCE(bags_per_barrel_val, 1) * COALESCE(liters_per_bag_val, 1));
        WHEN 'bag' THEN
            SET converted_quantity = base_quantity / COALESCE(liters_per_bag_val, 1);
        WHEN 'piece' THEN
            SET converted_quantity = base_quantity;
        WHEN 'liter' THEN
            SET converted_quantity = base_quantity;
        ELSE
            SET converted_quantity = base_quantity;
    END CASE;
    
    RETURN converted_quantity;
END$$
DELIMITER ;

-- Create view for inventory summary with unit conversions
CREATE OR REPLACE VIEW `inventory_summary` AS
SELECT 
    im.id,
    im.name,
    im.unit_type,
    im.current_quantity,
    im.pieces_per_carton,
    im.bags_per_barrel,
    im.liters_per_bag,
    im.liters_per_barrel,
    -- Calculate quantities in different units
    CASE 
        WHEN im.unit_type = 'carton' THEN im.current_quantity * COALESCE(im.pieces_per_carton, 1)
        WHEN im.unit_type = 'barrel' THEN im.current_quantity * COALESCE(im.bags_per_barrel, 1) * COALESCE(im.liters_per_bag, 1)
        WHEN im.unit_type = 'bag' THEN im.current_quantity * COALESCE(im.liters_per_bag, 1)
        ELSE im.current_quantity
    END as total_base_units,
    -- Available in cartons
    CASE 
        WHEN im.unit_type = 'carton' THEN im.current_quantity
        WHEN im.pieces_per_carton IS NOT NULL THEN im.current_quantity / im.pieces_per_carton
        ELSE 0
    END as available_cartons,
    -- Available in pieces
    CASE 
        WHEN im.unit_type = 'piece' THEN im.current_quantity
        WHEN im.unit_type = 'carton' THEN im.current_quantity * COALESCE(im.pieces_per_carton, 1)
        ELSE 0
    END as available_pieces,
    -- Available in barrels
    CASE 
        WHEN im.unit_type = 'barrel' THEN im.current_quantity
        WHEN im.bags_per_barrel IS NOT NULL AND im.liters_per_bag IS NOT NULL THEN im.current_quantity / (im.bags_per_barrel * im.liters_per_bag)
        ELSE 0
    END as available_barrels,
    -- Available in bags
    CASE 
        WHEN im.unit_type = 'bag' THEN im.current_quantity
        WHEN im.unit_type = 'barrel' THEN im.current_quantity * COALESCE(im.bags_per_barrel, 1)
        WHEN im.liters_per_bag IS NOT NULL THEN im.current_quantity / im.liters_per_bag
        ELSE 0
    END as available_bags,
    -- Available in liters
    CASE 
        WHEN im.unit_type = 'liter' THEN im.current_quantity
        WHEN im.unit_type = 'barrel' THEN im.current_quantity * COALESCE(im.bags_per_barrel, 1) * COALESCE(im.liters_per_bag, 1)
        WHEN im.unit_type = 'bag' THEN im.current_quantity * COALESCE(im.liters_per_bag, 1)
        ELSE 0
    END as available_liters,
    im.currency_type,
    im.purchase_price_usd,
    im.purchase_price_iqd,
    im.price_per_piece,
    im.price_per_liter,
    im.price_per_bag,
    im.created_at,
    im.updated_at
FROM inventory_materials im; 