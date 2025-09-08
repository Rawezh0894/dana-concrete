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
$sql = "SELECT c.id, c.name, c.mobile1, c.mobile2, c.opening_debt_usd, c.opening_debt_iqd,
    (
        SELECT IFNULL(SUM(quantity),0) FROM sales s WHERE s.customer_id = c.id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
    ) as total_credit_meter,
    (
        SELECT IFNULL(SUM(remaining_amount),0) FROM sales s WHERE s.customer_id = c.id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
    ) as total_sales_debt
FROM customers c
WHERE c.opening_debt_usd > 0 OR c.opening_debt_iqd > 0 OR EXISTS (
    SELECT 1 FROM sales s WHERE s.customer_id = c.id AND s.payment_type = 'قەرز' AND s.remaining_amount > 0
)
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
// Sort customers: those with remaining invoice debt first, then by name
usort($customers, function($a, $b) {
    $aScore = (floatval($a['total_sales_debt'] ?? 0) > 0) ? 0 : 1;
    $bScore = (floatval($b['total_sales_debt'] ?? 0) > 0) ? 0 : 1;
    if ($aScore !== $bScore) return $aScore - $bScore;
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});
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
    <link href="../assets/css/credit_of_all_customers.css" rel="stylesheet">
    <style>
        /* Remove all inline styles as they are now in the CSS file */
        
        /* Filter Section Styling */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .filter-section .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .filter-section .form-control {
            border: 2px solid #ced4da;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .filter-section .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .filter-section .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        
        .filter-section .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .gap-2 {
            gap: 0.5rem !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-section .row > div {
                margin-bottom: 15px;
            }
        }
    </style>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<div id="printSection" class="a4-sheet">
    <div class="report-header">
        <h1>لیستی قەرزی هەموو کڕیاران</h1>
        <div class="subtitle">ڕاپۆرتی تەواوی مامەڵە قەرزەکان بە وردی</div>
        <div class="date">بەروار و کات: <?php echo date('Y-m-d H:i'); ?></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="section-title">پرینتی قەرزی کڕیارەکان</h2>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> پرینت</button>
    </div>

    <!-- Invoice Number Filter -->
    <div class="filter-section mb-4 no-print">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="invoiceFilter" class="form-label">
                        <i class="fa fa-filter"></i> فلتەری ژمارەی پسوڵە:
                    </label>
                    <input type="text" id="invoiceFilter" class="form-control" placeholder="ژمارەی پسوڵە بنووسە بۆ فلتەرکردن...">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">کردارەکان:</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearInvoiceFilter()">
                            <i class="fa fa-times"></i> پاککردنەوە
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="toggleInvoiceVisibility()">
                            <i class="fa fa-eye-slash"></i> <span id="toggleText">دەرنەکەوتنی ژمارەی پسوڵە</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  
    <?php foreach ($customers as $c): ?>
        <?php
            $total_sales_amount = 0;
            if (!empty($sales_by_customer[$c['id']])) {
                foreach ($sales_by_customer[$c['id']] as $s) {
                    $total_sales_amount += floatval($s['total_price']);
                }
            }
            
            // کۆی قەرز (قەرزی سەرەتای + پارەی ماوەی مامەڵەکان)
            $total_debt = floatval($c['opening_debt_usd']) + floatval($c['total_sales_debt']);
        ?>
        <div class="customer-card print-break-inside-avoid">
            <div class="customer-info">
                <span class="text-primary">
                    <i class="fa fa-user-circle"></i>
                    <?= htmlspecialchars($c['name']) ?>
                </span>
                <span class="text-secondary">
                    <i class="fa fa-phone"></i>
                    <?= htmlspecialchars($c['mobile1']) ?>
                </span>
                <span>
                    <i class="fa fa-cube"></i>
                    مەتر سێجا: <?= number_format($c['total_credit_meter'], 2) ?> م³
                </span>
                
                <span>
                    <i class="fa fa-money-bill-wave"></i>
                    قەرزی سەرەتای: <?= number_format($c['opening_debt_usd'], 2) ?> $
                </span>
                <span>
                    <i class="fa fa-credit-card"></i>
                    پارەی ماوەی مامەڵەکان: <?= number_format($c['total_sales_debt'], 2) ?> $
                </span>
                <span class="text-danger fw-bold">
                    <i class="fa fa-exclamation-triangle"></i>
                    کۆی قەرز: <?= number_format($total_debt, 2) ?> $
                </span>
            </div>

            <?php if (!empty($sales_by_customer[$c['id']])): ?>
            <div class="table-container">
                <table class="credit-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>وەرگر</th>
                            <th>شوێن</th>
                            <th>مەتر</th>
                            <th>نرخ/مەتر</th>
                            <th>کۆی گشتی</th>
                            <th>دراو ($)</th>
                            <th>دراو (IQD)</th>
                            <th>ماوە</th>
                            <th>ژ.فاکتور</th>
                            <th>ڕێکەوت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_by_customer[$c['id']] as $i => $s): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($s['recipient']) ?></td>
                            <td><?= htmlspecialchars($s['location']) ?></td>
                            <td><?= number_format($s['quantity'], 2) ?> م³</td>
                            <td><?= number_format($s['price_per_unit'], 2) ?> $</td>
                            <td><?= number_format($s['total_price'], 2) ?> $</td>
                            <td><?= number_format($s['amount_paid_usd'], 2) ?> $</td>
                            <td><?= number_format($s['amount_paid_iq'], 0) ?> د.ع</td>
                            <td><?= number_format($s['remaining_amount'], 2) ?> $</td>
                            <td><?= htmlspecialchars($s['invoice_number']) ?></td>
                            <td><?= htmlspecialchars($s['order_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="p-3 text-muted">هیچ مامەڵە قەرزێکی پارەی ماوە نییە.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-5 no-print">
        <button class="btn btn-secondary" onclick="window.history.back()">گەڕانەوە</button>
    </div>
</div>

<script>
// Invoice Number Filter Functionality
let invoiceNumbersVisible = true;

function filterByInvoiceNumber() {
    const filterValue = document.getElementById('invoiceFilter').value.toLowerCase().trim();
    const customerCards = document.querySelectorAll('.customer-card');
    
    customerCards.forEach(card => {
        const invoiceCells = card.querySelectorAll('td:nth-child(10)'); // Invoice number column (10th after removing discount)
        let shouldShowCard = false;
        
        if (filterValue === '') {
            shouldShowCard = true;
        } else {
            invoiceCells.forEach(cell => {
                const invoiceText = cell.textContent.toLowerCase();
                if (invoiceText.includes(filterValue)) {
                    shouldShowCard = true;
                }
            });
        }
        
        card.style.display = shouldShowCard ? 'block' : 'none';
    });
}

function clearInvoiceFilter() {
    document.getElementById('invoiceFilter').value = '';
    filterByInvoiceNumber();
}

function toggleInvoiceVisibility() {
    const invoiceColumns = document.querySelectorAll('th:nth-child(10), td:nth-child(10)');
    const toggleText = document.getElementById('toggleText');
    
    invoiceNumbersVisible = !invoiceNumbersVisible;
    
    invoiceColumns.forEach(cell => {
        cell.style.display = invoiceNumbersVisible ? 'table-cell' : 'none';
    });
    
    toggleText.textContent = invoiceNumbersVisible ? 'دەرنەکەوتنی ژمارەی پسوڵە' : 'دەرکەوتنی ژمارەی پسوڵە';
    
    // Update button icon
    const toggleButton = document.querySelector('.btn-outline-info i');
    toggleButton.className = invoiceNumbersVisible ? 'fa fa-eye-slash' : 'fa fa-eye';
}

// Add event listener for real-time filtering
document.addEventListener('DOMContentLoaded', function() {
    const invoiceFilter = document.getElementById('invoiceFilter');
    if (invoiceFilter) {
        invoiceFilter.addEventListener('input', filterByInvoiceNumber);
    }
});
</script>
</body>
</html>
