<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_notes')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Get filter parameters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$is_read = $_GET['is_read'] ?? '';

// Build WHERE clause
$where = [];
$params = [];

if ($from) {
    $where[] = "n.date >= ?";
    $params[] = $from;
}

if ($to) {
    $where[] = "n.date <= ?";
    $params[] = $to;
}

if ($customer_id) {
    $where[] = "n.customer_id = ?";
    $params[] = $customer_id;
}

if ($is_read !== '') {
    $where[] = "n.is_read = ?";
    $params[] = $is_read;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get all notes with related data
$sql = "SELECT 
            n.*,
            c.name AS customer_name,
            f.name AS formula_name,
            mc.name AS mixer_car_name,
            md.name AS mixer_driver_name,
            pc.name AS pump_car_name,
            pd.name AS pump_driver_name
        FROM notes n
        LEFT JOIN customers c ON n.customer_id = c.id
        LEFT JOIN concrete_formulas f ON n.formula_id = f.id
        LEFT JOIN cars mc ON n.mixer_car_id = mc.id
        LEFT JOIN employees md ON n.mixer_driver_id = md.id
        LEFT JOIN cars pc ON n.pump_car_id = pc.id
        LEFT JOIN employees pd ON n.pump_driver_id = pd.id
        $where_sql
        ORDER BY n.date DESC, n.time DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $notes,
        'total' => count($notes)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
