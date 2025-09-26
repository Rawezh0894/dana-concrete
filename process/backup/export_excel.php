<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دەبێت سەرەتا چوونەژوورەوە بکەیت']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$table_name = $input['table_name'] ?? '';
$export_type = $input['export_type'] ?? 'table';

try {
    // Get database configuration
    $host = env('DB_HOST', 'localhost');
    $username = env('DB_USERNAME', 'dana_user');
    $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
    $database = env('DB_DATABASE', 'dana_concrete_db');

    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'export_excel') {
        // Generate Excel file
        $excel_file = generateExcelFile($pdo, $export_type, $table_name);
        
        if ($excel_file) {
            echo json_encode([
                'success' => true,
                'message' => 'فایلەکەی Excel بە سەرکەوتوویی دروستکرا',
                'file_path' => $excel_file,
                'filename' => basename($excel_file)
            ]);
        } else {
            throw new Exception('هیچ داتایەک نەدۆزرایەوە بۆ export');
        }
    } else {
        throw new Exception('کرداری نەدراوە');
    }

} catch (Exception $e) {
    error_log("Excel export error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generateExcelFile($pdo, $export_type, $table_name) {
    $export_dir = '../../exports/';
    if (!file_exists($export_dir)) {
        mkdir($export_dir, 0755, true);
    }

    $filename = generateFilename($export_type, $table_name);
    $file_path = $export_dir . $filename;

    // Create Excel content
    $excel_content = createExcelContent($pdo, $export_type, $table_name);
    
    if (empty($excel_content)) {
        return false;
    }

    // Add UTF-8 BOM for proper Excel encoding
    $bom = "\xEF\xBB\xBF";
    $excel_content = $bom . $excel_content;

    // Write to file with UTF-8 encoding
    file_put_contents($file_path, $excel_content);
    
    return $file_path;
}

function createExcelContent($pdo, $export_type, $table_name) {
    switch ($export_type) {
        case 'table':
            return createTableExcel($pdo, $table_name);
        case 'all_tables':
            return createAllTablesExcel($pdo);
        case 'sales_report':
            return createSalesReportExcel($pdo);
        case 'customers_report':
            return createCustomersReportExcel($pdo);
        case 'materials_report':
            return createMaterialsReportExcel($pdo);
        default:
            throw new Exception('جۆری export نەدراوە');
    }
}

function createTableExcel($pdo, $table_name) {
    if (empty($table_name)) {
        throw new Exception('ناوی خشتە نەدراوە');
    }

    // Get table structure
    $stmt = $pdo->prepare("DESCRIBE `$table_name`");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($columns)) {
        throw new Exception("خشتەی '$table_name' نەدۆزرایەوە");
    }

    // Get table data
    $stmt = $pdo->prepare("SELECT * FROM `$table_name` ORDER BY id DESC LIMIT 5000");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create Excel content (CSV format for simplicity)
    $excel_content = '';
    
    // Add headers
    $headers = array_column($columns, 'Field');
    $excel_content .= implode(',', $headers) . "\n";

    // Add data rows
    foreach ($data as $row) {
        $excel_content .= implode(',', array_map('escapeCsvValue', array_values($row))) . "\n";
    }

    return $excel_content;
}

function createAllTablesExcel($pdo) {
    // Get all table names
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Create a multi-sheet Excel-like structure using XML format
    $excel_content = createMultiSheetExcel($pdo, $tables);
    
    return $excel_content;
}

