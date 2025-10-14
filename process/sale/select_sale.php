<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('view_sale')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

// Check if requesting single sale for edit
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.name AS customer_name,
            f.name AS formula_name
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // Ensure all required fields are present with proper defaults
        $row['customer_id'] = $row['customer_id'] ?? '';
        $row['customer_name'] = $row['customer_name'] ?? '';
        $row['recipient'] = $row['recipient'] ?? '';
        $row['location'] = $row['location'] ?? '';
        $row['invoice_number'] = $row['invoice_number'] ?? '';
        $row['formula_id'] = $row['formula_id'] ?? '';
        $row['formula_name'] = $row['formula_name'] ?? '';
        $row['order_date'] = $row['order_date'] ?? '';
        $row['quantity'] = $row['quantity'] ?? 0;
        $row['price_per_unit'] = $row['price_per_unit'] ?? 0;
        $row['total_price'] = $row['total_price'] ?? 0;
        $row['payment_type'] = $row['payment_type'] ?? '';
        $row['amount_paid_iq'] = $row['amount_paid_iq'] ?? 0;
        $row['amount_paid_usd'] = $row['amount_paid_usd'] ?? 0;
        $row['remaining_amount'] = $row['remaining_amount'] ?? 0;
        $row['dolar_rate'] = $row['dolar_rate'] ?? 0;
        $row['discount'] = $row['discount'] ?? 0;
        $row['notes'] = $row['notes'] ?? '';
        
        error_log('Sale data for edit modal: ' . print_r($row, true));
    }
    echo json_encode($row);
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;
$search = $_GET['search'] ?? null;

// Column filters (Excel-style filters)
$column_filters = isset($_GET['column_filters']) ? json_decode($_GET['column_filters'], true) : null;

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(10, min(500, intval($_GET['limit']))) : 10;
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
if ($search) {
    $searchTerm = "%$search%";
    $where[] = "(c.name LIKE ? OR s.recipient LIKE ? OR s.location LIKE ? OR s.invoice_number LIKE ? OR f.name LIKE ? OR s.order_date LIKE ? OR s.payment_type LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Apply column filters (Excel-style filters)
if ($column_filters && is_array($column_filters)) {
    $columnMap = [
        'customer_name' => 'c.name',
        'recipient' => 's.recipient',
        'location' => 's.location',
        'invoice_number' => 's.invoice_number',
        'formula_name' => 'f.name',
        'order_date' => 's.order_date',
        'payment_type' => 's.payment_type'
    ];
    
    foreach ($column_filters as $column => $values) {
        if (isset($columnMap[$column]) && is_array($values) && count($values) > 0) {
            $dbColumn = $columnMap[$column];
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $where[] = "$dbColumn IN ($placeholders)";
            $params = array_merge($params, $values);
        }
    }
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM sales s
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN concrete_formulas f ON s.formula_id = f.id";
if ($where) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Get paginated data
$sql = "SELECT s.*, c.name AS customer_name, f.name AS formula_name 
FROM sales s 
LEFT JOIN customers c ON s.customer_id = c.id 
LEFT JOIN concrete_formulas f ON s.formula_id = f.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY s.order_date DESC LIMIT ? OFFSET ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $sales,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
