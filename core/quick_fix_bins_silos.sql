-- Quick fix for bins_silos table issue
-- This script checks the bins_silos table structure

-- Check if table exists
SELECT 'Checking bins_silos table...' AS info;
SHOW TABLES LIKE 'bins_silos';

-- Check table structure
DESCRIBE bins_silos;

-- Check average_price column
SELECT 'Checking average_price column...' AS info;
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'dana_concrete_db' 
AND TABLE_NAME = 'bins_silos' 
AND COLUMN_NAME = 'average_price';

-- Check gas data
SELECT 'Gas data in bins_silos:' AS info;
SELECT id, name, type, material_type, amount, average_price 
FROM bins_silos 
WHERE material_type = 'گاز' OR type = 'تەنکی';

-- Show success message
SELECT 'bins_silos table checked!' AS status; 