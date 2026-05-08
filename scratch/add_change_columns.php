<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $p->exec("ALTER TABLE sales ADD COLUMN change_back_usd DECIMAL(10,2) DEFAULT 0 AFTER discount");
    $p->exec("ALTER TABLE sales ADD COLUMN change_back_iq DECIMAL(10,2) DEFAULT 0 AFTER change_back_usd");
    echo "Columns added successfully";
} catch (Exception $e) {
    echo $e->getMessage();
}
