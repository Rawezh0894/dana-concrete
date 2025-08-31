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
$company_id = $_POST['company_id'] ?? '';
$location_id = $_POST['location_id'] ?? '';
$driver_id = $_POST['driver_id'] ?? '';
$material_id = $_POST['material_id'] ?? '';
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';
$export_type = $_POST['export_type'] ?? 'detailed';

// Build WHERE clause
$where = [];
$params = [];

if ($company_id) {
    $where[] = "p.company_id = ?";
    $params[] = $company_id;
}

if ($location_id) {
    $where[] = "l.id = ?";
    $params[] = $location_id;
}

if ($driver_id) {
    $where[] = "d.id = ?";
    $params[] = $driver_id;
}

if ($material_id) {
    $where[] = "p.material_id = ?";
    $params[] = $material_id;
}

if ($from_date) {
    $where[] = "p.date >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where[] = "p.date <= ?";
    $params[] = $to_date;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Helper function to format remaining amount based on currency type
function formatRemainingAmount($row) {
    if ($row['type'] === 'دۆلار') {
        // If currency type is USD, show remaining USD amount
        $remaining = $row['remaining_usd'] ?? 0;
        return number_format($remaining, 2) . ' $';
    } else {
        // If currency type is IQD, show remaining IQD amount
        $remaining = $row['remaining_iqd'] ?? 0;
        return number_format($remaining, 0) . ' د.ع';
    }
}

// Get data
$sql = "SELECT 
    c.name AS company_name,
    l.name AS location_name,
    d.name AS driver_name,
    p.invoice_number,
    m.name AS material_name,
    p.date,
    p.payment_type,
    p.type,
    p.kg,
    p.price_per_kg_usd,
    p.price_per_kg_iqd,
    p.price,
    p.amount_iqd,
    p.exchange_rate,
    p.paid_usd,
    p.paid_iqd,
    p.remaining_usd,
    p.remaining_iqd,
    b.name AS bin_name
FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id
$where_sql
ORDER BY p.date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    
    if ($export_type === 'summary') {
        // Export summary data
        header('Content-Disposition: attachment; filename="کورتەی_کڕینەکان_' . date('Y-m-d') . '.xls"');
        
        // Get summary data
        $summary_sql = "SELECT 
            COUNT(DISTINCT p.company_id) as total_companies,
            SUM(CASE WHEN p.payment_type = 'قەرز' THEN p.remaining_usd ELSE 0 END) as total_debt,
            COUNT(DISTINCT CASE WHEN p.payment_type = 'قەرز' AND p.remaining_usd > 0 THEN p.company_id END) as indebted_companies
        FROM purchases p
        LEFT JOIN company c ON p.company_id = c.id
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        LEFT JOIN materials m ON p.material_id = m.id
        LEFT JOIN bins_silos b ON p.bin_id = b.id
        $where_sql";
        
        $summary_stmt = $pdo->prepare($summary_sql);
        $summary_stmt->execute($params);
        $summary_data = $summary_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Start Excel content for summary with proper table formatting
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 2px solid #000; padding: 15px; text-align: center; vertical-align: middle; }';
        echo 'th { background-color: #2196F3; color: white; font-weight: bold; font-size: 14px; }';
        echo 'td { background-color: #ffffff; color: #000000; font-size: 12px; }';
        echo '.number { text-align: right; font-family: "Courier New", monospace; font-weight: bold; }';
        echo '.title-row { background-color: #1976D2; font-size: 18px; }';
        echo '.date-row { background-color: #42A5F5; font-size: 14px; }';
        echo '.data-row:nth-child(even) { background-color: #f9f9f9; }';
        echo '.data-row:nth-child(odd) { background-color: #ffffff; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        
        // Summary title row
        echo '<tr class="title-row">';
        echo '<th colspan="2" style="text-align: center; padding: 20px;">کورتەی کڕینەکان</th>';
        echo '</tr>';
        
        // Date row
        echo '<tr class="date-row">';
        echo '<th colspan="2" style="text-align: center; padding: 15px;">بەرواری ڕاپۆرت: ' . date('Y-m-d') . '</th>';
        echo '</tr>';
        
        // Summary data rows
        echo '<tr class="data-row">';
        echo '<th style="text-align: center; min-width: 200px;">کۆی قەرزی ئێمە</th>';
        echo '<td class="number" style="min-width: 150px;">$' . number_format($summary_data['total_debt'] ?? 0, 2) . '</td>';
        echo '</tr>';
        
        echo '<tr class="data-row">';
        echo '<th style="text-align: center;">کۆی ژمارەی کۆمپانیاکان</th>';
        echo '<td class="number">' . number_format($summary_data['total_companies'] ?? 0, 0) . '</td>';
        echo '</tr>';
        
        echo '<tr class="data-row">';
        echo '<th style="text-align: center;">کۆمپانیاکانی قەرزدار</th>';
        echo '<td class="number">' . number_format($summary_data['indebted_companies'] ?? 0, 0) . '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        
    } else {
        // Export detailed data
        header('Content-Disposition: attachment; filename="کڕینەکان_' . date('Y-m-d') . '.xls"');
        
        // Start Excel content for detailed export with proper table formatting
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 2px solid #000; padding: 12px; text-align: center; vertical-align: middle; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; font-size: 14px; }';
        echo 'td { background-color: #ffffff; color: #000000; font-size: 12px; }';
        echo '.number { text-align: right; font-family: "Courier New", monospace; }';
        echo '.header-row { background-color: #2E7D32; }';
        echo '.data-row:nth-child(even) { background-color: #f9f9f9; }';
        echo '.data-row:nth-child(odd) { background-color: #ffffff; }';
        echo '.data-row:hover { background-color: #e8f5e8; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        
        // Table title row
        echo '<tr class="header-row">';
        echo '<th colspan="8" style="text-align: center; font-size: 18px; padding: 20px;">ڕاپۆرتی کڕینەکان</th>';
        echo '</tr>';
        
        // Date range row
        echo '<tr class="header-row">';
        echo '<th colspan="8" style="text-align: center; font-size: 14px; padding: 15px;">';
        if ($from_date && $to_date) {
            echo 'لە بەرواری: ' . $from_date . ' بۆ بەرواری: ' . $to_date;
        } elseif ($from_date) {
            echo 'لە بەرواری: ' . $from_date;
        } elseif ($to_date) {
            echo 'بۆ بەرواری: ' . $to_date;
        } else {
            echo 'هەموو بەروارەکان';
        }
        echo '</th>';
        echo '</tr>';
        
        // Header row with proper Excel table formatting
        echo '<tr class="header-row">';
        echo '<th style="min-width: 120px;">شوێن</th>';
        echo '<th style="min-width: 100px;">بەروار</th>';
        echo '<th style="min-width: 120px;">ژمارەی پسووڵە</th>';
        echo '<th style="min-width: 120px;">شۆفێر</th>';
        echo '<th style="min-width: 100px;">بڕی مەواد (کگم)</th>';
        echo '<th style="min-width: 150px;">کۆمپانیا</th>';
        echo '<th style="min-width: 120px;">جۆری مەواد</th>';
        echo '<th style="min-width: 120px;">پارەی ماوە</th>';
        echo '</tr>';
        
        // Data rows with alternating colors and proper formatting
        foreach ($data as $index => $row) {
            $rowClass = ($index % 2 == 0) ? 'data-row' : 'data-row';
            echo '<tr class="' . $rowClass . '">';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['location_name'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['date'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['driver_name'] ?? '') . '</td>';
            echo '<td class="number" style="text-align: right;">' . number_format($row['kg'] ?? 0, 0) . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['company_name'] ?? '') . '</td>';
            echo '<td style="text-align: center;">' . htmlspecialchars($row['material_name'] ?? '') . '</td>';
            echo '<td class="number" style="text-align: right;">' . formatRemainingAmount($row) . '</td>';
            echo '</tr>';
        }
        
        // Summary row
        echo '<tr class="header-row">';
        echo '<th colspan="4" style="text-align: center;">کۆی گشتی</th>';
        echo '<th class="number">' . number_format(array_sum(array_column($data, 'kg')), 0) . ' کگم</th>';
        echo '<th colspan="3" style="text-align: center;">کۆی ڕیزەکان: ' . count($data) . '</th>';
        echo '</tr>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
}
