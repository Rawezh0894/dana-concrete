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
    
    // USD
    $sql_usd = "SELECT SUM(CASE WHEN type='deposit' THEN amount_usd ELSE -amount_usd END) as total_usd FROM cash_box $whereSql WHERE currency='دۆلار'";
    $stmt_usd = $pdo->prepare(str_replace('WHERE WHERE', 'WHERE', $sql_usd));
    $stmt_usd->execute($params);
    $total_usd = $stmt_usd->fetchColumn() ?: 0;
    
    // IQD
    $sql_iqd = "SELECT SUM(CASE WHEN type='deposit' THEN amount_iqd ELSE -amount_iqd END) as total_iqd FROM cash_box $whereSql WHERE currency='دینار'";
    $stmt_iqd = $pdo->prepare(str_replace('WHERE WHERE', 'WHERE', $sql_iqd));
    $stmt_iqd->execute($params);
    $total_iqd = $stmt_iqd->fetchColumn() ?: 0;
    
    // Convert IQD to USD using API rate
    $iqd_to_usd = $usd_iqd_rate > 0 ? ($total_iqd / ($usd_iqd_rate / 100)) : 0;
    $total_usd_all = round($total_usd + $iqd_to_usd, 2);
    
    echo json_encode(['success' => true, 'data' => [
        'total_usd_all' => $total_usd_all,
        'usd_iqd_rate' => $usd_iqd_rate
    ]]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 