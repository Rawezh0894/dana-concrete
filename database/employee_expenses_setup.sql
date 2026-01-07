-- SQL Queries to create employee_expenses table and update employees table
-- Run these queries in your dana_concrete_db database

-- 1. Update employees table to add missing fields
-- Note: MariaDB doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- So we check and add columns one by one

-- Check and add full_name
SET @dbname = DATABASE();
SET @tablename = 'employees';
SET @columnname = 'full_name';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(100) NULL AFTER name')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add position
SET @columnname = 'position';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NULL AFTER role')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add status
SET @columnname = 'status';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, " ENUM('active','inactive','on_leave','resigned') DEFAULT 'active' AFTER position")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add join_date
SET @columnname = 'join_date';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DATE NULL AFTER status')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add phone
SET @columnname = 'phone';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(20) NULL AFTER join_date')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add payable_balance
SET @columnname = 'payable_balance';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(15,2) DEFAULT 0.00 AFTER payable_balance_usd')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add receivable_balance
SET @columnname = 'receivable_balance';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(15,2) DEFAULT 0.00 AFTER payable_balance')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add notes
SET @columnname = 'notes';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT NULL AFTER receivable_balance')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add salary_start_date
SET @columnname = 'salary_start_date';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DATE NULL AFTER notes')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update full_name from name if full_name is NULL
UPDATE `employees` SET `full_name` = `name` WHERE `full_name` IS NULL OR `full_name` = '';
UPDATE `employees` SET `position` = `role` WHERE `position` IS NULL OR `position` = '';
UPDATE `employees` SET `phone` = `mobile` WHERE `phone` IS NULL OR `phone` = '';

