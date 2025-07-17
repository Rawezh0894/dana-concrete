<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$month = isset($_GET['month']) ? $_GET['month'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';

// Get all cars
$cars = $pdo->query('SELECT id, name FROM cars')->fetchAll(PDO::FETCH_ASSOC);

// Gather all years with expenses for dropdown
$years_stmt = $pdo->query('SELECT DISTINCT YEAR(date) as y FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0 ORDER BY y DESC');
$years = array_map(function($row) { return $row['y']; }, $years_stmt->fetchAll(PDO::FETCH_ASSOC));

// Prepare result array
$result = [];

foreach ($cars as $car) {
    $car_id = $car['id'];
    $params = [$car_id];
    $where = 'car_id = ?';
    if ($month) {
        $where .= ' AND DATE_FORMAT(date, "%Y-%m") = ?';
        $params[] = $month;
    } elseif ($year) {
        $where .= ' AND YEAR(date) = ?';
        $params[] = $year;
    }
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN currency_type = 'دینار' THEN amount_iqd ELSE 0 END) AS total_iqd,
        SUM(CASE WHEN currency_type = 'دۆلار' THEN amount_usd ELSE 0 END) AS total_usd,
        COUNT(*) AS expense_count
        FROM other_expenses WHERE $where");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $result[] = [
        'car_id' => $car_id,
        'car_name' => $car['name'],
        'total_iqd' => $row['total_iqd'] ? (float)$row['total_iqd'] : 0,
        'total_usd' => $row['total_usd'] ? (float)$row['total_usd'] : 0,
        'expense_count' => $row['expense_count'] ? (int)$row['expense_count'] : 0
    ];
}

echo json_encode(['data' => $result, 'years' => $years]);
