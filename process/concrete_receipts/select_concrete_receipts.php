<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to view concrete receipts
if (!hasPermission('view_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Gather filters
    $where = [];
    $params = [];
    if (!empty($_GET['customer_id'])) {
        $where[] = 'cr.customer_id = :customer_id';
        $params[':customer_id'] = $_GET['customer_id'];
    }
    if (!empty($_GET['location'])) {
        $where[] = 'cr.location LIKE :location';
        $params[':location'] = '%' . $_GET['location'] . '%';
    }
    if (!empty($_GET['formulas_id'])) {
        $where[] = 'cr.formulas_id = :formulas_id';
        $params[':formulas_id'] = $_GET['formulas_id'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'DATE(cr.created_at) >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'DATE(cr.created_at) <= :date_to';
        $params[':date_to'] = $_GET['date_to'];
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = '
        SELECT cr.*, c.name AS customer_name, f.name AS formula_name,
       pump_car.name AS pump_car_name, pump_driver.name AS pump_driver_name,
       mixer_car.name AS mixer_car_name, mixer_driver.name AS mixer_driver_name

        FROM concrete_receipts cr
        LEFT JOIN customers c ON cr.customer_id = c.id
        LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
        LEFT JOIN cars pump_car ON cr.pump_car_id = pump_car.id
        LEFT JOIN employees pump_driver ON cr.pump_driver_id = pump_driver.id
        LEFT JOIN cars mixer_car ON cr.mixer_car_id = mixer_car.id
        LEFT JOIN employees mixer_driver ON cr.mixer_driver_id = mixer_driver.id
        ' . $whereSql . '
        ORDER BY cr.id DESC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary queries
    $summary_sql = '
        SELECT COUNT(*) as total_receipts,
               SUM(cr.meter_amount) as total_meter,
               COUNT(DISTINCT cr.customer_id) as total_customers
        FROM concrete_receipts cr
        ' . $whereSql . '
    ';
    $summary_stmt = $pdo->prepare($summary_sql);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    // Fallbacks for null
    $summary["total_receipts"] = (int) ($summary["total_receipts"] ?? 0);
    $summary["total_meter"] = (float) ($summary["total_meter"] ?? 0);
    $summary["total_customers"] = (int) ($summary["total_customers"] ?? 0);

    echo json_encode(['success' => true, 'data' => $receipts, 'summary' => $summary]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
