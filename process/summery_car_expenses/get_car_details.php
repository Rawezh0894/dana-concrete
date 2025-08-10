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
    // Get car ID parameter
    $car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
    
    if ($car_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid car ID']);
        exit;
    }

    // Get car basic information
    $car_query = "SELECT id, name FROM cars WHERE id = ?";
    $car_stmt = $pdo->prepare($car_query);
    $car_stmt->execute([$car_id]);
    $car = $car_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$car) {
        echo json_encode(['success' => false, 'message' => 'Car not found']);
        exit;
    }

    // Get car expenses with details
    $expenses_query = "
        SELECT 
            oe.id,
            oe.purpose,
            oe.expense_type,
            oe.gas_liters,
            oe.material_quantity,
            oe.usage_unit_type,
            oe.amount_iqd,
            oe.amount_usd,
            oe.currency_type,
            oe.payment_type,
            oe.date,
            oe.car_id,
            oe.employee_id,
            oe.material_id,
            e.name as employee_name,
            m.name as material_name,
            CASE 
                WHEN oe.currency_type = 'دۆلار' THEN oe.amount_usd 
                ELSE oe.amount_iqd / 139250 
            END as total_cost_usd,
            CASE 
                WHEN oe.payment_type = 'قەرز' THEN 'pending'
                ELSE 'paid'
            END as payment_status
        FROM other_expenses oe
        LEFT JOIN employees e ON oe.employee_id = e.id
        LEFT JOIN materials m ON oe.material_id = m.id
        WHERE oe.car_id = ? AND oe.car_id IS NOT NULL AND oe.car_id != 0
        ORDER BY oe.date DESC
    ";

    $expenses_stmt = $pdo->prepare($expenses_query);
    $expenses_stmt->execute([$car_id]);
    $expenses = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary for this car
    $summary_query = "
        SELECT 
            COUNT(*) as expense_count,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی گاز' THEN 
                CASE WHEN currency_type = 'دۆلار' THEN amount_usd ELSE amount_iqd / 139250 END 
                ELSE 0 END) as total_gas_expenses_usd,
            SUM(CASE WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN 
                CASE WHEN currency_type = 'دۆلار' THEN amount_usd ELSE amount_iqd / 139250 END 
                ELSE 0 END) as total_material_expenses_usd,
            SUM(CASE WHEN currency_type = 'دۆلار' THEN amount_usd ELSE amount_iqd / 139250 END) as total_expenses_usd
        FROM other_expenses 
        WHERE car_id = ? AND car_id IS NOT NULL AND car_id != 0
    ";

    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute([$car_id]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Process expenses data
    foreach ($expenses as &$expense) {
        $expense['total_cost_usd'] = floatval($expense['total_cost_usd'] ?? 0);
        $expense['amount_iqd'] = floatval($expense['amount_iqd'] ?? 0);
        $expense['amount_usd'] = floatval($expense['amount_usd'] ?? 0);
        $expense['gas_liters'] = $expense['gas_liters'] ? floatval($expense['gas_liters']) : null;
        $expense['material_quantity'] = $expense['material_quantity'] ? floatval($expense['material_quantity']) : null;
    }

    // Process summary data
    $summary['expense_count'] = intval($summary['expense_count'] ?? 0);
    $summary['total_gas_expenses_usd'] = floatval($summary['total_gas_expenses_usd'] ?? 0);
    $summary['total_material_expenses_usd'] = floatval($summary['total_material_expenses_usd'] ?? 0);
    $summary['total_expenses_usd'] = floatval($summary['total_expenses_usd'] ?? 0);

    // Prepare response data
    $car_data = [
        'car_id' => $car['id'],
        'car_name' => $car['name'],
        'expense_count' => $summary['expense_count'],
        'total_gas_expenses_usd' => $summary['total_gas_expenses_usd'],
        'total_material_expenses_usd' => $summary['total_material_expenses_usd'],
        'total_expenses_usd' => $summary['total_expenses_usd'],
        'expenses' => $expenses
    ];

    echo json_encode([
        'success' => true,
        'data' => $car_data
    ]);

} catch (Exception $e) {
    error_log("Error in get_car_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
