-- Migration: Add is_recipient column to customers table
-- Date: 2025-01-XX
-- Description: Add is_recipient column to indicate if a customer is also a recipient (وەرگر)

-- Step 1: Add is_recipient column to customers table
ALTER TABLE `customers` 
ADD COLUMN `is_recipient` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Indicates if customer is also a recipient (وەرگر)' 
AFTER `opening_debt_iqd`;

-- Note: Default value is 0 (false) for existing records
-- When set to 1 (true), the customer is both a customer and a recipient

