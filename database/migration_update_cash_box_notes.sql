-- Migration: Update cash_box note field from VARCHAR(255) to TEXT
-- Date: 2025-01-XX
-- Description: Change note field to TEXT to support detailed professional notes

-- Step 1: Alter the table to change note column type
ALTER TABLE `cash_box` MODIFY COLUMN `note` TEXT DEFAULT NULL;

-- Note: The triggers will automatically generate detailed notes for new transactions
-- Existing records will keep their current notes