function createMultiSheetExcel($pdo, $tables) {
    // Create Excel XML structure with proper UTF-8 encoding
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
    
    foreach ($tables as $table) {
        // Create worksheet for each table
        $xml .= '<Worksheet ss:Name="' . escapeExcelValue($table) . '">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Get table structure
        $stmt = $pdo->prepare("DESCRIBE `$table`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($columns)) {
            // Add header row
            $xml .= '<Row>' . "\n";
            foreach ($columns as $column) {
                $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . escapeExcelValue($column['Field']) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
            
            // Get table data (limit to 1000 rows per table for performance)
            $stmt = $pdo->prepare("SELECT * FROM `$table` ORDER BY id DESC LIMIT 1000");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add data rows
            foreach ($data as $row) {
                $xml .= '<Row>' . "\n";
                foreach ($row as $value) {
                    $type = is_numeric($value) ? 'Number' : 'String';
                    $xml .= '<Cell ss:StyleID="Data"><Data ss:Type="' . $type . '">' . escapeExcelValue($value) . '</Data></Cell>' . "\n";
                }
                $xml .= '</Row>' . "\n";
            }
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
    }
    
    $xml .= '</Workbook>';
    
    return $xml;
}

function createSalesReportExcel($pdo) {
    $excel_content = '';
    $excel_content .= "=== ڕاپۆرتی فرۆشتن ===\n";
    $excel_content .= "ڕۆژ,ژمارەی فرۆشتن,کۆی گشتی,کڕیار\n";

    $stmt = $pdo->query("
        SELECT 
            DATE(s.created_at) as date,
            COUNT(s.id) as sales_count,
            SUM(s.total_amount) as total_amount,
            GROUP_CONCAT(DISTINCT c.name) as customers
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(s.created_at)
        ORDER BY date DESC
    ");
    $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sales_data as $row) {
        $excel_content .= "{$row['date']},{$row['sales_count']},{$row['total_amount']}," . escapeCsvValue($row['customers']) . "\n";
    }

    return $excel_content;
}

function createCustomersReportExcel($pdo) {
    $excel_content = '';
    $excel_content .= "=== ڕاپۆرتی کڕیاران ===\n";
    $excel_content .= "ناوی کڕیار,ژمارەی فرۆشتن,کۆی گشتی,دوایین فرۆشتن\n";

    $stmt = $pdo->query("
        SELECT 
            c.name as customer_name,
            COUNT(s.id) as sales_count,
            SUM(s.total_amount) as total_amount,
            MAX(s.created_at) as last_sale
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC
        LIMIT 100
    ");
    $customer_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($customer_data as $row) {
        $excel_content .= escapeCsvValue($row['customer_name']) . ",{$row['sales_count']},{$row['total_amount']},{$row['last_sale']}\n";
    }

    return $excel_content;
}

function createMaterialsReportExcel($pdo) {
    $excel_content = '';
    $excel_content .= "=== ڕاپۆرتی ماددەکان ===\n";
    $excel_content .= "ناوی ماددە,کۆی کڕین,کۆی فرۆشتن,ئێستا لە کۆگادا\n";

    $stmt = $pdo->query("
        SELECT 
            m.name as material_name,
            COALESCE(SUM(p.quantity), 0) as total_purchased,
            COALESCE(SUM(s.quantity), 0) as total_sold,
            COALESCE(SUM(p.quantity), 0) - COALESCE(SUM(s.quantity), 0) as current_stock
        FROM materials m
        LEFT JOIN purchases p ON m.id = p.material_id
        LEFT JOIN sales s ON m.id = s.material_id
        GROUP BY m.id, m.name
        ORDER BY current_stock DESC
    ");
    $material_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($material_data as $row) {
        $excel_content .= escapeCsvValue($row['material_name']) . ",{$row['total_purchased']},{$row['total_sold']},{$row['current_stock']}\n";
    }

    return $excel_content;
}

function generateFilename($export_type, $table_name = '') {
    $timestamp = date('Y-m-d_H-i-s');
    
    switch ($export_type) {
        case 'table':
            return "export_{$table_name}_{$timestamp}.csv";
        case 'all_tables':
            return "export_all_tables_{$timestamp}.xls";
        case 'sales_report':
            return "export_sales_report_{$timestamp}.csv";
        case 'customers_report':
            return "export_customers_report_{$timestamp}.csv";
        case 'materials_report':
            return "export_materials_report_{$timestamp}.csv";
        default:
            return "export_{$timestamp}.csv";
    }
}

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