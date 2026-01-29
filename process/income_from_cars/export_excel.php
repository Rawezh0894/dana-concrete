<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_income_from_cars')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

// Get filter parameters
$mixer_car_id = $_POST['mixer_car_id'] ?? '';
$mixer_driver_id = $_POST['mixer_driver_id'] ?? '';
$pump_car_id = $_POST['pump_car_id'] ?? '';
$pump_driver_id = $_POST['pump_driver_id'] ?? '';
$customer_id = $_POST['customer_id'] ?? '';
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';

// Build WHERE clause
$where = [];
$params = [];

if ($mixer_car_id) {
    $where[] = "cr.mixer_car_id = ?";
    $params[] = $mixer_car_id;
}

if ($mixer_driver_id) {
    $where[] = "cr.mixer_driver_id = ?";
    $params[] = $mixer_driver_id;
}

if ($pump_car_id) {
    $where[] = "cr.pump_car_id = ?";
    $params[] = $pump_car_id;
}

if ($pump_driver_id) {
    $where[] = "cr.pump_driver_id = ?";
    $params[] = $pump_driver_id;
}

if ($customer_id) {
    $where[] = "cr.customer_id = ?";
    $params[] = $customer_id;
}

if ($from_date) {
    $where[] = "DATE(cr.created_at) >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where[] = "DATE(cr.created_at) <= ?";
    $params[] = $to_date;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get data
$sql = "SELECT 
    cr.receipt_number,
    c.name AS customer_name,
    cr.location,
    cr.meter_amount,
    mc.name AS mixer_car_name,
    md.name AS mixer_driver_name,
    pc.name AS pump_car_name,
    pd.name AS pump_driver_name,
    cr.created_at,
    cr.receiver_name,
    f.name AS formula_name
FROM concrete_receipts cr
LEFT JOIN customers c ON cr.customer_id = c.id
LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
LEFT JOIN employees md ON cr.mixer_driver_id = md.id
LEFT JOIN cars pc ON cr.pump_car_id = pc.id
LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
$where_sql
ORDER BY cr.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="داهاتی_سەیارەکان_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start Excel content
    echo '<table border="1">';
    
    // Header row
    echo '<tr>';
    echo '<th>ژمارەی پسوڵە</th>';
    echo '<th>کڕیار</th>';
    echo '<th>شوێن</th>';
    echo '<th>بڕ (م³)</th>';
    echo '<th>سەیارەی میکسەر</th>';
    echo '<th>شۆفێری میکسەر</th>';
    echo '<th>سەیارەی پۆمپ</th>';
    echo '<th>شۆفێری پۆمپ</th>';
    echo '<th>بەروار</th>';
    echo '<th>وەرگر</th>';
    echo '<th>فۆرمۆلا</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . ($row['receipt_number'] ?? '') . '</td>';
        echo '<td>' . ($row['customer_name'] ?? '') . '</td>';
        echo '<td>' . ($row['location'] ?? '') . '</td>';
        echo '<td>' . ($row['meter_amount'] ?? '') . '</td>';
        echo '<td>' . ($row['mixer_car_name'] ?? '') . '</td>';
        echo '<td>' . ($row['mixer_driver_name'] ?? '') . '</td>';
        echo '<td>' . ($row['pump_car_name'] ?? '') . '</td>';
        echo '<td>' . ($row['pump_driver_name'] ?? '') . '</td>';
        echo '<td>' . ($row['created_at'] ?? '') . '</td>';
        echo '<td>' . ($row['receiver_name'] ?? '') . '</td>';
        echo '<td>' . ($row['formula_name'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
} 