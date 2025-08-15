<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_other_expenses')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

// Get filter parameters
$car_id = $_GET['car_id'] ?? '';
$employee_id = $_GET['employee_id'] ?? '';
$expense_type = $_GET['expense_type'] ?? '';
$payment_type = $_GET['payment_type'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Debug logging
error_log("Car Expenses Summary - Filters: " . json_encode([
    'car_id' => $car_id,
    'employee_id' => $employee_id,
    'expense_type' => $expense_type,
    'payment_type' => $payment_type,
    'from_date' => $from_date,
    'to_date' => $to_date
]));

// Build WHERE clause
$where = [];
$params = [];

if ($car_id) {
    $where[] = "oe.car_id = ?";
    $params[] = $car_id;
}

if ($employee_id) {
    $where[] = "oe.employee_id = ?";
    $params[] = $employee_id;
}

if ($expense_type) {
    $where[] = "oe.expense_type = ?";
    $params[] = $expense_type;
}

if ($payment_type) {
    $where[] = "oe.payment_type = ?";
    $params[] = $payment_type;
}

if ($from_date) {
    $where[] = "DATE(oe.date) >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where[] = "DATE(oe.date) <= ?";
    $params[] = $to_date;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Get detailed data
$sql = "SELECT 
    oe.*,
    c.name AS car_name,
    e.name AS employee_name,
    m.name AS material_name,
    oe.usage_unit_type AS material_unit_type
FROM other_expenses oe
LEFT JOIN cars c ON oe.car_id = c.id
LEFT JOIN employees e ON oe.employee_id = e.id
LEFT JOIN list_materials m ON oe.material_id = m.id
$where_sql
ORDER BY oe.date DESC, oe.id DESC";

try {
    // Debug logging
    error_log("Car Expenses Summary - SQL: " . $sql);
    error_log("Car Expenses Summary - Params: " . json_encode($params));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Car Expenses Summary - Data count: " . count($data));
    
    // Calculate summary statistics
    $summary = calculateSummary($data, $pdo, $where_sql, $params);
    
    // Add message for empty data
    $message = '';
    if (count($data) === 0) {
        $message = 'هیچ داتایەک نەدۆزرا بۆ ئەم فلتەرەکان. تکایە فلتەرەکان بگۆڕە یان داتای زیاتر زیاد بکە.';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => $summary,
        'message' => $message,
        'filters_applied' => [
            'car_id' => $car_id,
            'employee_id' => $employee_id,
            'expense_type' => $expense_type,
            'payment_type' => $payment_type,
            'from_date' => $from_date,
            'to_date' => $to_date
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Car Expenses Summary Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Car Expenses Summary General Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}

function calculateSummary($data, $pdo, $where_sql, $params) {
    $summary = [
        'total_usd' => 0,
        'total_iqd' => 0,
        'total_cars' => 0,
        'total_expenses' => count($data),
        'total_gas' => 0,
        'total_materials' => 0,
        'expense_type_distribution' => [],
        'car_expenses' => [],
        'monthly_trend' => [],
        'payment_type_distribution' => []
    ];
    
    // Calculate totals from data
    $car_ids = [];
    $expense_types = [];
    $payment_types = [];
    $monthly_data = [];
    
    foreach ($data as $row) {
        // Calculate total cost including material_total_cost and gas_total_cost
        $row_total_usd = floatval($row['amount_usd'] ?? 0);
        $row_total_iqd = floatval($row['amount_iqd'] ?? 0);
        
        // Add material total cost if available
        if ($row['material_total_cost']) {
            $row_total_iqd += floatval($row['material_total_cost']);
        }
        
        // Add gas total cost if available
        if ($row['gas_total_cost']) {
            $row_total_iqd += floatval($row['gas_total_cost']);
        }
        
        // Amount totals
        $summary['total_usd'] += $row_total_usd;
        $summary['total_iqd'] += $row_total_iqd;
        
        // Gas and materials
        if ($row['gas_liters']) {
            $summary['total_gas'] += floatval($row['gas_liters']);
        }
        if ($row['material_quantity']) {
            $summary['total_materials'] += floatval($row['material_quantity']);
        }
        
        // Collect unique cars
        if ($row['car_id']) {
            $car_ids[$row['car_id']] = [
                'id' => $row['car_id'],
                'name' => $row['car_name']
            ];
        }
        
        // Collect expense types
        $expense_type = $row['expense_type'];
        if (!isset($expense_types[$expense_type])) {
            $expense_types[$expense_type] = 0;
        }
        $expense_types[$expense_type] += $row_total_usd + ($row_total_iqd / 150000); // Convert IQD to USD for comparison
        
        // Collect payment types
        $payment_type = $row['payment_type'];
        if (!isset($payment_types[$payment_type])) {
            $payment_types[$payment_type] = 0;
        }
        $payment_types[$payment_type] += $row_total_usd + ($row_total_iqd / 150000);
        
        // Monthly trend
        $month = date('Y-m', strtotime($row['date']));
        if (!isset($monthly_data[$month])) {
            $monthly_data[$month] = [
                'usd' => 0,
                'iqd' => 0,
                'count' => 0
            ];
        }
        $monthly_data[$month]['usd'] += $row_total_usd;
        $monthly_data[$month]['iqd'] += $row_total_iqd;
        $monthly_data[$month]['count']++;
    }
    
    $summary['total_cars'] = count($car_ids);
    
    // Convert expense types to array format for charts
    foreach ($expense_types as $type => $amount) {
        $summary['expense_type_distribution'][] = [
            'type' => $type,
            'amount' => round($amount, 2)
        ];
    }
    
    // Convert payment types to array format for charts
    foreach ($payment_types as $type => $amount) {
        $summary['payment_type_distribution'][] = [
            'type' => $type,
            'amount' => round($amount, 2)
        ];
    }
    
    // Calculate car-specific expenses
    $summary['car_expenses'] = calculateCarExpenses($pdo, $where_sql, $params);
    
    // Convert monthly data to array format
    foreach ($monthly_data as $month => $data) {
        $summary['monthly_trend'][] = [
            'month' => $month,
            'usd' => round($data['usd'], 2),
            'iqd' => round($data['iqd'], 2),
            'count' => $data['count']
        ];
    }
    
    // Sort by month
    usort($summary['monthly_trend'], function($a, $b) {
        return strcmp($a['month'], $b['month']);
    });
    
    return $summary;
}

function calculateCarExpenses($pdo, $where_sql, $params) {
    $car_expenses_sql = "SELECT 
        c.id,
        c.name AS car_name,
        SUM(COALESCE(oe.amount_usd, 0)) AS total_usd,
        SUM(COALESCE(oe.amount_iqd, 0) + COALESCE(oe.material_total_cost, 0) + COALESCE(oe.gas_total_cost, 0)) AS total_iqd,
        COUNT(oe.id) AS expense_count
    FROM cars c
    LEFT JOIN other_expenses oe ON c.id = oe.car_id
    $where_sql
    GROUP BY c.id, c.name
    HAVING COUNT(oe.id) > 0
    ORDER BY total_usd DESC, total_iqd DESC";
    
    try {
        $stmt = $pdo->prepare($car_expenses_sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
?>
