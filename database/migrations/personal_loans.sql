-- Personal loans to non-customers (قەرزی کەسانی دەرەکی)
-- mysql -u USER -p DATABASE < database/migrations/personal_loans.sql

CREATE TABLE IF NOT EXISTS `personal_loan_persons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(50) DEFAULT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_personal_loan_persons_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personal_loans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` INT UNSIGNED NOT NULL,
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
  KEY `idx_personal_loans_person` (`person_id`),
  KEY `idx_personal_loans_status` (`status`),
  CONSTRAINT `fk_personal_loans_person` FOREIGN KEY (`person_id`) REFERENCES `personal_loan_persons` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personal_loan_repayments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_id` INT UNSIGNED NOT NULL,
  `received_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `received_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `change_back_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `change_back_iq` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `applied_usd` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `applied_iqd` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `dolar_rate` DECIMAL(14,2) NOT NULL DEFAULT 150000,
  `repayment_date` DATE NOT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_repayments_loan` (`loan_id`),
  CONSTRAINT `fk_pl_repayments_loan` FOREIGN KEY (`loan_id`) REFERENCES `personal_loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: link cash_box rows to personal loan (issuance / repayment)
-- Skip if column already exists
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cash_box' AND COLUMN_NAME = 'personal_loan_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `cash_box` ADD COLUMN `personal_loan_id` INT UNSIGNED NULL DEFAULT NULL COMMENT ''Personal loan link'' AFTER `employee_loan_id`, ADD INDEX `idx_cash_box_personal_loan_id` (`personal_loan_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
