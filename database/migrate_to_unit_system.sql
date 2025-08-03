-- Migration script to update existing database structure for unit system
-- Run this script to add the new fields to existing tables

-- Update list_materials table
ALTER TABLE `list_materials` 
ADD COLUMN `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') NOT NULL DEFAULT 'دانە' AFTER `name`,
ADD COLUMN `pieces_per_carton` int(11) DEFAULT NULL COMMENT 'ژمارەی دانە لە کارتۆن' AFTER `purchase_price_iqd`,
ADD COLUMN `buckets_per_barrel` int(11) DEFAULT NULL COMMENT 'ژمارەی دەبە لە بەرمیل' AFTER `pieces_per_carton`,
ADD COLUMN `liters_per_bucket` decimal(10,2) DEFAULT NULL COMMENT 'ژمارەی لیتر لە دەبە' AFTER `buckets_per_barrel`,
ADD COLUMN `liters_per_barrel` decimal(10,2) DEFAULT NULL COMMENT 'کۆی لیتر لە بەرمیل' AFTER `liters_per_bucket`,
ADD COLUMN `price_per_piece_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دۆلار' AFTER `liters_per_barrel`,
ADD COLUMN `price_per_piece_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دانە بە دینار' AFTER `price_per_piece_usd`,
ADD COLUMN `price_per_bucket_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دۆلار' AFTER `price_per_piece_iqd`,
ADD COLUMN `price_per_bucket_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی دەبە بە دینار' AFTER `price_per_bucket_usd`,
ADD COLUMN `price_per_liter_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دۆلار' AFTER `price_per_bucket_iqd`,
ADD COLUMN `price_per_liter_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی لیتر بە دینار' AFTER `price_per_liter_usd`;

-- Update purchase_materials table
ALTER TABLE `purchase_materials` 
ADD COLUMN `unit_type` enum('کارتۆن','دانە','بەرمیل','دەبە','لیتر') NOT NULL DEFAULT 'دانە' AFTER `person_id`,
ADD COLUMN `base_quantity` decimal(15,2) DEFAULT 0.00 COMMENT 'بڕی بنەڕەتی بە دانە' AFTER `updated_at`,
ADD COLUMN `base_price_per_unit_usd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دۆلار' AFTER `base_quantity`,
ADD COLUMN `base_price_per_unit_iqd` decimal(15,2) DEFAULT 0.00 COMMENT 'نرخی بنەڕەتی بە دانە بە دینار' AFTER `base_price_per_unit_usd`;

-- Update existing records to set default unit_type
UPDATE `list_materials` SET `unit_type` = 'دانە' WHERE `unit_type` IS NULL OR `unit_type` = '';

-- Update existing records to set default unit_type in purchase_materials
UPDATE `purchase_materials` SET `unit_type` = 'دانە' WHERE `unit_type` IS NULL OR `unit_type` = '';

-- Set base_quantity equal to quantity for existing purchase records (assuming they were all pieces)
UPDATE `purchase_materials` SET `base_quantity` = `quantity` WHERE `base_quantity` IS NULL OR `base_quantity` = 0;

-- Set base prices equal to unit prices for existing purchase records
UPDATE `purchase_materials` SET 
`base_price_per_unit_usd` = `price_per_unit_usd`,
`base_price_per_unit_iqd` = `price_per_unit_iqd`
WHERE `base_price_per_unit_usd` IS NULL OR `base_price_per_unit_usd` = 0; 