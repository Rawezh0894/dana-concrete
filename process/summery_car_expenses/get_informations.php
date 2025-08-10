<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if user has permission to view car expenses summary
if (!hasPermission('view_car_expenses_summary')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Get filter parameters
    $car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
    $employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // Build WHERE clause
    $where_conditions = ['car_id IS NOT NULL AND car_id != 0'];
    $params = [];

    if ($car_id > 0) {
        $where_conditions[] = 'car_id = ?';
        $params[] = $car_id;
    }

    if ($employee_id > 0) {
        $where_conditions[] = 'employee_id = ?';
        $params[] = $employee_id;
    }

    // Only add date filters if they are provided
    if ($date_from && $date_from !== '') {
        $where_conditions[] = 'date >= ?';
        $params[] = $date_from;
    }

    if ($date_to && $date_to !== '') {
        $where_conditions[] = 'date <= ?';
        $params[] = $date_to;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Debug: Log the final WHERE clause and parameters
    error_log("Final WHERE clause: " . $where_clause);
    error_log("Final parameters: " . json_encode($params));

    // Debug: Check what car expenses exist in the database
    $debug_query = "
        SELECT 
            car_id,
            expense_type,
            gas_total_cost,
            material_total_cost,
            amount_usd,
            amount_iqd,
            currency_type,
            date
        FROM other_expenses 
        WHERE car_id IS NOT NULL AND car_id != 0
        ORDER BY date DESC
        LIMIT 10
    ";
    
    $debug_stmt = $pdo->query($debug_query);
    $debug_data = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Debug: Car expenses in database: " . json_encode($debug_data));

    // Debug: Count total car expenses and cars with expenses
    $count_query = "
        SELECT 
            COUNT(*) as total_expenses,
            COUNT(DISTINCT car_id) as total_cars_with_expenses,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN 1 ELSE 0 END) as gas_expenses_count,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN 1 ELSE 0 END) as material_expenses_count
        FROM other_expenses 
        WHERE car_id IS NOT NULL AND car_id != 0
    ";
    
    $count_stmt = $pdo->query($count_query);
    $count_data = $count_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Debug: Count summary: " . json_encode($count_data));

    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(DISTINCT car_id) as total_cars,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN gas_total_cost ELSE 0 END) as total_gas_expenses_usd,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN material_total_cost ELSE 0 END) as total_material_expenses_usd,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN gas_total_cost 
                     WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN material_total_cost 
                     ELSE 0 END) as total_expenses_usd
        FROM other_expenses 
        WHERE $where_clause
    ";

    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Log the summary query and results
    error_log("Summary query: " . $summary_query);
    error_log("Summary params: " . json_encode($params));
    error_log("Summary results: " . json_encode($summary));

    // Get car expenses data
    $cars_query = "
        SELECT 
            c.id as car_id,
            c.name as car_name,
            e.name as employee_name,
            COUNT(oe.id) as expense_count,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN oe.gas_total_cost ELSE 0 END) as total_gas_expenses_usd,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN oe.material_total_cost ELSE 0 END) as total_material_expenses_usd,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN oe.gas_total_cost 
                     WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN oe.material_total_cost 
                     ELSE 0 END) as total_expenses_usd,
            CASE 
                WHEN SUM(CASE WHEN oe.payment_type = 'قەرز' THEN 1 ELSE 0 END) > 0 THEN 'pending'
                ELSE 'paid'
            END as payment_status
        FROM cars c
        LEFT JOIN other_expenses oe ON c.id = oe.car_id AND $where_clause
        LEFT JOIN employees e ON oe.employee_id = e.id
        GROUP BY c.id, c.name, e.name
        HAVING expense_count > 0
        ORDER BY total_expenses_usd DESC
    ";

    $cars_stmt = $pdo->prepare($cars_query);
    $cars_stmt->execute($params);
    $cars_data = $cars_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the cars query and results
    error_log("Cars query: " . $cars_query);
    error_log("Cars results count: " . count($cars_data));
    if (count($cars_data) > 0) {
        error_log("First car data: " . json_encode($cars_data[0]));
    }

    // Process data for better display
    foreach ($cars_data as &$car) {
        $car['total_gas_expenses_usd'] = floatval($car['total_gas_expenses_usd'] ?? 0);
        $car['total_material_expenses_usd'] = floatval($car['total_material_expenses_usd'] ?? 0);
        $car['total_expenses_usd'] = floatval($car['total_expenses_usd'] ?? 0);
        $car['expense_count'] = intval($car['expense_count'] ?? 0);
    }

    // Process summary data
    $summary['total_cars'] = intval($summary['total_cars'] ?? 0);
    $summary['total_gas_expenses_usd'] = floatval($summary['total_gas_expenses_usd'] ?? 0);
    $summary['total_material_expenses_usd'] = floatval($summary['total_material_expenses_usd'] ?? 0);
    $summary['total_expenses_usd'] = floatval($summary['total_expenses_usd'] ?? 0);

    echo json_encode([
        'success' => true,
        'data' => $cars_data,
        'summary' => $summary,
        'filters' => [
            'car_id' => $car_id,
            'employee_id' => $employee_id,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_informations.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
