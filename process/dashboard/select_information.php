<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

// Summary counts
$summary = [
    'customers' => $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
    'companies' => $pdo->query('SELECT COUNT(*) FROM company')->fetchColumn(),
    'employees' => $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
    'receipts' => $pdo->query('SELECT COUNT(*) FROM concrete_receipts')->fetchColumn(),
    'sales' => $pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn(),
    'materials' => $pdo->query('SELECT COUNT(*) FROM materials')->fetchColumn(),
    'cars' => $pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn(),
];

// Financial Summary
$financial = [
    'total_sales_usd' => $pdo->query('SELECT COALESCE(SUM(amount_paid_usd), 0) FROM sales WHERE payment_type = "نەقد"')->fetchColumn(),
    'total_sales_iqd' => $pdo->query('SELECT COALESCE(SUM(amount_paid_iq), 0) FROM sales WHERE payment_type = "نەقد"')->fetchColumn(),
    'total_purchases_usd' => $pdo->query('SELECT COALESCE(SUM(paid_usd), 0) FROM purchases WHERE payment_type = "نەقد"')->fetchColumn(),
    'total_purchases_iqd' => $pdo->query('SELECT COALESCE(SUM(paid_iqd), 0) FROM purchases WHERE payment_type = "نەقد"')->fetchColumn(),
    'cash_balance_usd' => $pdo->query('SELECT COALESCE(SUM(CASE WHEN type = "deposit" THEN amount_usd ELSE -amount_usd END), 0) FROM cash_box WHERE currency = "دۆلار"')->fetchColumn(),
    'cash_balance_iqd' => $pdo->query('SELECT COALESCE(SUM(CASE WHEN type = "deposit" THEN amount_iqd ELSE -amount_iqd END), 0) FROM cash_box WHERE currency = "دینار"')->fetchColumn(),
];

// Stock Status
$stock_status = [];
$stmt = $pdo->query('SELECT name, amount, material_type FROM bins_silos WHERE amount > 0 ORDER BY amount ASC');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $percentage = 0;
    if ($row['material_type'] === 'چیمەنتۆ') {
        $percentage = min(100, ($row['amount'] / 100000) * 100); // Assuming 100,000 kg is full capacity
    } elseif ($row['material_type'] === 'لمی ڕەش' || $row['material_type'] === 'لمی کەسارە') {
        $percentage = min(100, ($row['amount'] / 200000) * 100); // Assuming 200,000 kg is full capacity
    } else {
        $percentage = min(100, ($row['amount'] / 150000) * 100); // Default capacity
    }
    
    $stock_status[] = [
        'name' => $row['name'],
        'amount' => $row['amount'],
        'material_type' => $row['material_type'],
        'percentage' => round($percentage, 1),
        'status' => $percentage > 70 ? 'high' : ($percentage > 30 ? 'medium' : 'low')
    ];
}

// Statistics
$stats = [
    'monthly_sales' => $pdo->query('SELECT COUNT(*) FROM sales WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())')->fetchColumn(),
    'monthly_receipts' => $pdo->query('SELECT COUNT(*) FROM concrete_receipts WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())')->fetchColumn(),
    'monthly_purchases' => $pdo->query('SELECT COUNT(*) FROM purchases WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())')->fetchColumn(),
    'pending_debts' => $pdo->query('SELECT COUNT(*) FROM customers WHERE debt_usd > 0 OR debt_iqd > 0')->fetchColumn(),
    'low_stock_items' => $pdo->query('SELECT COUNT(*) FROM bins_silos WHERE amount < 10000')->fetchColumn(),
    'active_employees' => $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
];

// Notifications
$notifications = [];

// Low stock notifications
$low_stock_stmt = $pdo->query('SELECT name, amount, material_type FROM bins_silos WHERE amount < 10000 ORDER BY amount ASC LIMIT 3');
while ($row = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'bi-exclamation-triangle',
        'title' => 'ستۆکی کەم',
        'text' => "{$row['name']} ({$row['material_type']}) تەنها {$row['amount']} kg ماوەتەوە"
    ];
}

// Recent debt payments
$recent_debts = $pdo->query('SELECT dp.amount_usd, dp.amount_iqd, dp.date, c.name as company_name FROM debt_payments dp LEFT JOIN company c ON dp.company_id = c.id ORDER BY dp.date DESC LIMIT 2');
while ($row = $recent_debts->fetch(PDO::FETCH_ASSOC)) {
    $amount = '';
    if ($row['amount_usd'] > 0) {
        $amount = "{$row['amount_usd']} USD";
    } elseif ($row['amount_iqd'] > 0) {
        $amount = "{$row['amount_iqd']} IQD";
    }
    
    $notifications[] = [
        'type' => 'success',
        'icon' => 'bi-check-circle',
        'title' => 'گەڕاندنەوەی قەرز',
        'text' => "{$row['company_name']}: {$amount} لە {$row['date']}"
    ];
}

// System notifications
$notifications[] = [
    'type' => 'info',
    'icon' => 'bi-info-circle',
    'title' => 'سیستەم',
    'text' => 'هەموو سیستەمەکان بە باشی کار دەکەن'
];

// Recent activities
$recent = [];
// Concrete Receipts
$stmt = $pdo->query("SELECT cr.id, cr.receipt_number as name, cr.meter_amount as amount, cr.created_at as date, c.name as customer FROM concrete_receipts cr LEFT JOIN customers c ON cr.customer_id = c.id ORDER BY cr.created_at DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent[] = [
        'type' => 'receipt',
        'id' => $row['id'],
        'name' => $row['name'],
        'amount' => $row['amount'],
        'date' => $row['date'],
        'customer' => $row['customer'],
    ];
}
// Sales
$stmt = $pdo->query("SELECT s.id, s.invoice_number as name, s.quantity as amount, s.order_date as date, c.name as customer FROM sales s LEFT JOIN customers c ON s.customer_id = c.id ORDER BY s.order_date DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent[] = [
        'type' => 'sale',
        'id' => $row['id'],
        'name' => $row['name'],
        'amount' => $row['amount'],
        'date' => $row['date'],
        'customer' => $row['customer'],
    ];
}
// Purchases
$stmt = $pdo->query("SELECT p.id, p.invoice_number as name, p.kg as amount, p.date, co.name as company FROM purchases p LEFT JOIN company co ON p.company_id = co.id ORDER BY p.date DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent[] = [
        'type' => 'purchase',
        'id' => $row['id'],
        'name' => $row['name'],
        'amount' => $row['amount'],
        'date' => $row['date'],
        'company' => $row['company'],
    ];
}

// Sort all activities by date descending and keep only the 5 most recent
usort($recent, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$recent = array_slice($recent, 0, 5);

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'financial' => $financial,
    'stock_status' => $stock_status,
    'stats' => $stats,
    'notifications' => $notifications,
    'recent' => $recent
]);
