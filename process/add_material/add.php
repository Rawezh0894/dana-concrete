<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../../config/db_conected.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $unit_type = $_POST['unit_type'] ?? 'دانە';
        $quantity = $_POST['quantity'] ?? 0;
        $currency_type = $_POST['currency_type'] ?? 'دینار';
        $purchase_price_usd = !empty($_POST['purchase_price_usd']) ? $_POST['purchase_price_usd'] : 0;
        $purchase_price_iqd = !empty($_POST['purchase_price_iqd']) ? $_POST['purchase_price_iqd'] : 0;
        
        // Conversion fields
        $pieces_per_carton = !empty($_POST['pieces_per_carton']) ? $_POST['pieces_per_carton'] : null;
        $buckets_per_barrel = !empty($_POST['buckets_per_barrel']) ? $_POST['buckets_per_barrel'] : null;
        $liters_per_bucket = !empty($_POST['liters_per_bucket']) ? $_POST['liters_per_bucket'] : null;
        $liters_per_barrel = !empty($_POST['liters_per_barrel']) ? $_POST['liters_per_barrel'] : null;
        
        // Calculated prices
        $price_per_piece_usd = !empty($_POST['price_per_piece_usd']) ? $_POST['price_per_piece_usd'] : 0;
        $price_per_piece_iqd = !empty($_POST['price_per_piece_iqd']) ? $_POST['price_per_piece_iqd'] : 0;
        $price_per_bucket_usd = !empty($_POST['price_per_bucket_usd']) ? $_POST['price_per_bucket_usd'] : 0;
        $price_per_bucket_iqd = !empty($_POST['price_per_bucket_iqd']) ? $_POST['price_per_bucket_iqd'] : 0;
        $price_per_liter_usd = !empty($_POST['price_per_liter_usd']) ? $_POST['price_per_liter_usd'] : 0;
        $price_per_liter_iqd = !empty($_POST['price_per_liter_iqd']) ? $_POST['price_per_liter_iqd'] : 0;
        
        if ($name !== '') {
            // Check if table exists first
            $checkTable = $pdo->query("SHOW TABLES LIKE 'list_materials'");
            if ($checkTable->rowCount() == 0) {
                throw new Exception("Table 'list_materials' does not exist");
            }
            
            $stmt = $pdo->prepare("INSERT INTO list_materials (
                name, unit_type, quantity, currency_type, purchase_price_usd, purchase_price_iqd,
                pieces_per_carton, buckets_per_barrel, liters_per_bucket, liters_per_barrel,
                price_per_piece_usd, price_per_piece_iqd, price_per_bucket_usd, price_per_bucket_iqd,
                price_per_liter_usd, price_per_liter_iqd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $name, $unit_type, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd,
                $pieces_per_carton, $buckets_per_barrel, $liters_per_bucket, $liters_per_barrel,
                $price_per_piece_usd, $price_per_piece_iqd, $price_per_bucket_usd, $price_per_bucket_iqd,
                $price_per_liter_usd, $price_per_liter_iqd
            ]);
            
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
