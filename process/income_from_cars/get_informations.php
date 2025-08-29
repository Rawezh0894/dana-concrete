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

// Get detailed data
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
ORDER BY cr.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $totalMeters = 0;
    $totalReceipts = count($data);
    $uniqueCars = [];
    $uniqueDrivers = [];
    
    foreach ($data as $row) {
        $totalMeters += floatval($row['meter_amount']);
        
        if ($row['mixer_car_id']) {
            $uniqueCars[$row['mixer_car_id']] = $row['mixer_car_name'];
        }
        if ($row['pump_car_id']) {
            $uniqueCars[$row['pump_car_id']] = $row['pump_car_name'];
        }
        if ($row['mixer_driver_id']) {
            $uniqueDrivers[$row['mixer_driver_id']] = $row['mixer_driver_name'];
        }
        if ($row['pump_driver_id']) {
            $uniqueDrivers[$row['pump_driver_id']] = $row['pump_driver_name'];
        }
    }
    
    // Prepare chart data
    $carsData = [];
    $driversData = [];
    
    // Group by cars
    $carMeters = [];
    foreach ($data as $row) {
        if ($row['mixer_car_id']) {
            $carId = $row['mixer_car_id'];
            $carName = $row['mixer_car_name'];
            if (!isset($carMeters[$carId])) {
                $carMeters[$carId] = ['name' => $carName, 'meters' => 0];
            }
            $carMeters[$carId]['meters'] += floatval($row['meter_amount']);
        }
        if ($row['pump_car_id']) {
            $carId = $row['pump_car_id'];
            $carName = $row['pump_car_name'];
            if (!isset($carMeters[$carId])) {
                $carMeters[$carId] = ['name' => $carName, 'meters' => 0];
            }
            $carMeters[$carId]['meters'] += floatval($row['meter_amount']);
        }
    }
    
    // Group by drivers
    $driverMeters = [];
    foreach ($data as $row) {
        if ($row['mixer_driver_id']) {
            $driverId = $row['mixer_driver_id'];
            $driverName = $row['mixer_driver_name'];
            if (!isset($driverMeters[$driverId])) {
                $driverMeters[$driverId] = ['name' => $driverName, 'meters' => 0];
            }
            $driverMeters[$driverId]['meters'] += floatval($row['meter_amount']);
        }
        if ($row['pump_driver_id']) {
            $driverId = $row['pump_driver_id'];
            $driverName = $row['pump_driver_name'];
            if (!isset($driverMeters[$driverId])) {
                $driverMeters[$driverId] = ['name' => $driverName, 'meters' => 0];
            }
            $driverMeters[$driverId]['meters'] += floatval($row['meter_amount']);
        }
    }
    
    // Sort by meters (descending) and take top 10
    uasort($carMeters, function($a, $b) {
        return $b['meters'] <=> $a['meters'];
    });
    uasort($driverMeters, function($a, $b) {
        return $b['meters'] <=> $a['meters'];
    });
    
    $carsData = array_slice($carMeters, 0, 10, true);
    $driversData = array_slice($driverMeters, 0, 10, true);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => [
            'totalCars' => count($uniqueCars),
            'totalDrivers' => count($uniqueDrivers),
            'totalMeters' => round($totalMeters, 2),
            'totalReceipts' => $totalReceipts
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
