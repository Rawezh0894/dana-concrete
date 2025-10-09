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
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە']);
    exit;
}

$column = $_GET['column'] ?? null;

if (!$column) {
    echo json_encode(['success' => false, 'msg' => 'Column not specified']);
    exit;
}

// Map frontend column names to database columns
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

if (!isset($columnMap[$column])) {
    echo json_encode(['success' => false, 'msg' => 'Invalid column']);
    exit;
}

$dbColumn = $columnMap[$column];

$sql = "SELECT DISTINCT $dbColumn as value 
FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id
WHERE $dbColumn IS NOT NULL AND $dbColumn != ''
ORDER BY $dbColumn ASC";

$stmt = $pdo->query($sql);
$values = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'values' => $values
]);

