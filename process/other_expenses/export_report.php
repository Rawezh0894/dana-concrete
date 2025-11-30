<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!hasPermission('view_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}

try {
    // Build WHERE clause based on filters (same as select_expenses.php)
    $whereConditions = [];
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
    $sql = "SELECT 
        oe.id,
        oe.purpose,
        p.name AS person_name,
        e.name AS employee_name,
        c.name AS car_name,
        oe.gas_liters,
        oe.expense_type,
        lm.name AS material_name,
        oe.material_quantity,
        oe.material_purchase_price_iqd,
        oe.material_purchase_price_usd,
        oe.material_total_cost,
        oe.gas_purchase_price_input,
        oe.gas_total_cost,
        oe.payment_type,
        oe.currency_type,
        oe.invoice_number,
        oe.amount_iqd,
        oe.amount_usd,
        oe.paid_iqd,
        oe.paid_usd,
        oe.exchange_rate,
        oe.remaining_iqd,
        oe.remaining_usd,
        oe.date
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
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="other_expenses_report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    // Create CSV content
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 encoding (helps Excel display Arabic text correctly)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    $headers = [
        '#',
        'مەبەست',
        'کەس',
        'کارمەند',
        'سەیارە',
        'بڕی گاز (لیتر)',
        'جۆری خەرجی',
        'کاڵا لە کۆگا',
        'بڕی عەدەدی کاڵا',
        'نرخی کڕینی کاڵا بە دینار',
        'نرخی کڕینی کاڵا بە دۆلار',
        'کۆی نرخی کاڵای بەکارهاتوو',
        'ئینپوتی نرخی کڕینی گاز',
        'کۆی نرخی گازی بەکارهاتوو',
        'جۆری مامەڵە',
        'جۆری پارە',
        'ژمارەی وەسڵ',
        'بڕی دینار',
        'بڕی دۆلار',
        'پارەی دراو دینار',
        'پارەی دراو دۆلار',
        'نرخی 100 دۆلار',
        'ماوە دینار',
        'ماوە دۆلار',
        'بەروار'
    ];
    
    fputcsv($output, $headers);
    
    foreach ($expenses as $index => $expense) {
        $row = [
            $index + 1,
            $expense['purpose'] ?? '-',
            $expense['person_name'] ?? '-',
            $expense['employee_name'] ?? '-',
            $expense['car_name'] ?? '-',
            $expense['gas_liters'] ?? '-',
            $expense['expense_type'] ?? '-',
            $expense['material_name'] ?? '-',
            $expense['material_quantity'] ?? '-',
            $expense['material_purchase_price_iqd'] ?? '-',
            $expense['material_purchase_price_usd'] ?? '-',
            $expense['material_total_cost'] ?? '-',
            $expense['gas_purchase_price_input'] ?? '-',
            $expense['gas_total_cost'] ?? '-',
            $expense['payment_type'] ?? '-',
            $expense['currency_type'] ?? '-',
            $expense['invoice_number'] ?? '-',
            $expense['amount_iqd'] ?? '-',
            $expense['amount_usd'] ?? '-',
            $expense['paid_iqd'] ?? '-',
            $expense['paid_usd'] ?? '-',
            $expense['exchange_rate'] ?? '-',
            $expense['remaining_iqd'] ?? '-',
            $expense['remaining_usd'] ?? '-',
            $expense['date'] ?? '-'
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log('Error in export_report.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
?> 