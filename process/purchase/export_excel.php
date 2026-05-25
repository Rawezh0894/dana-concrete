<?php
ob_start();
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/purchase_export_helper.php';

if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    } else {
        header('Location: ../../login.php');
    }
    exit;
}

if (!hasPermission('view_purchase')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

$company_id = $_POST['company_id'] ?? '';
$location_id = $_POST['location_id'] ?? '';
$driver_id = $_POST['driver_id'] ?? '';
$material_id = $_POST['material_id'] ?? '';
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';
$export_type = $_POST['export_type'] ?? 'detailed';
$export_format = ($_POST['export_format'] ?? 'excel') === 'csv' ? 'csv' : 'excel';

$where = [];
$params = [];

if ($company_id) {
    $where[] = 'p.company_id = ?';
    $params[] = $company_id;
}
if ($location_id) {
    $where[] = 'l.id = ?';
    $params[] = $location_id;
}
if ($driver_id) {
    $where[] = 'd.id = ?';
    $params[] = $driver_id;
}
if ($material_id) {
    $where[] = 'p.material_id = ?';
    $params[] = $material_id;
}
if ($from_date) {
    $where[] = 'p.date >= ?';
    $params[] = $from_date;
}
if ($to_date) {
    $where[] = 'p.date <= ?';
    $params[] = $to_date;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    if (ob_get_length()) {
        ob_clean();
    }

    if ($export_type === 'summary') {
        $filename = 'کورتەی_کڕینەکان_' . date('Y-m-d');
        purchase_export_send_headers($export_format, $filename);

        $summary_sql = "SELECT 
            COUNT(p.id) as total_invoices,
            COUNT(DISTINCT p.company_id) as total_companies,
            SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE 0 END) as total_price_usd,
            SUM(CASE WHEN p.type = 'دینار' THEN p.amount_iqd ELSE 0 END) as total_price_iqd,
            SUM(p.remaining_usd) as remaining_usd,
            SUM(p.remaining_iqd) as remaining_iqd,
            SUM(p.remaining_iqd / NULLIF(p.exchange_rate / 100, 0)) as remaining_iqd_converted
        FROM purchases p
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        $where_sql";

        $summary_stmt = $pdo->prepare($summary_sql);
        $summary_stmt->execute($params);
        $summary_data = $summary_stmt->fetch(PDO::FETCH_ASSOC);

        $total_debt_usd = (float) ($summary_data['remaining_usd'] ?? 0) + (float) ($summary_data['remaining_iqd_converted'] ?? 0);
        if ($company_id) {
            $stmt = $pdo->prepare('SELECT SUM(opening_debt_usd) as usd FROM company WHERE id = ?');
            $stmt->execute([$company_id]);
            $comp_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_debt_usd += (float) ($comp_row['usd'] ?? 0);
        } else {
            $stmt = $pdo->query('SELECT SUM(opening_debt_usd) as usd FROM company');
            $comp_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_debt_usd += (float) ($comp_row['usd'] ?? 0);
        }

        $rows = [
            ['کورتەی کڕینەکان', ''],
            ['بەروار', date('Y-m-d')],
            ['کۆی نرخ (دۆلار)', purchase_export_num((float) ($summary_data['total_price_usd'] ?? 0), 2)],
            ['کۆی نرخ (دینار)', purchase_export_num((float) ($summary_data['total_price_iqd'] ?? 0), 0)],
            ['کۆی وەسڵەکان', (string) (int) ($summary_data['total_invoices'] ?? 0)],
            ['کۆی ژمارەی کۆمپانیاکان', (string) (int) ($summary_data['total_companies'] ?? 0)],
            ['کۆی قەرزی ئێمە (دۆلار - خەمڵێنراو)', purchase_export_num($total_debt_usd, 2)],
        ];

        if ($export_format === 'csv') {
            echo "\xEF\xBB\xBF";
            foreach ($rows as $r) {
                echo purchase_export_csv_field($r[0]) . ',' . purchase_export_csv_field($r[1]) . "\n";
            }
        } else {
            purchase_export_xls_begin('Summary');
            foreach ($rows as $r) {
                echo '<tr><td>' . htmlspecialchars($r[0], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($r[1], ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            purchase_export_xls_end();
        }
        exit;
    }

    if ($export_type === 'monthly_report') {
        $filename = 'ڕاپۆرتی_مانگانەی_کڕینەکان_' . date('Y-m-d');
        purchase_export_send_headers($export_format, $filename);

        $monthly_sql = "SELECT 
            c.name AS company_name,
            d.name AS driver_name,
            DATE_FORMAT(p.date, '%Y-%m') AS month_year,
            COUNT(*) AS convoy_count,
            SUM(p.kg) AS total_kg,
            SUM(p.kg / 1000) AS total_tons,
            SUM(CASE WHEN p.payment_type = 'قەرز' THEN 
                CASE WHEN p.type = 'دۆلار' THEN p.remaining_usd ELSE p.remaining_iqd / NULLIF(p.exchange_rate / 100, 0) END
                ELSE 0 END) AS total_remaining_usd,
            SUM(CASE WHEN p.payment_type = 'قەرز' THEN 
                CASE WHEN p.type = 'دینار' THEN p.remaining_iqd ELSE p.remaining_usd * p.exchange_rate / 100 END
                ELSE 0 END) AS total_remaining_iqd
        FROM purchases p
        LEFT JOIN company c ON p.company_id = c.id
        LEFT JOIN locations l ON p.location = l.name
        LEFT JOIN drivers d ON p.driver = d.name
        $where_sql
        GROUP BY c.id, c.name, d.id, d.name, DATE_FORMAT(p.date, '%Y-%m')
        ORDER BY c.name, driver_name, month_year DESC";

        $monthly_stmt = $pdo->prepare($monthly_sql);
        $monthly_stmt->execute($params);
        $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['کۆمپانیا', 'شۆفێر', 'مانگ', 'کۆی کاروان', 'کۆی کیلۆ', 'کۆی طەن', 'پارەی ماوە (دۆلار)', 'پارەی ماوە (دینار)'];

        if ($export_format === 'csv') {
            echo "\xEF\xBB\xBF";
            echo implode(',', array_map('purchase_export_csv_field', $headers)) . "\n";
            foreach ($monthly_data as $row) {
                echo implode(',', [
                    purchase_export_csv_field((string) ($row['company_name'] ?? '')),
                    purchase_export_csv_field((string) ($row['driver_name'] ?? '')),
                    purchase_export_csv_field((string) ($row['month_year'] ?? '')),
                    purchase_export_num((float) ($row['convoy_count'] ?? 0), 0),
                    purchase_export_num((float) ($row['total_kg'] ?? 0), 0),
                    purchase_export_num((float) ($row['total_tons'] ?? 0), 2),
                    purchase_export_num((float) ($row['total_remaining_usd'] ?? 0), 2),
                    purchase_export_num((float) ($row['total_remaining_iqd'] ?? 0), 0),
                ]) . "\n";
            }
        } else {
            purchase_export_xls_begin('Monthly Report');
            echo '<tr>';
            foreach ($headers as $h) {
                echo '<th>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            echo '</tr>';
            foreach ($monthly_data as $row) {
                echo '<tr>';
                echo '<td class="text">' . htmlspecialchars((string) ($row['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text">' . htmlspecialchars((string) ($row['driver_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text">' . htmlspecialchars((string) ($row['month_year'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="num0">' . purchase_export_num((float) ($row['convoy_count'] ?? 0), 0) . '</td>';
                echo '<td class="num0">' . purchase_export_num((float) ($row['total_kg'] ?? 0), 0) . '</td>';
                echo '<td class="num">' . purchase_export_num((float) ($row['total_tons'] ?? 0), 2) . '</td>';
                echo '<td class="num">' . purchase_export_num((float) ($row['total_remaining_usd'] ?? 0), 2) . '</td>';
                echo '<td class="num0">' . purchase_export_num((float) ($row['total_remaining_iqd'] ?? 0), 0) . '</td>';
                echo '</tr>';
            }
            purchase_export_xls_end();
        }
        exit;
    }

    // Detailed purchases (default)
    $filename = 'کڕینەکان_' . date('Y-m-d');
    purchase_export_send_headers($export_format, $filename);

    $sql = purchase_export_detailed_sql() . "\n$where_sql\nORDER BY p.date ASC, p.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = purchase_get_detailed_export_columns();

    if ($export_format === 'csv') {
        purchase_export_detailed_csv($data, $columns);
    } else {
        purchase_export_detailed_xls($data, $columns);
    }
} catch (Exception $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo 'هەڵە: ' . $e->getMessage();
}
