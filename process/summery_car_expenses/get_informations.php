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
    // Test database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
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

    if ($date_from) {
        $where_conditions[] = 'date >= ?';
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = 'date <= ?';
        $params[] = $date_to;
    }

    $where_clause = implode(' AND ', $where_conditions);
    
    // Debug: Log the WHERE clause and parameters
    error_log("WHERE clause: " . $where_clause);
    error_log("Parameters: " . json_encode($params));

    // First, let's check if there are any records at all
    $check_query = "SELECT COUNT(*) as total FROM other_expenses WHERE $where_clause";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute($params);
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Total records found: " . $check_result['total']);

    // Check if there are any cars in the database
    $cars_check_query = "SELECT COUNT(*) as total FROM cars";
    $cars_check_stmt = $pdo->query($cars_check_query);
    $cars_check_result = $cars_check_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Total cars in database: " . $cars_check_result['total']);

    // Check if there are any other_expenses with car_id
    $car_expenses_check_query = "SELECT COUNT(*) as total FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $car_expenses_check_stmt = $pdo->query($car_expenses_check_query);
    $car_expenses_check_result = $car_expenses_check_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Total expenses with car_id: " . $car_expenses_check_result['total']);

    // Check what expense types exist
    $expense_types_query = "SELECT DISTINCT expense_type FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $expense_types_stmt = $pdo->query($expense_types_query);
    $expense_types = $expense_types_stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Expense types found: " . json_encode($expense_types));

    // Check what currency types exist
    $currency_types_query = "SELECT DISTINCT currency_type FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $currency_types_stmt = $pdo->query($currency_types_query);
    $currency_types = $currency_types_stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Currency types found: " . json_encode($currency_types));

    // Get summary statistics with improved calculation
    $summary_query = "
        SELECT 
            COUNT(DISTINCT car_id) as total_cars,
            SUM(CASE 
                WHEN expense_type = 'بەکارهێنانی گاز' THEN 
                    CASE 
                        WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                        WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_gas_expenses_usd,
            SUM(CASE 
                WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN 
                    CASE 
                        WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                        WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_material_expenses_usd,
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                ELSE 0 
            END) as total_expenses_usd
        FROM other_expenses 
        WHERE $where_clause
    ";

    error_log("Summary query: " . $summary_query);
    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Summary result: " . json_encode($summary));

    // Get car expenses data with improved calculation
    $cars_query = "
        SELECT 
            c.id as car_id,
            c.name as car_name,
            e.name as employee_name,
            COUNT(oe.id) as expense_count,
            SUM(CASE 
                WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN 
                    CASE 
                        WHEN oe.currency_type = 'دۆلار' THEN COALESCE(oe.amount_usd, 0)
                        WHEN oe.currency_type = 'دینار' THEN COALESCE(oe.amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_gas_expenses_usd,
            SUM(CASE 
                WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN 
                    CASE 
                        WHEN oe.currency_type = 'دۆلار' THEN COALESCE(oe.amount_usd, 0)
                        WHEN oe.currency_type = 'دینار' THEN COALESCE(oe.amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_material_expenses_usd,
            SUM(CASE 
                WHEN oe.currency_type = 'دۆلار' THEN COALESCE(oe.amount_usd, 0)
                WHEN oe.currency_type = 'دینار' THEN COALESCE(oe.amount_iqd, 0) / 139250
                ELSE 0 
            END) as total_expenses_usd,
            CASE 
                WHEN SUM(CASE WHEN oe.payment_type = 'قەرز' THEN 1 ELSE 0 END) > 0 THEN 'pending'
                ELSE 'paid'
            END as payment_status
        FROM cars c
        LEFT JOIN other_expenses oe ON c.id = oe.car_id AND $where_clause
        LEFT JOIN employees e ON oe.employee_id = e.id
        GROUP BY c.id, c.name, e.name
        ORDER BY total_expenses_usd DESC
    ";

    error_log("Cars query: " . $cars_query);
    $cars_stmt = $pdo->prepare($cars_query);
    $cars_stmt->execute($params);
    $cars_data = $cars_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Cars data count: " . count($cars_data));
    error_log("First car data: " . json_encode($cars_data[0] ?? 'No data'));

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

    // If no expenses found, set summary to 0
    if ($summary['total_cars'] == 0) {
        $summary['total_cars'] = count($cars_data);
        $summary['total_gas_expenses_usd'] = 0;
        $summary['total_material_expenses_usd'] = 0;
        $summary['total_expenses_usd'] = 0;
    }

    // Debug information
    error_log("Summary data: " . json_encode($summary));
    error_log("Cars data count: " . count($cars_data));

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
