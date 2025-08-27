<?php
session_start();
require_once '../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// TODO: Fetch customer, sales, and debt data using $customer_id
// For now, use placeholders
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پسووڵە</title>
    <link rel="stylesheet" href="../assets/css/receipts.css">
    <link rel="stylesheet" href="../assets/fonts/Rabar_021.ttf">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Rabar', 'Rabar_021', sans-serif; direction: rtl; }
    
        @media print {
            #transaction-type-filter, label[for="transaction-type-filter"],
            #month-filter, label[for="month-filter"],
            #date-from-filter, label[for="date-from-filter"],
            #date-to-filter, label[for="date-to-filter"],
            #show-invoice-number, label[for="show-invoice-number"],
            #print-btn, .fa-print,
            #refresh-btn, .fa-refresh {
                display: none !important;
            }
            /* Also hide the parent div if needed */
            #transaction-type-filter:parent, #transaction-type-filter:parent * {
                display: none !important;
            }
        }
        
        /* Checkbox styling */
        #show-invoice-number {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #003b73;
        }
        
        #show-invoice-number:checked {
            accent-color: #009688;
        }
        
        label[for="show-invoice-number"] {
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        label[for="show-invoice-number"]:hover {
            color: #003b73;
        }
        
        /* Invoice number formatting styles */
        .receipt-table td {
            vertical-align: top;
            line-height: 1.4;
        }
        
        /* Ensure proper spacing for invoice numbers with line breaks */
        .receipt-table td br {
            display: block;
            content: "";
            margin-top: 0.2rem;
        }
        
        /* Style for invoice number cells */
        .receipt-table td:nth-child(8),
        #paid-table td:nth-child(4) {
            white-space: pre-line;
            word-wrap: break-word;
            max-width: 200px;
        }
    </style>
</head>
<body>
<!-- Print & Refresh Buttons (outside container) -->
<div style="text-align: center; margin-bottom: 1.5rem;">
    <button id="print-btn" style="padding: 0.5rem 2rem; font-size: 1.1rem; background: #003b73; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
        <i class="fa fa-print"></i> چاپ
    </button>
    <button id="refresh-btn" style="padding: 0.5rem 2rem; font-size: 1.1rem; background: #009688; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-right: 1rem;">
        <i class="fa fa-refresh"></i> ڕیفرێش
    </button>
