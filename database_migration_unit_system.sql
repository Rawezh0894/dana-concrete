-- Database Migration for New Unit System
-- This migration creates a new inventory system with different unit types: Carton, Piece, Barrel, Bag, Liter
-- Each unit type maintains its own separate quantity in inventory

-- Create new inventory_materials table
CREATE TABLE `inventory_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece',
  `pieces_per_carton` INT NULL DEFAULT NULL COMMENT 'Number of pieces in one carton',
  `bags_per_barrel` INT NULL DEFAULT NULL COMMENT 'Number of bags in one barrel',
  `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Number of liters in one bag',
  `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Total liters in one barrel',
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

-- Create inventory_by_unit table to track quantities by unit type
CREATE TABLE `inventory_by_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_unit_unique` (`material_id`, `unit_type`),
  FOREIGN KEY (`material_id`) REFERENCES `inventory_materials`(`id`) ON DELETE CASCADE
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

-- Update existing purchase_materials to have default unit type
UPDATE purchase_materials SET unit_type = 'piece' WHERE unit_type IS NULL;

-- Drop existing views if they exist
DROP VIEW IF EXISTS `inventory_unit_calculations`;
DROP VIEW IF EXISTS `inventory_summary`;
DROP VIEW IF EXISTS `available_inventory_by_unit`;

-- Create a view for material unit calculations
CREATE VIEW `inventory_unit_calculations` AS
SELECT 
    im.id,
    im.name,
    im.unit_type,
    im.pieces_per_carton,
    im.bags_per_barrel,
    im.liters_per_bag,
    im.liters_per_barrel,
    im.currency_type,
    im.purchase_price_usd,
    im.purchase_price_iqd,
    im.price_per_piece,
    im.price_per_liter,
    im.price_per_bag,
    -- Get quantities by unit type
    COALESCE(carton_qty.quantity, 0) as carton_quantity,
    COALESCE(piece_qty.quantity, 0) as piece_quantity,
    COALESCE(barrel_qty.quantity, 0) as barrel_quantity,
    COALESCE(bag_qty.quantity, 0) as bag_quantity,
    COALESCE(liter_qty.quantity, 0) as liter_quantity,
    -- Get total quantity across all units
    COALESCE(carton_qty.quantity, 0) + COALESCE(piece_qty.quantity, 0) + 
    COALESCE(barrel_qty.quantity, 0) + COALESCE(bag_qty.quantity, 0) + 
    COALESCE(liter_qty.quantity, 0) as total_quantity
FROM inventory_materials im
LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter';

-- Create view for inventory summary with unit-specific quantities
CREATE VIEW `inventory_summary` AS
SELECT 
    im.id,
    im.name,
    im.unit_type,
    im.pieces_per_carton,
    im.bags_per_barrel,
    im.liters_per_bag,
    im.liters_per_barrel,
    -- Get quantities by unit type
    COALESCE(carton_qty.quantity, 0) as carton_quantity,
    COALESCE(piece_qty.quantity, 0) as piece_quantity,
    COALESCE(barrel_qty.quantity, 0) as barrel_quantity,
    COALESCE(bag_qty.quantity, 0) as bag_quantity,
    COALESCE(liter_qty.quantity, 0) as liter_quantity,
    -- Get total quantity across all units
    COALESCE(carton_qty.quantity, 0) + COALESCE(piece_qty.quantity, 0) + 
    COALESCE(barrel_qty.quantity, 0) + COALESCE(bag_qty.quantity, 0) + 
    COALESCE(liter_qty.quantity, 0) as total_quantity,
    im.currency_type,
    im.purchase_price_usd,
    im.purchase_price_iqd,
    im.price_per_piece,
    im.price_per_liter,
    im.price_per_bag,
    im.created_at,
    im.updated_at
FROM inventory_materials im
LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter';

-- Create a view for available inventory by unit type
CREATE VIEW `available_inventory_by_unit` AS
SELECT 
    im.id,
    im.name,
    'carton' as unit_type,
    COALESCE(carton_qty.quantity, 0) as available_quantity,
    im.pieces_per_carton,
    im.price_per_piece
FROM inventory_materials im
LEFT JOIN inventory_by_unit carton_qty ON im.id = carton_qty.material_id AND carton_qty.unit_type = 'carton'
WHERE COALESCE(carton_qty.quantity, 0) > 0

UNION ALL

SELECT 
    im.id,
    im.name,
    'piece' as unit_type,
    COALESCE(piece_qty.quantity, 0) as available_quantity,
    1 as pieces_per_carton,
    im.price_per_piece
FROM inventory_materials im
LEFT JOIN inventory_by_unit piece_qty ON im.id = piece_qty.material_id AND piece_qty.unit_type = 'piece'
WHERE COALESCE(piece_qty.quantity, 0) > 0

UNION ALL

SELECT 
    im.id,
    im.name,
    'barrel' as unit_type,
    COALESCE(barrel_qty.quantity, 0) as available_quantity,
    im.bags_per_barrel as pieces_per_carton,
    im.price_per_bag
FROM inventory_materials im
LEFT JOIN inventory_by_unit barrel_qty ON im.id = barrel_qty.material_id AND barrel_qty.unit_type = 'barrel'
WHERE COALESCE(barrel_qty.quantity, 0) > 0

UNION ALL

SELECT 
    im.id,
    im.name,
    'bag' as unit_type,
    COALESCE(bag_qty.quantity, 0) as available_quantity,
    im.liters_per_bag as pieces_per_carton,
    im.price_per_bag
FROM inventory_materials im
LEFT JOIN inventory_by_unit bag_qty ON im.id = bag_qty.material_id AND bag_qty.unit_type = 'bag'
WHERE COALESCE(bag_qty.quantity, 0) > 0

UNION ALL

SELECT 
    im.id,
    im.name,
    'liter' as unit_type,
    COALESCE(liter_qty.quantity, 0) as available_quantity,
    1 as pieces_per_carton,
    im.price_per_liter
FROM inventory_materials im
LEFT JOIN inventory_by_unit liter_qty ON im.id = liter_qty.material_id AND liter_qty.unit_type = 'liter'
WHERE COALESCE(liter_qty.quantity, 0) > 0; 