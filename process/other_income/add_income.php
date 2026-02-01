<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php'; // Checks session start
    header('Content-Type: application/json');

    // if (!hasPermission('add_other_income')) {
    //     echo json_encode(['success' => false, 'msg' => 'Permission denied']);
    //     exit;
    // }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = $_POST['description'] ?? '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $currency = $_POST['currency'] ?? 'دینار';
        $date = $_POST['date'] ?? date('Y-m-d');
        $created_by = $_SESSION['user_id'] ?? null;

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

        $sql = "INSERT INTO other_income (description, amount_iqd, amount_usd, currency, date, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $description,
            $amount_iqd,
            $amount_usd,
            $currency,
            $date,
            $created_by
        ]);

        echo json_encode(['success' => true, 'msg' => 'بە سەرکەوتوویی زیادکرا']);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Invalid Request']);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()]);
}
