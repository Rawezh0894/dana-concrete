<?php
// c:\xampp\htdocs\dana-concrete\process\truck_expenses\add.php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $truck_id = $_POST['truck_id'] ?? null;
    $expense_type = $_POST['expense_type'] ?? '';
    $date = $_POST['date'] ?? null;
    $amount_usd = (float)($_POST['amount_usd'] ?? 0);
    $amount_iqd = (float)($_POST['amount_iqd'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if (!$truck_id || !$expense_type || !$date) {
        echo json_encode(['success' => false, 'msg' => 'هەموو مەرجەکان پێویستە']);
        exit;
    }

    if ($amount_usd <= 0 && $amount_iqd <= 0) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارە پێویستە']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO truck_expenses (truck_id, expense_type, amount_usd, amount_iqd, date, note) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$truck_id, $expense_type, $amount_usd, $amount_iqd, $date, $note])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Error inserting record']);
        }
    } catch (PDOException $e) {
        error_log('Truck Expense Add Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
