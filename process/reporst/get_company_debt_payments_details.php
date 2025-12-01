<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'تکایە بەژوور بە']);
    exit;
}

if (!hasPermission('view_company')) {
    echo json_encode(['success' => false, 'error' => 'توانای دەست گەیشتنت نییە']);
    exit;
}

try {
    // Get date filters
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    
    // Build date condition
    $date_condition = '';
    $params = [];
    
    if ($from_date && $to_date) {
        $date_condition = ' AND date >= ? AND date <= ?';
        $params[] = $from_date;
        $params[] = $to_date;
    } elseif ($from_date) {
        $date_condition = ' AND date >= ?';
        $params[] = $from_date;
    } elseif ($to_date) {
        $date_condition = ' AND date <= ?';
        $params[] = $to_date;
    }
    
    // Get total amounts
    $total_query = "SELECT 
        SUM(amount_usd) as total_usd, 
        SUM(amount_iqd) as iqd,
        SUM(amount_usd) as usd_amount,
        COUNT(*) as count
        FROM debt_payments 
        WHERE 1=1 $date_condition";
    
    $stmt = $pdo->prepare($total_query);
    $stmt->execute($params);
    $total_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get exchange rate for conversion (same method as get_information.php)
    $usd_iqd_rate = 139250; // Default fallback value
    
    // Try to get rate from API first
    function fetchDollarRateFromAPI() {
        $apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
        $apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
        $id = '8'; // 100 dollar ID
        
        $url = $apiUrl . '?id=' . $id . '&api_token=' . $apiToken;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'DanaConcrete/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['value']) && is_numeric($data['value'])) {
                return floatval($data['value']);
            }
        }
        
        return null;
    }
    
    // Try to get rate from API
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    } else {
        // Fallback to database
        $rate_query = "SELECT value as rate FROM settings WHERE setting_key = 'usd_to_iqd_rate' LIMIT 1";
        $rate_stmt = $pdo->query($rate_query);
        $rate_row = $rate_stmt->fetch(PDO::FETCH_ASSOC);
        if ($rate_row && $rate_row['rate']) {
            $usd_iqd_rate = floatval($rate_row['rate']);
        }
    }
    
    // Convert IQD to USD
    $total_iqd = floatval($total_row['iqd'] ?? 0);
    $total_iqd_converted = ($usd_iqd_rate > 0) ? ($total_iqd / ($usd_iqd_rate / 100)) : 0;
    $total_usd = floatval($total_row['total_usd'] ?? 0) + $total_iqd_converted;
    
    // Get individual payments
    $payments_query = "SELECT 
        date,
        amount_usd,
        amount_iqd,
        discount_usd,
        discount_iqd,
        dollar_rate,
        note
        FROM debt_payments 
        WHERE 1=1 $date_condition
        ORDER BY date DESC, id DESC
        LIMIT 100";
    
    $payments_stmt = $pdo->prepare($payments_query);
    $payments_stmt->execute($params);
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_usd' => $total_usd,
            'total_iqd' => $total_iqd,
            'usd_amount' => floatval($total_row['usd_amount'] ?? 0),
            'count' => intval($total_row['count'] ?? 0),
            'payments' => $payments
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_company_debt_payments_details.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'هەڵەیەک ڕویدا']);
}
?>

