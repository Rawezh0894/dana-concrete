-- =====================================================
-- SQL Queries to Remove debt_usd and debt_iqd Columns
-- From customers and company tables
-- Created: 2025-01-27
-- =====================================================

-- IMPORTANT: Backup your database before running these queries!

-- =====================================================
-- Step 1: Remove columns from customers table
-- =====================================================

-- Remove debt_usd column from customers table
ALTER TABLE `customers` DROP COLUMN `debt_usd`;

-- Remove debt_iqd column from customers table  
ALTER TABLE `customers` DROP COLUMN `debt_iqd`;

-- =====================================================
-- Step 2: Remove columns from company table
-- =====================================================

-- Remove debt_usd column from company table
ALTER TABLE `company` DROP COLUMN `debt_usd`;

-- Remove debt_iqd column from company table
ALTER TABLE `company` DROP COLUMN `debt_iqd`;

-- =====================================================
-- Verification Queries
-- =====================================================

-- Check if columns were successfully removed from customers table
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND COLUMN_NAME IN ('debt_usd', 'debt_iqd');

-- Check if columns were successfully removed from company table
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'company' 
AND COLUMN_NAME IN ('debt_usd', 'debt_iqd');

-- Show final table structures
DESCRIBE `customers`;
DESCRIBE `company`;

-- =====================================================
-- Test the new debt calculation method
-- =====================================================

-- Test customer debt calculation (should work without errors)
SELECT 
    c.id,
    c.name,
    c.opening_debt_usd,
    c.opening_debt_iqd,
    COALESCE(SUM(s.remaining_amount), 0) as remaining_from_sales,
    (c.opening_debt_usd + COALESCE(SUM(s.remaining_amount), 0)) as total_debt_usd
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_type = 'قەرز'
GROUP BY c.id, c.name, c.opening_debt_usd, c.opening_debt_iqd
LIMIT 5;

-- Test company debt calculation (should work without errors)
SELECT 
    c.id,
    c.name,
    c.opening_debt_usd,
    c.opening_debt_iqd,
    COALESCE(SUM(p.remaining_usd), 0) as remaining_from_purchases,
    (c.opening_debt_usd + COALESCE(SUM(p.remaining_usd), 0)) as total_debt_usd
FROM company c
LEFT JOIN purchases p ON c.id = p.company_id AND p.payment_type = 'قەرز'
GROUP BY c.id, c.name, c.opening_debt_usd, c.opening_debt_iqd
LIMIT 5;

-- =====================================================
-- Notes:
-- =====================================================
-- 1. These columns are no longer needed as the system now uses:
--    - opening_debt_usd and opening_debt_iqd for initial debts
--    - sales.remaining_amount for current debts from sales (customers)
--    - purchases.remaining_usd for current debts from purchases (companies)
--    - customer_debt_payments table for debt payment tracking
--
-- 2. The debt calculation is now done dynamically:
--    - Customer total debt = opening_debt + remaining_amount from sales
--    - Company total debt = opening_debt + remaining_usd from purchases
--    - This provides more accurate and real-time debt tracking
--
-- 3. All PHP code has been updated to use the new calculation method
--    instead of the removed columns. 