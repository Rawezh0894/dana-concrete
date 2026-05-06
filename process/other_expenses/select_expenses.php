<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    require_once '../../config/permissions.php';
    if (!hasPermission('view_other_expenses')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
        exit;
    }
    // Build WHERE clause based on filters
    $whereConditions = ["oe.expense_type != 'بەکارهێنانی گاز'"];
    $params = [];
    
    // Date filters
    if (!empty($_GET['dateFrom'])) {
        $whereConditions[] = "oe.date >= ?";
        $params[] = $_GET['dateFrom'];
    }
    
    if (!empty($_GET['dateTo'])) {
        $whereConditions[] = "oe.date <= ?";
        $params[] = $_GET['dateTo'];
    }
    
    // Entity filters
    if (!empty($_GET['car'])) {
        $whereConditions[] = "oe.car_id = ?";
        $params[] = $_GET['car'];
    }
    
    if (!empty($_GET['employee'])) {
        $whereConditions[] = "oe.employee_id = ?";
        $params[] = $_GET['employee'];
    }
    
    if (!empty($_GET['person'])) {
        $whereConditions[] = "oe.person_id = ?";
        $params[] = $_GET['person'];
    }
    
    if (!empty($_GET['paymentType'])) {
        $whereConditions[] = "oe.payment_type = ?";
        $params[] = $_GET['paymentType'];
    }
    
    if (!empty($_GET['expenseTypes']) && is_array($_GET['expenseTypes'])) {
        $placeholders = str_repeat('?,', count($_GET['expenseTypes']) - 1) . '?';
        $whereConditions[] = "oe.expense_type IN ($placeholders)";
        $params = array_merge($params, $_GET['expenseTypes']);
    }
    

    
    // Build SQL query
    $sql = "SELECT oe.*, p.name AS person_name, e.name AS employee_name, c.name AS car_name, lm.name AS material_name, oe.car_id, oe.gas_liters
        FROM other_expenses oe
        LEFT JOIN other_expense_persons p ON oe.person_id = p.id
        LEFT JOIN employees e ON oe.employee_id = e.id
        LEFT JOIN cars c ON oe.car_id = c.id
        LEFT JOIN list_materials lm ON oe.material_id = lm.id";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY oe.date DESC, oe.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if AG Grid format is requested
    if (isset($_GET['ag_grid']) && $_GET['ag_grid'] == '1') {
        echo json_encode([
            'success' => true,
            'data' => $expenses,
            'total_count' => count($expenses)
        ]);
        exit;
    }
    
    // Calculate summary including car expenses (material and gas usage)
    $summarySql = "SELECT 
        -- Other expenses (non-car expenses)
        SUM(CASE WHEN currency_type = 'دینار' THEN amount_iqd ELSE 0 END) as total_other_expenses_iqd,
        SUM(CASE WHEN currency_type = 'دۆلار' THEN amount_usd ELSE 0 END) as total_other_expenses_usd
        FROM other_expenses oe";
    
    if (!empty($whereConditions)) {
        $summarySql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'expenses' => $expenses,
        'summary' => $summary,
        'total_count' => count($expenses)
    ]);
} catch (Exception $e) {
    error_log('Error in select_expenses.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'msg' => 'هەڵەی سیستەم: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
