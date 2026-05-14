
ALTER TABLE `employee_expenses`
  ADD COLUMN `amount_usd` DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'Cash paid in USD' AFTER `amount`,
  ADD COLUMN `amount_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'Cash paid in IQD' AFTER `amount_usd`,
  ADD COLUMN `exchange_rate` DECIMAL(20,4) NOT NULL DEFAULT 0 COMMENT 'IQD per 1 USD (0 if no USD)' AFTER `amount_iqd`;

ALTER TABLE `cash_box`
  ADD COLUMN `employee_expense_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK-style link to employee_expenses.id' AFTER `created_by`,
  ADD INDEX `idx_cash_box_employee_expense_id` (`employee_expense_id`);

