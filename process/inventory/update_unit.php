<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name_ku = $_POST['name_ku'] ?? '';

    if (empty($id) || empty($name_ku)) {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکەرەوە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE inv_units SET name_ku = ? WHERE id = ?");
        $stmt->execute([$name_ku, $id]);
        echo json_encode(['success' => true, 'msg' => 'یەکە بە سەرکەوتوویی نوێکرایەوە']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
?>
