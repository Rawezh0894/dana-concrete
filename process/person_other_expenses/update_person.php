<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!hasPermission('update_person_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['person_name'] ?? '');
    $expense_usd = isset($_POST['expense_usd']) ? floatval($_POST['expense_usd']) : 0;
    $expense_iqd = isset($_POST['expense_iqd']) ? floatval($_POST['expense_iqd']) : 0;
    $opening_debt_usd = isset($_POST['opening_debt_usd']) ? floatval($_POST['opening_debt_usd']) : 0;
    $opening_debt_iqd = isset($_POST['opening_debt_iqd']) ? floatval($_POST['opening_debt_iqd']) : 0;
    if (!$id || !$name) {
        echo json_encode(['success' => false, 'msg' => 'زانیاری پێویست نەبوو']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE other_expense_persons SET name = ?, expense_usd = ?, expense_iqd = ?, opening_debt_usd = ?, opening_debt_iqd = ? WHERE id = ?');
    $ok = $stmt->execute([$name, $expense_usd, $expense_iqd, $opening_debt_usd, $opening_debt_iqd, $id]);
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
