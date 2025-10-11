<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT id, name FROM customers ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($customers);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}

