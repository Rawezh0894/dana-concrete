<?php
session_start();
require_once '../../config/db_conected.php';

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'تکایە بەژوور بە']);
    exit;
}

try {
    // Get date filters
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    
    // Build date condition
    $date_condition = '';
    $params = [];
    
    if ($from_date && $to_date) {
        $date_condition = ' AND dp.date >= ? AND dp.date <= ?';
        $params[] = $from_date;
        $params[] = $to_date;
    } elseif ($from_date) {
        $date_condition = ' AND dp.date >= ?';
        $params[] = $from_date;
    } elseif ($to_date) {
        $date_condition = ' AND dp.date <= ?';
        $params[] = $to_date;
    }
    
    // Get all debt payments with company names
    $query = "
        SELECT 
            dp.id,
            dp.company_id,
            dp.date,
            dp.amount_usd,
            dp.amount_iqd,
            dp.discount_usd,
            dp.discount_iqd,
            dp.dollar_rate,
            dp.note,
            c.name AS company_name
        FROM debt_payments dp
        LEFT JOIN company c ON dp.company_id = c.id
        WHERE 1=1 $date_condition
        ORDER BY dp.date DESC, dp.id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_usd = 0;
    $total_iqd = 0;
    $usd_amount = 0;
    $count = count($payments);
    
    foreach ($payments as $payment) {
        $total_usd += floatval($payment['amount_usd'] ?? 0);
        $total_iqd += floatval($payment['amount_iqd'] ?? 0);
        $usd_amount += floatval($payment['amount_usd'] ?? 0);
    }
    
    // Get USD to IQD rate for conversion
    $rate_query = "SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1";
    $rate_stmt = $pdo->query($rate_query);
    $rate_row = $rate_stmt->fetch(PDO::FETCH_ASSOC);
    $usd_iqd_rate = floatval($rate_row['value'] ?? 150000);
    
    // Convert IQD to USD
    $iqd_converted = ($usd_iqd_rate > 0) ? ($total_iqd / ($usd_iqd_rate / 100)) : 0;
    $total_usd_converted = $usd_amount + $iqd_converted;
    
    $response = [
        'success' => true,
        'data' => [
            'total_usd' => $total_usd_converted,
            'total_iqd' => $total_iqd,
            'usd_amount' => $usd_amount,
            'count' => $count,
            'payments' => $payments
        ]
    ];
    
    $json_output = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if ($json_output === false) {
        error_log('JSON encoding error: ' . json_last_error_msg());
        echo json_encode(['success' => false, 'error' => 'هەڵە لە دروستکردنی وەڵام: ' . json_last_error_msg()]);
    } else {
        echo $json_output;
    }
    
} catch (PDOException $e) {
    error_log('PDOException in get_company_debt_payments_details.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'هەڵە لە وەرگرتنی وردەکاریەکان: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in get_company_debt_payments_details.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'هەڵە لە وەرگرتنی وردەکاریەکان: ' . $e->getMessage()]);
}

