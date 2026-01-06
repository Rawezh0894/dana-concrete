-- 1. Update employees table with new HR fields
ALTER TABLE `employees`
ADD COLUMN `job_title` VARCHAR(100) NULL AFTER `name`,
ADD COLUMN `department` VARCHAR(100) NULL AFTER `job_title`,
ADD COLUMN `join_date` DATE NULL AFTER `department`,
ADD COLUMN `basic_salary` DECIMAL(15, 2) DEFAULT 0.00 AFTER `salary`,
ADD COLUMN `daily_rate` DECIMAL(15, 2) DEFAULT 0.00 AFTER `basic_salary`,
ADD COLUMN `overtime_rate` DECIMAL(15, 2) DEFAULT 0.00 AFTER `daily_rate`,
ADD COLUMN `balance` DECIMAL(15, 2) DEFAULT 0.00 AFTER `overtime_rate`,
ADD COLUMN `status` ENUM('active', 'inactive', 'on_leave') DEFAULT 'active' AFTER `balance`,
ADD COLUMN `image` VARCHAR(255) NULL AFTER `status`;

-- 2. Create table for Payroll/Salary Generation (Credits to Employee)
CREATE TABLE IF NOT EXISTS `salary_generations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `month` VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    `basic_salary` DECIMAL(15, 2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(10, 2) DEFAULT 0.00,
    `overtime_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `bonuses` DECIMAL(15, 2) DEFAULT 0.00,
    `deductions` DECIMAL(15, 2) DEFAULT 0.00,
    `total_amount` DECIMAL(15, 2) NOT NULL, -- This amount is Credited to employee balance
    `note` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NULL,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create table for Employee Transactions (The Ledger)
-- This tracks everything: Salaries (Credit), Payments (Debit), etc.
CREATE TABLE IF NOT EXISTS `employee_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `type` ENUM('salary', 'payment', 'bonus', 'deduction', 'opening_balance') NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL, -- Positive value
    `operation` ENUM('credit', 'debit') NOT NULL, -- Credit adds to balance (Company owes Employee), Debit subtracts (Company paid Employee)
    `transaction_date` DATE NOT NULL,
    `description` TEXT NULL,
    `reference_id` INT NULL, -- ID of the related salary_generation or payment
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NULL,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Triggers to maintain Employee Balance automatically
DELIMITER //

CREATE TRIGGER after_employee_transaction_insert
AFTER INSERT ON employee_transactions
FOR EACH ROW
BEGIN
    IF NEW.operation = 'credit' THEN
        UPDATE employees SET balance = balance + NEW.amount WHERE id = NEW.employee_id;
    ELSE
        UPDATE employees SET balance = balance - NEW.amount WHERE id = NEW.employee_id;
    END IF;
END;
//

CREATE TRIGGER after_employee_transaction_delete
AFTER DELETE ON employee_transactions
FOR EACH ROW
BEGIN
    IF OLD.operation = 'credit' THEN
        UPDATE employees SET balance = balance - OLD.amount WHERE id = OLD.employee_id;
    ELSE
        UPDATE employees SET balance = balance + OLD.amount WHERE id = OLD.employee_id;
    END IF;
END;
//

DELIMITER ;
