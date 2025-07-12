<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!hasPermission('add_person_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['person_name'] ?? '');
    $expense_usd = isset($_POST['expense_usd']) ? floatval($_POST['expense_usd']) : 0;
    $expense_iqd = isset($_POST['expense_iqd']) ? floatval($_POST['expense_iqd']) : 0;
    $opening_debt_usd = isset($_POST['opening_debt_usd']) ? floatval($_POST['opening_debt_usd']) : 0;
    $opening_debt_iqd = isset($_POST['opening_debt_iqd']) ? floatval($_POST['opening_debt_iqd']) : 0;
    if (!$name) {
        echo json_encode(['success' => false, 'msg' => 'ناوی کەس پێویستە']);
        exit;
    }
    // Prevent duplicate names
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM other_expense_persons WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'msg' => 'ئەم ناوە پێشتر تۆمارکراوە!']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO other_expense_persons (name, expense_usd, expense_iqd, opening_debt_usd, opening_debt_iqd) VALUES (?, ?, ?, ?, ?)');
    $ok = $stmt->execute([$name, $expense_usd, $expense_iqd, $opening_debt_usd, $opening_debt_iqd]);
    if ($ok) {
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => $name, 'expense_usd' => $expense_usd, 'expense_iqd' => $expense_iqd]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە زیادکردن']);
    }
    exit;
}
echo json_encode(['success' => false, 'msg' => 'POST تەنها ڕێگەپێدراوە']);
