<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $amount_iqd = $_POST['amount_iqd'] ?? 0;
    $amount_usd = $_POST['amount_usd'] ?? 0;
    $note = $_POST['note'] ?? '';

    if (empty($date)) {
        echo json_encode(['success' => false, 'message' => 'بەروار پێویستە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO asset_depreciation (depreciation_date, amount_iqd, amount_usd, note) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$date, $amount_iqd, $amount_usd, $note]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'داخوران بە سەرکەوتوویی تۆمارکرا']);
        } else {
            echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە کاتی تۆمارکردن']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ڕێگەی ناردن هەڵەیە']);
}
?>
