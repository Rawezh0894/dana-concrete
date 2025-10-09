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
            l.name as location_name,
            d.id as driver_id,
            d.name as driver_name,
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
        $row['location_name'] = $row['location_name'] ?? '';
        $row['driver_id'] = $row['driver_id'] ?? '';
        $row['driver_name'] = $row['driver_name'] ?? '';
        $row['company_id'] = $row['company_id'] ?? '';
        $row['company_name'] = $row['company_name'] ?? '';
        $row['material_id'] = $row['material_id'] ?? '';
        $row['material_name'] = $row['material_name'] ?? '';
        $row['bin_id'] = $row['bin_id'] ?? '';
        $row['bin_name'] = $row['bin_name'] ?? '';
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
$search = $_GET['search'] ?? null;

// Column filters (Excel-style filters)
$column_filters = isset($_GET['column_filters']) ? json_decode($_GET['column_filters'], true) : null;

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(10, min(500, intval($_GET['limit']))) : 100;
$offset = ($page - 1) * $limit;

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
if ($search) {
    $searchTerm = "%$search%";
    $where[] = "(c.name LIKE ? OR l.name LIKE ? OR d.name LIKE ? OR p.invoice_number LIKE ? OR m.name LIKE ? OR b.name LIKE ? OR p.date LIKE ? OR p.payment_type LIKE ? OR p.type LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Apply column filters (Excel-style filters)
if ($column_filters && is_array($column_filters)) {
    $columnMap = [
        'company_name' => 'c.name',
        'location_name' => 'l.name',
        'driver_name' => 'd.name',
        'material_name' => 'm.name',
        'bin_name' => 'b.name',
        'invoice_number' => 'p.invoice_number',
        'date' => 'p.date',
        'payment_type' => 'p.payment_type',
        'type' => 'p.type'
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
$count_sql = "SELECT COUNT(*) as total FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id";
if ($where) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Get paginated data
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
$sql .= " ORDER BY p.date DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination info
$total_pages = ceil($total_records / $limit);

echo json_encode([
    'success' => true,
    'data' => $data,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'per_page' => $limit,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ]
]);
