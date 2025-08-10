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

    if ($date_from) {
        $where_conditions[] = 'date >= ?';
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = 'date <= ?';
        $params[] = $date_to;
    }

    $where_clause = implode(' AND ', $where_conditions);

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

    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

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
        HAVING expense_count > 0
        ORDER BY total_expenses_usd DESC
    ";

    $cars_stmt = $pdo->prepare($cars_query);
    $cars_stmt->execute($params);
    $cars_data = $cars_stmt->fetchAll(PDO::FETCH_ASSOC);

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