</div>
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
            <span class="label">ناوی کڕیار:</span>
            <span class="customer-name">ناوی کڕیار</span>
        </div>
        <div class="company-info-box">
            <i class="fa fa-phone"></i>
            <span class="label">ژ.م کڕیار:</span>
            <span class="customer-mobile">ژ.م کڕیار</span>
        </div>
    </div>
    <!-- Transaction Type Filter -->
    <div style="text-align: center; margin: 1rem 0;">
        <label for="transaction-type-filter" style="font-weight:bold;">جۆری مامەڵە:</label>
        <select id="transaction-type-filter" style="padding: 0.3rem 1rem; font-size: 1rem;">
            <option value="all">هەموو</option>
            <option value="cash">نەقد</option>
            <option value="debt">قەرز</option>
            <option value="has_remaining">پارەی ماوە</option>
        </select>
        <!-- Month Filter -->
        <label for="month-filter" style="font-weight:bold; margin-right: 1rem;">مانگ:</label>
        <select id="month-filter" style="padding: 0.3rem 1rem; font-size: 1rem;">
            <option value="all">All</option>
            <option value="01">January</option>
            <option value="02">February</option>
            <option value="03">March</option>
            <option value="04">April</option>
            <option value="05">May</option>
            <option value="06">June</option>
            <option value="07">July</option>
            <option value="08">August</option>
            <option value="09">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <!-- Date Range Filter -->
        <label for="date-from-filter" style="font-weight:bold; margin-right: 1rem;">لە بەروار:</label>
        <input type="date" id="date-from-filter" style="padding: 0.3rem; font-size: 1rem;">
        <label for="date-to-filter" style="font-weight:bold; margin-right: 1rem;">بۆ بەروار:</label>
        <input type="date" id="date-to-filter" style="padding: 0.3rem; font-size: 1rem;">
        
        <!-- Invoice Number Visibility Checkbox -->
        <label for="show-invoice-number" style="font-weight:bold; margin-right: 1rem; margin-left: 1rem;">
            <input type="checkbox" id="show-invoice-number" checked style="margin-left: 0.5rem;">
            نیشاندانی ژمارەی پسووڵە
        </label>
    </div>

    <!-- Grouping Information -->
    <div style="text-align: center; margin: 1rem 0; padding: 0.5rem; background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 6px; color: #0c5460;">
        <i class="fa fa-info-circle" style="margin-left: 0.5rem;"></i>
        <strong>تێبینی:</strong> ئەو ڕیکۆردانەی کە هەمان بەروار و هەمان ڕێژەیان هەیە، بە یەک ڕیز دەنوێنرێنەوە
    </div>

    <table class="receipt-table receipt-table-custom">
        <thead>
            <tr>
                <th>پێوانە</th>
                <th>ڕێژە</th>
                <th>نرخی 1 م 3</th>
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
    const CUSTOMER_ID = <?php echo $customer_id; ?>;
    document.addEventListener('DOMContentLoaded', function() {
        var printBtn = document.getElementById('print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                window.print();
            });
        }
        var refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                // Reset all filters to default
                var type = document.getElementById('transaction-type-filter');
                var month = document.getElementById('month-filter');
                var dateFrom = document.getElementById('date-from-filter');
                var dateTo = document.getElementById('date-to-filter');
                var showInvoiceCheckbox = document.getElementById('show-invoice-number');
                
                if (type) type.value = 'all';
                if (month) month.value = 'all';
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                if (showInvoiceCheckbox) {
                    showInvoiceCheckbox.checked = true;
                    toggleInvoiceNumberColumn(true);
                    // Clear localStorage preference
                    localStorage.removeItem('showInvoiceNumber');
                }
                
                // Reload data
                if (typeof loadSalesData === 'function') loadSalesData();
                
                // Re-format invoice numbers after data refresh
                setTimeout(() => {
                    formatAllInvoiceNumbers();
                }, 500);
            });
        }
        
        // Invoice number visibility toggle
        var showInvoiceCheckbox = document.getElementById('show-invoice-number');
        if (showInvoiceCheckbox) {
            // Load saved preference
            const savedPreference = localStorage.getItem('showInvoiceNumber');
            if (savedPreference !== null) {
                showInvoiceCheckbox.checked = savedPreference === 'true';
                toggleInvoiceNumberColumn(showInvoiceCheckbox.checked);
            } else {
                // If no saved preference, format invoice numbers for default visible state
                formatAllInvoiceNumbers();
            }
            
            showInvoiceCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                toggleInvoiceNumberColumn(isChecked);
                // Save preference to localStorage
                localStorage.setItem('showInvoiceNumber', isChecked.toString());
            });
        }
        
        // Format all invoice numbers on page load
        function formatAllInvoiceNumbers() {
            const table = document.querySelector('.receipt-table');
            if (table) {
                const dataRows = table.querySelectorAll('tbody tr');
                dataRows.forEach(row => {
                    if (row.children[7]) {
                        const invoiceCell = row.children[7];
                        const originalInvoiceNumber = invoiceCell.textContent;
                        
                        // Store original invoice number
                        invoiceCell.setAttribute('data-original-invoice', originalInvoiceNumber);
                        
                        // Format to show only 3 invoice numbers per row
                        const formattedInvoice = formatInvoiceNumbers(originalInvoiceNumber);
                        invoiceCell.innerHTML = formattedInvoice;
                    }
                });
            }
            
            // Also format paid table
            const paidTable = document.getElementById('paid-table');
            if (paidTable) {
                const paidDataRows = paidTable.querySelectorAll('tbody tr');
                paidDataRows.forEach(row => {
                    if (row.children[3]) {
                        const invoiceCell = row.children[3];
                        const originalInvoiceNumber = invoiceCell.textContent;
                        
                        // Store original invoice number
                        invoiceCell.setAttribute('data-original-invoice', originalInvoiceNumber);
                        
                        // Format to show only 3 invoice numbers per row
                        const formattedInvoice = formatInvoiceNumbers(originalInvoiceNumber);
                        invoiceCell.innerHTML = formattedInvoice;
                    }
                });
            }
        }
    });
    
    // Function to toggle invoice number column visibility
    function toggleInvoiceNumberColumn(show) {
        const table = document.querySelector('.receipt-table');
        if (!table) return;
        
        // Get the invoice number column (8th column, index 7)
        const headerRow = table.querySelector('thead tr');
        const dataRows = table.querySelectorAll('tbody tr');
        
        if (headerRow && headerRow.children[7]) {
            headerRow.children[7].style.display = show ? '' : 'none';
        }
        
        // Hide/show invoice number column in all data rows
        dataRows.forEach(row => {
            if (row.children[7]) {
                row.children[7].style.display = show ? '' : 'none';
                
                // Format invoice numbers to show only 3 per row when visible
                if (show) {
                    const invoiceCell = row.children[7];
                    const originalInvoiceNumber = invoiceCell.getAttribute('data-original-invoice') || invoiceCell.textContent;
                    
                    // Store original invoice number if not already stored
                    if (!invoiceCell.getAttribute('data-original-invoice')) {
                        invoiceCell.setAttribute('data-original-invoice', originalInvoiceNumber);
                    }
                    
                    // Format to show only 3 invoice numbers per row
                    const formattedInvoice = formatInvoiceNumbers(originalInvoiceNumber);
                    invoiceCell.innerHTML = formattedInvoice;
                }
            }
        });
        
        // Also handle the paid table if it exists
        const paidTable = document.getElementById('paid-table');
        if (paidTable) {
            const paidHeaderRow = paidTable.querySelector('thead tr');
            const paidDataRows = paidTable.querySelectorAll('tbody tr');
            
            if (paidHeaderRow && paidHeaderRow.children[3]) {
                paidHeaderRow.children[3].style.display = show ? '' : 'none';
            }
            
            paidDataRows.forEach(row => {
                if (row.children[3]) {
                    row.children[3].style.display = show ? '' : 'none';
                    
                    // Format invoice numbers to show only 3 per row when visible
                    if (show) {
                        const invoiceCell = row.children[3];
                        const originalInvoiceNumber = invoiceCell.getAttribute('data-original-invoice') || invoiceCell.textContent;
                        
                        // Store original invoice number if not already stored
                        if (!invoiceCell.getAttribute('data-original-invoice')) {
                            invoiceCell.setAttribute('data-original-invoice', originalInvoiceNumber);
                        }
                        
                        // Format to show only 3 invoice numbers per row
                        const formattedInvoice = formatInvoiceNumbers(originalInvoiceNumber);
                        invoiceCell.innerHTML = formattedInvoice;
                    }
                }
            });
        }
    }
    
    // Function to format invoice numbers to show only 3 per row
    function formatInvoiceNumbers(invoiceNumbers) {
        if (!invoiceNumbers || invoiceNumbers.trim() === '') {
            return '';
        }
        
        // Split by comma and clean up
        const invoices = invoiceNumbers.split(',').map(inv => inv.trim()).filter(inv => inv);
        
        if (invoices.length === 0) {
            return '';
        }
        
        // Group into rows of 3
        const rows = [];
        for (let i = 0; i < invoices.length; i += 3) {
            const row = invoices.slice(i, i + 3);
            rows.push(row.join(', '));
        }
        
        // Join rows with line breaks
        return rows.join('<br>');
    }
</script>
<script src="../assets/js/receipts/receipts.js"></script>
<script src="../assets/js/receipts/select_sale.js"></script>
<script src="../assets/js/receipts/select_return_debt.js"></script>
</body>
</html>
