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

    // Write to file
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

    $excel_content = '';
    $excel_content .= "خشتە,ژمارەی ڕیزەکان,سایزی خشتە,داتاکانی نموونە\n";

    foreach ($tables as $table) {
        // Get row count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
        $stmt->execute();
        $row_count = $stmt->fetchColumn();

        // Get table size
        $stmt = $pdo->prepare("
            SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
            FROM information_schema.tables 
            WHERE table_schema = ? AND table_name = ?
        ");
        $stmt->execute([env('DB_DATABASE', 'dana_concrete_db'), $table]);
        $table_size = $stmt->fetchColumn();

        // Get sample data (first 3 rows)
        $stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 3");
        $stmt->execute();
        $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sample_text = '';
        if (!empty($sample_data)) {
            $sample_text = json_encode($sample_data, JSON_UNESCAPED_UNICODE);
            $sample_text = str_replace(["\n", "\r"], " ", $sample_text);
        }

        $excel_content .= "$table,$row_count,{$table_size} MB," . escapeCsvValue($sample_text) . "\n";
    }

    return $excel_content;
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
            return "export_all_tables_{$timestamp}.csv";
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