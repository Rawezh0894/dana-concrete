<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $description = $_POST['description'] ?? '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $currency = $_POST['currency'] ?? 'دینار';
        $date = $_POST['date'] ?? date('Y-m-d');

        if (!$id) {
            echo json_encode(['success' => false, 'msg' => 'ID provided is invalid']);
            exit;
        }
        if (empty($description)) {
            echo json_encode(['success' => false, 'msg' => 'وەسف پڕ بکەوە']);
            exit;
        }
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'msg' => 'بڕی پارە دەبێت زیاتر بێت لە سفر']);
            exit;
        }

        $amount_iqd = 0;
        $amount_usd = 0;

        if ($currency === 'دینار') {
            $amount_iqd = $amount;
        } else {
            $amount_usd = $amount;
        }

        $sql = "UPDATE other_income SET description = ?, amount_iqd = ?, amount_usd = ?, currency = ?, date = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $description,
            $amount_iqd,
            $amount_usd,
            $currency,
            $date,
            $id
        ]);

        echo json_encode(['success' => true, 'msg' => 'بە سەرکەوتوویی نوێکرایەوە']);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Invalid Request']);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}
