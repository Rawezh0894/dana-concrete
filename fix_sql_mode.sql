-- Fix SQL Mode issue for dana-concrete database
-- This will disable ONLY_FULL_GROUP_BY mode which is causing the error

-- Check current SQL mode
SELECT @@sql_mode as current_sql_mode;

-- Set SQL mode to remove ONLY_FULL_GROUP_BY
SET sql_mode = (SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

-- Verify the change
SELECT @@sql_mode as new_sql_mode;

-- Alternative: Set specific SQL mode
-- SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Show success message
SELECT 'SQL mode updated successfully!' as message; 