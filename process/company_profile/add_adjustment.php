<?php
// c:\xampp\htdocs\dana-concrete\process\company_profile\add_adjustment.php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = (int)($_POST['company_id'] ?? 0);
    $amount_usd = (float)($_POST['amount_usd'] ?? 0);
    $amount_iqd = (float)($_POST['amount_iqd'] ?? 0);
    $date = $_POST['date'] ?? null;
    $note = trim($_POST['note'] ?? '');

    if ($company_id === 0 || !$date || empty($note)) {
        echo json_encode(['success' => false, 'msg' => 'زانیارییەکان تەواو نین']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO company_adjustments (company_id, amount_usd, amount_iqd, date, note) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$company_id, $amount_usd, $amount_iqd, $date, $note])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Error saving adjustment']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
