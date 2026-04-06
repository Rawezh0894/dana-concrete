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

// Fetch Trips (Purchases)
$trips_stmt = $pdo->prepare("
    SELECT p.*, m.name as material_name 
    FROM purchases p 
    LEFT JOIN materials m ON p.material_id = m.id 
    WHERE p.factory_truck_id = ? AND MONTH(p.date) = ? AND YEAR(p.date) = ?
    ORDER BY p.date ASC
");
$trips_stmt->execute([$truck_id, $month, $year]);
$trips = $trips_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Expenses
$exp_stmt = $pdo->prepare("
    SELECT * FROM truck_expenses 
    WHERE truck_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
    ORDER BY date ASC
");
$exp_stmt->execute([$truck_id, $month, $year]);
$expenses = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Exchange Rate for summary if needed
$rate_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
$current_rate = (float)($rate_stmt->fetchColumn() ?: 150000);

// Calculations
$total_material_value_usd = 0;
$total_trips = count($trips);
foreach($trips as $t) {
    $total_material_value_usd += (float)$t['price'];
}

$total_exp_usd = 0;
$total_exp_iqd = 0;
foreach($expenses as $e) {
    $total_exp_usd += (float)$e['amount_usd'];
    $total_exp_iqd += (float)$e['amount_iqd'];
}

// Driver Commission logic (Amount per trip)
$commission_per_trip = (float)($truck['commission_per_trip'] ?? 20000);
$total_commission_iqd = $total_trips * $commission_per_trip;

// Total Summary in USD equivalent
$total_exp_combined_usd = $total_exp_usd + ($total_exp_iqd / ($current_rate/100));
$total_commission_usd = $total_commission_iqd / ($current_rate/100);

$net_profit_usd = $total_material_value_usd - ($total_exp_combined_usd + $total_commission_usd);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ڕاپۆرتی مانگانەی تڕێلە - <?= htmlspecialchars($truck['truck_name']) ?></title>
    <style>
        @font-face { font-family: 'Rabar'; src: url('../assets/fonts/Rabar_01.ttf'); }
        body { font-family: 'Rabar', sans-serif; margin: 0; padding: 20px; color: #333; background: #fff; line-height: 1.6; }
        .print-container { width: 210mm; min-height: 297mm; margin: auto; padding: 10mm; background: white; border: 1px solid #ddd; box-sizing: border-box; }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 28px; color: #000; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 10px; }
        .info-item { font-size: 16px; }
        .section-title { background: #333; color: #fff; padding: 8px 15px; font-size: 18px; margin-top: 30px; margin-bottom: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #eee; font-weight: bold; }
        .summary-box { background: #eee; padding: 20px; border-radius: 10px; margin-top: 40px; border: 2px solid #333; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }
        .final-profit { font-size: 22px; font-weight: bold; color: #000; border-top: 2px solid #333; padding-top: 10px; margin-top: 10px; }
        @media print {
            body { padding: 0; background: none; }
            .print-container { border: none; padding: 0; margin: 0; width: 100%; box-shadow: none; }
            .no-print { display: none; }
        }
        .text-end { text-align: left; }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 30px; font-size: 18px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">چاپی ڕاپۆرت (A4)</button>
    <button onclick="window.close()" style="padding: 10px 30px; font-size: 18px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 5px; margin-right: 10px;">داخستن</button>
</div>

<div class="print-container">
    <div class="header">
        <h1>دانە کۆنکریت</h1>
        <p>ڕاپۆرتی وردەکاری ئەدای بارهەڵگر (مانگانە)</p>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>ناوی تڕێلە:</strong> <?= htmlspecialchars($truck['truck_name']) ?></div>
        <div class="info-item"><strong>ژمارەی تەبلێ:</strong> <?= htmlspecialchars($truck['plate_number']) ?></div>
        <div class="info-item"><strong>ماوەی ڕاپۆرت:</strong> <?= $month ?> / <?= $year ?></div>
        <div class="info-item"><strong>ناوی شۆفێر:</strong> <?= htmlspecialchars($truck['driver_name']) ?></div>
    </div>

    <div class="section-title">١. وردەکاری کاروانەکان ( trips )</div>
    <table>
        <thead>
            <tr>
                <th>ڕێککەوت</th>
                <th>فۆڕم</th>
                <th>جۆری مەواد</th>
                <th>شوێن (سەرچاوە)</th>
                <th>کێش (تەن)</th>
                <th>رخ (USD)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($trips as $trip): ?>
            <tr>
                <td><?= $trip['date'] ?></td>
                <td><?= $trip['invoice_number'] ?></td>
                <td><?= htmlspecialchars($trip['material_name'] ?: 'دیارینەکراو') ?></td>
                <td><?= htmlspecialchars($trip['location']) ?></td>
                <td><?= number_format($trip['kg'] / 1000, 2) ?></td>
                <td>$<?= number_format($trip['price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($trips)): ?>
            <tr><td colspan="6">هیچ گەشتێک نەکراوە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">٢. وردەکاری خەرجی و تێچووەکان</div>
    <table>
        <thead>
            <tr>
                <th>ڕێککەوت</th>
                <th>جۆری خەرجی</th>
                <th>بڕ (USD)</th>
                <th>بڕ (IQD)</th>
                <th>تێبینی</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($expenses as $exp): ?>
            <tr>
                <td><?= $exp['date'] ?></td>
                <td><?= htmlspecialchars($exp['expense_type']) ?></td>
                <td>$<?= number_format($exp['amount_usd'], 2) ?></td>
                <td><?= number_format($exp['amount_iqd']) ?> д.ع</td>
                <td><?= htmlspecialchars($exp['note']) ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Driver Commission as Row -->
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="2">کاروان حیسابی (کۆی گەشتەکان: <?= $total_trips ?>)</td>
                <td>-</td>
                <td><?= number_format($total_commission_iqd) ?> д.ع</td>
                <td>پاداشتی شۆفێر (<?= number_format($commission_per_trip) ?> بۆ هەر گەشتێک)</td>
            </tr>
            <?php if(empty($expenses) && $total_commission_iqd == 0): ?>
            <tr><td colspan="5">هیچ خەرجییەک نییە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row">
            <span>کۆی گشتی بەهای مەوادی هێنراو:</span>
            <strong>$<?= number_format($total_material_value_usd, 2) ?></strong>
        </div>
        <div class="summary-row">
            <span>کۆی مەسروفات (بەدەر لە پاداشت):</span>
            <strong>$<?= number_format($total_exp_combined_usd, 2) ?></strong>
        </div>
        <div class="summary-row">
            <span>کۆی پاداشتی شۆفێر (کاروان حیسابی):</span>
            <strong>$<?= number_format($total_commission_usd, 2) ?> <small>(<?= number_format($total_commission_iqd) ?> د.ع)</small></strong>
        </div>
        <div class="summary-row final-profit">
            <span>قازانجی پاکی تڕێلە:</span>
            <strong>$<?= number_format($net_profit_usd, 2) ?></strong>
        </div>
        <p style="font-size: 12px; margin-top: 15px; text-align: center; opacity: 0.6;">- ئەم ڕاپۆرتە خۆکارانە لە سیستەمی دانە کۆنکریتەوە دروست کراوە -</p>
    </div>
</div>

</body>
</html>
