<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM inv_units ORDER BY id DESC");
    $units = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $units]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
