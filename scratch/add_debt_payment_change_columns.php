<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $p->exec("ALTER TABLE customer_debt_payments ADD COLUMN change_back_usd DECIMAL(10,2) DEFAULT 0 AFTER from_sales_usd");
    $p->exec("ALTER TABLE customer_debt_payments ADD COLUMN change_back_iq DECIMAL(20,2) DEFAULT 0 AFTER change_back_usd");
    echo "Columns added successfully to customer_debt_payments";
} catch (Exception $e) {
    echo $e->getMessage();
}
