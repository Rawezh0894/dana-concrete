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
    
    // Server-side global search (like SAP/Odoo/Oracle)
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $where[] = '(
            cr.receipt_number LIKE :search_receipt OR
            c.name LIKE :search_customer OR
            cr.location LIKE :search_location OR
            cr.receiver_name LIKE :search_receiver OR
            f.name LIKE :search_formula OR
            pump_car.name LIKE :search_pump_car OR
            pump_driver.name LIKE :search_pump_driver OR
            mixer_car.name LIKE :search_mixer_car OR
            mixer_driver.name LIKE :search_mixer_driver
        )';
        $params[':search_receipt'] = $searchTerm;
        $params[':search_customer'] = $searchTerm;
        $params[':search_location'] = $searchTerm;
        $params[':search_receiver'] = $searchTerm;
        $params[':search_formula'] = $searchTerm;
        $params[':search_pump_car'] = $searchTerm;
        $params[':search_pump_driver'] = $searchTerm;
        $params[':search_mixer_car'] = $searchTerm;
        $params[':search_mixer_driver'] = $searchTerm;
    }
    
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
    
    // Pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
    $offset = ($page - 1) * $pageSize;
    
    // Get total count for pagination
    $count_sql = '
        SELECT COUNT(*) as total
        FROM concrete_receipts cr
        LEFT JOIN customers c ON cr.customer_id = c.id
        LEFT JOIN concrete_formulas f ON cr.formulas_id = f.id
        LEFT JOIN cars pump_car ON cr.pump_car_id = pump_car.id
        LEFT JOIN employees pump_driver ON cr.pump_driver_id = pump_driver.id
        LEFT JOIN cars mixer_car ON cr.mixer_car_id = mixer_car.id
        LEFT JOIN employees mixer_driver ON cr.mixer_driver_id = mixer_driver.id
        ' . $whereSql . '
    ';
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Main query with pagination
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
        ORDER BY cr.created_at DESC, cr.id DESC
        LIMIT :limit OFFSET :offset
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Detect duplicate receipt numbers
    $receipt_numbers = array_column($receipts, 'receipt_number');
    $duplicate_numbers = array_unique(array_diff_assoc($receipt_numbers, array_unique($receipt_numbers)));
    
    // Mark duplicates in the receipts array
    foreach ($receipts as &$receipt) {
        $receipt['is_duplicate'] = in_array($receipt['receipt_number'], $duplicate_numbers);
    }

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

    echo json_encode([
        'success' => true, 
        'data' => $receipts, 
        'summary' => $summary,
        'pagination' => [
            'total' => (int)$total_count,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total_count / $pageSize)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
