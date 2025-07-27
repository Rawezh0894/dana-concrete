<?php
// Debug file for add_material console error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type to JSON for AJAX response
header('Content-Type: application/json');

try {
    // Test database connection
    require_once 'config/db_conected.php';
    
    // Log the request
    error_log("Add Material Debug - Request received: " . json_encode($_POST));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $currency_type = $_POST['currency_type'] ?? 'دینار';
        $purchase_price_usd = $_POST['purchase_price_usd'] ?? 0;
        $purchase_price_iqd = $_POST['purchase_price_iqd'] ?? 0;
        
        // Log the data
        error_log("Add Material Debug - Data: name=$name, quantity=$quantity, currency=$currency_type, usd=$purchase_price_usd, iqd=$purchase_price_iqd");
        
        if ($name !== '') {
            // Check if table exists first
            $checkTable = $pdo->query("SHOW TABLES LIKE 'list_materials'");
            if ($checkTable->rowCount() == 0) {
                throw new Exception("Table 'list_materials' does not exist");
            }
            
            // Log table exists
            error_log("Add Material Debug - Table exists, proceeding with INSERT");
            
            $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd]);
            
            if ($result) {
                error_log("Add Material Debug - INSERT successful");
                echo json_encode(['status' => 'success', 'message' => 'کاڵا بە سەرکەوتوویی زیادکرا']);
            } else {
                error_log("Add Material Debug - INSERT failed");
                echo json_encode(['status' => 'error', 'message' => 'هەڵە لە زیادکردنی کاڵا']);
            }
        } else {
            error_log("Add Material Debug - Name is empty");
            echo json_encode(['status' => 'error', 'message' => 'ناوی کاڵا بەتاڵە']);
        }
    } else {
        error_log("Add Material Debug - Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        echo json_encode(['status' => 'error', 'message' => 'هەڵەیەک لە داواکاری هەیە']);
    }
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("Add Material Debug - Exception: " . $errorMessage);
    echo json_encode(['status' => 'error', 'message' => 'هەڵە: ' . $errorMessage]);
}
?> 