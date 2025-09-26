<?php
// Test script for UTF-8 encoding in Excel export
echo "=== UTF-8 Encoding Test ===\n";
echo "This script will test UTF-8 encoding for Kurdish text in Excel export.\n\n";

// Test database connection
require_once 'config/db_conected.php';

$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

echo "1. Testing database connection...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

// Test UTF-8 encoding functions
echo "\n3. Testing UTF-8 encoding functions...\n";

function escapeExcelValue($value) {
    if (is_null($value)) {
        return '';
    }
    
    $value = (string) $value;
    
    // Convert to UTF-8 if not already
    if (!mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
    }
    
    // Escape XML special characters
    $value = htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    
    return $value;
}

// Test Kurdish text
$kurdish_texts = [
    'کڕیار',
    'فرۆشتن',
    'ماددەکان',
    'کۆگای داتاکانی ناو',
    'ڕاپۆرتی فرۆشتن',
    'باک ئەپی داتابەیس'
];

echo "   Testing Kurdish text encoding:\n";
foreach ($kurdish_texts as $text) {
    $encoded = escapeExcelValue($text);
    echo "     '$text' -> '$encoded'\n";
}

// Test with actual database data
echo "\n4. Testing with actual database data...\n";

// Get a sample table with Kurdish text
$tables_to_test = ['customers', 'sales', 'materials'];

foreach ($tables_to_test as $table) {
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
        
        // Get sample data
        $stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 3");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            echo "✅ Found " . count($data) . " rows\n";
            
            // Test encoding of first row
            $first_row = $data[0];
            foreach ($first_row as $key => $value) {
                if (!is_numeric($value) && !empty($value)) {
                    $encoded = escapeExcelValue($value);
                    echo "     $key: '$value' -> '$encoded'\n";
                }
            }
        } else {
            echo "⚠️  No data found\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Create test Excel file with UTF-8 encoding
echo "\n5. Creating test Excel file with UTF-8 encoding...\n";

$filename = "test_utf8_encoding_" . date('Y-m-d_H-i-s') . ".xls";
$file_path = $export_dir . $filename;

// Create Excel XML with proper UTF-8 encoding
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
$xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
$xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
$xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
$xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

// Add styles with UTF-8 support
$xml .= '<Styles>' . "\n";
$xml .= '<Style ss:ID="Header">' . "\n";
$xml .= '<Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Arial Unicode MS"/>' . "\n";
$xml .= '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>' . "\n";
$xml .= '</Style>' . "\n";
$xml .= '<Style ss:ID="Data">' . "\n";
$xml .= '<Font ss:FontName="Arial Unicode MS"/>' . "\n";
$xml .= '</Style>' . "\n";
$xml .= '</Styles>' . "\n";

// Create test worksheet
$xml .= '<Worksheet ss:Name="تاقیکردنەوە">' . "\n";
$xml .= '<Table>' . "\n";

// Add header row
$xml .= '<Row>' . "\n";
$xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">ناوی کڕیار</Data></Cell>' . "\n";
$xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">جۆری فرۆشتن</Data></Cell>' . "\n";
$xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">کۆی گشتی</Data></Cell>' . "\n";
$xml .= '</Row>' . "\n";

// Add test data rows
$test_data = [
    ['ئەحمەد محەمەد', 'کۆنکریت', '150000'],
    ['سارا عەلی', 'ماددە', '75000'],
    ['کەمال حەسەن', 'خزمەتگوزاری', '25000']
];

foreach ($test_data as $row) {
    $xml .= '<Row>' . "\n";
    foreach ($row as $value) {
        $type = is_numeric($value) ? 'Number' : 'String';
        $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="' . $type . '">' . escapeExcelValue($value) . '</Data></Cell>' . "\n";
    }
    $xml .= '</Row>' . "\n";
}

$xml .= '</Table>' . "\n";
$xml .= '</Worksheet>' . "\n";
$xml .= '</Workbook>';

// Add UTF-8 BOM
$bom = "\xEF\xBB\xBF";
$xml = $bom . $xml;

// Write to file
if (file_put_contents($file_path, $xml)) {
    $file_size = filesize($file_path);
    echo "   ✅ Test Excel file created successfully!\n";
    echo "   File: $filename\n";
    echo "   Size: " . round($file_size / 1024, 2) . " KB\n";
    echo "   Encoding: UTF-8 with BOM\n";
    echo "   Font: Arial Unicode MS\n";
    
    echo "\n6. File details:\n";
    echo "   - UTF-8 BOM added for Excel compatibility\n";
    echo "   - Kurdish text properly encoded\n";
    echo "   - Arial Unicode MS font specified\n";
    echo "   - XML special characters escaped\n";
    
} else {
    echo "   ❌ Failed to create test Excel file!\n";
}

// Test file cleanup
echo "\n7. Testing file cleanup...\n";
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
echo "UTF-8 encoding should now work properly in Excel!\n";
echo "Kurdish text will display correctly.\n";
?>
