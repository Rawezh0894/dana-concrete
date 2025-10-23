<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_income_from_cars')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Get filter parameters
$mixer_car_id = $_GET['mixer_car_id'] ?? '';
$mixer_driver_id = $_GET['mixer_driver_id'] ?? '';
$pump_car_id = $_GET['pump_car_id'] ?? '';
$pump_driver_id = $_GET['pump_driver_id'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

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

// Get pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(100, max(10, intval($_GET['limit'] ?? 50))); // Limit between 10-100
$offset = ($page - 1) * $limit;

// Get detailed data with pagination
$sql = "SELECT 
    cr.*,
    c.name AS customer_name,
    mc.name AS mixer_car_name,
    md.name AS mixer_driver_name,
    pc.name AS pump_car_name,
    pd.name AS pump_driver_name,
    f.name AS formula_name
FROM concrete_receipts cr
LEFT JOIN customers c ON cr.customer_id = c.id
LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
LEFT JOIN employees md ON cr.mixer_driver_id = md.id
LEFT JOIN cars pc ON cr.pump_car_id = pc.id
LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
$where_sql
ORDER BY cr.created_at DESC
LIMIT $limit OFFSET $offset";

try {
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM concrete_receipts cr $where_sql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated data
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics efficiently with separate queries
    $summarySql = "SELECT 
        COUNT(DISTINCT CASE WHEN cr.mixer_car_id IS NOT NULL THEN cr.mixer_car_id END) + 
        COUNT(DISTINCT CASE WHEN cr.pump_car_id IS NOT NULL THEN cr.pump_car_id END) as total_cars,
        COUNT(DISTINCT CASE WHEN cr.mixer_driver_id IS NOT NULL THEN cr.mixer_driver_id END) + 
        COUNT(DISTINCT CASE WHEN cr.pump_driver_id IS NOT NULL THEN cr.pump_driver_id END) as total_drivers,
        COALESCE(SUM(cr.meter_amount), 0) as total_meters,
        COUNT(*) as total_receipts
        FROM concrete_receipts cr $where_sql";
    
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get chart data efficiently with SQL aggregation
    $carsChartSql = "SELECT 
        COALESCE(mc.id, pc.id) as car_id,
        COALESCE(mc.name, pc.name) as car_name,
        SUM(cr.meter_amount) as total_meters
        FROM concrete_receipts cr
        LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
        LEFT JOIN cars pc ON cr.pump_car_id = pc.id
        $where_sql
        AND (cr.mixer_car_id IS NOT NULL OR cr.pump_car_id IS NOT NULL)
        GROUP BY COALESCE(mc.id, pc.id), COALESCE(mc.name, pc.name)
        ORDER BY total_meters DESC
        LIMIT 10";
    
    $driversChartSql = "SELECT 
        COALESCE(md.id, pd.id) as driver_id,
        COALESCE(md.name, pd.name) as driver_name,
        SUM(cr.meter_amount) as total_meters
        FROM concrete_receipts cr
        LEFT JOIN employees md ON cr.mixer_driver_id = md.id
        LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
        $where_sql
        AND (cr.mixer_driver_id IS NOT NULL OR cr.pump_driver_id IS NOT NULL)
        GROUP BY COALESCE(md.id, pd.id), COALESCE(md.name, pd.name)
        ORDER BY total_meters DESC
        LIMIT 10";
    
    $carsChartStmt = $pdo->prepare($carsChartSql);
    $carsChartStmt->execute($params);
    $carsChartData = $carsChartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $driversChartStmt = $pdo->prepare($driversChartSql);
    $driversChartStmt->execute($params);
    $driversChartData = $driversChartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format chart data
    $carsData = [];
    $driversData = [];
    
    foreach ($carsChartData as $row) {
        $carsData[$row['car_id']] = [
            'name' => $row['car_name'],
            'meters' => floatval($row['total_meters'])
        ];
    }
    
    foreach ($driversChartData as $row) {
        $driversData[$row['driver_id']] = [
            'name' => $row['driver_name'],
            'meters' => floatval($row['total_meters'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => intval($totalRecords),
            'total_pages' => ceil($totalRecords / $limit)
        ],
        'summary' => [
            'totalCars' => intval($summary['total_cars']),
            'totalDrivers' => intval($summary['total_drivers']),
            'totalMeters' => round(floatval($summary['total_meters']), 2),
            'totalReceipts' => intval($summary['total_receipts'])
        ],
        'charts' => [
            'cars' => $carsData,
            'drivers' => $driversData
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
