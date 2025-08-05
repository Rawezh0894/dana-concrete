<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

try {
    // Try to get rate from external API
    $api_url = 'https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    $response = file_get_contents($api_url);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['value'])) {
            echo json_encode([
                'success' => true,
                'rate' => floatval($data['value']),
                'source' => 'external_api'
            ]);
            exit;
        }
    }
    
    // Fallback to default rate
    echo json_encode([
        'success' => true,
        'rate' => 139250,
        'default_rate' => 139250,
        'source' => 'default'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'default_rate' => 139250
    ]);
}
