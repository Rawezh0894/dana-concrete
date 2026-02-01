<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $date = $_POST['date'] ?? '';
    $amount_iqd = $_POST['amount_iqd'] ?? 0;
    $amount_usd = $_POST['amount_usd'] ?? 0;
    $note = $_POST['note'] ?? '';

    if (empty($id) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'پێویستە هەموو فیڵدەکان پڕبکرێنەوە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE asset_depreciation SET depreciation_date = ?, amount_iqd = ?, amount_usd = ?, note = ? WHERE id = ?");
        $result = $stmt->execute([$date, $amount_iqd, $amount_usd, $note, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'داخوران بە سەرکەوتوویی نوێکرایەوە']);
        } else {
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوە']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ڕێگەی ناردن هەڵەیە']);
}
?>
