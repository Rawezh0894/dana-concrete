<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM inv_categories ORDER BY id DESC");
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
