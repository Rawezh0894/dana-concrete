-- Add load_capacity column to drivers table
-- بەتاڵەی بارهەڵگر بە کیلۆگرام

USE dana_concrete_db;

-- Add load_capacity column after name column
ALTER TABLE drivers 
ADD COLUMN load_capacity DECIMAL(10,2) DEFAULT NULL 
COMMENT 'بەتاڵەی بارهەڵگر بە کیلۆگرام' 
AFTER name;

-- Show the updated table structure
DESCRIBE drivers; 