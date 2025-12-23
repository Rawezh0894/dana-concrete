<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_person_other_expenses_profile')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;
if (isset($_GET['stats'])) {
    // Get opening debts
    $sql = "SELECT COALESCE(opening_debt_usd,0) as opening_debt_usd, COALESCE(opening_debt_iqd,0) as opening_debt_iqd FROM other_expense_persons WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$person_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get sum of remaining_usd/iqd from other_expenses
    $sql2 = "SELECT COALESCE(SUM(remaining_usd),0) as rem_usd, COALESCE(SUM(remaining_iqd),0) as rem_iqd FROM other_expenses WHERE person_id = ? AND payment_type = 'قەرز'";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$person_id]);
    $rem = $stmt2->fetch(PDO::FETCH_ASSOC);
    $row['total_usd'] = $rem['rem_usd'] + $row['opening_debt_usd'];
    $row['total_iqd'] = $rem['rem_iqd'] + $row['opening_debt_iqd'];
    // Get count from other_expenses
    $sql3 = "SELECT COUNT(*) as count FROM other_expenses WHERE person_id = ?";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([$person_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $row['count'] = $row3['count'];
    echo json_encode(['stats' => $row]);
    exit;
}
$sql = "SELECT 
            oe.id, 
            oe.person_id, 
            oe.employee_id, 
            oe.car_id, 
            oe.purpose, 
            oe.date,
            COALESCE(oe.amount_usd, 0) as amount_usd, 
            COALESCE(oe.amount_iqd, 0) as amount_iqd,
            COALESCE(oe.paid_usd, 0) as initial_paid_usd, 
            COALESCE(oe.paid_iqd, 0) as initial_paid_iqd,
            COALESCE(oe.remaining_usd, 0) as remaining_usd, 
            COALESCE(oe.remaining_iqd, 0) as remaining_iqd,
            oe.payment_type, 
            oe.currency_type, 
            oe.invoice_number, 
            COALESCE(oe.exchange_rate, 0) as exchange_rate,
            oe.expense_type,
            e.name AS employee_name, 
            c.name AS car_name,
            (COALESCE(oe.amount_usd, 0) - COALESCE(oe.remaining_usd, 0)) as current_paid_usd,
            (COALESCE(oe.amount_iqd, 0) - COALESCE(oe.remaining_iqd, 0)) as current_paid_iqd
        FROM other_expenses oe
        LEFT JOIN employees e ON oe.employee_id = e.id
        LEFT JOIN cars c ON oe.car_id = c.id
        WHERE oe.person_id = ?
        ORDER BY oe.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$person_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure numeric values are actually numbers in JSON
foreach ($data as &$row) {
    $row['amount_usd'] = (float)$row['amount_usd'];
    $row['amount_iqd'] = (float)$row['amount_iqd'];
    $row['remaining_usd'] = (float)$row['remaining_usd'];
    $row['remaining_iqd'] = (float)$row['remaining_iqd'];
    $row['current_paid_usd'] = (float)$row['current_paid_usd'];
    $row['current_paid_iqd'] = (float)$row['current_paid_iqd'];
    $row['exchange_rate'] = (float)$row['exchange_rate'];
}

echo json_encode($data);
