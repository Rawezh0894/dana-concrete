-- Revert HR System Changes
-- This script removes the tables and columns added for the HR system.

-- 1. Drop Triggers
DROP TRIGGER IF EXISTS after_employee_transaction_insert;
DROP TRIGGER IF EXISTS after_employee_transaction_delete;

-- 2. Drop Tables (Child tables first to maintain foreign key integrity usually, though DROP TABLE handles it if CASCADE/checks off, but safe order is good)
DROP TABLE IF EXISTS employee_transactions;
DROP TABLE IF EXISTS salary_generations;

-- 3. Remove Columns from Employees
-- Using a stored procedure-like block or just direct ALTERs if we assume columns exist.
-- If you are unsure if they exist, you might get an error.
-- Ignoring errors is not standard SQL without stored procs, but for manual run this is fine.

ALTER TABLE `employees`
DROP COLUMN `job_title`,
DROP COLUMN `department`,
DROP COLUMN `join_date`,
DROP COLUMN `basic_salary`,
DROP COLUMN `daily_rate`,
DROP COLUMN `overtime_rate`,
DROP COLUMN `balance`,
DROP COLUMN `status`,
DROP COLUMN `image`;
