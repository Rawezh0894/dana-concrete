-- Database Migration for New Unit System
-- This migration adds support for different unit types: Carton, Piece, Barrel, Bag, Liter

-- Add new columns to materials table
ALTER TABLE `materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece' AFTER `unit`,
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel';

-- Add new columns to list_materials table
ALTER TABLE `list_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece' AFTER `name`,
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel',
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per individual piece',
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per liter';

-- Add new columns to purchase_materials table
ALTER TABLE `purchase_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece' AFTER `material_id`,
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel',
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per individual piece',
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Price per liter';

-- Create a view for material unit calculations
CREATE VIEW `material_unit_calculations` AS
SELECT 
    m.id,
    m.name,
    m.unit_type,
    m.pieces_per_carton,
    m.bags_per_barrel,
    m.liters_per_bag,
    m.liters_per_barrel,
    lm.quantity as current_quantity,
    lm.purchase_price_usd,
    lm.purchase_price_iqd,
    lm.price_per_piece,
    lm.price_per_liter,
    CASE 
        WHEN m.unit_type = 'carton' THEN lm.quantity * m.pieces_per_carton
        WHEN m.unit_type = 'barrel' THEN lm.quantity * m.bags_per_barrel * m.liters_per_bag
        WHEN m.unit_type = 'bag' THEN lm.quantity * m.liters_per_bag
        ELSE lm.quantity
    END as total_pieces_or_liters
FROM materials m
LEFT JOIN list_materials lm ON m.id = lm.id;

-- Create a function to calculate unit prices
DELIMITER $$
CREATE FUNCTION `calculate_unit_price`(
    p_unit_type ENUM('carton', 'piece', 'barrel', 'bag', 'liter'),
    p_total_price DECIMAL(15,2),
    p_quantity DECIMAL(15,2),
    p_pieces_per_carton INT,
    p_bags_per_barrel INT,
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE unit_price DECIMAL(15,2);
    
    CASE p_unit_type
        WHEN 'carton' THEN
            SET unit_price = p_total_price / p_quantity;
        WHEN 'piece' THEN
            SET unit_price = p_total_price / p_quantity;
        WHEN 'barrel' THEN
            SET unit_price = p_total_price / p_quantity;
        WHEN 'bag' THEN
            SET unit_price = p_total_price / p_quantity;
        WHEN 'liter' THEN
            SET unit_price = p_total_price / p_quantity;
        ELSE
            SET unit_price = 0;
    END CASE;
    
    RETURN unit_price;
END$$
DELIMITER ;

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

-- Update existing materials to have default unit type
UPDATE materials SET unit_type = 'piece' WHERE unit_type IS NULL;
UPDATE list_materials SET unit_type = 'piece' WHERE unit_type IS NULL;
UPDATE purchase_materials SET unit_type = 'piece' WHERE unit_type IS NULL; 