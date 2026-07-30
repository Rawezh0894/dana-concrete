<?php
// c:\xampp\htdocs\dana-concrete\pages\print_location_statement.php
session_start();
require_once '../config/db_conected.php';

if (!isset($_SESSION['user_id'])) die("Forbidden");

$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$location = isset($_GET['location']) ? $_GET['location'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (!$company_id) die("Invalid Parameters");

// Fetch Company Info
$comp_stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
$comp_stmt->execute([$company_id]);
$company = $comp_stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) die("Company Not Found");

// Build Query
$where = "company_id = :company_id AND location IS NOT NULL AND location != ''";
$params = [':company_id' => $company_id];

$date_label = "تەواوی کاتەکان";
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND date >= :from_date AND date <= :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
    $date_label = $from_date . " تا " . $to_date;
} elseif (!empty($from_date)) {
    $where .= " AND date >= :from_date";
    $params[':from_date'] = $from_date;
    $date_label = "لە " . $from_date . " بەدواوە";
} elseif (!empty($to_date)) {
    $where .= " AND date <= :to_date";
    $params[':to_date'] = $to_date;
    $date_label = "تا " . $to_date;
}

$query = "
    SELECT 
        location,
        SUM(price) as total_cost_usd,
        SUM(amount_iqd) as total_cost_iqd,
        SUM(paid_to_location_usd) as total_paid_usd,
        SUM(paid_to_location_iqd) as total_paid_iqd,
        SUM(price - paid_to_location_usd) as remaining_usd,
        SUM(amount_iqd - paid_to_location_iqd) as remaining_iqd
    FROM purchases
    WHERE $where
    GROUP BY location
    ORDER BY location ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($transactions as $t) {
    $total_cost_usd += (float)$t['total_cost_usd'];
    $total_cost_iqd += (float)$t['total_cost_iqd'];
    $total_paid_usd += (float)$t['total_paid_usd'];
    $total_paid_iqd += (float)$t['total_paid_iqd'];
    $remaining_usd += (float)$t['remaining_usd'];
    $remaining_iqd += (float)$t['remaining_iqd'];
}

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>کەشف حیسابی گشت شوێنەکان</title>
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

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #20b2aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info h1 { margin: 0; font-size: 30px; color: #111; letter-spacing: 1px; }
        .company-info p { margin: 5px 0 0; font-size: 14px; color: #555; }
        
        .report-title {
            text-align: center;
            background: #20b2aa;
            color: white;
            padding: 12px 40px;
            border-radius: 8px;
            font-size: 20px;
            font-weight: bold;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .meta-item {
            background: #f9fbfb;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            border: 1px solid #e0e0e0;
        }

        .meta-label { color: #555; font-size: 15px; }
        .meta-value { font-weight: bold; font-size: 16px; color: #111; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
        }

        th {
            background: #f2f2f2;
            color: #333;
            font-weight: bold;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        td {
            padding: 8px;
            border: 1px solid #eee;
            text-align: center;
        }

        tr:nth-child(even) { background: #fafafa; }

        .summary-wrapper {
            margin-top: 40px;
            border: 2px solid #20b2aa;
            border-radius: 12px;
            padding: 20px;
            background: #f4faf9;
            page-break-inside: avoid;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .summary-col h3 {
            margin-top: 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
            font-size: 15px;
        }
        
        .summary-row:last-of-type { border-bottom: none; }

        .total-profit-row {
            margin-top: 15px;
            background: #20b2aa;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
        }

        .footer-signatures {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 100px;
            text-align: center;
            page-break-inside: avoid;
        }

        .sig-box { border-top: 1px solid #333; padding-top: 10px; font-size: 15px; font-weight: bold; }

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
            background: #20b2aa;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-print:hover { background: #188c86; }

        .text-danger { color: #dc3545 !important; }
        .text-success { color: #198754 !important; }

        @media print {
            .no-print-toolbar { display: none; }
            body { background: white; }
            .print-page { margin: 0; box-shadow: none; border: none; width: 100%; min-height: auto; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-toolbar">
    <button class="btn-print" onclick="window.print()">
        <svg style="width:16px;height:16px;vertical-align:middle;margin-left:5px" viewBox="0 0 512 512"><path fill="currentColor" d="M128 0C92.7 0 64 28.7 64 64v96h64V64h226.7L384 93.3V160h64V73.4c0-17-6.7-33.3-18.7-45.3L400 6.3C388 -5.7 371.7-12.4 354.7-12.4H128zM512 288c0-53-43-96-96-96H96c-53 0-96 43-96 96v128h64v-64h384v64h64V288zM432 272c8.8 0 16 7.2 16 16s-7.2 16-16 16-16-7.2-16-16 7.2-16 16-16zM384 416H128v96h256v-96z"/></svg>
        پرێنتکردنی ڕاپۆرت
    </button>
</div>

<div class="print-page">
    <div class="header-section">
        <div class="company-info">
            <h1><?= htmlspecialchars($company['name']) ?></h1>
            <p>بۆ کۆنکرێتی ئامادەکراو</p>
            <p>ڕێککەوتی چاپ: <?= date('Y-m-d') ?></p>
        </div>
        <div class="report-title">کەشف حیسابی گشت شوێنەکان</div>
    </div>

    <div class="meta-grid">
        <div class="meta-item"><span class="meta-label">ناوی کۆمپانیا:</span><span class="meta-value"><?= htmlspecialchars($company['name']) ?></span></div>

        <div class="meta-item" style="grid-column: span 2;"><span class="meta-label">ماوەی ڕاپۆرت:</span><span class="meta-value"><?= $date_label ?></span></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ناوی شوێن</th>
                <th>بڕی کڕین ($)</th>
                <th>بڕی کڕین (د.ع)</th>
                <th>پارەی دراو ($)</th>
                <th>پارەی دراو (د.ع)</th>
                <th>قەرزی ماوە ($)</th>
                <th>قەرزی ماوە (د.ع)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($transactions as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['location']) ?></td>
                <td class="text-danger"><?= (float)$t['total_cost_usd'] > 0 ? number_format($t['total_cost_usd'], 2) : '-' ?></td>
                <td class="text-danger"><?= (float)$t['total_cost_iqd'] > 0 ? number_format($t['total_cost_iqd']) : '-' ?></td>
                <td class="text-success"><?= (float)$t['total_paid_usd'] > 0 ? number_format($t['total_paid_usd'], 2) : '-' ?></td>
                <td class="text-success"><?= (float)$t['total_paid_iqd'] > 0 ? number_format($t['total_paid_iqd']) : '-' ?></td>
                <td class="<?= (float)$t['remaining_usd'] > 0 ? 'text-danger' : 'text-success' ?>" style="font-weight: bold;"><?= (float)$t['remaining_usd'] != 0 ? number_format($t['remaining_usd'], 2) : '-' ?></td>
                <td class="<?= (float)$t['remaining_iqd'] > 0 ? 'text-danger' : 'text-success' ?>" style="font-weight: bold;"><?= (float)$t['remaining_iqd'] != 0 ? number_format($t['remaining_iqd']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($transactions)): ?>
            <tr><td colspan="7">هیچ مامەڵەیەک نەدۆزرایەوە</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-wrapper">
        <div class="summary-grid">
            <div class="summary-col">
                <h3>پوختەی دۆلار ($)</h3>
                <div class="summary-row">
                    <span>کۆی گشتی تێچووی کڕین:</span>
                    <span class="text-danger" style="font-weight: bold;"><?= number_format($total_cost_usd, 2) ?> $</span>
                </div>
                <div class="summary-row">
                    <span>کۆی پارەی دراو:</span>
                    <span class="text-success" style="font-weight: bold;"><?= number_format($total_paid_usd, 2) ?> $</span>
                </div>
                <div class="total-profit-row" style="<?= $remaining_usd > 0 ? 'background: #dc3545;' : ($remaining_usd < 0 ? 'background: #198754;' : '') ?>">
                    <span>قەرزی کۆتایی ماوە:</span>
                    <span><?= number_format(abs($remaining_usd), 2) ?> $ <?= $remaining_usd > 0 ? '(قەرزدارە)' : ($remaining_usd < 0 ? '(داواکارە)' : '') ?></span>
                </div>
            </div>
            <div class="summary-col">
                <h3>پوختەی دینار (د.ع)</h3>
                <div class="summary-row">
                    <span>کۆی گشتی تێچووی کڕین:</span>
                    <span class="text-danger" style="font-weight: bold;"><?= number_format($total_cost_iqd) ?> د.ع</span>
                </div>
                <div class="summary-row">
                    <span>کۆی پارەی دراو:</span>
                    <span class="text-success" style="font-weight: bold;"><?= number_format($total_paid_iqd) ?> د.ع</span>
                </div>
                <div class="total-profit-row" style="<?= $remaining_iqd > 0 ? 'background: #dc3545;' : ($remaining_iqd < 0 ? 'background: #198754;' : '') ?>">
                    <span>قەرزی کۆتایی ماوە:</span>
                    <span><?= number_format(abs($remaining_iqd)) ?> د.ع <?= $remaining_iqd > 0 ? '(قەرزدارە)' : ($remaining_iqd < 0 ? '(داواکارە)' : '') ?></span>
                </div>
            </div>
        </div>
        <div class="summary-row" style="margin-top: 15px; border-top: 2px solid #ccc; padding-top: 15px; justify-content: center; font-size: 16px;">
            <span>ژمارەی شوێنەکان: </span>
            <span style="font-weight: bold; margin-right: 10px;"><?= count($transactions) ?> شوێن</span>
        </div>
    </div>

    <div class="footer-signatures">
        <div class="sig-box">واژۆی شوێن (وەرگر/نێرەر)</div>
        <div class="sig-box">مۆر و واژۆی کۆمپانیا</div>
    </div>
</div>

</body>
</html>
