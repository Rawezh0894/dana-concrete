<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
$formulas = $pdo->query('SELECT id, name FROM concrete_formulas')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($formulas); 