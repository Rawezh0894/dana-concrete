<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_monthly_report_print.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) die("Forbidden");

$truck_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch Truck Info
$truck_stmt = $pdo->prepare("SELECT * FROM factory_trucks WHERE id = ?");
$truck_stmt->execute([$truck_id]);
$truck = $truck_stmt->fetch(PDO::FETCH_ASSOC);

if (!$truck) die("Truck Not Found");

// Aggregated trips
$trips_stmt = $pdo->prepare("
    SELECT 
        m.name as material_name, 
        p.location, 
        COUNT(*) as trip_count,
        SUM(p.amount_iqd) as total_iqd,
        SUM(p.kg) as total_kg
    FROM purchases p 
    LEFT JOIN materials m ON p.material_id = m.id 
    WHERE p.factory_truck_id = ? AND MONTH(p.date) = ? AND YEAR(p.date) = ?
    GROUP BY p.material_id, p.location
    ORDER BY m.name ASC
");
$trips_stmt->execute([$truck_id, $month, $year]);
$aggregated_trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_trips = 0;
$total_material_value_iqd = 0;
foreach($aggregated_trips as $at) {
    $total_trips += (int)$at['trip_count'];
    $total_material_value_iqd += (float)$at['total_iqd'];
}

// Commission
$commission_per_trip = (float)($truck['commission_per_trip'] ?? 20000);
$total_commission_iqd = $total_trips * $commission_per_trip;

// Expenses
$exp_stmt = $pdo->prepare("SELECT * FROM truck_expenses WHERE truck_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date ASC");
$exp_stmt->execute([$truck_id, $month, $year]);
$expenses = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);

$rate_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
$current_rate = (float)($rate_stmt->fetchColumn() ?: 150000);

$total_exp_iqd = 0;
foreach($expenses as $e) {
    $total_exp_iqd += (float)$e['amount_iqd'] + ((float)$e['amount_usd'] * ($current_rate/100));
}

