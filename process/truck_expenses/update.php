<?php
// c:\xampp\htdocs\dana-concrete\process\truck_expenses\update.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $truck_id = $_POST['truck_id'] ?? null;
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $date = $_POST['date'] ?? null;
    $amount_usd = (float)($_POST['amount_usd'] ?? 0);
    $amount_iqd = (float)($_POST['amount_iqd'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($id === 0 || !$truck_id || !$date || empty($note)) {
        echo json_encode(['success' => false, 'msg' => 'زانیارییەکان تەواو نین']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE truck_expenses SET truck_id = ?, invoice_number = ?, amount_usd = ?, amount_iqd = ?, date = ?, note = ? WHERE id = ?");
        if ($stmt->execute([$truck_id, $invoice_number, $amount_usd, $amount_iqd, $date, $note, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Update failed or no changes made']);
        }
    } catch (PDOException $e) {
        error_log('Truck Expense Update Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
