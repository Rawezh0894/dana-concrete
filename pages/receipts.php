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
            /* Hide entire filter section */
            .filter-section {
                display: none !important;
            }
            
            /* Hide individual filter elements (backup) */
            #transaction-type-filter, label[for="transaction-type-filter"],
            #month-filter, label[for="month-filter"],
            #date-from-filter, label[for="date-from-filter"],
            #date-to-filter, label[for="date-to-filter"],
            #location-filter, label[for="location-filter"],
            #show-invoice-number, label[for="show-invoice-number"],
            #show-opening-debt, label[for="show-opening-debt"],
            #force-debt-pagination, label[for="force-debt-pagination"],
            #print-btn, .fa-print,
            #refresh-btn, .fa-refresh {
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
        
        /* Hide invoice number column by default */
        .receipt-table th:nth-child(7),
        .receipt-table td:nth-child(7) {
            display: none !important;
        }
        
        /* Show invoice number column when explicitly enabled */
        .receipt-table th:nth-child(7).show-invoice,
        .receipt-table td:nth-child(7).show-invoice {
            display: table-cell !important;
        }
        
        /* Filter Section Styles */
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filter-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .filter-row:last-child {
            margin-bottom: 0;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
            flex: 1;
        }
        
        .filter-group.checkbox-group {
            flex-direction: row;
            align-items: center;
            min-width: auto;
        }
        
        .filter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-label i {
            color: #6c757d;
            font-size: 12px;
        }
        
        .filter-select,
        .filter-input {
            padding: 8px 12px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
            font-family: 'Rabar', sans-serif;
        }
        
        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .filter-checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 500;
            color: #495057;
            font-size: 14px;
            margin: 0;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        
        .filter-checkbox-label:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .filter-checkbox {
            display: none;
        }
        
        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid #ced4da;
            border-radius: 4px;
            position: relative;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .filter-checkbox:checked + .checkmark {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .filter-checkbox:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .filter-checkbox-label i {
            color: #6c757d;
            font-size: 12px;
        }
        
        /* Glass Morphism Effect for Receipt Header */
        .receipt-header {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            position: relative;
            overflow: hidden;
        }
        
        .receipt-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.05) 50%, 
                rgba(255, 255, 255, 0.1) 100%);
            pointer-events: none;
        }
        
        .receipt-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                rgba(0, 59, 115, 0.8) 0%, 
                rgba(0, 123, 255, 0.6) 50%, 
                rgba(0, 59, 115, 0.8) 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .receipt-title {
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .logo-circle {
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(0, 59, 115, 0.3);
        }
        
        .receipt-meta {
            position: relative;
            z-index: 1;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .filter-group.checkbox-group {
                flex-direction: row;
                justify-content: flex-start;
            }
        }
        .contact-info {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .contact-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1e40af, #3b82f6, #60a5fa);
        }

        .contact-info h3 {
            color: #1e3a8a;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .contact-info p {
            font-size: 13px;
            color: #374151;
            margin-bottom: 0;
            font-weight: 600;
            line-height: 1.6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-icon {
            font-size: 16px;
            color: #1e40af;
            margin-right: 8px;
            vertical-align: middle;
        }

        .contact-separator {
            color: #6b7280;
            font-weight: 400;
            margin: 0 8px;
        }
    </style>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
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
        <div class="receipt-title">دانا کۆنکرێت</div>
        <div class="logo-circle">
            <img src="../assets/images/Screenshot_2025-07-05_103044-removebg-preview.png" alt="Dana Concrete Logo" class="receipt-logo" />
        </div>
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
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-container">
            <!-- Row 1: Main Filters -->
            <div class="filter-row">
                <div class="filter-group">
                    <label for="transaction-type-filter" class="filter-label">
                        <i class="fa fa-filter"></i> جۆری مامەڵە:
                    </label>
                    <select id="transaction-type-filter" class="filter-select">
            <option value="all">هەموو</option>
            <option value="cash">نەقد</option>
            <option value="debt">قەرز</option>
            <option value="has_remaining">پارەی ماوە</option>
        </select>
                </div>
                
                <div class="filter-group">
                    <label for="month-filter" class="filter-label">
                        <i class="fa fa-calendar"></i> مانگ:
                    </label>
                    <select id="month-filter" class="filter-select">
                        <option value="all">هەموو</option>
                        <option value="01">کانوونی دووەم</option>
                        <option value="02">شوبات</option>
                        <option value="03">ئازار</option>
                        <option value="04">نیسان</option>
                        <option value="05">ئایار</option>
                        <option value="06">حوزەیران</option>
                        <option value="07">تەممووز</option>
                        <option value="08">ئاب</option>
                        <option value="09">ئەیلوول</option>
                        <option value="10">تشرینی یەکەم</option>
                        <option value="11">تشرینی دووەم</option>
                        <option value="12">کانوونی یەکەم</option>
        </select>
                </div>
                
                <div class="filter-group">
                    <label for="location-filter" class="filter-label">
                        <i class="fa fa-map-marker-alt"></i> شوێن:
        </label>
                    <select id="location-filter" class="filter-select">
            <option value="all">هەموو</option>
        </select>
                </div>
            </div>
            
            <!-- Row 2: Date Range Filters -->
            <div class="filter-row">
                <div class="filter-group">
                    <label for="date-from-filter" class="filter-label">
                        <i class="fa fa-calendar-alt"></i> لە بەروار:
                    </label>
                    <input type="date" id="date-from-filter" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label for="date-to-filter" class="filter-label">
                        <i class="fa fa-calendar-alt"></i> بۆ بەروار:
                    </label>
                    <input type="date" id="date-to-filter" class="filter-input">
                </div>
            </div>
            
            <!-- Row 3: Display Options -->
            <div class="filter-row">
                <div class="filter-group checkbox-group">
                    <label for="show-invoice-number" class="filter-checkbox-label">
                        <input type="checkbox" id="show-invoice-number" class="filter-checkbox">
                        <span class="checkmark"></span>
                        <i class="fa fa-file-invoice"></i> نیشاندانی ژمارەی پسووڵە
        </label>
                </div>
                
                <div class="filter-group checkbox-group">
                    <label for="show-opening-debt" class="filter-checkbox-label">
                        <input type="checkbox" id="show-opening-debt" checked class="filter-checkbox">
                        <span class="checkmark"></span>
                        <i class="fa fa-credit-card"></i> نیشاندانی قەرزی پێشوو
        </label>
                </div>
                
                <div class="filter-group checkbox-group">
                    <label for="force-debt-pagination" class="filter-checkbox-label">
                        <input type="checkbox" id="force-debt-pagination" class="filter-checkbox">
                        <span class="checkmark"></span>
                        <i class="fa fa-columns"></i> جیاکردنەوەی زانیارییەکانی قەرز
                    </label>
                </div>
            </div>
        </div>
    </div>

   
    <table class="receipt-table receipt-table-custom">
        <thead>
            <tr>
                <th>شوێن</th>
                <th>پێوانە</th>
                <th>ڕێژە</th>
                <th>نرخی 1 م 3</th>
                <th>کۆی نرخ</th>
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
    <div class="debt-summary" id="debt-summary-container">
        <!-- Pagination controls -->
        <div class="debt-pagination" id="debt-pagination" style="display: none;">
            <button id="prev-page-btn" onclick="changeDebtPage(-1)">
                <i class="fa fa-chevron-right"></i> پێشوو
            </button>
            <span class="page-info" id="debt-page-info">لاپەڕەی 1 لە 1</span>
            <button id="next-page-btn" onclick="changeDebtPage(1)">
                دواتر <i class="fa fa-chevron-left"></i>
            </button>
        </div>
        
        <!-- Debt summary pages -->
        <div id="debt-summary-pages">
            <!-- Pages will be dynamically generated -->
        </div>
    </div>
    
    <!-- Contact Information Footer -->
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     <br>
     
    <div class="contact-info" style="font-size:12px; margin-top: 2rem; text-align: center; padding: 1rem; border-top: 1px solid #dee2e6;"> 
        <p style="font-size:12px; margin: 0; line-height: 1.6;">
            <i class="fa fa-map-marker-alt" style="margin-left: 0.5rem; color: #6c757d;"></i>
           سلێمانی، تاسڵوجە - نزیک بازگەی کەڵەوانان
            <br>
            <i class="fa fa-phone" style="margin-left: 0.5rem; color: #6c757d;"></i>
            1454 144 0773
            <i class="fa fa-phone" style="margin-left: 1rem; color: #6c757d;"></i>
            0101 995 0772
            <i class="fa fa-phone" style="margin-left: 1rem; color: #6c757d;"></i>
            0543 152 0750
        </p>
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
                var location = document.getElementById('location-filter');
                var showInvoiceCheckbox = document.getElementById('show-invoice-number');
                var showOpeningDebtCheckbox = document.getElementById('show-opening-debt');
                var forceDebtPaginationCheckbox = document.getElementById('force-debt-pagination');
                
                if (type) type.value = 'all';
                if (month) month.value = 'all';
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                if (location) location.value = 'all';
                if (showInvoiceCheckbox) {
                    showInvoiceCheckbox.checked = false;
                    toggleInvoiceNumberColumn(false);
                    // Clear localStorage preference
                    localStorage.removeItem('showInvoiceNumber');
                }
                if (showOpeningDebtCheckbox) {
                    showOpeningDebtCheckbox.checked = true;
                    // Clear localStorage preference
                    localStorage.removeItem('showOpeningDebt');
                }
                if (forceDebtPaginationCheckbox) {
                    forceDebtPaginationCheckbox.checked = false;
                    // Clear localStorage preference
                    localStorage.removeItem('forceDebtPagination');
                }
                
                // Reload data
                if (typeof loadSalesData === 'function') loadSalesData();
                
                // Re-format invoice numbers after data refresh (only if column is visible)
                setTimeout(() => {
                    if (showInvoiceCheckbox && showInvoiceCheckbox.checked) {
                    formatAllInvoiceNumbers();
                    }
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
                // If no saved preference, default to hidden state
                showInvoiceCheckbox.checked = false;
                toggleInvoiceNumberColumn(false);
            }
            
            showInvoiceCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                toggleInvoiceNumberColumn(isChecked);
                // Save preference to localStorage
                localStorage.setItem('showInvoiceNumber', isChecked.toString());
            });
        }
        
        // Show opening debt toggle
        var showOpeningDebtCheckbox = document.getElementById('show-opening-debt');
        if (showOpeningDebtCheckbox) {
            // Load saved preference
            const savedOpeningDebtPreference = localStorage.getItem('showOpeningDebt');
            if (savedOpeningDebtPreference !== null) {
                showOpeningDebtCheckbox.checked = savedOpeningDebtPreference === 'true';
            }
            
            showOpeningDebtCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                // Save preference to localStorage
                localStorage.setItem('showOpeningDebt', isChecked.toString());
                
                // Toggle opening debt display
                toggleOpeningDebtDisplay(isChecked);
                
                // Recalculate debt summary based on checkbox state
                if (window.receiptManager && window.receiptManager.updateDebtSummary) {
                    const openingDebt = window.OPENING_DEBT || 0;
                    const remainingTotal = window.REMAINING_TOTAL || 0;
                    window.receiptManager.updateDebtSummary(openingDebt, remainingTotal);
                }
                
                // Note: Table summary is NOT affected by opening debt filter
                // The table shows only sales transaction totals, not opening debt
            });
        }
        
        // Force debt pagination toggle
        var forceDebtPaginationCheckbox = document.getElementById('force-debt-pagination');
        if (forceDebtPaginationCheckbox) {
            // Load saved preference
            const savedPaginationPreference = localStorage.getItem('forceDebtPagination');
            if (savedPaginationPreference !== null) {
                forceDebtPaginationCheckbox.checked = savedPaginationPreference === 'true';
            }
            
            forceDebtPaginationCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                // Save preference to localStorage
                localStorage.setItem('forceDebtPagination', isChecked.toString());
                
                // Reload debt summary with new pagination setting
                if (window.receiptManager && window.OPENING_DEBT !== undefined && window.REMAINING_TOTAL !== undefined) {
                    const debtData = {
                        openingDebt: window.OPENING_DEBT,
                        remainingAmount: window.REMAINING_TOTAL,
                        totalDebt: window.OPENING_DEBT + window.REMAINING_TOTAL
                    };
                    window.receiptManager.initializeDebtPagination(debtData);
                }
            });
        }
        
        // Format all invoice numbers on page load
        function formatAllInvoiceNumbers() {
            const table = document.querySelector('.receipt-table');
            if (table) {
                const dataRows = table.querySelectorAll('tbody tr');
                dataRows.forEach(row => {
                    if (row.children[6]) {
                        const invoiceCell = row.children[6];
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
        
        // Get the invoice number column (7th column, index 6)
        const headerRow = table.querySelector('thead tr');
        const dataRows = table.querySelectorAll('tbody tr');
        
        if (headerRow && headerRow.children[6]) {
            if (show) {
                headerRow.children[6].classList.add('show-invoice');
            } else {
                headerRow.children[6].classList.remove('show-invoice');
            }
        }
        
        // Hide/show invoice number column in all data rows
        dataRows.forEach(row => {
            if (row.children[6]) {
                if (show) {
                    row.children[6].classList.add('show-invoice');
                } else {
                    row.children[6].classList.remove('show-invoice');
                }
                
                // Format invoice numbers to show only 3 per row when visible
                if (show) {
                    const invoiceCell = row.children[6];
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
        
        // Note: The paid table doesn't have an invoice number column, so we don't need to handle it here
        // The paid table only has: Paid Amount USD, Paid Amount IQD, Payment Date, and Note columns
        // The invoice number filter should only affect the main receipt table
        
        // Update colspan in summary row to match visible columns
        updateSummaryColspan(show);
        
        // Also update the summary row if it exists
        if (window.receiptManager && window.receiptManager.updateSummary) {
            // Get current totals from the summary row
            const summaryRow = document.querySelector('.summary-row');
            if (summaryRow) {
                const firstCellText = summaryRow.querySelector('td:first-child')?.textContent || '';
                const secondCellText = summaryRow.querySelector('td:last-child')?.textContent || '';
                
                // Extract totals from the text (basic parsing)
                const totalMatch = firstCellText.match(/کۆی نرخ: \$?([\d,]+\.?\d*)/);
                const remainingMatch = secondCellText.match(/کۆی پارەی ماوە: \$?([\d,]+\.?\d*)/);
                
                if (totalMatch && remainingMatch) {
                    const total = parseFloat(totalMatch[1].replace(/,/g, ''));
                    const remaining = parseFloat(remainingMatch[1].replace(/,/g, ''));
                    window.receiptManager.updateSummary(total, remaining);
                }
            }
        }
    }
    
    // Function to update summary row colspan based on visible columns
    function updateSummaryColspan(showInvoiceColumn) {
        const summaryRow = document.querySelector('.summary-row');
        if (summaryRow) {
            const firstCell = summaryRow.querySelector('td:first-child');
            const secondCell = summaryRow.querySelector('td:last-child');
            
            if (firstCell && secondCell) {
                // If invoice column is visible, use 3 and 5 colspans (total 8 columns)
                // If invoice column is hidden, use 2 and 6 colspans (total 8 columns)
                if (showInvoiceColumn) {
                    firstCell.setAttribute('colspan', '3');
                    secondCell.setAttribute('colspan', '5');
                } else {
                    firstCell.setAttribute('colspan', '2');
                    secondCell.setAttribute('colspan', '6');
                }
            }
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
    
    // Function to toggle opening debt display
    function toggleOpeningDebtDisplay(show) {
        // Instead of just hiding/showing, recreate the debt summary with current checkbox state
        if (window.receiptManager && window.receiptManager.updateDebtSummary) {
            const openingDebt = window.OPENING_DEBT || 0;
            const remainingTotal = window.REMAINING_TOTAL || 0;
            window.receiptManager.updateDebtSummary(openingDebt, remainingTotal);
        }
    }
    
    // Function to change debt summary pages
    function changeDebtPage(direction) {
        if (!window.receiptManager) return;
        
        const currentPage = window.DEBT_CURRENT_PAGE || 0;
        const totalPages = window.DEBT_TOTAL_PAGES || 1;
        const newPage = currentPage + direction;
        
        if (newPage >= 0 && newPage < totalPages) {
            window.DEBT_CURRENT_PAGE = newPage;
            window.receiptManager.showDebtPage(newPage);
            window.receiptManager.updateDebtPaginationControls();
        }
    }
</script>
<script src="../assets/js/receipts/receipts.js"></script>
<script src="../assets/js/receipts/select_sale.js"></script>
<script src="../assets/js/receipts/select_return_debt.js"></script>
<script src="../assets/js/receipts/load_locations.js"></script>
</body>
</html>
