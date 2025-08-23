<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی کڕینەکان']);
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            l.id as location_id, 
            d.id as driver_id,
            c.name as company_name,
            m.name as material_name,
            b.name as bin_name
        FROM purchases p 
        LEFT JOIN locations l ON p.location = l.name 
        LEFT JOIN drivers d ON p.driver = d.name
        LEFT JOIN company c ON p.company_id = c.id
        LEFT JOIN materials m ON p.material_id = m.id
        LEFT JOIN bins_silos b ON p.bin_id = b.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // Ensure all required fields are present with proper defaults
        $row['location_id'] = $row['location_id'] ?? '';
        $row['driver_id'] = $row['driver_id'] ?? '';
        $row['company_id'] = $row['company_id'] ?? '';
        $row['material_id'] = $row['material_id'] ?? '';
        $row['bin_id'] = $row['bin_id'] ?? '';
        $row['invoice_number'] = $row['invoice_number'] ?? '';
        $row['date'] = $row['date'] ?? '';
        $row['type'] = $row['type'] ?? '';
        $row['kg'] = $row['kg'] ?? 0;
        $row['price_per_kg_iqd'] = $row['price_per_kg_iqd'] ?? 0;
        $row['price_per_kg_usd'] = $row['price_per_kg_usd'] ?? 0;
        $row['exchange_rate'] = $row['exchange_rate'] ?? 0;
        $row['price'] = $row['price'] ?? 0;
        $row['amount_iqd'] = $row['amount_iqd'] ?? 0;
        $row['payment_type'] = $row['payment_type'] ?? '';
        $row['paid_usd'] = $row['paid_usd'] ?? 0;
        $row['paid_iqd'] = $row['paid_iqd'] ?? 0;
        $row['remaining_usd'] = $row['remaining_usd'] ?? 0;
        $row['remaining_iqd'] = $row['remaining_iqd'] ?? 0;
        
        // Log the data being returned for debugging
        error_log('Purchase data for edit modal: ' . print_r($row, true));
    }
    echo json_encode($row);
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$company_id = $_GET['company_id'] ?? null;
$location_id = $_GET['location_id'] ?? null;
$driver_id = $_GET['driver_id'] ?? null;
$material_id = $_GET['material_id'] ?? null;

$where = [];
$params = [];

if ($from) {
    $where[] = "p.date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "p.date <= ?";
    $params[] = $to;
}
if ($company_id) {
    $where[] = "p.company_id = ?";
    $params[] = $company_id;
}
if ($location_id) {
    $where[] = "l.id = ?";
    $params[] = $location_id;
}
if ($driver_id) {
    $where[] = "d.id = ?";
    $params[] = $driver_id;
}
if ($material_id) {
    $where[] = "p.material_id = ?";
    $params[] = $material_id;
}

$sql = "SELECT p.id, c.name AS company_name, l.name AS location_name, d.name AS driver_name, p.invoice_number, m.name AS material_name, p.date, p.payment_type, p.type, p.kg, p.price_per_kg_usd, p.price_per_kg_iqd, p.price, p.amount_iqd, p.exchange_rate, p.paid_usd, p.paid_iqd, p.remaining_usd, p.remaining_iqd, b.name AS bin_name
FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
