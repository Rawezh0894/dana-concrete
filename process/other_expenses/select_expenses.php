<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
$sql = "SELECT oe.*, p.name AS person_name, e.name AS employee_name, c.name AS car_name, oe.car_id, oe.gas_liters
        FROM other_expenses oe
        LEFT JOIN other_expense_persons p ON oe.person_id = p.id
        LEFT JOIN employees e ON oe.employee_id = e.id
        LEFT JOIN cars c ON oe.car_id = c.id
        ORDER BY oe.id DESC";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
