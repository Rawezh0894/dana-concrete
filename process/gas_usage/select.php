<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $dateFrom = $_GET['dateFrom'] ?? null;
    $dateTo = $_GET['dateTo'] ?? null;
    $car_id = $_GET['car_id'] ?? null;

    $where = ["expense_type = 'بەکارهێنانی گاز'"];
    $params = [];

    if ($dateFrom) {
        $where[] = "date >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where[] = "date <= ?";
        $params[] = $dateTo;
    }
    if ($car_id) {
        $where[] = "car_id = ?";
        $params[] = $car_id;
    }

    $whereSql = implode(" AND ", $where);

    // Main Query
    $sql = "SELECT oe.*, c.name as car_name 
            FROM other_expenses oe 
            LEFT JOIN cars c ON oe.car_id = c.id 
            WHERE $whereSql 
            ORDER BY oe.date DESC, oe.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary Query
    $sumSql = "SELECT SUM(gas_liters) as total_liters, SUM(gas_total_cost) as total_cost 
               FROM other_expenses 
               WHERE $whereSql";
    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total_liters' => $summary['total_liters'] ?: 0,
            'total_cost' => $summary['total_cost'] ?: 0
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'msg' => 'هەڵە لە وەرگرتنی زانیارییەکان: ' . $e->getMessage()
    ]);
}
