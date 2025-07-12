<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (!hasPermission('add_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purpose = $_POST['purpose'] ?? '';
    $person_id = $_POST['person_id'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $car_id = $_POST['car_id'] ?? null;
    $payment_type = $_POST['payment_type'] ?? '';
    $currency_type = $_POST['currency_type'] ?? '';
    $invoice_number = $_POST['invoice_number'] ?? '';
    $amount_iqd = isset($_POST['amount_iqd']) ? floatval($_POST['amount_iqd']) : 0;
    $amount_usd = isset($_POST['amount_usd']) ? floatval($_POST['amount_usd']) : 0;
    $paid_iqd = isset($_POST['paid_iqd']) ? floatval($_POST['paid_iqd']) : 0;
    $paid_usd = isset($_POST['paid_usd']) ? floatval($_POST['paid_usd']) : 0;
    $exchange_rate = isset($_POST['exchange_rate']) ? floatval($_POST['exchange_rate']) : 150000;
    $remaining_iqd = isset($_POST['remaining_iqd']) ? floatval($_POST['remaining_iqd']) : 0;
    $remaining_usd = isset($_POST['remaining_usd']) ? floatval($_POST['remaining_usd']) : 0;
    $date = $_POST['date'] ?? '';

    if (!$purpose || !$person_id || !$payment_type || !$currency_type || !$date) {
        echo json_encode(['success' => false, 'msg' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // Check for duplicate invoice_number
    if ($invoice_number) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expenses WHERE invoice_number = ?');
        $stmt->execute([$invoice_number]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'msg' => 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!']);
            exit;
        }
    }

    $sql = "INSERT INTO other_expenses (
        purpose, person_id, employee_id, car_id, payment_type, currency_type, invoice_number,
        amount_iqd, amount_usd, paid_iqd, paid_usd, exchange_rate, remaining_iqd, remaining_usd, date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $purpose,
        $person_id,
        $employee_id ?: null,
        $car_id ?: null,
        $payment_type,
        $currency_type,
        $invoice_number,
        $amount_iqd,
        $amount_usd,
        $paid_iqd,
        $paid_usd,
        $exchange_rate,
        $remaining_iqd,
        $remaining_usd,
        $date
    ]);
    if ($ok) {
        // Update person expense if person_id is set
        if ($person_id) {
            $update = $pdo->prepare('UPDATE other_expense_persons SET expense_usd = expense_usd + ?, expense_iqd = expense_iqd + ? WHERE id = ?');
            $update->execute([$remaining_usd, $remaining_iqd, $person_id]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
