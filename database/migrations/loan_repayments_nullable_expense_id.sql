-- Allow direct (cash) loan repayments without a payroll employee_expenses row.
-- Run after employee_loans.sql if loan_repayments already exists with NOT NULL expense_id.
--
--   mysql -u USER -p DATABASE < database/migrations/loan_repayments_nullable_expense_id.sql

ALTER TABLE `loan_repayments`
  MODIFY `expense_id` INT UNSIGNED NULL DEFAULT NULL
  COMMENT 'employee_expenses.id for payroll deduction; NULL for direct cash repayment';
