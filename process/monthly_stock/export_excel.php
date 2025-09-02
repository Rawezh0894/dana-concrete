<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if (!hasPermission('view_purchase')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

// Get filter parameters
$bin_id = $_POST['bin_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$export_type = $_POST['export_type'] ?? 'monthly_stock_history';

// Build parameters array
$params = [];
$param_types = '';

if ($bin_id) {
    $params[] = $bin_id;
    $param_types .= 'i';
} else {
    $params[] = null;
    $param_types .= 's';
}

if ($start_date) {
    $params[] = $start_date;
    $param_types .= 's';
} else {
    $params[] = null;
    $param_types .= 's';
}

if ($end_date) {
    $params[] = $end_date;
    $param_types .= 's';
} else {
    $params[] = null;
    $param_types .= 's';
}

try {
    // Call stored procedure to get data
    $stmt = $pdo->prepare("CALL GetMaterialStockHistory(?, ?, ?)");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current stock for comparison
    $current_stock_sql = "SELECT id, name, material_type, amount, total_value, average_price FROM bins_silos";
    if ($bin_id) {
        $current_stock_sql .= " WHERE id = ?";
        $current_stmt = $pdo->prepare($current_stock_sql);
        $current_stmt->execute([$bin_id]);
    } else {
        $current_stmt = $pdo->prepare($current_stock_sql);
        $current_stmt->execute();
    }
    $current_stock = $current_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="مێژووی_بڕی_مەوادەکان_' . date('Y-m-d') . '.xls"');
    
    // Start Excel content
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
    echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
    echo '.number { text-align: right; }';
    echo '.header { background-color: #2196F3; color: white; font-size: 16px; }';
    echo '.subheader { background-color: #FF9800; color: white; font-size: 14px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Header
    echo '<tr><th colspan="8" class="header">مێژووی بڕی مەوادەکان - مانگانە</th></tr>';
    echo '<tr><th colspan="8" class="subheader">بەروار: ' . date('Y-m-d') . '</th></tr>';
    
    // Column headers
    echo '<tr>';
    echo '<th>شوێن</th>';
    echo '<th>جۆری مەواد</th>';
    echo '<th>بڕ (کیلۆ)</th>';
    echo '<th>کۆی بەها (د.ع)</th>';
    echo '<th>نرخی ناوەند (د.ع)</th>';
    echo '<th>مانگ</th>';
    echo '<th>بەرواری تۆمارکردن</th>';
    echo '<th>تۆمارکراو لەلایەن</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['bin_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['material_type'] ?? '') . '</td>';
        echo '<td class="number">' . number_format($row['amount'] ?? 0, 0) . '</td>';
        echo '<td class="number">' . number_format($row['total_value'] ?? 0, 0) . '</td>';
        echo '<td class="number">' . number_format($row['average_price'] ?? 0, 2) . '</td>';
        echo '<td>' . htmlspecialchars($row['month_year'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['recorded_date'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['created_by_username'] ?? 'سیستەم') . '</td>';
        echo '</tr>';
    }
    
    // Add empty row for separation
    echo '<tr><td colspan="8">&nbsp;</td></tr>';
    
    // Current stock section
    echo '<tr><th colspan="8" class="header">بڕی ئێستای مەوادەکان</th></tr>';
    echo '<tr><th colspan="8" class="subheader">بەروار: ' . date('Y-m-d') . '</th></tr>';
    
    // Current stock headers
    echo '<tr>';
    echo '<th>شوێن</th>';
    echo '<th>جۆری مەواد</th>';
    echo '<th>بڕی ئێستا (کیلۆ)</th>';
    echo '<th>کۆی بەهای ئێستا (د.ع)</th>';
    echo '<th>نرخی ناوەندی ئێستا (د.ع)</th>';
    echo '<th colspan="3">تێبینی</th>';
    echo '</tr>';
    
    // Current stock data
    foreach ($current_stock as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['material_type'] ?? '') . '</td>';
        echo '<td class="number">' . number_format($row['amount'] ?? 0, 0) . '</td>';
        echo '<td class="number">' . number_format($row['total_value'] ?? 0, 0) . '</td>';
        echo '<td class="number">' . number_format($row['average_price'] ?? 0, 2) . '</td>';
        echo '<td colspan="3">بڕی ئێستا</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
}
?>
