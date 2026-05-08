<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $p->exec("ALTER TABLE person_other_expenses_debt_payments ADD COLUMN change_back_usd DECIMAL(15,2) DEFAULT 0 AFTER discount_iqd");
    $p->exec("ALTER TABLE person_other_expenses_debt_payments ADD COLUMN change_back_iqd DECIMAL(15,2) DEFAULT 0 AFTER change_back_usd");
    $p->exec("ALTER TABLE person_other_expenses_debt_payments ADD COLUMN dollar_rate DECIMAL(15,2) DEFAULT 150000 AFTER change_back_iqd");
    echo "Columns added successfully";
} catch (Exception $e) {
    echo $e->getMessage();
}