$net_profit_iqd = $total_material_value_iqd - ($total_exp_iqd + $total_commission_iqd);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ڕاپۆرتی دارایی تڕێلە - <?= htmlspecialchars($truck['truck_name']) ?></title>
    <style>
        @font-face {
            font-family: 'Rabar21';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        
        * { box-sizing: border-box; }
        body { 
            font-family: 'Rabar21', sans-serif; 
            margin: 0; padding: 0; 
            background: #fdfdfd; 
            color: #1a1a1a;
            -webkit-print-color-adjust: exact;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 15mm;
            position: relative;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        /* Watermark or Decorative Border */
        .print-page::after {
            content: "";
            position: absolute;
            top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
            border: 1px solid #eee;
            pointer-events: none;
            z-index: 0;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #111;
            padding-bottom: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .company-info h1 { margin: 0; font-size: 32px; color: #000; letter-spacing: 1px; }
        .company-info p { margin: 5px 0 0; font-size: 14px; color: #555; }
        
        .report-title {
            text-align: center;
            background: #111;
            color: white;
            padding: 10px 40px;
            border-radius: 5px;
            font-size: 20px;
        }

        .truck-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .meta-item {
            background: #f9f9f9;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            border: 1px solid #f0f0f0;
        }

        .meta-label { color: #666; font-size: 14px; }
        .meta-value { font-weight: bold; font-size: 16px; color: #000; }

        .section-header {
            display: flex;
            align-items: center;
            margin: 40px 0 15px;
            position: relative;
            z-index: 1;
        }
        
        .section-header h2 {
            font-size: 18px;
            margin: 0;
            background: #fff;
            padding-left: 15px;
            color: #111;
            white-space: nowrap;
        }

        .section-header .line {
            flex-grow: 1;
            height: 1px;
            background: #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        th {
            background: #f2f2f2;
            color: #333;
            font-weight: bold;
            padding: 12px;
            border: 1px solid #ddd;
            font-size: 14px;
            text-align: center;
        }

        td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: center;
            font-size: 14px;
        }

        tr:nth-child(even) { background: #fafafa; }
        
        .summary-wrapper {
            margin-top: 50px;
            border: 2px solid #111;
            border-radius: 12px;
            padding: 25px;
            background: #fcfcfc;
            position: relative;
            z-index: 1;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
            font-size: 17px;
        }
        
        .summary-row:last-of-type { border-bottom: none; }

        .total-profit-row {
            margin-top: 15px;
            background: #111;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 22px;
            font-weight: bold;
        }

        .footer-signatures {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .sig-box { border-top: 1px solid #333; padding-top: 10px; font-size: 14px; }

        .no-print-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .btn-print {
            background: #111;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-print:hover { background: #333; }

        @media print {
            .no-print-toolbar { display: none; }
            body { background: white; }
            .print-page { margin: 0; box-shadow: none; border: none; width: 100%; min-height: auto; }
            .print-page::after { border: none; }
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
    <div class="header-section">
        <div class="company-info">
            <h1>دانە کۆنکریت</h1>
            <p>بۆ بەرهەمهێنانی کۆنکرێتی ئاماداکراو</p>
            <p>ڕێککەوتی چاپ: <?= date('Y-m-d') ?></p>
        </div>
        <div class="report-title">ڕاپۆرتی وردەکاری بارهەڵگر</div>
    </div>

    <div class="truck-meta-grid">
        <div class="meta-item"><span class="meta-label">ناوی تڕێلە:</span><span class="meta-value"><?= htmlspecialchars($truck['truck_name']) ?></span></div>
        <div class="meta-item"><span class="meta-label">ژمارەی تابلۆ:</span><span class="meta-value"><?= htmlspecialchars($truck['plate_number']) ?></span></div>
        <div class="meta-item"><span class="meta-label">ناوی شۆفێر:</span><span class="meta-value"><?= htmlspecialchars($truck['driver_name']) ?></span></div>
        <div class="meta-item"><span class="meta-label">ماوەی ڕاپۆرت:</span><span class="meta-value"><?= $month ?> / <?= $year ?></span></div>
    </div>

    <div class="section-header">
        <h2>١. کۆبەندى کاروانەکان ( Trips )</h2>
        <div class="line"></div>
    </div>
    <table>
        <thead>
            <tr>
                <th>جۆری مەواد</th>
                <th>شوێن (سەرچاوە)</th>
                <th>ژمارەی گەشتەکان</th>
                <th>کۆی کێش (تەن)</th>
                <th>کۆی نرخ (IQD)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($aggregated_trips as $at): ?>
            <tr>
                <td style="font-weight: bold;"><?= htmlspecialchars($at['material_name'] ?: 'مەوادی تر') ?></td>
                <td><?= htmlspecialchars($at['location'] ?: 'دیارینەکراو') ?></td>
                <td><?= $at['trip_count'] ?> کاروان</td>
                <td><?= number_format($at['total_kg'] / 1000, 2) ?></td>
                <td><?= number_format($at['total_iqd']) ?> د.ع</td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($aggregated_trips)): ?>
            <tr><td colspan="5">هیچ گەشتێک تۆمار نەکراوە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-header">
        <h2>٢. وردەکاری خەرجییەکان ( Costs )</h2>
        <div class="line"></div>
    </div>
    <table>
        <thead>
            <tr>
                <th width="15%">ڕێککەوت</th>
                <th>جۆری خەرجی</th>
                <th width="20%">بڕ (IQD)</th>
                <th width="30%">تێبینی</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($expenses as $exp): 
                $v = (float)$exp['amount_iqd'] + ((float)$e['amount_usd'] * ($current_rate/100));
            ?>
            <tr>
                <td><?= $exp['date'] ?></td>
                <td><?= htmlspecialchars($exp['expense_type']) ?></td>
                <td style="font-weight: bold;"><?= number_format($v) ?> د.ع</td>
                <td><?= htmlspecialchars($exp['note']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #fdfdfd; font-weight: bold;">
                <td colspan="2">کۆی پاداشتی شۆفێر (کاروان حیسابی)</td>
                <td><?= number_format($total_commission_iqd) ?> د.ع</td>
                <td><?= $total_trips ?> کاروان (<?= number_format($commission_per_trip) ?>/گەشت)</td>
            </tr>
            <?php if(empty($expenses) && $total_commission_iqd == 0): ?>
            <tr><td colspan="4">هیچ تێچوویەک نییە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-wrapper">
        <div class="summary-row">
            <span>کۆی گشتی داهاتی بارهەڵگر بۆ ئەم مانگە:</span>
            <span style="font-weight: bold;"><?= number_format($total_material_value_iqd) ?> د.ع</span>
        </div>
        <div class="summary-row">
            <span>کۆی خەرجییەکان + پاداشتی شۆفێر (Total Costs):</span>
            <span style="font-weight: bold;"><?= number_format($total_exp_iqd + $total_commission_iqd) ?> د.ع</span>
        </div>
        <div class="total-profit-row">
            <span>قازانجی پاکی بارهەڵگر (Net Profit):</span>
            <span><?= number_format($net_profit_iqd) ?> د.ع</span>
        </div>
    </div>

    <div class="footer-signatures">
        <div class="sig-box">واژۆی ژمێریاری</div>
        <div class="sig-box">واژۆی شۆفێر</div>
        <div class="sig-box">مۆر و واژۆی بەڕێوەبەر</div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
</body>
</html>
