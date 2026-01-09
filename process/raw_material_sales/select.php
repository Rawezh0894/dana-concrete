<?php
/**
 * Raw Material Sales - Select/List API
 * Following ERP standards for data retrieval
 */
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_raw_material_sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    // Build query with filters
    $where = ['rms.is_deleted = 0'];
    $params = [];
    
    // Date filters
    if (!empty($_GET['from'])) {
        $where[] = 'rms.sale_date >= ?';
        $params[] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $where[] = 'rms.sale_date <= ?';
        $params[] = $_GET['to'];
    }
    
    // Buyer type filter
    if (!empty($_GET['buyer_type'])) {
        $where[] = 'rms.buyer_type = ?';
        $params[] = $_GET['buyer_type'];
    }
    
    // Customer filter
    if (!empty($_GET['customer_id'])) {
        $where[] = 'rms.customer_id = ?';
        $params[] = $_GET['customer_id'];
    }
    
    // Company filter
    if (!empty($_GET['company_id'])) {
        $where[] = 'rms.company_id = ?';
        $params[] = $_GET['company_id'];
    }
    
    // Material type filter
    if (!empty($_GET['material_type'])) {
        $where[] = 'rms.material_type = ?';
        $params[] = $_GET['material_type'];
    }
    
    // Bin filter
    if (!empty($_GET['bin_id'])) {
        $where[] = 'rms.bin_id = ?';
        $params[] = $_GET['bin_id'];
    }
    
    // Payment type filter
    if (!empty($_GET['payment_type'])) {
        $where[] = 'rms.payment_type = ?';
        $params[] = $_GET['payment_type'];
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT 
            rms.*,
            bs.name as bin_name,
            c.name as customer_name,
            co.name as company_name,
            u.username as created_by_name,
            CASE 
                WHEN rms.buyer_type = 'کڕیار' THEN c.name
                WHEN rms.buyer_type = 'کۆمپانیا' THEN co.name
                ELSE rms.external_buyer_name
            END as buyer_name
        FROM raw_material_sales rms
        LEFT JOIN bins_silos bs ON rms.bin_id = bs.id
        LEFT JOIN customers c ON rms.customer_id = c.id
        LEFT JOIN company co ON rms.company_id = co.id
        LEFT JOIN users u ON rms.created_by = u.id
        $whereClause
        ORDER BY rms.sale_date DESC, rms.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary statistics
    $summary = [
        'total_sales' => count($sales),
        'total_quantity_kg' => 0,
        'total_revenue_iqd' => 0,
        'total_revenue_usd' => 0,
        'total_profit_iqd' => 0,
        'total_profit_usd' => 0,
        'total_remaining_iqd' => 0,
        'total_remaining_usd' => 0
    ];

    foreach ($sales as $sale) {
        $summary['total_quantity_kg'] += floatval($sale['quantity_kg']);
        
        if ($sale['currency_type'] === 'دینار') {
            $summary['total_revenue_iqd'] += floatval($sale['total_price']);
            $summary['total_profit_iqd'] += floatval($sale['profit_amount']);
            $summary['total_remaining_iqd'] += floatval($sale['remaining_amount']);
        } else {
            $summary['total_revenue_usd'] += floatval($sale['total_price']);
            $summary['total_profit_usd'] += floatval($sale['profit_amount']);
            $summary['total_remaining_usd'] += floatval($sale['remaining_amount']);
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $sales,
        'summary' => $summary
    ]);

} catch (PDOException $e) {
    error_log('Raw Material Sales Select Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
}
