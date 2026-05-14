-- Optional: run only if `employee_loans` / `loan_repayments` already exist but `cash_box.employee_loan_id` is missing.
-- If you see "Duplicate column name 'employee_loan_id'", skip this file.

ALTER TABLE `cash_box`
  ADD COLUMN `employee_loan_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Loan issuance link' AFTER `employee_expense_id`,
  ADD INDEX `idx_cash_box_employee_loan_id` (`employee_loan_id`);
