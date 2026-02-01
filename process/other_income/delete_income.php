<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'msg' => 'ID provides is invalid']);
            exit;
        }

        $sql = "DELETE FROM other_income WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'msg' => 'بە سەرکەوتوویی سڕایەوە']);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Invalid Request']);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}
