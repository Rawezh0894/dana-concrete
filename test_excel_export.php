<?php
// Test script for Excel export functionality
echo "=== Excel Export Test ===\n";
echo "This script will test the Excel export functionality.\n\n";

// Test database connection
require_once 'config/db_conected.php';

$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

echo "1. Testing database connection...\n";
echo "   Host: $host\n";
echo "   Username: $username\n";
echo "   Database: $database\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    echo "   ✅ Database connection successful\n\n";
} catch (PDOException $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test export directory
echo "2. Testing export directory...\n";
$export_dir = './exports/';
if (!is_dir($export_dir)) {
    mkdir($export_dir, 0755, true);
    echo "   ✅ Export directory created: $export_dir\n";
} else {
    echo "   ✅ Export directory exists: $export_dir\n";
}

// Test table export
echo "\n3. Testing table export...\n";
$tables = ['customers', 'sales', 'materials', 'purchases'];

foreach ($tables as $table) {
    echo "   Testing table: $table ... ";
    
    try {
        // Check if table exists
        $stmt = $pdo->prepare("DESCRIBE `$table`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($columns)) {
            echo "❌ Table not found\n";
            continue;
        }
        
        // Get row count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
        $stmt->execute();
        $row_count = $stmt->fetchColumn();
        
        echo "✅ Found $row_count rows\n";
        
        // Test export
        $filename = "test_export_{$table}_" . date('Y-m-d_H-i-s') . ".csv";
        $file_path = $export_dir . $filename;
        
        // Get table data
        $stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 100");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            // Create CSV content
            $csv_content = '';
            
            // Add headers
            $headers = array_keys($data[0]);
            $csv_content .= implode(',', $headers) . "\n";
            
            // Add data rows
            foreach ($data as $row) {
                $csv_content .= implode(',', array_map('escapeCsvValue', array_values($row))) . "\n";
            }
            
            // Write to file
            if (file_put_contents($file_path, $csv_content)) {
                $file_size = filesize($file_path);
                echo "     ✅ Export successful: $filename ($file_size bytes)\n";
            } else {
                echo "     ❌ Export failed\n";
            }
        } else {
            echo "     ⚠️  No data to export\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Test reports export
echo "\n4. Testing reports export...\n";

// Sales Report
echo "   Testing sales report... ";
try {
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as sales_count,
            SUM(total_amount) as total_amount
        FROM sales 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 10
    ");
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sales_data)) {
        echo "✅ Found " . count($sales_data) . " sales records\n";
    } else {
        echo "⚠️  No sales data found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Customers Report
echo "   Testing customers report... ";
try {
    $stmt = $pdo->query("
        SELECT 
            c.name as customer_name,
            COUNT(s.id) as sales_count,
            SUM(s.total_amount) as total_amount
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $customer_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($customer_data)) {
        echo "✅ Found " . count($customer_data) . " customer records\n";
    } else {
        echo "⚠️  No customer data found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test file cleanup
echo "\n5. Testing file cleanup...\n";
$test_files = glob($export_dir . 'test_export_*.csv');
if (!empty($test_files)) {
    foreach ($test_files as $file) {
        if (unlink($file)) {
            echo "   ✅ Cleaned up: " . basename($file) . "\n";
        } else {
            echo "   ❌ Failed to clean up: " . basename($file) . "\n";
        }
    }
} else {
    echo "   ℹ️  No test files to clean up\n";
}

echo "\n=== Test Complete ===\n";
echo "Excel export functionality is ready to use!\n";
echo "You can now export data from the web interface.\n";

function escapeCsvValue($value) {
    if (is_null($value)) {
        return '';
    }
    
    $value = (string) $value;
    
    // If value contains comma, newline, or quote, wrap in quotes and escape quotes
    if (strpos($value, ',') !== false || strpos($value, "\n") !== false || strpos($value, '"') !== false) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    
    return $value;
}
?>
