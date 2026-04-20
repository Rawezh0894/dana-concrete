<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $vehicle_id = $_GET['vehicle_id'] ?? '';
    $category = $_GET['category'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';

    $params = [];
    $where = ["1=1"];

    if (!empty($vehicle_id)) {
        $where[] = "iss.vehicle_id = ?";
        $params[] = $vehicle_id;
    }

    if (!empty($category)) {
        $where[] = "i.category = ?";
        $params[] = $category;
    }

    if (!empty($from_date)) {
        $where[] = "iss.issued_date >= ?";
        $params[] = $from_date;
    }

    if (!empty($to_date)) {
        $where[] = "iss.issued_date <= ?";
        $params[] = $to_date;
    }

    $sql = "SELECT iss.*, i.name as item_name, i.category, i.unit, c.name as vehicle_name, c.car_number
            FROM inv_issuance iss
            JOIN inv_items i ON iss.item_id = i.id
            JOIN cars c ON iss.vehicle_id = c.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY iss.issued_date DESC, iss.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    $total_cost = 0;
    foreach ($data as &$row) {
        $row['total_line_cost'] = floatval($row['qty']) * floatval($row['cost_usd_at_time']);
        $total_cost += $row['total_line_cost'];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total_cost' => $total_cost
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
