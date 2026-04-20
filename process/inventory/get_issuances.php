<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $total = $pdo->query("SELECT COUNT(*) FROM inv_issuance")->fetchColumn();

    $stmt = $pdo->prepare("SELECT iss.*, i.name as item_name, i.unit, c.name as car_name 
                         FROM inv_issuance iss 
                         JOIN inv_items i ON iss.item_id = i.id 
                         JOIN cars c ON iss.vehicle_id = c.id 
                         ORDER BY iss.issued_date DESC, iss.id DESC
                         LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $issuances = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $issuances, 'total' => $total]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
