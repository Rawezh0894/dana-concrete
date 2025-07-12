<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['person_name'] ?? '');
    $expense_usd = isset($_POST['expense_usd']) ? floatval($_POST['expense_usd']) : 0;
    $expense_iqd = isset($_POST['expense_iqd']) ? floatval($_POST['expense_iqd']) : 0;
    if (!$name) {
        echo json_encode(['success' => false, 'msg' => 'ناوی کەس پێویستە']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO other_expense_persons (name, expense_usd, expense_iqd) VALUES (?, ?, ?)');
    $ok = $stmt->execute([$name, $expense_usd, $expense_iqd]);
    if ($ok) {
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => $name, 'expense_usd' => $expense_usd, 'expense_iqd' => $expense_iqd]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']); 