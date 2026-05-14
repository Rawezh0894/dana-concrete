<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ بینینی خەرجی']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی ID']);
    exit;
}

try {
    $query = "
        SELECT 
            ee.id,
            ee.employee_id,
            e.name as employee_name,
            e.salary as monthly_salary,
            ee.expense_type,
            ee.amount,
            COALESCE(ee.amount_usd, 0) AS amount_usd,
            COALESCE(ee.amount_iqd, 0) AS amount_iqd,
            COALESCE(ee.exchange_rate, 0) AS exchange_rate,
            ee.notes,
            ee.expense_date,
            ee.created_at,
            u.username as created_by_name
        FROM employee_expenses ee
        LEFT JOIN employees e ON ee.employee_id = e.id
        LEFT JOIN users u ON ee.created_by = u.id
        WHERE ee.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$expense) {
        echo json_encode(['success' => false, 'message' => 'خەرجی نەدۆزرایەوە']);
        exit;
    }
    
    // Translate expense type to Kurdish
    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا',
        'overtime_payment' => 'پێدانی کاروانحیسابی',
    ];
    
    $expense['expense_type_kurdish'] = $expense_types_kurdish[$expense['expense_type']] ?? $expense['expense_type'];
    
    echo json_encode([
        'success' => true,
        'data' => $expense
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
