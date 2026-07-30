<?php
// c:\xampp\htdocs\dana-concrete\pages\print_location_statement.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) die("Forbidden");

$location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (!$location_id) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px; font-size:20px; color:red;'>تکایە سەرەتا لە بەشی فلتەرەکان شوێنێک هەڵبژێرە، پاشان کرتە لە دوگمەی 'کەشف حیسابی شوێن' بکە.</div>");
}

// Get Location Name
$loc_stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
$loc_stmt->execute([$location_id]);
$location_name = $loc_stmt->fetchColumn();

if (!$location_name) die("شوێن نەدۆزرایەوە");

// Get Company Name if selected
$company_name = "هەموو کۆمپانیاکان";
if ($company_id) {
    $comp_stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $comp_stmt->execute([$company_id]);
    $company_name = $comp_stmt->fetchColumn() ?: "هەموو کۆمپانیاکان";
}

// Build Query
$where = "l.id = :location_id";
$params = [':location_id' => $location_id];

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

$query = "
    SELECT 
        p.id, p.date, p.invoice_number, p.driver, p.kg, p.type,
        p.price, p.amount_iqd, p.total_freight_cost_usd, p.total_freight_cost_iqd, 
        p.paid_to_location_usd, p.paid_to_location_iqd, 
        (p.kg / 1000 * p.price_per_kg_iqd) AS material_cost_iqd,
        (p.kg / 1000 * p.price_per_kg_usd) AS material_cost_usd,
        m.name AS material_name,
        c.name AS company_name
    FROM purchases p
    LEFT JOIN locations l ON p.location = l.name
    LEFT JOIN company c ON p.company_id = c.id
    LEFT JOIN materials m ON p.material_id = m.id
    LEFT JOIN drivers d ON p.driver = d.name
    WHERE $where
    ORDER BY p.date ASC, p.id ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summarize Data
$total_kg = 0;
$total_cost_usd = 0;
$total_cost_iqd = 0;
$total_paid_usd = 0;
$total_paid_iqd = 0;

$total_invoices = count($transactions);
$material_counts = []; // total invoices per material
$summary_groups = []; // grouped by material and unit price

foreach ($transactions as $t) {
    $mat = $t['material_name'] ?: 'بێ ناو';
    $mat_cost_iqd = (float)$t['material_cost_iqd'];
    $mat_cost_usd = (float)$t['material_cost_usd'];
    
    // Fallback if price is not populated as cost
    if ($mat_cost_iqd == 0 && $t['amount_iqd'] > 0) {
        // If material cost is 0 but amount exists (legacy data), maybe just use amount minus freight
        $mat_cost_iqd = (float)$t['amount_iqd'] - (float)$t['total_freight_cost_iqd'];
    }
    
    $total_kg += (float)$t['kg'];
    $total_cost_usd += $mat_cost_usd;
    $total_cost_iqd += $mat_cost_iqd;
    $total_paid_usd += (float)$t['paid_to_location_usd'];
    $total_paid_iqd += (float)$t['paid_to_location_iqd'];
    
    // Material Invoice Counts
    if (!isset($material_counts[$mat])) {
        $material_counts[$mat] = 0;
    }
    $material_counts[$mat]++;
    
    // Grouping by Material and Price
    if (!isset($summary_groups[$mat])) {
        $summary_groups[$mat] = ['iqd' => [], 'usd' => []];
    }
    
    if ($mat_cost_iqd > 0) {
        $key = (string)$mat_cost_iqd;
        if (!isset($summary_groups[$mat]['iqd'][$key])) $summary_groups[$mat]['iqd'][$key] = 0;
        $summary_groups[$mat]['iqd'][$key]++;
    }
    if ($mat_cost_usd > 0) {
        $key = (string)$mat_cost_usd;
        if (!isset($summary_groups[$mat]['usd'][$key])) $summary_groups[$mat]['usd'][$key] = 0;
        $summary_groups[$mat]['usd'][$key]++;
    }
}

