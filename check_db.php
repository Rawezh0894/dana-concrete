<?php
require_once 'config/db_conected.php';
$stmt = $pdo->query("DESCRIBE other_expense_persons");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT SUM(expense_usd) as usd, SUM(expense_iqd) as iqd, SUM(opening_debt_usd) as o_usd, SUM(opening_debt_iqd) as o_iqd FROM other_expense_persons");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
