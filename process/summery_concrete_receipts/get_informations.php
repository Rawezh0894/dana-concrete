<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user has permission to view summery concrete receipts
if (!hasPermission('view_summery_concrete_receipts')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

try {
    // Get filter parameters
    $customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
    $formulas_id = isset($_GET['formulas_id']) ? $_GET['formulas_id'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // Build WHERE clause
    $where_conditions = [];
    $params = [];

    if (!empty($customer_id)) {
        $where_conditions[] = "cr.customer_id = ?";
        $params[] = $customer_id;
    }

    if (!empty($formulas_id)) {
        $where_conditions[] = "cr.formulas_id = ?";
        $params[] = $formulas_id;
    }

    if (!empty($date_from)) {
        $where_conditions[] = "DATE(cr.created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(cr.created_at) <= ?";
        $params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(DISTINCT cr.id) as total_receipts,
            SUM(cr.meter_amount) as total_meter,
            COUNT(DISTINCT cr.customer_id) as total_customers,
            AVG(cr.meter_amount) as average_meter,
            SUM(CASE WHEN cr.price_per_meter IS NOT NULL THEN cr.meter_amount * cr.price_per_meter ELSE 0 END) as total_price
        FROM concrete_receipts cr
        $where_clause
    ";

    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Get customer summary
    $customer_summary_query = "
        SELECT 
            c.id as customer_id,
            c.name as customer_name,
            c.mobile1,
            COUNT(cr.id) as receipt_count,
            SUM(cr.meter_amount) as total_meter,
            AVG(cr.meter_amount) as average_meter,
            SUM(CASE WHEN cr.price_per_meter IS NOT NULL THEN cr.meter_amount * cr.price_per_meter ELSE 0 END) as total_price,
            GROUP_CONCAT(DISTINCT cf.name) as formulas_used,
            MAX(cr.notes) as latest_notes
        FROM customers c
        LEFT JOIN concrete_receipts cr ON c.id = cr.customer_id
        LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
        $where_clause
        GROUP BY c.id, c.name, c.mobile1
        HAVING receipt_count > 0
        ORDER BY total_meter DESC
    ";

    $customer_summary_stmt = $pdo->prepare($customer_summary_query);
    $customer_summary_stmt->execute($params);
    $customer_summary = $customer_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed receipts for a specific customer (if requested)
    $customer_details = null;
    if (isset($_GET['get_customer_details']) && !empty($_GET['customer_id'])) {
        // Build WHERE clause for customer details with same filters
        $customer_details_where_conditions = ["cr.customer_id = ?"];
        $customer_details_params = [$_GET['customer_id']];

        if (!empty($formulas_id)) {
            $customer_details_where_conditions[] = "cr.formulas_id = ?";
            $customer_details_params[] = $formulas_id;
        }

        if (!empty($date_from)) {
            $customer_details_where_conditions[] = "DATE(cr.created_at) >= ?";
            $customer_details_params[] = $date_from;
        }

        if (!empty($date_to)) {
            $customer_details_where_conditions[] = "DATE(cr.created_at) <= ?";
            $customer_details_params[] = $date_to;
        }

        $customer_details_where_clause = "WHERE " . implode(" AND ", $customer_details_where_conditions);

        $customer_details_query = "
            SELECT 
                cr.id,
                cr.receipt_number,
                cr.location,
                cr.receiver_name,
                cr.meter_amount,
                cr.price_per_meter,
                cr.notes,
                cr.created_at,
                cf.name as formula_name,
                CONCAT(mc.name, ' - ', md.name) as mixer_info,
                CONCAT(pc.name, ' - ', pd.name) as pump_info
            FROM concrete_receipts cr
            LEFT JOIN concrete_formulas cf ON cr.formulas_id = cf.id
            LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
            LEFT JOIN employees md ON cr.mixer_driver_id = md.id
            LEFT JOIN cars pc ON cr.pump_car_id = pc.id
            LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
            $customer_details_where_clause
            ORDER BY cr.created_at DESC
        ";
        
        $customer_details_stmt = $pdo->prepare($customer_details_query);
        $customer_details_stmt->execute($customer_details_params);
        $customer_details = $customer_details_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Format the response with null checks
    $response = [
        'success' => true,
        'summary' => [
            'total_receipts' => (int)($summary['total_receipts'] ?? 0),
            'total_meter' => round((float)($summary['total_meter'] ?? 0), 2),
            'total_customers' => (int)($summary['total_customers'] ?? 0),
            'average_meter' => round((float)($summary['average_meter'] ?? 0), 2),
            'total_price' => round((float)($summary['total_price'] ?? 0), 2)
        ],
        'customer_summary' => array_map(function($customer) {
            return [
                'customer_id' => (int)($customer['customer_id'] ?? 0),
                'customer_name' => $customer['customer_name'] ?? 'Unknown',
                'mobile1' => $customer['mobile1'] ?? '',
                'receipt_count' => (int)($customer['receipt_count'] ?? 0),
                'total_meter' => round((float)($customer['total_meter'] ?? 0), 2),
                'average_meter' => round((float)($customer['average_meter'] ?? 0), 2),
                'total_price' => round((float)($customer['total_price'] ?? 0), 2),
                'formulas_used' => $customer['formulas_used'] ? explode(',', $customer['formulas_used']) : [],
                'latest_notes' => $customer['latest_notes'] ?? null
            ];
        }, $customer_summary)
    ];

    if ($customer_details !== null) {
        $response['customer_details'] = array_map(function($receipt) {
            return [
                'id' => (int)$receipt['id'],
                'receipt_number' => $receipt['receipt_number'],
                'location' => $receipt['location'],
                'receiver_name' => $receipt['receiver_name'],
                'meter_amount' => round((float)$receipt['meter_amount'], 2),
                'price_per_meter' => $receipt['price_per_meter'] ? round((float)$receipt['price_per_meter'], 2) : null,
                'notes' => $receipt['notes'],
                'created_at' => $receipt['created_at'],
                'formula_name' => $receipt['formula_name'],
                'mixer_info' => $receipt['mixer_info'],
                'pump_info' => $receipt['pump_info']
            ];
        }, $customer_details);
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
