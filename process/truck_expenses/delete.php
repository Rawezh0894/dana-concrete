<?php
// c:\xampp\htdocs\dana-concrete\process\truck_expenses\delete.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'msg' => 'ID needed']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM truck_expenses WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Error deleting record']);
        }
    } catch (PDOException $e) {
        error_log('Truck Expense Delete Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
