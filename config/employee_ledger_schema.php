<?php
/**
 * Employee Ledger Schema bootstrapper
 *
 * This project historically used `employee_payments` only. To support ERP-like payroll
 * (advances, penalties, overtime, multiple withdrawals) we use `employee_transactions`
 * as a ledger and keep `employees.balance` updated via triggers.
 *
 * This helper makes the feature self-contained by creating the required objects if missing.
 */

function employeeLedgerDbName(PDO $pdo): string
{
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    if (!$dbName) {
        throw new RuntimeException('No database selected');
    }
    return (string)$dbName;
}

function employeeLedgerHasColumn(PDO $pdo, string $dbName, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$dbName, $table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function employeeLedgerHasTable(PDO $pdo, string $dbName, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
    ");
    $stmt->execute([$dbName, $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function employeeLedgerHasTrigger(PDO $pdo, string $dbName, string $triggerName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = ? AND TRIGGER_NAME = ?
    ");
    $stmt->execute([$dbName, $triggerName]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureEmployeeBalanceColumn(PDO $pdo, string $dbName): void
{
    if (!employeeLedgerHasColumn($pdo, $dbName, 'employees', 'balance')) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN balance DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    }
}

function ensureEmployeeTransactionsTable(PDO $pdo, string $dbName): void
{
    if (employeeLedgerHasTable($pdo, $dbName, 'employee_transactions')) {
        return;
    }

    $pdo->exec("
        CREATE TABLE employee_transactions (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function ensureEmployeeLedgerTriggers(PDO $pdo, string $dbName): void
{
    $triggers = [
        'trg_employee_txn_ai' => "
            CREATE TRIGGER trg_employee_txn_ai
            AFTER INSERT ON employee_transactions
            FOR EACH ROW
            BEGIN
                UPDATE employees
                SET balance = balance + (CASE WHEN NEW.operation = 'credit' THEN NEW.amount ELSE -NEW.amount END)
                WHERE id = NEW.employee_id;
            END
        ",
        'trg_employee_txn_au' => "
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
            END
        ",
        'trg_employee_txn_ad' => "
            CREATE TRIGGER trg_employee_txn_ad
            AFTER DELETE ON employee_transactions
            FOR EACH ROW
            BEGIN
                UPDATE employees
                SET balance = balance - (CASE WHEN OLD.operation = 'credit' THEN OLD.amount ELSE -OLD.amount END)
                WHERE id = OLD.employee_id;
            END
        ",
    ];

    foreach ($triggers as $triggerName => $createSql) {
        if (!employeeLedgerHasTrigger($pdo, $dbName, $triggerName)) {
            $pdo->exec("DROP TRIGGER IF EXISTS {$triggerName}");
            $pdo->exec($createSql);
        }
    }
}

function ensureEmployeeLedgerSchema(PDO $pdo): void
{
    $dbName = employeeLedgerDbName($pdo);

    ensureEmployeeBalanceColumn($pdo, $dbName);
    ensureEmployeeTransactionsTable($pdo, $dbName);
    ensureEmployeeLedgerTriggers($pdo, $dbName);
}

function formatSignedAmount(string $operation, $amount): float
{
    $amt = (float)$amount;
    return $operation === 'credit' ? $amt : -$amt;
}


