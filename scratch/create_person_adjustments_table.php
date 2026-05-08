<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $sql = "CREATE TABLE IF NOT EXISTS person_other_expenses_adjustments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        person_id INT(11) NOT NULL,
        amount_usd DECIMAL(15,2) DEFAULT 0,
        amount_iqd DECIMAL(15,2) DEFAULT 0,
        date DATE NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $p->exec($sql);
    echo "Table 'person_other_expenses_adjustments' created successfully";
} catch (Exception $e) {
    echo $e->getMessage();
}
