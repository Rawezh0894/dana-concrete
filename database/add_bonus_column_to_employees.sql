-- کوێری بۆ زیادکردنی ستوونی bonus بۆ تەیبڵی employees
-- Run this query in your dana_concrete_db database

USE dana_concrete_db;

-- Check and add bonus column
SET @dbname = DATABASE();
SET @tablename = 'employees';
SET @columnname = 'bonus';

-- Check if bonus column exists
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
);

-- Add bonus column if it doesn't exist
SET @sql = IF(@column_exists = 0,
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) DEFAULT 0.00 AFTER salary'),
    'SELECT "Column bonus already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify bonus column exists
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'employees' 
  AND COLUMN_NAME = 'bonus';
