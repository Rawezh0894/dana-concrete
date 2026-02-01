<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID پێویستە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM asset_depreciation WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'سڕایەوە بە سەرکەوتوویی']);
        } else {
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ڕێگەی ناردن هەڵەیە']);
}
?>
