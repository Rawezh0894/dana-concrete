<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $p->exec("ALTER TABLE debt_payments ADD COLUMN change_back_usd DECIMAL(14,2) DEFAULT 0 AFTER discount_iqd");
    $p->exec("ALTER TABLE debt_payments ADD COLUMN change_back_iqd DECIMAL(20,2) DEFAULT 0 AFTER change_back_usd");
    echo "Columns added successfully";
} catch (Exception $e) {
    echo $e->getMessage();
}
