<?php
// c:\xampp\htdocs\dana-concrete\pages\print_all_locations_statement.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) die("Forbidden");

$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Get Company Name if selected
$company_name = "دانا کۆنکرێت";
if ($company_id) {
    $comp_stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $comp_stmt->execute([$company_id]);
    $company_name = $comp_stmt->fetchColumn() ?: "دانا کۆنکرێت";
}

// Build Query
$where = "p.location IS NOT NULL AND p.location != ''";
$params = [];

$date_label = "تەواوی کاتەکان";
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND p.date >= :from_date AND p.date <= :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
    $date_label = $from_date . " تا " . $to_date;
} elseif (!empty($from_date)) {
    $where .= " AND p.date >= :from_date";
    $params[':from_date'] = $from_date;
    $date_label = "لە " . $from_date . " بەدواوە";
} elseif (!empty($to_date)) {
    $where .= " AND p.date <= :to_date";
    $params[':to_date'] = $to_date;
    $date_label = "تا " . $to_date;
}

if ($company_id) {
    $where .= " AND p.company_id = :company_id";
    $params[':company_id'] = $company_id;
}
if ($driver_id) {
    $where .= " AND d.id = :driver_id";
    $params[':driver_id'] = $driver_id;
}
if ($material_id) {
    $where .= " AND p.material_id = :material_id";
    $params[':material_id'] = $material_id;
}

// In previous fixes for location statement, if material cost is 0 we fallback to amount_iqd - total_freight_cost_iqd
$query = "
    SELECT 
        p.location,
        p.price,
        p.amount_iqd,
        p.total_freight_cost_iqd,
        (p.kg / 1000 * p.price_per_kg_usd) AS material_cost_usd,
        (p.kg / 1000 * p.price_per_kg_iqd) AS material_cost_iqd,
        p.paid_to_location_usd,
        p.paid_to_location_iqd
    FROM purchases p
    LEFT JOIN drivers d ON p.driver = d.name
    WHERE $where
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summarize Data
$total_cost_usd_all = 0;
$total_cost_iqd_all = 0;
$total_paid_usd_all = 0;
$total_paid_iqd_all = 0;

$locations_data = [];

foreach ($purchases as $p) {
    $loc = trim($p['location']);
    if (!isset($locations_data[$loc])) {
        $locations_data[$loc] = [
            'cost_usd' => 0,
            'cost_iqd' => 0,
            'paid_usd' => 0,
            'paid_iqd' => 0,
        ];
    }
    
    $mat_cost_iqd = (float)$p['material_cost_iqd'];
    $mat_cost_usd = (float)$p['material_cost_usd'];
    
    // Fallback if price is not populated as cost
    if ($mat_cost_iqd == 0 && $p['amount_iqd'] > 0) {
        $mat_cost_iqd = (float)$p['amount_iqd'] - (float)$p['total_freight_cost_iqd'];
    }
    
    $locations_data[$loc]['cost_usd'] += $mat_cost_usd;
    $locations_data[$loc]['cost_iqd'] += $mat_cost_iqd;
    $locations_data[$loc]['paid_usd'] += (float)$p['paid_to_location_usd'];
    $locations_data[$loc]['paid_iqd'] += (float)$p['paid_to_location_iqd'];
    
    $total_cost_usd_all += $mat_cost_usd;
    $total_cost_iqd_all += $mat_cost_iqd;
    $total_paid_usd_all += (float)$p['paid_to_location_usd'];
    $total_paid_iqd_all += (float)$p['paid_to_location_iqd'];
}

// Sort locations by name
ksort($locations_data);

