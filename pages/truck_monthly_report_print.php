<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_monthly_report_print.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) die("Forbidden");

$truck_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch Truck Info (including commission_per_trip)
$truck_stmt = $pdo->prepare("SELECT * FROM factory_trucks WHERE id = ?");
$truck_stmt->execute([$truck_id]);
$truck = $truck_stmt->fetch(PDO::FETCH_ASSOC);

if (!$truck) die("Truck Not Found");

// Aggregated trips from purchases
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
");
$trips_stmt->execute([$truck_id, $month, $year]);
$aggregated_trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_trips = 0;
$total_material_value_iqd = 0;
foreach($aggregated_trips as $at) {
    $total_trips += (int)$at['trip_count'];
    $total_material_value_iqd += (float)$at['total_iqd'];
}

// Fixed commission logic based on truck setting
$commission_per_trip = (float)($truck['commission_per_trip'] ?? 20000);
$total_commission_iqd = $total_trips * $commission_per_trip;

// Expenses
$exp_stmt = $pdo->prepare("SELECT * FROM truck_expenses WHERE truck_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
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
    <style>
        @font-face { font-family: 'Rabar21'; src: url('../assets/fonts/Rabar_021.ttf'); }
        body { font-family: 'Rabar21', sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        .print-container { width: 210mm; margin: auto; padding: 10mm; background: white; border: none; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; border: 1px solid #ccc; padding: 15px; border-radius: 8px; }
        .section-title { border-right: 5px solid #111; padding: 5px 15px; font-size: 18px; margin-top: 30px; margin-bottom: 15px; font-weight: bold; background: #f8f9fa; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #777; padding: 10px; text-align: center; font-size: 14px; }
        th { background: #f0f0f0; }
        .summary-box { border: 2px solid #111; padding: 20px; border-radius: 10px; margin-top: 40px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; border-bottom: 1px dashed #777; padding-bottom: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align: center; margin-bottom: 20px;"><button onclick="window.print()">پرێنت</button></div>
<div class="print-container">
    <div class="header"><h1>دانە کۆنکریت</h1><p>ڕاپۆرتی پوختەی بارهەڵگر (مانگی <?= $month ?> / <?= $year ?>)</p></div>
    <div class="info-grid"><div>تڕێلە: <?= htmlspecialchars($truck['truck_name'] ?: '') ?></div><div>تەبلێ: <?= htmlspecialchars($truck['plate_number'] ?: '') ?></div><div>شۆفێر: <?= htmlspecialchars($truck['driver_name'] ?: '') ?></div><div>مانگ: <?= $month ?> / <?= $year ?></div></div>
    <div class="section-title">١. پوختەی کاروانەکان</div>
    <table>
        <thead><tr><th>مەواد</th><th>کانی</th><th>گەشت</th><th>کێش</th><th>نرخ (دینار)</th></tr></thead>
        <tbody>
            <?php foreach($aggregated_trips as $at): ?>
            <tr><td><?= htmlspecialchars($at['material_name'] ?: '') ?></td><td><?= htmlspecialchars($at['location'] ?: '') ?></td><td><?= $at['trip_count'] ?> کاروان</td><td><?= number_format($at['total_kg']/1000, 2) ?></td><td><?= number_format($at['total_iqd']) ?></td></tr>
            <?php endforeach; ?>
            <tr style="font-weight:bold; background:#eee;"><td colspan="2">کۆی گشتی</td><td><?= $total_trips ?> کاروان</td><td>-</td><td><?= number_format($total_material_value_iqd) ?></td></tr>
        </tbody>
    </table>
    <div class="section-title">٢. خەرجی و پاداشت</div>
    <table>
        <thead><tr><th>ڕێککەوت</th><th>جۆری خەرجی</th><th>بڕ (دینار)</th><th>تێبینی</th></tr></thead>
        <tbody>
            <?php foreach($expenses as $exp): 
                $v = (float)$exp['amount_iqd'] + ((float)$exp['amount_usd'] * ($current_rate/100));
            ?>
            <tr><td><?= $exp['date'] ?></td><td><?= htmlspecialchars($exp['expense_type']) ?></td><td><?= number_format($v) ?></td><td><?= htmlspecialchars($exp['note']) ?></td></tr>
            <?php endforeach; ?>
            <tr style="font-weight:bold; background:#eee;"><td colspan="2">کۆی پاداشتی شۆفێر (<?= $total_trips ?> کاروان)</td><td><?= number_format($total_commission_iqd) ?></td><td>بۆ هەر گەشتێک: <?= number_format($commission_per_trip) ?></td></tr>
        </tbody>
    </table>
    <div class="summary-box">
        <div class="summary-row"><span>کۆی گشتی داهات:</span><strong><?= number_format($total_material_value_iqd) ?> دینار</strong></div>
        <div class="summary-row"><span>کۆی گشتی خەرجی و پاداشت:</span><strong><?= number_format($total_exp_iqd + $total_commission_iqd) ?> دینار</strong></div>
        <div class="summary-row" style="font-size:22px; border-top:2px solid #111; padding-top:10px;"><span>قازانجی پاک:</span><strong><?= number_format($net_profit_iqd) ?> دینار</strong></div>
    </div>
</div>
</body>
</html>
