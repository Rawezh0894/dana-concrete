<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = [];
$params = [];
if ($from) {
    $where[] = 'date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'date <= ?';
    $params[] = $to;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Function to fetch dollar rate from API
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

try {
    // Get exchange rate from API
    $usd_iqd_rate = 139250; // Default fallback value
    
    // Try to get rate from API
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    }
    
    // Build WHERE clause for USD with currency filter
    $usd_where = [];
    $usd_params = [];
    if ($from) {
        $usd_where[] = 'date >= ?';
        $usd_params[] = $from;
    }
    if ($to) {
        $usd_where[] = 'date <= ?';
        $usd_params[] = $to;
    }
    $usd_where[] = "currency='دۆلار'";
    $usd_whereSql = 'WHERE ' . implode(' AND ', $usd_where);
    
    // Build WHERE clause for IQD with currency filter
    $iqd_where = [];
    $iqd_params = [];
    if ($from) {
        $iqd_where[] = 'date >= ?';
        $iqd_params[] = $from;
    }
    if ($to) {
        $iqd_where[] = 'date <= ?';
        $iqd_params[] = $to;
    }
    $iqd_where[] = "currency='دینار'";
    $iqd_whereSql = 'WHERE ' . implode(' AND ', $iqd_where);
    
    // Calculate USD total
    $sql_usd = "SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_usd ELSE -amount_usd END), 0) as total_usd FROM cash_box $usd_whereSql";
    $stmt_usd = $pdo->prepare($sql_usd);
    $stmt_usd->execute($usd_params);
    $total_usd = floatval($stmt_usd->fetchColumn() ?: 0);
    
    // Calculate IQD total
    $sql_iqd = "SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_iqd ELSE -amount_iqd END), 0) as total_iqd FROM cash_box $iqd_whereSql";
    $stmt_iqd = $pdo->prepare($sql_iqd);
    $stmt_iqd->execute($iqd_params);
    $total_iqd = floatval($stmt_iqd->fetchColumn() ?: 0);
    
    // Convert IQD to USD using API rate (100 USD = usd_iqd_rate IQD)
    // So 1 USD = (usd_iqd_rate / 100) IQD
    // Therefore: IQD amount in USD = total_iqd / (usd_iqd_rate / 100)
    $iqd_to_usd = 0;
    if ($usd_iqd_rate > 0 && $total_iqd > 0) {
        $iqd_to_usd = $total_iqd / ($usd_iqd_rate / 100);
    }
    
    $calculated_total = round($total_usd + $iqd_to_usd, 2);
    
    // Check if there's a manually set total
    $stmt_manual = $pdo->prepare("SELECT value FROM settings WHERE name = 'cash_box_total_usd_all' LIMIT 1");
    $stmt_manual->execute();
    $manual_total = $stmt_manual->fetchColumn();
    
    // Use manual total if exists, otherwise use calculated
    $total_usd_all = $manual_total !== false ? floatval($manual_total) : $calculated_total;
    
    echo json_encode(['success' => true, 'data' => [
        'total_usd_all' => $total_usd_all,
        'calculated_total' => $calculated_total,
        'is_manual' => $manual_total !== false,
        'total_usd' => $total_usd,
        'total_iqd' => $total_iqd,
        'iqd_to_usd' => $iqd_to_usd,
        'usd_iqd_rate' => $usd_iqd_rate
    ]]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 