-- 2. Create employee_expenses table
CREATE TABLE IF NOT EXISTS `employee_expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `expense_type` ENUM('salary','bonus','overtime','deduction','advance','penalty') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expense_date` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM for month/year',
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_employee_expenses_expense_date` (`expense_date`),
  KEY `idx_employee_expenses_employee_id` (`employee_id`),
  KEY `idx_employee_expenses_created_by` (`created_by`),
  KEY `idx_employee_expenses_type` (`expense_type`),
  CONSTRAINT `employee_expenses_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create trigger to update employee balances when expense is added
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_after_insert_employee_expense_balance`$$
CREATE TRIGGER `trg_after_insert_employee_expense_balance` 
AFTER INSERT ON `employee_expenses` 
FOR EACH ROW 
BEGIN
    DECLARE v_current_payable DECIMAL(15,2) DEFAULT 0;
    DECLARE v_current_receivable DECIMAL(15,2) DEFAULT 0;
    
    -- Get current balances
    SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
    INTO v_current_payable, v_current_receivable
    FROM `employees` 
    WHERE `id` = NEW.employee_id;
    
    IF NEW.expense_type IN ('salary', 'bonus', 'overtime') THEN
        -- Increase payable balance (company owes employee)
        UPDATE `employees` 
        SET `payable_balance` = COALESCE(`payable_balance`, 0) + NEW.amount 
        WHERE `id` = NEW.employee_id;
    ELSEIF NEW.expense_type IN ('deduction', 'penalty') THEN
        -- First reduce payable balance, then add excess to receivable balance
        IF v_current_payable >= NEW.amount THEN
            -- All deduction comes from payable balance
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable - NEW.amount
            WHERE `id` = NEW.employee_id;
        ELSE
            -- Payable balance becomes 0, excess goes to receivable
            UPDATE `employees` 
            SET `payable_balance` = 0,
                `receivable_balance` = COALESCE(`receivable_balance`, 0) + (NEW.amount - v_current_payable)
            WHERE `id` = NEW.employee_id;
        END IF;
    ELSEIF NEW.expense_type = 'advance' THEN
        -- Advance reduces payable balance first (money taken from salary)
        -- If payable balance is not enough, excess goes to receivable balance
        IF v_current_payable >= NEW.amount THEN
            -- All advance comes from payable balance (salary)
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable - NEW.amount
            WHERE `id` = NEW.employee_id;
        ELSE
            -- Payable balance becomes 0, excess goes to receivable
            UPDATE `employees` 
            SET `payable_balance` = 0,
                `receivable_balance` = v_current_receivable + (NEW.amount - v_current_payable)
            WHERE `id` = NEW.employee_id;
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_after_update_employee_expense_balance`$$
CREATE TRIGGER `trg_after_update_employee_expense_balance` 
AFTER UPDATE ON `employee_expenses` 
FOR EACH ROW 
BEGIN
    DECLARE v_current_payable DECIMAL(15,2) DEFAULT 0;
    DECLARE v_current_receivable DECIMAL(15,2) DEFAULT 0;
    
    -- Only process if amount or type changed
    IF OLD.amount != NEW.amount OR OLD.expense_type != NEW.expense_type THEN
        -- Get current balances before reverting
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM `employees` 
        WHERE `id` = OLD.employee_id;
        
        -- Revert old amount
        IF OLD.expense_type IN ('salary', 'bonus', 'overtime') THEN
            UPDATE `employees` 
            SET `payable_balance` = GREATEST(0, v_current_payable - OLD.amount) 
            WHERE `id` = OLD.employee_id;
        ELSEIF OLD.expense_type IN ('deduction', 'penalty') THEN
            -- When reverting deduction/penalty:
            -- If receivable has the amount, restore it to payable
            -- Otherwise, just add back to payable
            IF v_current_receivable >= OLD.amount THEN
                -- All was in receivable, restore to payable
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable + OLD.amount,
                    `receivable_balance` = v_current_receivable - OLD.amount
                WHERE `id` = OLD.employee_id;
            ELSE
                -- Some was in receivable, some was from payable
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable + (OLD.amount - v_current_receivable),
                    `receivable_balance` = 0
                WHERE `id` = OLD.employee_id;
            END IF;
        ELSEIF OLD.expense_type = 'advance' THEN
            -- When reverting advance:
            -- If receivable has the amount, restore it to payable
            -- Otherwise, just add back to payable
            IF v_current_receivable >= OLD.amount THEN
                -- All was in receivable, restore to payable
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable + OLD.amount,
                    `receivable_balance` = v_current_receivable - OLD.amount
                WHERE `id` = OLD.employee_id;
            ELSE
                -- Some was in receivable, some was from payable
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable + (OLD.amount - v_current_receivable),
                    `receivable_balance` = 0
                WHERE `id` = OLD.employee_id;
            END IF;
        END IF;
        
        -- Get updated balances after revert
        SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
        INTO v_current_payable, v_current_receivable
        FROM `employees` 
        WHERE `id` = NEW.employee_id;
        
        -- Apply new amount
        IF NEW.expense_type IN ('salary', 'bonus', 'overtime') THEN
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable + NEW.amount 
            WHERE `id` = NEW.employee_id;
        ELSEIF NEW.expense_type IN ('deduction', 'penalty') THEN
            -- First reduce payable balance, then add excess to receivable balance
            IF v_current_payable >= NEW.amount THEN
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable - NEW.amount
                WHERE `id` = NEW.employee_id;
            ELSE
                UPDATE `employees` 
                SET `payable_balance` = 0,
                    `receivable_balance` = v_current_receivable + (NEW.amount - v_current_payable)
                WHERE `id` = NEW.employee_id;
            END IF;
        ELSEIF NEW.expense_type = 'advance' THEN
            -- Advance reduces payable balance first
            IF v_current_payable >= NEW.amount THEN
                UPDATE `employees` 
                SET `payable_balance` = v_current_payable - NEW.amount
                WHERE `id` = NEW.employee_id;
            ELSE
                UPDATE `employees` 
                SET `payable_balance` = 0,
                    `receivable_balance` = v_current_receivable + (NEW.amount - v_current_payable)
                WHERE `id` = NEW.employee_id;
            END IF;
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_after_delete_employee_expense_balance`$$
CREATE TRIGGER `trg_after_delete_employee_expense_balance` 
AFTER DELETE ON `employee_expenses` 
FOR EACH ROW 
BEGIN
    DECLARE v_current_payable DECIMAL(15,2) DEFAULT 0;
    DECLARE v_current_receivable DECIMAL(15,2) DEFAULT 0;
    
    -- Get current balances
    SELECT COALESCE(payable_balance, 0), COALESCE(receivable_balance, 0)
    INTO v_current_payable, v_current_receivable
    FROM `employees` 
    WHERE `id` = OLD.employee_id;
    
    -- Revert balance changes
    IF OLD.expense_type IN ('salary', 'bonus', 'overtime') THEN
        -- Reduce payable balance
        UPDATE `employees` 
        SET `payable_balance` = GREATEST(0, v_current_payable - OLD.amount) 
        WHERE `id` = OLD.employee_id;
    ELSEIF OLD.expense_type IN ('deduction', 'penalty') THEN
        -- When reverting deduction/penalty, we need to restore the balance correctly
        -- The deduction might have come from payable balance or been added to receivable
        -- We need to check current state and restore appropriately
        -- Strategy: Check if receivable has enough to cover the deduction amount
        -- If yes, restore from receivable to payable
        -- If no, restore what we can from receivable and add rest to payable
        IF v_current_receivable >= OLD.amount THEN
            -- All deduction amount is in receivable, restore it all to payable
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable + OLD.amount,
                `receivable_balance` = v_current_receivable - OLD.amount
            WHERE `id` = OLD.employee_id;
        ELSE
            -- Part of deduction is in receivable, part was from payable
            -- Restore receivable part to payable, and add the rest to payable
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable + OLD.amount,
                `receivable_balance` = 0
            WHERE `id` = OLD.employee_id;
        END IF;
    ELSEIF OLD.expense_type = 'advance' THEN
        -- When reverting advance:
        -- If receivable has the amount, restore it to payable
        -- Otherwise, just add back to payable
        IF v_current_receivable >= OLD.amount THEN
            -- All was in receivable, restore to payable
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable + OLD.amount,
                `receivable_balance` = v_current_receivable - OLD.amount
            WHERE `id` = OLD.employee_id;
        ELSE
            -- Some was in receivable, some was from payable
            UPDATE `employees` 
            SET `payable_balance` = v_current_payable + (OLD.amount - v_current_receivable),
                `receivable_balance` = 0
            WHERE `id` = OLD.employee_id;
        END IF;
    END IF;
END$$

DELIMITER ;

