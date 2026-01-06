-- Remove Employee Ledger (ERP-style payroll)
-- Date: 2026-01-06
--
-- This script removes:
-- - Triggers for employee_transactions
-- - employee_transactions table
-- - balance column from employees table

-- Drop triggers first
DROP TRIGGER IF EXISTS trg_employee_txn_ai;
DROP TRIGGER IF EXISTS trg_employee_txn_au;
DROP TRIGGER IF EXISTS trg_employee_txn_ad;

-- Drop the employee_transactions table
DROP TABLE IF EXISTS employee_transactions;

-- Remove balance column from employees table
-- MySQL does NOT support: DROP COLUMN IF EXISTS
-- Use information_schema + prepared statement instead
SET @db := DATABASE();
SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'balance'
);
SET @sql := IF(
  @col_exists > 0,
  'ALTER TABLE employees DROP COLUMN balance',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

