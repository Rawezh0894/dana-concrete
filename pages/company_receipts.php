<?php
require_once '../config/db_conected.php';
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Fetch company info
$company = $pdo->prepare('SELECT name FROM company WHERE id = ?');
$company->execute([$company_id]);
$company_row = $company->fetch(PDO::FETCH_ASSOC);
$company_name = $company_row ? $company_row['name'] : '';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پسووڵەی کۆمپانیا</title>
    <link rel="stylesheet" href="../assets/css/receipts.css">
    <link rel="stylesheet" href="../assets/fonts/Rabar_021.ttf">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Rabar', 'Rabar_021', sans-serif; direction: rtl; }
    </style>
</head>
<body>
<div class="receipt-container">
    <div class="receipt-header">
        <div class="logo-circle">
            <img src="../assets/images/Screenshot_2025-07-05_103044-removebg-preview.png" alt="Dana Concrete Logo" class="receipt-logo" />
        </div>
        <div class="receipt-title">دانا کۆنکرێت</div>
        <div class="receipt-meta">
            <div class="payment-date-row">
                <i class="fa fa-calendar-alt"></i>
                <span>بەرواری ئەمڕۆ: <span id="payment-date"></span></span>
            </div>
            <div class="phone-number-row">
                <i class="fa fa-phone"></i>
                <span>ژ.م: <span class="phone-number">0101 995 0772</span></span>
            </div>
        </div>
    </div>
    <div class="company-info-row">
        <div class="company-info-box">
            <i class="fa fa-building"></i>
            <span class="label">ناوی کۆمپانیا:</span>
            <span class="company-name"><?php echo htmlspecialchars($company_name); ?></span>
        </div>
    </div>
    <table class="receipt-table receipt-table-custom">
        <thead>
            <tr>
                <th>پێوانە</th>
                <th>ڕێژە</th>
                <th>نرخی 1 کیلۆ</th>
                <th>کۆی نرخ</th>
                <th>پارەی دراو (USD)</th>
                <th>پارەی دراو (د.ع)</th>
                <th>پارەی ماوە</th>
                <th>ژمارەی پسووڵە</th>
                <th>بەروار</th>
            </tr>
        </thead>
        <tbody id="receipt-table-body">
            <!-- Rows will be loaded by JS -->
        </tbody>
        <tfoot id="receipt-table-footer">
            <!-- Summary will be loaded by JS -->
        </tfoot>
    </table>
    <!-- تابلەی vertical پارەی واسڵ کراو و بەرواری پارەدان -->
    <table class="receipt-table receipt-table-custom" id="paid-table" style="margin-top: 1.5rem; width: 85%; margin-right: auto; margin-left: auto;">
        <thead>
            <tr>
                <th>پارەی واسڵ کراو (USD)</th>
                <th>پارەی واسڵ کراو (د.ع)</th>
                <th>بەرواری پارەدان</th>
                <th>تێبینی</th>
            </tr>
        </thead>
        <tbody id="paid-table-body">
            <!-- Rows will be loaded by JS -->
        </tbody>
    </table>
    <!-- زانیارییەکانی قەرز -->
    <div class="debt-summary">
        <div class="debt-summary-row">
            <div class="debt-summary-box">
                <i class="fa fa-history"></i>
                <span class="debt-label">قەرزی پێشوو:</span>
                <span class="debt-value" id="opening-debt">$0.00</span>
            </div>
            <div class="debt-summary-box">
                <i class="fa fa-money-bill-wave"></i>
                <span class="debt-label">پارەی ماوە:</span>
                <span class="debt-value" id="remaining-amount">$0.00</span>
            </div>
            <div class="debt-summary-box total-box">
                <i class="fa fa-calculator"></i>
                <span class="debt-label">کۆی گشتی پارەی ماوە:</span>
                <span class="debt-value" id="total-debt">$0.00</span>
            </div>
        </div>
    </div>
</div>
<script>
    const COMPANY_ID = <?php echo $company_id; ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/company_receipts/select_return_debt.js"></script>
<script src="../assets/js/company_receipts/select_purchase.js"></script>
<script>window.onload = function() { window.print(); };</script>
</body>
</html> 