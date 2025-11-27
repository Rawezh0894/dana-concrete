<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and GET data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('select_sale.php GET: ' . print_r($_GET, true));

if (!hasPermission('view_sale')) {
    error_log('Permission denied for user: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown') . ' to view sales');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
    if (!$customer_id) {
        error_log('No customer ID provided for sales retrieval');
        echo json_encode(['success' => false, 'message' => 'کڕیار دیاری نەکراوە']);
        exit;
    }
    
    $include_sales = [];
    if (!empty($_GET['include_sales'])) {
        $raw_ids = explode(',', $_GET['include_sales']);
        foreach ($raw_ids as $raw_id) {
            $sale_id = intval($raw_id);
            if ($sale_id > 0) {
                $include_sales[] = $sale_id;
            }
        }
    }
    
    if (isset($_GET['stats'])) {
        // Get opening debt
        $row = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM customers WHERE id = ?');
        $row->execute([$customer_id]);
        $debt = $row->fetch(PDO::FETCH_ASSOC);
        
        if (!$debt) {
            error_log('Customer not found: ID=' . $customer_id);
            echo json_encode(['success' => false, 'message' => 'کڕیار نەدۆزرایەوە']);
            exit;
        }
        
        // Sum of remaining amounts from sales (USD) - هەموو فرۆشتنەکانی قەرز (جگە لە دینار)
        $sales_usd = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) 
                                   FROM sales 
                                   WHERE customer_id = ? 
                                   AND payment_type = 'قەرز' 
                                   AND amount_paid_iq = 0");
        $sales_usd->execute([$customer_id]);
        $total_remaining_usd = $sales_usd->fetchColumn();
        
        // Sum of remaining amounts from sales (IQD) - تەنها فرۆشتنەکانی دینار
        // تەنها ئەوانەی amount_paid_iq > 0 (یەکێک لە پارەدانەکان دینار بووە)
        $sales_iqd = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) 
                                   FROM sales 
                                   WHERE customer_id = ? 
                                   AND payment_type = 'قەرز' 
                                   AND amount_paid_iq > 0");
        $sales_iqd->execute([$customer_id]);
        $total_remaining_iqd = $sales_iqd->fetchColumn();
        
        // Add opening debt
        $total_debt_usd = floatval($total_remaining_usd) + floatval($debt['opening_debt_usd'] ?? 0);
        $total_debt_iqd = floatval($total_remaining_iqd) + floatval($debt['opening_debt_iqd'] ?? 0);
        
        $count = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
        $count->execute([$customer_id]);
        $sales_count = $count->fetchColumn();
        
        error_log('Customer stats retrieved: Customer=' . $customer_id . ', Total Debt USD=' . $total_debt_usd . ', Total Debt IQD=' . $total_debt_iqd . ', Sales Count=' . $sales_count);
        
        echo json_encode(['stats' => [
            'total_debt_usd' => $total_debt_usd,
            'total_debt_iqd' => $total_debt_iqd,
            'opening_debt_usd' => $debt['opening_debt_usd'] ?? 0,
            'opening_debt_iqd' => $debt['opening_debt_iqd'] ?? 0,
            'sales_count' => $sales_count
        ]]);
        exit;
    }
    
    // Check if we need only sales with remaining debt
    $remaining_only = isset($_GET['remaining_only']) && $_GET['remaining_only'] == '1';
    
    if ($remaining_only) {
        // Get only sales with remaining debt for payment allocation
        $params = [$customer_id];
        $includeClause = '';
        if (!empty($include_sales)) {
            $placeholders = implode(',', array_fill(0, count($include_sales), '?'));
            $includeClause = " OR s.id IN ($placeholders)";
            $params = array_merge($params, $include_sales);
        }
        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS customer_name, f.strength_mpa, f.strength_kg
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN concrete_formulas f ON s.formula_id = f.id
            WHERE s.customer_id = ?
              AND s.payment_type = 'قەرز'
              AND (s.remaining_amount > 0{$includeClause})
            ORDER BY s.order_date ASC, s.id ASC
        ");
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('Customer remaining sales retrieved: Customer=' . $customer_id . ', Count=' . count($sales));
        echo json_encode(['success' => true, 'sales' => $sales]);
    } else {
        // Get all sales
        $stmt = $pdo->prepare('
            SELECT s.*, c.name AS customer_name, f.name AS formula_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN concrete_formulas f ON s.formula_id = f.id
            WHERE s.customer_id = ?
            ORDER BY s.id DESC
        ');
        $stmt->execute([$customer_id]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('Customer sales retrieved: Customer=' . $customer_id . ', Count=' . count($sales));
        echo json_encode(['success' => true, 'data' => $sales]);
    }
    
} catch (PDOException $e) {
    error_log('PDOException in select_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in select_sale.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
