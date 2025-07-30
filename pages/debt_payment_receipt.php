<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to print debt receipts
if (!hasPermission('print_concrete_receipts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get debt payment ID from URL parameter
$debt_id = isset($_GET['id']) ? $_GET['id'] : null;
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسوڵەی دانەوەی قەرز - دانا کۆنکرێت</title>
    
    <link href="../assets/css/debt_payment_receipt.css" rel="stylesheet">
    <link rel="icon" href="../../../../public/assets/images/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="receipt" id="receiptTemplate">
        <!-- Decorative corners -->
        <img src="../assets/images/leaves.png" class="corner corner-top-left" alt="corner">
        <img src="../assets/images/leaves.png" class="corner corner-top-right" alt="corner">
        <img src="../assets/images/leaves.png" class="corner corner-bottom-left" alt="corner">
        <img src="../assets/images/leaves.png" class="corner corner-bottom-right" alt="corner">
        
        <div class="header">
            <div class="company-info">
                <div class="title-arabic">دانا کۆنکرێت</div>
                <div class="title-english">Dana Concrete Company</div>
            </div>
            
            <!-- Center: Logo -->
            <div class="logo">
                <img src="../assets/images/logo.png" alt="Dana Concrete">
            </div>
            
            <!-- Right: Phone numbers -->
            <div class="contact-info">
                <div class="contact-item">
                    <span class="phone-icon"><i class="bi bi-telephone"></i></span>
                    <span style="margin-left: 5px;">0770 152 8120</span>
                </div>
                <div class="contact-item">
                    <span class="phone-icon"><i class="bi bi-telephone"></i></span>
                    <span style="margin-left: 5px;">0773 144 5414</span>
                </div>
                <div class="contact-item">
                    <span class="phone-icon"><i class="bi bi-telephone"></i></span>
                    <span style="margin-left: 5px;">0772 995 0101</span>
                </div>
                <div class="contact-item">
                    <span class="phone-icon"><i class="bi bi-telephone"></i></span>
                    <span style="margin-left: 5px;">0750 152 0543</span>
                </div>
            </div>
        </div>
        
        <!-- Receipt number and debt payment notice -->
        <div class="info-row1" style="margin-top:5px">
            <div class="concrete-type" style="font-size:16px;">
                پسوڵەی دانەوەی قەرز
            </div>
            <div class="receipt-title" style="font-size:16px;">پسوڵەی ناردنی دانەوەی قەرز</div>
            
            <div class="receipt-number" style="background: var(--light-green); font-size:16px; color: var(--primary-green); font-weight: bold; border-radius: var(--border-radius); padding: 4px 10px; font-size: 16px; display: inline-block;">
                ژ.پسووڵە: <span id="receipt_number">QW-0001</span>
            </div>
        </div>
        
        <!-- Customer info -->
        <div class="customer-info" style="margin-top:5px;margin-buttom:5px;">
            <div>
                <strong>کڕیار:</strong> <span id="customer_name">-</span>
            </div>
            <div>
                <strong>ژ.م:</strong> <span id="customer_phone">-</span>
            </div>
            <div>
                <strong>ناونیشان:</strong> <span id="customer_address">-</span>
            </div>
            <div>
                <strong>بەروار:</strong> <span id="payment_date">-</span>
            </div>
        </div>
        
        <!-- Main table for debt payment details -->
        <table class="receipt-table" dir="rtl" style="margin-top:5px">
            <thead>
                <tr>
                    <th>بەروار</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>بڕی پارەی داوە بە دۆلار</th>
                    <th>بڕی پارەی داوە بە دینار</th>
                    <th>داشکاندن</th>
                    <th>تێبینی</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span id="debt_payment_date">-</span></td>
                    <td><span id="dolar_rate">-</span></td>
                    <td><span id="paid_usd">-</span></td>
                    <td><span id="paid_iqd">-</span></td>
                    <td><span id="discount">-</span></td>
                    <td><span id="note">-</span></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Decorative line section -->
        <div class="decorative-line-section">
            <div class="decorative-line"></div>
            <div class="decorative-text">✧ ✧ ✧</div>
            <div class="decorative-line"></div>
        </div>
        
        <!-- Footer signatures -->
        <div class="footer">
            <div class="signature-box">
                واژووی پێدەر
                <span class="signature-line"></span>
            </div>
            <div class="signature-box">
                واژووی وەرگر
                <span class="signature-line"></span>
            </div>
        </div>
    </div>

    <?php if (!isset($_GET['id'])): ?>
    <div class="actions">
        <a href="../pages/add_customers.php" class="btn-back">
            گەڕانەوە
        </a>
    </div>
    <?php elseif (isset($_GET['auto_print'])): ?>
    <!-- No action buttons when auto_print is set -->
    <?php else: ?>
    <div class="actions">
        <a href="../pages/add_customers.php" class="btn-back" id="backButton">
            گەڕانەوە
        </a>
        <?php if (hasPermission('print_concrete_receipts')): ?>
        <button onclick="printInPortrait()" class="btn-print">
            چاپکردن
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Include the external JavaScript file -->
    <script src="../assets/js/debt_payment_receipt/get_information.js"></script>
  
    <div id="print-loading-overlay" class="loading-overlay" style="display:none;">
        <span>تکایە چاوەڕێ بکە ...</span>
    </div>
    <script>
    function printInPortrait() {
        var overlay = document.getElementById('print-loading-overlay');
        overlay.style.display = 'flex';
        setTimeout(function() {
            overlay.style.display = 'none';
            window.print();
        }, 500);
    }

    // Function to update back button link
    function updateBackButtonLink() {
        if (typeof CUSTOMER_ID_FOR_REDIRECT !== 'undefined' && CUSTOMER_ID_FOR_REDIRECT) {
            const backButton = document.getElementById('backButton');
            if (backButton) {
                backButton.href = '../pages/customer_profile.php?id=' + CUSTOMER_ID_FOR_REDIRECT;
            }
        }
    }
    
    // Update back button when customer ID is available
    document.addEventListener('DOMContentLoaded', function() {
        // Check periodically for customer ID
        const checkInterval = setInterval(function() {
            if (typeof CUSTOMER_ID_FOR_REDIRECT !== 'undefined' && CUSTOMER_ID_FOR_REDIRECT) {
                updateBackButtonLink();
                clearInterval(checkInterval);
            }
        }, 100);
    });
    
    // Redirect after print dialog closes (for manual print)
    window.addEventListener('afterprint', function() {
        if (typeof CUSTOMER_ID_FOR_REDIRECT !== 'undefined' && CUSTOMER_ID_FOR_REDIRECT) {
            window.location.href = '../pages/customer_profile.php?id=' + CUSTOMER_ID_FOR_REDIRECT;
        } else {
            window.location.href = '../pages/add_customers.php';
        }
    });
    </script>
<?php if (isset($_GET['auto_print'])): ?>
    <script>
    window.addEventListener('DOMContentLoaded', function() {
        printInPortrait();
    });
    </script>
<?php endif; ?>
</body>
</html> 