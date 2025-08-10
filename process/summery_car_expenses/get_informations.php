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

    // Build WHERE clause for all queries
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
    
    // Create WHERE clause for cars query (with table alias)
    $cars_where_conditions = [];
    $cars_params = [];
    
    if ($car_id > 0) {
        $cars_where_conditions[] = 'oe.car_id = ?';
        $cars_params[] = $car_id;
    }
    
    if ($employee_id > 0) {
        $cars_where_conditions[] = 'oe.employee_id = ?';
        $cars_params[] = $employee_id;
    }
    
    // Only add date filters if they are provided
    if ($date_from && $date_from !== '') {
        $cars_where_conditions[] = 'oe.date >= ?';
        $cars_params[] = $date_from;
    }
    
    if ($date_to && $date_to !== '') {
        $cars_where_conditions[] = 'oe.date <= ?';
        $cars_params[] = $date_to;
    }
    
    $cars_where_clause = !empty($cars_where_conditions) ? 'WHERE ' . implode(' AND ', $cars_where_conditions) : '';

    // Debug: Log the final WHERE clause and parameters
    error_log("Final WHERE clause: " . $where_clause);
    error_log("Final parameters: " . json_encode($params));
    error_log("Cars WHERE clause: " . $cars_where_clause);
    error_log("Cars parameters: " . json_encode($cars_params));

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

    // Debug: Show expense types breakdown
    $debug_expense_types_query = "
        SELECT 
            expense_type,
            COUNT(*) as count,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN gas_total_cost ELSE 0 END) as gas_costs,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN material_total_cost ELSE 0 END) as material_costs,
            SUM(CASE WHEN expense_type = 'خەرجی تر' AND currency_type = 'دۆلار' THEN amount_usd ELSE 0 END) as other_usd_costs,
            SUM(CASE WHEN expense_type = 'خەرجی تر' AND currency_type = 'دینار' THEN amount_iqd ELSE 0 END) as other_iqd_costs
        FROM other_expenses 
        WHERE car_id IS NOT NULL AND car_id != 0
        GROUP BY expense_type
    ";
    
    try {
        $debug_stmt = $pdo->prepare($debug_expense_types_query);
        $debug_stmt->execute($params);
        $debug_expense_types = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Debug: Expense types breakdown: " . json_encode($debug_expense_types));
    } catch (Exception $e) {
        error_log("Debug: Error getting expense types breakdown: " . $e->getMessage());
    }

    // Use the WHERE clause already built above
    $where_clause = 'WHERE ' . $where_clause;
    
    // Summary query - Fixed to handle different expense types correctly
    $summary_query = "
        SELECT 
            COUNT(DISTINCT car_id) as total_cars,
            COUNT(*) as total_expenses,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN gas_total_cost ELSE 0 END) as total_gas_expenses_usd,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN material_total_cost ELSE 0 END) as total_material_expenses_usd,
            SUM(CASE 
                WHEN expense_type = 'بەکارهێنانی گاز' THEN gas_total_cost
                WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN material_total_cost
                WHEN expense_type = 'خەرجی تر' THEN 
                    CASE 
                        WHEN currency_type = 'دۆلار' THEN amount_usd
                        WHEN currency_type = 'دینار' THEN amount_iqd / exchange_rate * 100
                        ELSE 0
                    END
                ELSE 0
            END) as total_expenses_usd
        FROM other_expenses 
        $where_clause
    ";

    try {
        $summary_stmt = $pdo->prepare($summary_query);
        $summary_stmt->execute($params);
        $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error executing summary query: " . $e->getMessage());
        error_log("Summary query: " . $summary_query);
        error_log("Summary params: " . json_encode($params));
        throw $e;
    }

    // Debug: Log the summary query and results
    error_log("Summary query: " . $summary_query);
    error_log("Summary params: " . json_encode($params));
    error_log("Summary results: " . json_encode($summary));

    // Cars query - Fixed to handle different expense types correctly
    $cars_query = "
        SELECT 
            c.id as car_id,
            c.name as car_name,
            c.plate_number,
            COUNT(oe.id) as expense_count,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN oe.gas_total_cost ELSE 0 END) as total_gas_expenses_usd,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN oe.material_total_cost ELSE 0 END) as total_material_expenses_usd,
            SUM(CASE 
                WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN oe.gas_total_cost
                WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN oe.material_total_cost
                WHEN oe.expense_type = 'خەرجی تر' THEN 
                    CASE 
                        WHEN oe.currency_type = 'دۆلار' THEN oe.amount_usd
                        WHEN oe.currency_type = 'دینار' THEN oe.amount_iqd / oe.exchange_rate * 100
                        ELSE 0
                    END
                ELSE 0
            END) as total_expenses_usd
        FROM cars c
        INNER JOIN other_expenses oe ON c.id = oe.car_id
        $cars_where_clause
        GROUP BY c.id, c.name, c.plate_number
        ORDER BY total_expenses_usd DESC
    ";

    try {
        $cars_stmt = $pdo->prepare($cars_query);
        $cars_stmt->execute($cars_params);
        $cars_data = $cars_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error executing cars query: " . $e->getMessage());
        error_log("Cars query: " . $cars_query);
        error_log("Cars params: " . json_encode($cars_params));
        throw $e;
    }

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
