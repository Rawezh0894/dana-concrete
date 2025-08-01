<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
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

// Get data grouped by drivers
$sql = "SELECT 
    COALESCE(md.name, pd.name) AS driver_name,
    COALESCE(md.id, pd.id) AS driver_id,
    CASE 
        WHEN md.id IS NOT NULL THEN 'میکسەر'
        WHEN pd.id IS NOT NULL THEN 'پۆمپ'
        ELSE 'نامۆ'
    END AS driver_type,
    COUNT(cr.id) AS total_receipts,
    SUM(cr.meter_amount) AS total_meters,
    MIN(cr.created_at) AS first_date,
    MAX(cr.created_at) AS last_date,
    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS customers,
    GROUP_CONCAT(DISTINCT cr.location SEPARATOR ', ') AS locations
FROM concrete_receipts cr
LEFT JOIN customers c ON cr.customer_id = c.id
LEFT JOIN employees md ON cr.mixer_driver_id = md.id
LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
$where_sql
GROUP BY COALESCE(md.id, pd.id), COALESCE(md.name, pd.name)
HAVING driver_name IS NOT NULL
ORDER BY total_meters DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $drivers_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed data for each driver
    $detailed_sql = "SELECT 
        cr.receipt_number,
        c.name AS customer_name,
        cr.location,
        cr.meter_amount,
        COALESCE(md.name, pd.name) AS driver_name,
        CASE 
            WHEN md.id IS NOT NULL THEN 'میکسەر'
            WHEN pd.id IS NOT NULL THEN 'پۆمپ'
            ELSE 'نامۆ'
        END AS driver_type,
        mc.name AS mixer_car_name,
        pc.name AS pump_car_name,
        cr.created_at,
        cr.receiver_name,
        f.name AS formula_name
    FROM concrete_receipts cr
    LEFT JOIN customers c ON cr.customer_id = c.id
    LEFT JOIN employees md ON cr.mixer_driver_id = md.id
    LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
    LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
    LEFT JOIN cars pc ON cr.pump_car_id = pc.id
    LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
    $where_sql
    AND (md.id IS NOT NULL OR pd.id IS NOT NULL)
    ORDER BY COALESCE(md.name, pd.name), cr.created_at DESC";
    
    $stmt = $pdo->prepare($detailed_sql);
    $stmt->execute($params);
    $detailed_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="داهاتی_شۆفێران_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start Excel content
    echo '<table border="1">';
    
    // Summary Sheet - Driver Summary
    echo '<tr><td colspan="8" style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; font-size: 16px;">پوختەی داهاتی شۆفێران</td></tr>';
    echo '<tr>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">ناوی شۆفێر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">جۆری شۆفێر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">کۆی پسوڵەکان</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">کۆی مەتر سێج</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">یەکەم بەروار</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">دوایین بەروار</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">کڕیارەکان</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">شوێنەکان</th>';
    echo '</tr>';
    
    foreach ($drivers_data as $driver) {
        echo '<tr>';
        echo '<td>' . ($driver['driver_name'] ?? '') . '</td>';
        echo '<td>' . ($driver['driver_type'] ?? '') . '</td>';
        echo '<td>' . ($driver['total_receipts'] ?? 0) . '</td>';
        echo '<td>' . ($driver['total_meters'] ?? 0) . ' م³</td>';
        echo '<td>' . ($driver['first_date'] ?? '') . '</td>';
        echo '<td>' . ($driver['last_date'] ?? '') . '</td>';
        echo '<td>' . ($driver['customers'] ?? '') . '</td>';
        echo '<td>' . ($driver['locations'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    // Add empty rows for separation
    echo '<tr><td colspan="8"></td></tr>';
    echo '<tr><td colspan="8"></td></tr>';
    
    // Detailed Sheet - All Receipts
    echo '<tr><td colspan="11" style="background-color: #2196F3; color: white; font-weight: bold; text-align: center; font-size: 16px;">وردەکاری هەموو پسوڵەکان</td></tr>';
    echo '<tr>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">ژمارەی پسوڵە</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">کڕیار</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">شوێن</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">بڕ (م³)</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">شۆفێر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">جۆری شۆفێر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">سەیارەی میکسەر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">سەیارەی پۆمپ</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">بەروار</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">وەرگر</th>';
    echo '<th style="background-color: #f0f0f0; font-weight: bold;">فۆرمۆلا</th>';
    echo '</tr>';
    
    foreach ($detailed_data as $row) {
        echo '<tr>';
        echo '<td>' . ($row['receipt_number'] ?? '') . '</td>';
        echo '<td>' . ($row['customer_name'] ?? '') . '</td>';
        echo '<td>' . ($row['location'] ?? '') . '</td>';
        echo '<td>' . ($row['meter_amount'] ?? '') . '</td>';
        echo '<td>' . ($row['driver_name'] ?? '') . '</td>';
        echo '<td>' . ($row['driver_type'] ?? '') . '</td>';
        echo '<td>' . ($row['mixer_car_name'] ?? '') . '</td>';
        echo '<td>' . ($row['pump_car_name'] ?? '') . '</td>';
        echo '<td>' . ($row['created_at'] ?? '') . '</td>';
        echo '<td>' . ($row['receiver_name'] ?? '') . '</td>';
        echo '<td>' . ($row['formula_name'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
} 