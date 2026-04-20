<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'msg' => 'ID یەکە دیاری نەکراوە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM inv_units WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'msg' => 'یەکە بە سەرکەوتوویی سڕایەوە']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
?>
