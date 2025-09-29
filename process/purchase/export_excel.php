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
$export_format = $_POST['export_format'] ?? 'excel'; // 'excel' or 'csv'

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

// Helper function to format remaining amount based on currency type (without currency symbols)
function formatRemainingAmount($row) {
    if ($row['type'] === 'دۆلار') {
        // If currency type is USD, show remaining USD amount
        $remaining = $row['remaining_usd'] ?? 0;
        return number_format($remaining, 2);
    } else {
        // If currency type is IQD, show remaining IQD amount
        $remaining = $row['remaining_iqd'] ?? 0;
        return number_format($remaining, 0);
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
    
    // Set headers based on export format
    if ($export_format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Transfer-Encoding: binary');
    } else {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Transfer-Encoding: binary');
    }
    
    if ($export_type === 'summary') {
        // Export summary data
        if ($export_format === 'csv') {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'کورتەی_کڕینەکان_' . date('Y-m-d') . '.csv');
        } else {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'کورتەی_کڕینەکان_' . date('Y-m-d') . '.xls');
        }
        
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
        
        if ($export_format === 'csv') {
            // CSV export for summary
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo "کورتەی کڕینەکان\n";
            echo "بەروار," . date('Y-m-d') . "\n";
            echo "کۆی قەرزی ئێمە," . number_format($summary_data['total_debt'] ?? 0, 2) . "\n";
            echo "کۆی ژمارەی کۆمپانیاکان," . number_format($summary_data['total_companies'] ?? 0, 0) . "\n";
            echo "کۆمپانیاکانی قەرزدار," . number_format($summary_data['indebted_companies'] ?? 0, 0) . "\n";
        } else {
            // Start Excel content for summary with UTF-8 BOM
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
        // Summary header
        echo '<tr><th colspan="2" style="background-color: #2196F3; color: white; font-size: 16px;">کورتەی کڕینەکان</th></tr>';
        echo '<tr><th>بەروار</th><th>' . date('Y-m-d') . '</th></tr>';
        echo '<tr><th>کۆی قەرزی ئێمە</th><td class="number">' . number_format($summary_data['total_debt'] ?? 0, 2) . '</td></tr>';
        echo '<tr><th>کۆی ژمارەی کۆمپانیاکان</th><td class="number">' . number_format($summary_data['total_companies'] ?? 0, 0) . '</td></tr>';
        echo '<tr><th>کۆمپانیاکانی قەرزدار</th><td class="number">' . number_format($summary_data['indebted_companies'] ?? 0, 0) . '</td></tr>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        }
        
    } elseif ($export_type === 'monthly_report') {
        // Export monthly company report
        if ($export_format === 'csv') {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'ڕاپۆرتی_مانگانەی_کڕینەکان_و_شۆفێرەکان_' . date('Y-m-d') . '.csv');
        } else {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'ڕاپۆرتی_مانگانەی_کڕینەکان_و_شۆفێرەکان_' . date('Y-m-d') . '.xls');
        }
        
        // Get monthly company and driver report data
        $monthly_sql = "SELECT 
            c.name AS company_name,
            d.name AS driver_name,
            DATE_FORMAT(p.date, '%Y-%m') AS month_year,
            COUNT(*) AS convoy_count,
            SUM(p.kg) AS total_kg,
            SUM(p.kg / 1000) AS total_tons,
            SUM(CASE WHEN p.payment_type = 'قەرز' THEN 
                CASE WHEN p.type = 'دۆلار' THEN p.remaining_usd ELSE p.remaining_iqd / p.exchange_rate * 100 END
                ELSE 0 
            END) AS total_remaining_usd,
            SUM(CASE WHEN p.payment_type = 'قەرز' THEN 
                CASE WHEN p.type = 'دینار' THEN p.remaining_iqd ELSE p.remaining_usd * p.exchange_rate / 100 END
                ELSE 0 
            END) AS total_remaining_iqd
        FROM purchases p
        LEFT JOIN company c ON p.company_id = c.id
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        LEFT JOIN materials m ON p.material_id = m.id
        LEFT JOIN bins_silos b ON p.bin_id = b.id
        $where_sql
        GROUP BY c.id, c.name, d.id, d.name, DATE_FORMAT(p.date, '%Y-%m')
        ORDER BY c.name, driver_name, month_year DESC";
        
        $monthly_stmt = $pdo->prepare($monthly_sql);
        $monthly_stmt->execute($params);
        $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($export_format === 'csv') {
            // CSV export for monthly report
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo "ڕاپۆرتی مانگانەی کڕینەکان\n";
            echo "بەروار," . date('Y-m-d') . "\n";
            echo "کۆمپانیا,شۆفێر,مانگ,کۆی ژمارەی کاروانەکان,کۆی کیلۆ,کۆی طەن,پارەی ماوە (دۆلار),پارەی ماوە (دینار)\n";
            
            foreach ($monthly_data as $row) {
                echo '"' . ($row['company_name'] ?? '') . '",';
                echo '"' . ($row['driver_name'] ?? '') . '",';
                echo '"' . ($row['month_year'] ?? '') . '",';
                echo number_format($row['convoy_count'] ?? 0, 0) . ',';
                echo number_format($row['total_kg'] ?? 0, 0) . ',';
                echo number_format($row['total_tons'] ?? 0, 2) . ',';
                echo number_format($row['total_remaining_usd'] ?? 0, 2) . ',';
                echo number_format($row['total_remaining_iqd'] ?? 0, 0) . "\n";
            }
        } else {
            // Start Excel content for monthly report with UTF-8 BOM
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
        // Monthly report header
        echo '<tr><th colspan="8" style="background-color: #2196F3; color: white; font-size: 16px;">ڕاپۆرتی مانگانەی کڕینەکان</th></tr>';
        echo '<tr><th colspan="8" style="background-color: #FF9800; color: white; font-size: 14px;">بەروار: ' . date('Y-m-d') . '</th></tr>';
        
        // Column headers
        echo '<tr>';
        echo '<th>کۆمپانیا</th>';
        echo '<th>شۆفێر</th>';
        echo '<th>مانگ</th>';
        echo '<th>کۆی ژمارەی کاروانەکان</th>';
        echo '<th>کۆی کیلۆ</th>';
        echo '<th>کۆی طەن</th>';
        echo '<th>پارەی ماوە (دۆلار)</th>';
        echo '<th>پارەی ماوە (دینار)</th>';
        echo '</tr>';
        
        // Data rows
        foreach ($monthly_data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['company_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['driver_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['month_year'] ?? '') . '</td>';
            echo '<td class="number">' . number_format($row['convoy_count'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['total_kg'] ?? 0, 0) . '</td>';
            echo '<td class="number">' . number_format($row['total_tons'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['total_remaining_usd'] ?? 0, 2) . '</td>';
            echo '<td class="number">' . number_format($row['total_remaining_iqd'] ?? 0, 0) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        }
        
    } else {
        // Export detailed data
        if ($export_format === 'csv') {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'کڕینەکان_' . date('Y-m-d') . '.csv');
        } else {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'کڕینەکان_' . date('Y-m-d') . '.xls');
        }
        
        if ($export_format === 'csv') {
            // CSV export for detailed data
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo "شوێن,بەروار,ژ. پسوله,شؤفير,بڕی مەواد,مکان مشتريات,نوع مشتريات,پارەی ماوە\n";
            
            foreach ($data as $index => $row) {
                echo '"' . ($row['location_name'] ?? '') . '",';
                echo '"' . ($row['date'] ?? '') . '",';
                echo '"' . ($row['invoice_number'] ?? '') . '",';
                echo '"' . ($row['driver_name'] ?? '') . '",';
                echo number_format($row['kg'] ?? 0, 0) . ',';
                echo '"' . ($row['company_name'] ?? '') . '",';
                echo '"' . ($row['material_name'] ?? '') . '",';
                echo formatRemainingAmount($row) . "\n";
            }
        } else {
            // Start Excel content for detailed export with UTF-8 BOM
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo '<!DOCTYPE html>';
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.number { text-align: right; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table border="1">';
        
                // Header row - Columns ordered from right to left as requested
        echo '<tr>';
        echo '<th>شوێن</th>'; // Location (rightmost)
        echo '<th>بەروار</th>'; // Date
        echo '<th>ژ. پسوله</th>'; // Receipt No.
        echo '<th>شؤفير</th>'; // Driver
        echo '<th>بڕی مەواد</th>'; // Material Amount
        echo '<th>مکان مشتريات</th>'; // Purchase Location (Companies)
        echo '<th>نوع مشتريات</th>'; // Purchase Type
        echo '<th>پارەی ماوە</th>'; // Remaining Amount (leftmost)
        echo '</tr>';
        
        // Data rows - Columns ordered from right to left as requested
        foreach ($data as $index => $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['location_name'] ?? '') . '</td>'; // Location (rightmost)
            echo '<td>' . htmlspecialchars($row['date'] ?? '') . '</td>'; // Date
            echo '<td>' . htmlspecialchars($row['invoice_number'] ?? '') . '</td>'; // Receipt Number
            echo '<td>' . htmlspecialchars($row['driver_name'] ?? '') . '</td>'; // Driver Name
            echo '<td class="number">' . number_format($row['kg'] ?? 0, 0) . '</td>'; // Material Amount in KG
            echo '<td>' . htmlspecialchars($row['company_name'] ?? '') . '</td>'; // Company name (مکان مشتريات)
            echo '<td>' . htmlspecialchars($row['material_name'] ?? '') . '</td>'; // Material name (نوع مشتريات)
            echo '<td class="number">' . formatRemainingAmount($row) . '</td>'; // Remaining Amount (leftmost)
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        }
    }
    
} catch (Exception $e) {
    echo 'هەڵە: ' . $e->getMessage();
}