$remaining_usd_all = $total_cost_usd_all - $total_paid_usd_all;
$remaining_iqd_all = $total_cost_iqd_all - $total_paid_iqd_all;
$total_locations = count($locations_data);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>کەشف حیسابی گشتی شوێنەکان</title>
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Rabar21';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        
        :root {
            --primary: #1e3a8a; 
            --secondary: #3b82f6; 
            --accent: #f59e0b; 
            --text-main: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --border: #e5e7eb;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }
        body { 
            font-family: 'Rabar21', sans-serif; 
            margin: 0; padding: 0; 
            background: #eef2f6; 
            color: var(--text-main);
            -webkit-print-color-adjust: exact;
        }

        .print-page {
            width: 210mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        /* ERP Header Style */
        .erp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 4px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-title-section h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
            color: var(--primary);
            font-weight: bold;
        }

        .header-title-section h2 {
            margin: 0;
            font-size: 18px;
            color: var(--text-light);
            font-weight: normal;
        }

        .doc-details { text-align: left; }

        .doc-type-badge {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .doc-info-table { border-collapse: collapse; }
        .doc-info-table td { padding: 3px 10px; border: none; text-align: right; font-size: 14px; }
        .doc-info-table td.label { color: var(--text-light); font-weight: bold; }

        /* Filter Meta Info */
        .filter-meta {
            background: var(--bg-light);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
            border-right: 4px solid var(--accent);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .meta-group { display: flex; flex-direction: column; }
        .meta-label { font-size: 12px; color: var(--text-light); margin-bottom: 4px; }
        .meta-value { font-size: 15px; font-weight: bold; color: var(--primary); }

        /* Summary Dashboard Section */
        .summary-dashboard {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-row-1 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .kpi-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-left: 15px;
            color: white;
            flex-shrink: 0;
        }

        .kpi-details { flex-grow: 1; }
        .kpi-title { font-size: 13px; color: var(--text-light); margin-bottom: 5px; }
        .kpi-value { font-size: 18px; font-weight: bold; color: var(--text-main); }

        /* Main Table */
        .table-container { margin-bottom: 40px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: var(--primary);
            color: white;
            font-weight: bold;
            padding: 12px 10px;
            text-align: center;
            border: 1px solid var(--primary);
            font-size: 13px;
        }
        td {
            padding: 10px;
            border: 1px solid var(--border);
            text-align: center;
            vertical-align: middle;
        }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #f1f5f9; }

        .text-danger { color: var(--danger) !important; }
        .text-success { color: var(--success) !important; }

        /* Financial Balances Section */
        .financial-balances {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .balance-card {
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
        }
        .balance-header {
            background: var(--primary);
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            text-align: center;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid var(--bg-light);
            font-size: 15px;
        }
        .balance-row:last-child { border-bottom: none; }
        .balance-row.total {
            background: var(--bg-light);
            font-weight: bold;
            font-size: 16px;
        }

        .footer-signatures {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            text-align: center;
            page-break-inside: avoid;
        }
        .sig-box { 
            border-top: 1px solid var(--border); 
            padding-top: 10px; 
            font-size: 14px; 
            color: var(--text-light);
        }

        /* Print Button */
        .no-print-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover { background: #152c6b; }

        @media print {
            @page { size: A4; margin: 10mm; }
            .no-print-toolbar { display: none; }
            body { background: white; }
            .print-page { margin: 0; box-shadow: none; border: none; width: 100%; min-height: auto; padding: 0; }
            .balance-header, th, .doc-type-badge { -webkit-print-color-adjust: exact; background: var(--primary) !important; color: white !important; }
            .kpi-icon { -webkit-print-color-adjust: exact; }
            .footer-signatures {
                margin-top: 30px;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print-toolbar">
    <button class="btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> پرێنتکردنی ڕاپۆرت
    </button>
</div>

<div class="print-page">
    
    <!-- Header -->
    <div class="erp-header">
        <div class="header-title-section">
            <h1><?= htmlspecialchars($company_name) ?></h1>
            <h2>بۆ کۆنکرێتی ئامادەکراو</h2>
        </div>
        <div class="doc-details">
            <div class="doc-type-badge">کەشف حیسابی گشتی شوێنەکان</div>
            <table class="doc-info-table">
                <tr><td class="label">ڕێککەوتی چاپ:</td><td><?= date('Y-m-d H:i') ?></td></tr>
                <tr><td class="label">کۆی شوێنەکان:</td><td><?= number_format($total_locations) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Filters Meta -->
    <div class="filter-meta">
        <div class="meta-group">
            <span class="meta-label">ناوی کۆمپانیا</span>
            <span class="meta-value"><?= htmlspecialchars($company_name) ?></span>
        </div>
        <div class="meta-group">
            <span class="meta-label">ماوەی ڕاپۆرت</span>
            <span class="meta-value"><?= $date_label ?></span>
        </div>
        <div class="meta-group">
            <span class="meta-label">فلتەری زیاتر</span>
            <span class="meta-value" style="font-size: 13px;">
                <?= $driver_id ? "شۆفێر دیاریکراوە" : "هەموو شۆفێرەکان" ?> / 
                <?= $material_id ? "مەواد دیاریکراوە" : "هەموو مەوادەکان" ?>
            </span>
        </div>
    </div>

    <!-- Dashboard KPIs -->
    <div class="summary-dashboard">
        <div class="dashboard-row-1">
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--primary);"><i class="fas fa-map-marker-alt"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی گشتی شوێنەکان</div>
                    <div class="kpi-value"><?= number_format($total_locations) ?> شوێن</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--danger);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی گشتی کڕین (دینار)</div>
                    <div class="kpi-value"><?= number_format($total_cost_iqd_all) ?> د.ع</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--success);"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی پارەی پێدراو (دینار)</div>
                    <div class="kpi-value"><?= number_format($total_paid_iqd_all) ?> د.ع</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">ناوی شوێن</th>
                    <th colspan="2">کۆی کڕینەکان</th>
                    <th colspan="2">کۆی پارەی دراو</th>
                    <th colspan="2">قەرزی ماوە</th>
                </tr>
                <tr>
                    <th style="background: #2563eb;">($)</th>
                    <th style="background: #2563eb;">(د.ع)</th>
                    <th style="background: #2563eb;">($)</th>
                    <th style="background: #2563eb;">(د.ع)</th>
                    <th style="background: #2563eb;">($)</th>
                    <th style="background: #2563eb;">(د.ع)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($locations_data as $loc => $data): 
                    $rem_usd = $data['cost_usd'] - $data['paid_usd'];
                    $rem_iqd = $data['cost_iqd'] - $data['paid_iqd'];
                ?>
                <tr>
                    <td style="font-weight: bold;"><?= htmlspecialchars($loc) ?></td>
                    <td class="text-danger"><?= $data['cost_usd'] > 0 ? number_format($data['cost_usd'], 2) : '-' ?></td>
                    <td class="text-danger"><?= $data['cost_iqd'] > 0 ? number_format($data['cost_iqd']) : '-' ?></td>
                    <td class="text-success"><?= $data['paid_usd'] > 0 ? number_format($data['paid_usd'], 2) : '-' ?></td>
                    <td class="text-success"><?= $data['paid_iqd'] > 0 ? number_format($data['paid_iqd']) : '-' ?></td>
                    <td class="<?= $rem_usd > 0 ? 'text-danger' : 'text-success' ?>" style="font-weight: bold;">
                        <?= $rem_usd != 0 ? number_format(abs($rem_usd), 2) . ($rem_usd > 0 ? ' (لێمان)' : ' (لایە)') : '-' ?>
                    </td>
                    <td class="<?= $rem_iqd > 0 ? 'text-danger' : 'text-success' ?>" style="font-weight: bold;">
                        <?= $rem_iqd != 0 ? number_format(abs($rem_iqd)) . ($rem_iqd > 0 ? ' (لێمان)' : ' (لایە)') : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($locations_data)): ?>
                <tr><td colspan="7">هیچ داتایەک نەدۆزرایەوە</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Financial Balances -->
    <div class="financial-balances">
        <div class="balance-card">
            <div class="balance-header">سەرجەم حیساباتی دینار (د.ع)</div>
            <div class="balance-row">
                <span>کۆی گشتی تێچووی کڕین:</span>
                <span style="color: var(--danger); font-weight: bold;"><?= number_format($total_cost_iqd_all) ?></span>
            </div>
            <div class="balance-row">
                <span>کۆی پارەی پێدراو بۆ شوێنەکان:</span>
                <span style="color: var(--success); font-weight: bold;"><?= number_format($total_paid_iqd_all) ?></span>
            </div>
            <div class="balance-row total">
                <span>ماوە (قەرزی کۆتایی هەموویان):</span>
                <span style="color: <?= $remaining_iqd_all > 0 ? 'var(--danger)' : ($remaining_iqd_all < 0 ? 'var(--success)' : 'inherit') ?>;">
                    <?= number_format(abs($remaining_iqd_all)) ?> 
                    <?= $remaining_iqd_all > 0 ? '(شوێنەکان داواکارن)' : ($remaining_iqd_all < 0 ? '(ئێمە داواکارین)' : '') ?>
                </span>
            </div>
        </div>
        <div class="balance-card">
            <div class="balance-header" style="background: #0ea5e9;">سەرجەم حیساباتی دۆلار ($)</div>
            <div class="balance-row">
                <span>کۆی گشتی تێچووی کڕین:</span>
                <span style="color: var(--danger); font-weight: bold;"><?= number_format($total_cost_usd_all, 2) ?></span>
            </div>
            <div class="balance-row">
                <span>کۆی پارەی پێدراو بۆ شوێنەکان:</span>
                <span style="color: var(--success); font-weight: bold;"><?= number_format($total_paid_usd_all, 2) ?></span>
            </div>
            <div class="balance-row total">
                <span>ماوە (قەرزی کۆتایی هەموویان):</span>
                <span style="color: <?= $remaining_usd_all > 0 ? 'var(--danger)' : ($remaining_usd_all < 0 ? 'var(--success)' : 'inherit') ?>;">
                    <?= number_format(abs($remaining_usd_all), 2) ?> 
                    <?= $remaining_usd_all > 0 ? '(شوێنەکان داواکارن)' : ($remaining_usd_all < 0 ? '(ئێمە داواکارین)' : '') ?>
                </span>
            </div>
        </div>
    </div>

    <div class="footer-signatures">
        <div class="sig-box">ناوی ئامادەکار / ژمێریار</div>
        <div class="sig-box">مۆر و واژۆی بەڕێوەبەر</div>
    </div>
</div>

</body>
</html>
