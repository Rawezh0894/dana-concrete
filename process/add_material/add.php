<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $currency_type = $_POST['currency_type'] ?? 'دینار';
        $purchase_price_usd = $_POST['purchase_price_usd'] ?? 0;
        $purchase_price_iqd = $_POST['purchase_price_iqd'] ?? 0;
        
        if ($name !== '') {
            // Check if table exists first
            $checkTable = $pdo->query("SHOW TABLES LIKE 'list_materials'");
            if ($checkTable->rowCount() == 0) {
                throw new Exception("Table 'list_materials' does not exist");
            }
            
            $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd]);
            
            if ($result) {
                echo 'success';
            } else {
                echo 'error: insert failed';
            }
        } else {
            echo 'error: name is empty';
        }
    } else {
        echo 'error: invalid request method';
    }
} catch (Exception $e) {
    error_log("Add Material Error: " . $e->getMessage());
    echo 'error: ' . $e->getMessage();
}
