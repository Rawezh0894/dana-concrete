<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Log session for debugging
error_log('SESSION: ' . print_r($_SESSION, true));

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log('User not logged in for dashboard access');
        echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
        exit;
    }

    // Check if user has permission to view dashboard
    if (!hasPermission('view_dashboard')) {
        error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to view dashboard');
        echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
        exit;
    }

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
    $stmt = $pdo->query('SELECT name, type, amount, material_type, average_price, total_value FROM bins_silos WHERE amount > 0 ORDER BY amount ASC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate average price per kg if amount > 0
        $avg_price_per_kg = 0;
        $price_currency = 'دینار'; // Default currency
        
        if ($row['amount'] > 0) {
            if ($row['average_price'] > 0) {
                $avg_price_per_kg = $row['average_price'];
            } elseif ($row['total_value'] > 0) {
                $avg_price_per_kg = $row['total_value'] / $row['amount'];
            }
            
            // Set currency based on material type
            if ($row['material_type'] === 'دەرمان' || $row['material_type'] === 'چیمەنتۆ') {
                $price_currency = 'دۆلار';
            }
        }
        
        $stock_status[] = [
            'name' => $row['name'],
            'type' => $row['type'],
            'amount' => $row['amount'],
            'material_type' => $row['material_type'],
            'average_price_per_kg' => round($avg_price_per_kg, 2),
            'price_currency' => $price_currency,
            'total_value' => $row['total_value']
        ];
    }

    // Statistics
    $stats = [
        'monthly_sales' => $pdo->query('SELECT COUNT(*) FROM sales WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())')->fetchColumn(),
        'monthly_receipts' => $pdo->query('SELECT COUNT(*) FROM concrete_receipts WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())')->fetchColumn(),
        'monthly_purchases' => $pdo->query('SELECT COUNT(*) FROM purchases WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())')->fetchColumn(),
        'pending_debts' => $pdo->query('SELECT COUNT(*) FROM customers WHERE opening_debt_usd > 0 OR opening_debt_iqd > 0')->fetchColumn(),
        'low_stock_items' => $pdo->query('SELECT COUNT(*) FROM bins_silos WHERE amount < 10000')->fetchColumn(),
        'active_employees' => $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
    ];

    // Notifications
    $notifications = [];

    // Low stock notifications
    $low_stock_stmt = $pdo->query('SELECT name, type, amount, material_type FROM bins_silos WHERE amount < 10000 ORDER BY amount ASC LIMIT 3');
    while ($row = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format amount for display - show full numbers
        $amountText = number_format($row['amount']);
            
        $notifications[] = [
            'type' => 'warning',
            'icon' => 'bi-exclamation-triangle',
            'title' => 'ستۆکی کەم',
            'text' => "{$row['name']} ({$row['type']} - {$row['material_type']}) تەنها {$amountText} کگم ماوەتەوە"
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

    $response = [
        'success' => true,
        'summary' => $summary,
        'financial' => $financial,
        'stock_status' => $stock_status,
        'stats' => $stats,
        'notifications' => $notifications,
        'recent' => $recent,
        'permissions' => [
            'view_dashboard_prices' => hasPermission('view_dashboard_prices')
        ]
    ];

    error_log('Dashboard data loaded successfully for user: ' . $_SESSION['user_id']);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('PDOException in select_information.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in select_information.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
