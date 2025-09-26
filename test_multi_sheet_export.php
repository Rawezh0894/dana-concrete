<?php
// Test script for multi-sheet Excel export
echo "=== Multi-Sheet Excel Export Test ===\n";
echo "This script will test the multi-sheet Excel export functionality.\n\n";

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

// Test multi-sheet export
echo "\n3. Testing multi-sheet Excel export...\n";

// Get all table names
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "   Found " . count($tables) . " tables: " . implode(', ', $tables) . "\n";

// Create multi-sheet Excel file
$filename = "test_multi_sheet_" . date('Y-m-d_H-i-s') . ".xls";
$file_path = $export_dir . $filename;

echo "   Creating multi-sheet Excel file: $filename\n";

// Create Excel XML structure
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
$xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
$xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
$xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
$xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

// Add styles
$xml .= '<Styles>' . "\n";
$xml .= '<Style ss:ID="Header">' . "\n";
$xml .= '<Font ss:Bold="1" ss:Color="#FFFFFF"/>' . "\n";
$xml .= '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>' . "\n";
$xml .= '</Style>' . "\n";
$xml .= '</Styles>' . "\n";

$total_tables = 0;
$total_rows = 0;

foreach ($tables as $table) {
    echo "   Processing table: $table ... ";
    
    // Create worksheet for each table
    $xml .= '<Worksheet ss:Name="' . htmlspecialchars($table) . '">' . "\n";
    $xml .= '<Table>' . "\n";
    
    // Get table structure
    $stmt = $pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($columns)) {
        // Add header row
        $xml .= '<Row>' . "\n";
        foreach ($columns as $column) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($column['Field']) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Get table data (limit to 100 rows for testing)
        $stmt = $pdo->prepare("SELECT * FROM `$table` ORDER BY id DESC LIMIT 100");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $row_count = count($data);
        $total_rows += $row_count;
        
        // Add data rows
        foreach ($data as $row) {
            $xml .= '<Row>' . "\n";
            foreach ($row as $value) {
                $type = is_numeric($value) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
        
        echo "✅ $row_count rows\n";
    } else {
        echo "⚠️  No columns found\n";
    }
    
    $xml .= '</Table>' . "\n";
    $xml .= '</Worksheet>' . "\n";
    $total_tables++;
}

$xml .= '</Workbook>';

// Write to file
if (file_put_contents($file_path, $xml)) {
    $file_size = filesize($file_path);
    echo "\n   ✅ Multi-sheet Excel file created successfully!\n";
    echo "   File: $filename\n";
    echo "   Size: " . round($file_size / 1024, 2) . " KB\n";
    echo "   Tables: $total_tables\n";
    echo "   Total rows: $total_rows\n";
    
    // Show file structure
    echo "\n4. File structure:\n";
    echo "   - Excel XML format (.xls)\n";
    echo "   - Each table in separate worksheet\n";
    echo "   - Headers with blue background\n";
    echo "   - Data types preserved (String/Number)\n";
    
} else {
    echo "\n   ❌ Failed to create Excel file!\n";
}

// Test file cleanup
echo "\n5. Testing file cleanup...\n";
if (file_exists($file_path)) {
    if (unlink($file_path)) {
        echo "   ✅ Test file cleaned up successfully\n";
    } else {
        echo "   ❌ Failed to clean up test file\n";
    }
} else {
    echo "   ℹ️  No test file to clean up\n";
}

echo "\n=== Test Complete ===\n";
echo "Multi-sheet Excel export is ready!\n";
echo "Each table will be exported to a separate worksheet.\n";
?>
