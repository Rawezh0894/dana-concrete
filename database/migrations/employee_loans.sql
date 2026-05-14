-- Employee loans + repayments. Run in MySQL client, e.g.:
--   mysql -u USER -p DATABASE < database/migrations/employee_loans.sql
--
-- If `employee_loan_id` already exists on cash_box, skip the ALTER at the bottom
-- or use employee_loans_cash_box_column.sql only when needed.

CREATE TABLE IF NOT EXISTS `employee_loans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `loan_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `loan_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `remaining_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `remaining_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `loan_date` DATE NOT NULL,
  `status` ENUM('active', 'paid_off', 'cancelled') NOT NULL DEFAULT 'active',
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_loans_employee` (`employee_id`),
  KEY `idx_employee_loans_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_repayments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` INT UNSIGNED NOT NULL,
  `expense_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'employee_expenses.id for payroll; NULL for direct cash repayment',
  `deducted_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `deducted_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loan_repayments_loan` (`loan_id`),
  KEY `idx_loan_repayments_expense` (`expense_id`),
  CONSTRAINT `fk_loan_repayments_loan` FOREIGN KEY (`loan_id`) REFERENCES `employee_loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `cash_box`
  ADD COLUMN `employee_loan_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Loan issuance link' AFTER `employee_expense_id`,
  ADD INDEX `idx_cash_box_employee_loan_id` (`employee_loan_id`);
