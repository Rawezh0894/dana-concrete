<?php
header('Content-Type: application/json; charset=utf-8');

// Check if request method is GET (only if running via web server)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Check if cURL is available
    if (!function_exists('curl_init')) {
        throw new Exception('cURL is not available');
    }
    
    // API configuration
    $apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    $apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    $dollarId = 8; // ID for 100 USD
    
    // Build API URL
    $url = $apiUrl . '?id=' . $dollarId . '&api_token=' . $apiToken;
    
    // Initialize cURL
    $ch = curl_init();
    if ($ch === false) {
        throw new Exception('Failed to initialize cURL');
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DanaConcrete/1.0');
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        error_log("cURL error in get_usd_rate.php: " . $error);
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to fetch exchange rate',
            'default_rate' => 139250 // Default rate from the API response
        ]);
        exit;
    }
    
    // Check HTTP response code
    if ($httpCode !== 200) {
        error_log("API error in get_usd_rate.php: HTTP " . $httpCode);
        echo json_encode([
            'success' => false, 
            'error' => 'API returned error code: ' . $httpCode,
            'default_rate' => 139250 // Default rate from the API response
        ]);
        exit;
    }
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['value'])) {
        error_log("Invalid API response in get_usd_rate.php: " . $response);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid API response',
            'default_rate' => 139250 // Default rate from the API response
        ]);
        exit;
    }
    
    // Return the exchange rate
    echo json_encode([
        'success' => true,
        'rate' => $data['value'],
        'created_at' => $data['created_at'] ?? null
    ]);
    
} catch (Exception $e) {
    error_log("Exception in get_usd_rate.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage(),
        'default_rate' => 139250 // Default rate from the API response
    ]);
}
?> 