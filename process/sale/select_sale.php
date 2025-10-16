<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 50); // Default limit of 50 records
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($from) {
    $where[] = "s.order_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where[] = "s.order_date <= ?";
    $params[] = $to;
}
if ($customer_id) {
    $where[] = "s.customer_id = ?";
    $params[] = $customer_id;
}

$sql = "SELECT s.*, c.name AS customer_name, f.name AS formula_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id LEFT JOIN concrete_formulas f ON s.formula_id = f.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY s.order_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM sales s";
$count_where = [];
$count_params = [];

if ($from) {
    $count_where[] = "s.order_date >= ?";
    $count_params[] = $from;
}
if ($to) {
    $count_where[] = "s.order_date <= ?";
    $count_params[] = $to;
}
if ($customer_id) {
    $count_where[] = "s.customer_id = ?";
    $count_params[] = $customer_id;
}

if ($count_where) {
    $count_sql .= " WHERE " . implode(" AND ", $count_where);
}

try {
    // Debug: Log the queries
    error_log("Count SQL: " . $count_sql);
    error_log("Count Params: " . json_encode($count_params));
    error_log("Data SQL: " . $sql);
    error_log("Data Params: " . json_encode($params));
    
    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get sales data
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Total records: " . $total_records);
    error_log("Sales count: " . count($sales));
    
    echo json_encode([
        'success' => true, 
        'data' => $sales,
        'pagination' => [
            'current_page' => $page,
            'total_records' => intval($total_records),
            'total_pages' => ceil($total_records / $limit),
            'limit' => $limit
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
