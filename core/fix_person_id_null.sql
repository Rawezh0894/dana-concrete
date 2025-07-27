-- Fix person_id column to allow NULL values
-- This script modifies the person_id column to allow NULL values

-- First, let's check the current structure of the other_expenses table
DESCRIBE other_expenses;

-- Check if person_id has NOT NULL constraint
SELECT 
    COLUMN_NAME,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'dana_concrete_db' 
AND TABLE_NAME = 'other_expenses' 
AND COLUMN_NAME = 'person_id';

-- Modify the person_id column to allow NULL values
ALTER TABLE `other_expenses` 
MODIFY COLUMN `person_id` int(11) NULL DEFAULT NULL;

-- Verify the change
DESCRIBE other_expenses;

-- Check the foreign key constraint
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'dana_concrete_db' 
AND TABLE_NAME = 'other_expenses' 
AND COLUMN_NAME = 'person_id';

-- Show confirmation
SELECT 'person_id column has been modified to allow NULL values!' AS status; 