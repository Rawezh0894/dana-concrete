<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژmێرەوە!']);
    exit;
}

if (!hasPermission('view_service_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $where = [];
    $params = [];
    
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . trim($_GET['search']) . '%';
        $where[] = '(
            LOWER(sr.receipt_number) LIKE LOWER(:search_receipt) OR
            LOWER(COALESCE(c.name, \'\')) LIKE LOWER(:search_customer) OR
            LOWER(COALESCE(sr.location, \'\')) LIKE LOWER(:search_location) OR
            LOWER(COALESCE(sr.receiver_name, \'\')) LIKE LOWER(:search_receiver) OR
            LOWER(COALESCE(pump_car.name, \'\')) LIKE LOWER(:search_pump_car) OR
            LOWER(COALESCE(mixer_car.name, \'\')) LIKE LOWER(:search_mixer_car)
        )';
        $params[':search_receipt'] = $searchTerm;
        $params[':search_customer'] = $searchTerm;
        $params[':search_location'] = $searchTerm;
        $params[':search_receiver'] = $searchTerm;
        $params[':search_pump_car'] = $searchTerm;
        $params[':search_mixer_car'] = $searchTerm;
    }
    
    if (!empty($_GET['customer_id'])) {
        $where[] = 'sr.customer_id = :customer_id';
        $params[':customer_id'] = $_GET['customer_id'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = 'DATE(sr.created_at) >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = 'DATE(sr.created_at) <= :date_to';
        $params[':date_to'] = $_GET['date_to'];
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
    $offset = ($page - 1) * $pageSize;
    
    // Base Join clause for all queries to ensure aliases work
    $joins = "
        LEFT JOIN customers c ON sr.customer_id = c.id
        LEFT JOIN cars pump_car ON sr.pump_car_id = pump_car.id
        LEFT JOIN employees pump_driver ON sr.pump_driver_id = pump_driver.id
        LEFT JOIN cars mixer_car ON sr.mixer_car_id = mixer_car.id
        LEFT JOIN employees mixer_driver ON sr.mixer_driver_id = mixer_driver.id
    ";

    $count_sql = "SELECT COUNT(*) as total FROM service_receipts sr $joins $whereSql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT sr.*, c.name AS customer_name,
       pump_car.name AS pump_car_name, pump_driver.name AS pump_driver_name,
       mixer_car.name AS mixer_car_name, mixer_driver.name AS mixer_driver_name,
       (sr.meter_amount * COALESCE(sr.price_per_meter, 0)) as total_price,
       ((sr.meter_amount * COALESCE(sr.price_per_meter, 0)) - (COALESCE(sr.paid_usd,0) + (COALESCE(sr.paid_iqd,0) / NULLIF(sr.exchange_rate, 0)))) as remaining_balance
        FROM service_receipts sr
        $joins
        $whereSql
        ORDER BY sr.created_at DESC, sr.id DESC
        LIMIT :limit OFFSET :offset";
        
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary_sql = "SELECT COUNT(*) as total_receipts,
               SUM(sr.meter_amount) as total_meter,
               SUM(sr.meter_amount * COALESCE(sr.price_per_meter, 0)) as total_price
        FROM service_receipts sr
        $joins
        $whereSql";
    $summary_stmt = $pdo->prepare($summary_sql);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    $summary["total_receipts"] = (int) ($summary["total_receipts"] ?? 0);
    $summary["total_meter"] = (float) ($summary["total_meter"] ?? 0);
    $summary["total_price"] = (float) ($summary["total_price"] ?? 0);

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
