<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_customer')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// گەڕانەوەی هەموو کڕیارە قەرزارەکان
$sql = "SELECT c.id, c.name, c.mobile1, c.mobile2, c.debt_usd, c.debt_iqd,
    (
        SELECT IFNULL(SUM(quantity),0) FROM sales s WHERE s.customer_id = c.id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
    ) as total_credit_meter
FROM customers c
WHERE c.debt_usd > 0 OR c.debt_iqd > 0
ORDER BY c.name ASC";
$stmt = $pdo->query($sql);
$customers = $stmt->fetchAll();

// گەڕانەوەی هەموو مامەڵە قەرزەکان (پارەی ماوە) بۆ هەموو کڕیارە قەرزارەکان
$sales_sql = "SELECT s.*, c.name as customer_name, c.mobile1 FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.payment_type = 'قەرز' AND s.remaining_amount > 0 ORDER BY c.name ASC, s.order_date DESC";
$sales_stmt = $pdo->query($sales_sql);
$sales = $sales_stmt->fetchAll();

// گرووپکردنی مامەڵەکان بە IDی کڕیار
$sales_by_customer = [];
foreach ($sales as $sale) {
    $sales_by_customer[$sale['customer_id']][] = $sale;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <title>پرینتی قەرزی کڕیارەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm 5mm 10mm 5mm; }
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .a4-sheet { width: 297mm; min-height: auto; margin: 0 auto; background: #fff; box-shadow: none; }
            .print-card { page-break-inside: avoid; }
            html, body {
                overflow: visible !important;
            }
            .table-responsive {
                overflow: visible !important;
                box-shadow: none !important;
            }
            .table.compact-table th, .table.compact-table td {
                font-size: 1rem;
                padding: 1px 3px !important;
            }
        }
        .a4-sheet {
       
     
         
            
        }
        .table th, .table td { vertical-align: middle !important; }
        .section-title { color: #1a7f5a; font-weight: bold; margin-bottom: 20px; }
        .customer-block { margin-bottom: 40px; page-break-inside: avoid; }
        .customer-header { background: #e9f7ef; border-radius: 6px; padding: 12px 18px; margin-bottom: 10px; font-weight: bold; font-size: 1.1rem; color: #1a7f5a; }
        .table.compact-table th, .table.compact-table td {
            font-size: 1.02rem;
            padding: 2px 6px !important;
            white-space: nowrap;
            vertical-align: middle !important;
        }
    </style>
</head>
<body dir="rtl">
<div class="a4-sheet">
    <div class="no-print" style="height: 20px;"></div>
    <div class="text-center mb-4" style="background:var(--seafoam-green,#003b73); border-bottom:2px solid var(--seafoam-green,#003b73); padding-bottom: 12px; margin-bottom: 32px; border-radius:10px; box-shadow:0 2px 8px #003b7322; padding-top:18px;">
        <h1 style="color:#fff; font-weight:bold; letter-spacing:1px; font-size:2.1rem;">لیستی قەرزی هەموو کڕیاران</h1>
        <div style="color:var(--lime-green,#0074b7); font-size:1.15rem; font-weight:500;">ڕاپۆرتی تەواوی مامەڵە قەرزەکان بە وردی</div>
        <div style="color:#fff; font-size:1rem; margin-top:6px;">
            بەروار و کات: <?php echo date('Y-m-d H:i'); ?>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="section-title">پرینتی قەرزی کڕیارەکان</h2>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> پرینت</button>
    </div>
  
    <div class="row g-4">
    <?php foreach ($customers as $c): ?>
        <div class="col-12">
            <div class="card shadow-lg border-0 mb-3 print-card" style="background: var(--kelly-green, #bfd7ed);">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-start gap-4" style="font-size: 1.13rem;">
                    <span style="font-weight:bold; color:var(--seafoam-green, #003b73); display:flex; align-items:center; gap:6px;">
                        <i class="fa fa-user-circle fa-lg" style="color:var(--lime-green, #0074b7);"></i> <?= htmlspecialchars($c['name']) ?>
                    </span>
                    <span style="color:var(--spearmint, #60a3d9); font-weight:500; display:flex; align-items:center; gap:6px;">
                        <i class="fa fa-phone"></i> <?= htmlspecialchars($c['mobile1']) ?>
                    </span>
                    <span style="color:#6c757d; font-weight:500; display:flex; align-items:center; gap:6px;">
                        <i class="fa fa-cube"></i> مەتر سێجا: <?= number_format($c['total_credit_meter'], 2) ?> م٣
                    </span>
                    <span style="color:#e67e22; font-weight:500; display:flex; align-items:center; gap:6px;">
                        <i class="fa fa-money-bill-wave"></i> قەرز (USD): <?= number_format($c['debt_usd'], 2) ?> $
                    </span>
                </div>
                <?php if (!empty($sales_by_customer[$c['id']])): ?>
                <div class="table-responsive px-3 pb-3">
                    <table class="table table-bordered table-hover align-middle text-center mb-0 compact-table">
                        <colgroup>
                            <col style="width:40px;">
                            <col style="width:70px;">
                            <col style="width:70px;">
                            <col style="width:90px;">
                            <col style="width:90px;">
                            <col style="width:60px;">
                            <col style="width:60px;">
                            <col style="width:70px;">
                            <col style="width:70px;">
                            <col style="width:70px;">
                            <col style="width:70px;">
                            <col style="width:60px;">
                        </colgroup>
                        <thead class="table-warning">
                            <tr>
                                <th>#</th>
                                <th>ژمارە فاکتور</th>
                                <th>ڕێکەوت</th>
                                <th>وەرگر</th>
                                <th>شوێن</th>
                                <th>کۆی مەتر</th>
                                <th>نرخی مەتر</th>
                                <th>کۆی گشتی</th>
                                <th>پارەی دراو (USD)</th>
                                <th>پارەی دراو (IQD)</th>
                                <th>بڕی ماوە</th>
                                <th>داشکاندن</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_by_customer[$c['id']] as $i => $s): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($s['invoice_number']) ?></td>
                                <td><?= htmlspecialchars($s['order_date']) ?></td>
                                <td><?= htmlspecialchars($s['recipient']) ?></td>
                                <td><?= htmlspecialchars($s['location']) ?></td>
                                <td><?= number_format($s['quantity'], 2) ?> م٣</td>
                                <td><?= number_format($s['price_per_unit'], 2) ?> $</td>
                                <td><?= number_format($s['total_price'], 2) ?> $</td>
                                <td><?= number_format($s['amount_paid_usd'], 2) ?> $</td>
                                <td><?= number_format($s['amount_paid_iq'], 0) ?> IQD</td>
                                <td><?= number_format($s['remaining_amount'], 2) ?> $</td>
                                <td><?= number_format($s['discount'], 2) ?> $</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-muted px-3 pb-3">هیچ مامەڵە قەرزێکی پارەی ماوە نییە.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="text-center mt-5 no-print">
        <button class="btn btn-secondary" onclick="window.history.back()">گەڕانەوە</button>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</body>
</html>
