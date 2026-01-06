-- Employee Ledger (ERP-style payroll)
-- Date: 2026-01-06
--
-- Supports:
-- - Salary accrual (credit)
-- - Overtime/bonus (credit)
-- - Penalty/advance/payment (debit)
-- - Multiple withdrawals per employee
-- - Running employee balance stored on employees.balance (via triggers)

-- MySQL does NOT support: ADD COLUMN IF NOT EXISTS
-- Use information_schema + prepared statement instead
SET @db := DATABASE();
SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'balance'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE employees ADD COLUMN balance DECIMAL(15,2) NOT NULL DEFAULT 0.00',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS employee_transactions (
  id INT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  operation ENUM('credit','debit') NOT NULL,
  pay_month VARCHAR(7) NULL,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_employee_date (employee_id, transaction_date),
  KEY idx_type (type),
  CONSTRAINT fk_employee_transactions_employee
    FOREIGN KEY (employee_id) REFERENCES employees(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TRIGGER IF EXISTS trg_employee_txn_ai;
DROP TRIGGER IF EXISTS trg_employee_txn_au;
DROP TRIGGER IF EXISTS trg_employee_txn_ad;

DELIMITER ;;
CREATE TRIGGER trg_employee_txn_ai
AFTER INSERT ON employee_transactions
FOR EACH ROW
BEGIN
  UPDATE employees
  SET balance = balance + (CASE WHEN NEW.operation = 'credit' THEN NEW.amount ELSE -NEW.amount END)
  WHERE id = NEW.employee_id;
END;;

CREATE TRIGGER trg_employee_txn_au
AFTER UPDATE ON employee_transactions
FOR EACH ROW
BEGIN
  DECLARE old_signed DECIMAL(15,2);
  DECLARE new_signed DECIMAL(15,2);
  SET old_signed = (CASE WHEN OLD.operation = 'credit' THEN OLD.amount ELSE -OLD.amount END);
  SET new_signed = (CASE WHEN NEW.operation = 'credit' THEN NEW.amount ELSE -NEW.amount END);

  IF OLD.employee_id <> NEW.employee_id THEN
    UPDATE employees SET balance = balance - old_signed WHERE id = OLD.employee_id;
    UPDATE employees SET balance = balance + new_signed WHERE id = NEW.employee_id;
  ELSE
    UPDATE employees SET balance = balance + (new_signed - old_signed) WHERE id = NEW.employee_id;
  END IF;
END;;

CREATE TRIGGER trg_employee_txn_ad
AFTER DELETE ON employee_transactions
FOR EACH ROW
BEGIN
  UPDATE employees
  SET balance = balance - (CASE WHEN OLD.operation = 'credit' THEN OLD.amount ELSE -OLD.amount END)
  WHERE id = OLD.employee_id;
END;;
DELIMITER ;


