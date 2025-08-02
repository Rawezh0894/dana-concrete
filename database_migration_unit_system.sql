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