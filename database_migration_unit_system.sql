-- Database Migration for New Unit System
-- This migration creates a new inventory system with different unit types: Carton, Piece, Barrel, Bag, Liter

-- Add unit system columns to purchase_materials table
ALTER TABLE `purchase_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece' AFTER `material_id`,
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton' AFTER `quantity`,
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel' AFTER `pieces_per_carton`,
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag' AFTER `bags_per_barrel`,
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel' AFTER `liters_per_bag`,
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per individual piece' AFTER `price_per_unit_iqd`,
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per liter' AFTER `price_per_piece`,
ADD COLUMN `price_per_bag` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per bag' AFTER `price_per_liter`;

-- Update existing purchase_materials records to have default unit_type
UPDATE `purchase_materials` SET `unit_type` = 'piece' WHERE `unit_type` IS NULL OR `unit_type` = '';

-- Create the new inventory_materials table
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

-- Create SQL functions for unit price calculations
DELIMITER $$

CREATE FUNCTION `calculate_piece_price_from_carton`(
    carton_price DECIMAL(15,2),
    pieces_per_carton INT
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF pieces_per_carton IS NULL OR pieces_per_carton <= 0 THEN
        RETURN carton_price;
    END IF;
    RETURN carton_price / pieces_per_carton;
END$$

CREATE FUNCTION `calculate_liter_price_from_barrel`(
    barrel_price DECIMAL(15,2),
    bags_per_barrel INT,
    liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF bags_per_barrel IS NULL OR bags_per_barrel <= 0 OR liters_per_bag IS NULL OR liters_per_bag <= 0 THEN
        RETURN barrel_price;
    END IF;
    RETURN barrel_price / (bags_per_barrel * liters_per_bag);
END$$

CREATE FUNCTION `calculate_liter_price_from_bag`(
    bag_price DECIMAL(15,2),
    liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF liters_per_bag IS NULL OR liters_per_bag <= 0 THEN
        RETURN bag_price;
    END IF;
    RETURN bag_price / liters_per_bag;
END$$

CREATE FUNCTION `calculate_bag_price_from_barrel`(
    barrel_price DECIMAL(15,2),
    bags_per_barrel INT
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF bags_per_barrel IS NULL OR bags_per_barrel <= 0 THEN
        RETURN barrel_price;
    END IF;
    RETURN barrel_price / bags_per_barrel;
END$$

CREATE FUNCTION `calculate_bag_price_from_liter`(
    liter_price DECIMAL(15,2),
    liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF liters_per_bag IS NULL OR liters_per_bag <= 0 THEN
        RETURN liter_price;
    END IF;
    RETURN liter_price * liters_per_bag;
END$$

DELIMITER ;

-- Create view for unit calculations
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
    currency_type,
    purchase_price_usd,
    purchase_price_iqd,
    price_per_piece,
    price_per_liter,
    price_per_bag,
    CASE 
        WHEN unit_type = 'carton' AND pieces_per_carton > 0 THEN purchase_price_usd / pieces_per_carton
        WHEN unit_type = 'piece' THEN purchase_price_usd
        ELSE price_per_piece
    END as calculated_price_per_piece,
    CASE 
        WHEN unit_type = 'barrel' AND bags_per_barrel > 0 AND liters_per_bag > 0 THEN purchase_price_usd / (bags_per_barrel * liters_per_bag)
        WHEN unit_type = 'bag' AND liters_per_bag > 0 THEN purchase_price_usd / liters_per_bag
        WHEN unit_type = 'liter' THEN purchase_price_usd
        ELSE price_per_liter
    END as calculated_price_per_liter,
    CASE 
        WHEN unit_type = 'barrel' AND bags_per_barrel > 0 THEN purchase_price_usd / bags_per_barrel
        WHEN unit_type = 'bag' THEN purchase_price_usd
        ELSE price_per_bag
    END as calculated_price_per_bag
FROM inventory_materials; 