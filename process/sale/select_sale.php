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
if ($where) {
    $count_sql .= " WHERE " . implode(" AND ", array_slice($where, 0, -2)); // Remove customer_id condition for count
}
$count_params = array_slice($params, 0, -2); // Remove limit and offset params

try {
    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get sales data
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
