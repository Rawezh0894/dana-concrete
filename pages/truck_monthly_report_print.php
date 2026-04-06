<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_monthly_report_print.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) {
    die("تکایە سەرەتا داخڵ ببە");
}

$truck_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch Truck Info
$truck_stmt = $pdo->prepare("SELECT * FROM factory_trucks WHERE id = ?");
$truck_stmt->execute([$truck_id]);
$truck = $truck_stmt->fetch(PDO::FETCH_ASSOC);

if (!$truck) {
    die("تڕێلە نەدۆزرایەوە!");
}

// 1. Fetch Aggregated Trips (Purchases) - Grouped by Material and Location
$trips_stmt = $pdo->prepare("
    SELECT 
        m.name as material_name, 
        p.location, 
        COUNT(*) as trip_count,
        SUM(p.amount_iqd) as total_iqd,
        SUM(p.price) as total_usd,
        SUM(p.kg) as total_kg
    FROM purchases p 
    LEFT JOIN materials m ON p.material_id = m.id 
    WHERE p.factory_truck_id = ? AND MONTH(p.date) = ? AND YEAR(p.date) = ?
    GROUP BY p.material_id, p.location
    ORDER BY m.name ASC
");
$trips_stmt->execute([$truck_id, $month, $year]);
$aggregated_trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

// Total Trips Calculation
$total_trips = 0;
$total_material_value_iqd = 0;
foreach($aggregated_trips as $at) {
    $total_trips += (int)$at['trip_count'];
    $total_material_value_iqd += (float)$at['total_iqd'];
}

// 2. Fetch All Detailed Expenses
$exp_stmt = $pdo->prepare("
    SELECT * FROM truck_expenses 
    WHERE truck_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
    ORDER BY date ASC
");
$exp_stmt->execute([$truck_id, $month, $year]);
$expenses = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Exchange Rate from settings (to convert any USD expenses to IQD)
$rate_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
$current_rate = (float)($rate_stmt->fetchColumn() ?: 150000);

// Calculations in IQD
$total_exp_iqd = 0;
foreach($expenses as $e) {
    // If expense was in USD, convert to IQD using the current rate
    $val_in_iqd = (float)$e['amount_iqd'];
    if ((float)$e['amount_usd'] > 0) {
        $val_in_iqd += (float)$e['amount_usd'] * ($current_rate / 100);
    }
    $total_exp_iqd += $val_in_iqd;
}

// Driver Commission (Amount per trip)
$commission_per_trip = (float)($truck['commission_per_trip'] ?? 20000);
$total_commission_iqd = $total_trips * $commission_per_trip;

// Final Profit (داهات = کۆی گشتی مەواد بە دینار - (مەسروفات + پاداشت))
$net_profit_iqd = $total_material_value_iqd - ($total_exp_iqd + $total_commission_iqd);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ڕاپۆرتی مانگانەی تڕێلە - <?= htmlspecialchars($truck['truck_name']) ?></title>
    <style>
        @font-face {
            font-family: 'Rabar21';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        body { 
            font-family: 'Rabar21', sans-serif; 
            margin: 0; padding: 20px; color: #333; background: #f0f0f0; line-height: 1.6; 
        }
        .print-container { 
            width: 210mm; min-height: 297mm; margin: auto; padding: 15mm; 
            background: white; border: 1px solid #ddd; box-sizing: border-box; 
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header { text-align: center; border-bottom: 4px solid #111; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 32px; letter-spacing: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; border: 1px solid #eee; padding: 20px; border-radius: 12px; }
        .info-item { font-size: 18px; }
        .section-title { 
            background: #f8f9fa; border-right: 6px solid #111; 
            color: #111; padding: 10px 20px; font-size: 20px; 
            margin-top: 40px; margin-bottom: 20px; font-weight: bold;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #999; padding: 12px; text-align: center; font-size: 15px; }
        th { background: #f2f2f2; font-weight: bold; }
        .summary-box { 
            background: #111; color: #fff; padding: 30px; 
            border-radius: 15px; margin-top: 50px; 
        }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .final-profit { font-size: 26px; font-weight: bold; border-top: 2px solid #fff; padding-top: 15px; margin-top: 15px; }
        
        .badge-total { background: #333; color: white; padding: 3px 10px; border-radius: 4px; font-size: 14px; }

        @media print {
            body { padding: 0; background: none; }
            .print-container { border: none; padding: 10mm; margin: 0; width: 100%; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 30px; text-align: center;">
    <button onclick="window.print()" style="padding: 12px 40px; font-size: 20px; background: #000; color: white; border: none; cursor: pointer; border-radius: 10px; font-family: inherit;">پرێنتکردنی ڕاپۆرت</button>
</div>

<div class="print-container">
    <div class="header">
        <h1>دانە کۆنکریت</h1>
        <p style="font-size: 18px;">ڕاپۆرتی پوختەی مانگانەی بارهەڵگر</p>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>تڕێلە:</strong> <?= htmlspecialchars($truck['truck_name']) ?></div>
        <div class="info-item"><strong>تەبلێ:</strong> <?= htmlspecialchars($truck['plate_number']) ?></div>
        <div class="info-item"><strong>مانگ/ساڵ:</strong> <?= $month ?> / <?= $year ?></div>
        <div class="info-item"><strong>شۆفێر:</strong> <?= htmlspecialchars($truck['driver_name']) ?></div>
    </div>

    <div class="section-title">١. کۆبەندى کاروانەکان ( trips summary )</div>
    <table class="trips-table">
        <thead>
            <tr>
                <th>جۆری مەواد</th>
                <th>شوێن (کانی)</th>
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
                <td><span class="badge-total"><?= $at['trip_count'] ?></span> گەشت</td>
                <td><?= number_format($at['total_kg'] / 1000, 2) ?> تەن</td>
                <td><?= number_format($at['total_iqd']) ?> д.ع</td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #fdfdfd; font-weight: bold; border-top: 2px solid #000;">
                <td colspan="2">کۆی گشتی هەموو گەشتەکان</td>
                <td><?= $total_trips ?> کاروان</td>
                <td>-</td>
                <td><?= number_format($total_material_value_iqd) ?> д.ع</td>
            </tr>
            <?php if(empty($aggregated_trips)): ?>
            <tr><td colspan="5">هیچ داتایەک نەدۆزرایەوە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">٢. وردەکاری خەرجییەکان ( Expenses )</div>
    <table>
        <thead>
            <tr>
                <th>ڕێککەوت</th>
                <th>وردەکاری خەرجی</th>
                <th>بڕ (IQD)</th>
                <th>تێبینی</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($expenses as $exp): 
                $iqd_val = (float)$exp['amount_iqd'];
                if ((float)$exp['amount_usd'] > 0) { $iqd_val += (float)$exp['amount_usd'] * ($current_rate / 100); }
            ?>
            <tr>
                <td><?= $exp['date'] ?></td>
                <td><?= htmlspecialchars($exp['expense_type']) ?></td>
                <td><?= number_format($iqd_val) ?> д.ع</td>
                <td><?= htmlspecialchars($exp['note']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #fdfdfd; font-weight: bold;">
                <td colspan="2">کاروان حیسابی بۆ شۆفێر (<?= $total_trips ?> گەشت)</td>
                <td><?= number_format($total_commission_iqd) ?> д.ع</td>
                <td><?= number_format($commission_per_trip) ?> بۆ هەر گەشتێک</td>
            </tr>
            <?php if(empty($expenses) && $total_commission_iqd == 0): ?>
            <tr><td colspan="4">هیچ خەرجییەک نییە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row">
            <span>کۆی گشتی بەهای داهاتی مەواد:</span>
            <strong><?= number_format($total_material_value_iqd) ?> د.ع</strong>
        </div>
        <div class="summary-row">
            <span>کۆی گشتی خەرجی و مەسروفات:</span>
            <strong><?= number_format($total_exp_iqd) ?> د.ع</strong>
        </div>
        <div class="summary-row">
            <span>کۆی کاروان حیسابی (پاداشت):</span>
            <strong><?= number_format($total_commission_iqd) ?> د.ع</strong>
        </div>
        <div class="summary-row final-profit">
            <span>قازانجی پاکی ئەم مانگە:</span>
            <strong><?= number_format($net_profit_iqd) ?> دینار</strong>
        </div>
        <p style="font-size: 13px; margin-top: 20px; text-align: center; opacity: 0.8; color: #aaa;">ڕێککەوتی چاپ: <?= date('Y-m-d') ?></p>
    </div>
</div>

</body>
</html>
