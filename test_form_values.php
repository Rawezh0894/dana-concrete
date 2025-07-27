<?php
// Test form values handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Form Values Test</h2>";

// Simulate POST data
$_POST['name'] = 'Test Material';
$_POST['quantity'] = '10.5';
$_POST['currency_type'] = 'دینار';
$_POST['purchase_price_usd'] = ''; // Empty value
$_POST['purchase_price_iqd'] = '1000.00';

echo "<h3>Original POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Test different ways to handle empty values
echo "<h3>Testing Value Handling:</h3>";

// Method 1: Using ?? operator
$price_usd_1 = $_POST['purchase_price_usd'] ?? 0;
echo "Method 1 (??): purchase_price_usd = '$price_usd_1'<br>";

// Method 2: Using !empty()
$price_usd_2 = !empty($_POST['purchase_price_usd']) ? $_POST['purchase_price_usd'] : 0;
echo "Method 2 (!empty): purchase_price_usd = '$price_usd_2'<br>";

// Method 3: Using isset() and trim()
$price_usd_3 = (isset($_POST['purchase_price_usd']) && trim($_POST['purchase_price_usd']) !== '') ? $_POST['purchase_price_usd'] : 0;
echo "Method 3 (isset + trim): purchase_price_usd = '$price_usd_3'<br>";

// Method 4: Using filter_var
$price_usd_4 = filter_var($_POST['purchase_price_usd'], FILTER_VALIDATE_FLOAT);
if ($price_usd_4 === false) {
    $price_usd_4 = 0;
}
echo "Method 4 (filter_var): purchase_price_usd = '$price_usd_4'<br>";

// Test with actual values
echo "<h3>Final Values for Database:</h3>";
$name = $_POST['name'] ?? '';
$quantity = $_POST['quantity'] ?? 0;
$currency_type = $_POST['currency_type'] ?? 'دینار';
$purchase_price_usd = !empty($_POST['purchase_price_usd']) ? $_POST['purchase_price_usd'] : 0;
$purchase_price_iqd = !empty($_POST['purchase_price_iqd']) ? $_POST['purchase_price_iqd'] : 0;

echo "name: '$name'<br>";
echo "quantity: '$quantity'<br>";
echo "currency_type: '$currency_type'<br>";
echo "purchase_price_usd: '$purchase_price_usd'<br>";
echo "purchase_price_iqd: '$purchase_price_iqd'<br>";

// Test database connection and INSERT
echo "<h3>Testing Database INSERT:</h3>";
try {
    require_once 'config/db_conected.php';
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'list_materials'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table exists<br>";
        
        // Test INSERT with the values
        $testName = "Test Form Values " . date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO list_materials (name, quantity, currency_type, purchase_price_usd, purchase_price_iqd) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$testName, $quantity, $currency_type, $purchase_price_usd, $purchase_price_iqd]);
        
        if ($result) {
            echo "✅ INSERT successful<br>";
            
            // Clean up
            $stmt = $pdo->prepare("DELETE FROM list_materials WHERE name = ?");
            $stmt->execute([$testName]);
            echo "🧹 Test record cleaned up<br>";
        } else {
            echo "❌ INSERT failed<br>";
        }
    } else {
        echo "❌ Table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}
?> 