$remaining_usd = $total_cost_usd - $total_paid_usd;
$remaining_iqd = $total_cost_iqd - $total_paid_iqd;

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>کەشف حیسابی شوێن - <?= htmlspecialchars($location_name) ?></title>
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Rabar21';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        
        :root {
            --primary: #1e3a8a; /* Deep blue ERP primary */
            --secondary: #3b82f6; /* Lighter blue */
            --accent: #f59e0b; /* Amber */
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
            min-height: 297mm;
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

        .doc-details {
            text-align: left;
        }

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

        .doc-info-table {
            border-collapse: collapse;
        }
        .doc-info-table td {
            padding: 3px 10px;
            border: none;
            text-align: right;
            font-size: 14px;
        }
        .doc-info-table td.label {
            color: var(--text-light);
            font-weight: bold;
        }

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
        .meta-group {
            display: flex;
            flex-direction: column;
        }
        .meta-label {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 4px;
        }
        .meta-value {
            font-size: 15px;
            font-weight: bold;
            color: var(--primary);
        }

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

        /* The Detailed Grouping Section Requested by User */
        .material-groups-section {
            background: white;
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .section-header {
            background: var(--bg-light);
            padding: 10px 15px;
            border-bottom: 1px solid var(--border);
            font-weight: bold;
            color: var(--primary);
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .section-header i { margin-left: 8px; }

        .groups-content {
            padding: 15px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .material-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 12px;
            border-radius: 6px;
        }
        .material-box-title {
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .group-item {
            font-size: 14px;
            padding: 6px 0;
            display: flex;
            align-items: center;
        }
        .group-item::before {
            content: "•";
            color: var(--accent);
            font-size: 18px;
            margin-left: 8px;
        }
        .group-highlight {
            font-weight: bold;
            color: var(--danger);
            margin: 0 4px;
        }

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

        /* Main Table */
        .table-container { margin-bottom: 40px; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th {
            background: var(--primary);
            color: white;
            font-weight: normal;
            padding: 10px 8px;
            text-align: center;
            border: 1px solid var(--primary);
        }
        td {
            padding: 8px;
            border: 1px solid var(--border);
            text-align: center;
        }
        tr:nth-child(even) { background: #f8fafc; }
        tr:hover { background: #f1f5f9; }

        .footer-signatures {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            .no-print-toolbar { display: none; }
            body { background: white; }
            .print-page { margin: 0; box-shadow: none; border: none; width: 100%; padding: 0; }
            .balance-header { -webkit-print-color-adjust: exact; background: var(--primary) !important; color: white !important; }
            th { -webkit-print-color-adjust: exact; background: var(--primary) !important; color: white !important; }
            .doc-type-badge { -webkit-print-color-adjust: exact; background: var(--primary) !important; color: white !important; }
            .kpi-icon { -webkit-print-color-adjust: exact; }
            .section-header { -webkit-print-color-adjust: exact; }
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
            <h2>سیستەمی بەڕێوەبردنی کڕین و فرۆشتن</h2>
        </div>
        <div class="doc-details">
            <div class="doc-type-badge">کەشف حیسابی شوێن</div>
            <table class="doc-info-table">
                <tr><td class="label">ڕێککەوتی چاپ:</td><td><?= date('Y-m-d H:i') ?></td></tr>
                <tr><td class="label">کۆی مامەڵەکان:</td><td><?= number_format($total_invoices) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Filters Meta -->
    <div class="filter-meta">
        <div class="meta-group">
            <span class="meta-label">ناوی شوێن</span>
            <span class="meta-value"><?= htmlspecialchars($location_name) ?></span>
        </div>
        <div class="meta-group">
            <span class="meta-label">ماوەی ڕاپۆرت</span>
            <span class="meta-value"><?= $date_label ?></span>
        </div>
        <div class="meta-group">
            <span class="meta-label">شۆفێر / مەواد</span>
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
                <div class="kpi-icon" style="background: var(--primary);"><i class="fas fa-file-invoice"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی گشتی پسووڵەکان</div>
                    <div class="kpi-value"><?= number_format($total_invoices) ?> پسووڵە</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--accent);"><i class="fas fa-weight-hanging"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی کێشی گواستراوە</div>
                    <div class="kpi-value"><?= number_format($total_kg) ?> کگم</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background: var(--danger);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="kpi-details">
                    <div class="kpi-title">کۆی تێچووی کڕین (دینار)</div>
                    <div class="kpi-value"><?= number_format($total_cost_iqd) ?> د.ع</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grouped Materials Section (As requested by user) -->
    <div class="material-groups-section">
        <div class="section-header">
            <i class="fas fa-layer-group"></i> پوختەی کڕینەکان بەپێی جۆر و نرخ (بۆ شوێنەکە)
        </div>
        <div class="groups-content" style="padding: 20px; font-size: 17px; line-height: 2; font-weight: bold; color: var(--primary); text-align: right;">
            <?php if (empty($summary_groups)): ?>
                <div style="color: var(--text-light); text-align: center; font-weight: normal;">هیچ داتایەک نییە بۆ نیشاندان</div>
            <?php else: ?>
                <?php
                $sentences = [];
                foreach ($summary_groups as $mat_name => $currencies) {
                    foreach ($currencies['iqd'] as $price => $count) {
                        $sentences[] = "{$count} {$mat_name} بردراوە بە " . number_format((float)$price);
                    }
                    foreach ($currencies['usd'] as $price => $count) {
                        $sentences[] = "{$count} {$mat_name} بردراوە بە " . number_format((float)$price, 2) . " $";
                    }
                }
                echo implode(' ، ', $sentences);
                ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Financial Balances -->
    <div class="financial-balances">
        <div class="balance-card">
            <div class="balance-header">حیساباتی دینار (د.ع)</div>
            <div class="balance-row">
                <span>کۆی گشتی تێچووی کڕین:</span>
                <span style="color: var(--danger); font-weight: bold;"><?= number_format($total_cost_iqd) ?></span>
            </div>
            <div class="balance-row">
                <span>کۆی پارەی پێدراو بۆ شوێن:</span>
                <span style="color: var(--success); font-weight: bold;"><?= number_format($total_paid_iqd) ?></span>
            </div>
            <div class="balance-row total">
                <span>ماوە (قەرزی کۆتایی):</span>
                <span style="color: <?= $remaining_iqd > 0 ? 'var(--danger)' : ($remaining_iqd < 0 ? 'var(--success)' : 'inherit') ?>;">
                    <?= number_format(abs($remaining_iqd)) ?> 
                    <?= $remaining_iqd > 0 ? '(شوێن قەرزدارە)' : ($remaining_iqd < 0 ? '(شوێن داواکارە)' : '') ?>
                </span>
            </div>
        </div>
        <div class="balance-card">
            <div class="balance-header" style="background: #0ea5e9;">حیساباتی دۆلار ($)</div>
            <div class="balance-row">
                <span>کۆی گشتی تێچووی کڕین:</span>
                <span style="color: var(--danger); font-weight: bold;"><?= number_format($total_cost_usd, 2) ?></span>
            </div>
            <div class="balance-row">
                <span>کۆی پارەی پێدراو بۆ شوێن:</span>
                <span style="color: var(--success); font-weight: bold;"><?= number_format($total_paid_usd, 2) ?></span>
            </div>
            <div class="balance-row total">
                <span>ماوە (قەرزی کۆتایی):</span>
                <span style="color: <?= $remaining_usd > 0 ? 'var(--danger)' : ($remaining_usd < 0 ? 'var(--success)' : 'inherit') ?>;">
                    <?= number_format(abs($remaining_usd), 2) ?> 
                    <?= $remaining_usd > 0 ? '(شوێن قەرزدارە)' : ($remaining_usd < 0 ? '(شوێن داواکارە)' : '') ?>
                </span>
            </div>
        </div>
    </div>



    <div class="footer-signatures">
        <div class="sig-box">ناوی ئامادەکار / ژمێریار</div>
        <div class="sig-box">واژۆی خاوەنی شوێن</div>
        <div class="sig-box">مۆر و واژۆی بەڕێوەبەر</div>
    </div>
</div>

</body>
</html>
