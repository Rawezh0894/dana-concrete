<?php
require 'c:\xampp\htdocs\dana-concrete\config\db_conected.php';
$stmt = $pdo->query('SELECT SUM(quantity) as q, SUM(price_per_unit * quantity) as gross_total, SUM(total_price) as total_price, SUM(discount) as discount FROM sales');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
