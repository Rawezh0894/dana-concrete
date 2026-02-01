<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM other_income ORDER BY date DESC, id DESC";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
