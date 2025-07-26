<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to print concrete receipts
if (!hasPermission('print_concrete_receipts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get receipt ID from URL parameter
$receipt_id = isset($_GET['id']) ? $_GET['id'] : null;
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسوڵەی کۆنکرێت - دانا کۆنکرێت</title>
    <link href="../assets/css/central_receipts.css" rel="stylesheet">
    <link rel="icon" href="../../../../public/assets/images/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Force portrait orientation for printing -->
 
   
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
        
        <!-- Receipt number and prepared concrete notice -->
        <div class="info-row1" style="margin-top:4px">
           
        <div class="concrete-type" style="font-size:14px;">
                کۆنکرێتی ئامادەکراو
            </div>
            <div class="receipt-title" style="font-size:14px;">پسوڵەی ناردنی کۆنکرێت</div>
            
           
            <div class="receipt-number" style="background: var(--light-green); font-size:14px; color: var(--primary-green); font-weight: bold; border-radius: var(--border-radius); padding: 3px 8px; font-size: 14px; display: inline-block;">
                ژ.پسووڵە: <span id="receipt_number">W-0001</span>
            </div>
        </div>
        
        <!-- Date, Location, Customer info -->
        <div class="customer-info" style="margin-top:4px;margin-bottom:4px;">
            <div>
                <strong>کڕیار:</strong> <span id="customer_name">-</span><span id="customer_receiver_sep"></span><span id="receiver_name">-</span>
            </div>
          
            <div>
                <strong>ژ.م:</strong> <span id="customer_phone">-</span>
            </div>
            <div>
                <strong>ناونیشان:</strong> <span id="location">-</span>
            </div>
           
            <div>
                <strong>بەروار:</strong> <span id="created_date">-</span>
            </div>
        </div>
        
        <!-- Main table -->
        <table class="receipt-table" dir="ltr" style="margin-top:4px">
            <thead>
                <tr>
                    <th>میکسەر</th>
                    <th>پەمپ</th>
                    <th>Mpa & Kg - جۆر</th>
                    <th>بڕی مەتر</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                    <span id="mixer_car_name">-</span>:میکسەر <br>
                        شۆفێر: <span id="mixer_driver_name">-</span><br>
                        ژ.مۆبایل: <span id="mixer_driver_mobile">-</span>
                    </td>
                    <td>
                    <span id="pump_car_name">-</span>:پەمپ <br>
                        شۆفێر: <span id="pump_driver_name">-</span><br>
                        ژ.مۆبایل: <span id="pump_driver_mobile">-</span>
                    </td>
                    <td>
                        <span id="strength_info" style="direction: ltr; display: inline-block;">-</span>
                    </td>
                    <td><span id="meter_amount">-</span></td>
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
                واژووی کارپێکەر
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
        <a href="/pages/concrete_receipts.php" class="btn-back">
            گەڕانەوە
        </a>
    </div>
    <?php elseif (isset($_GET['auto_print'])): ?>
    <!-- No action buttons when auto_print is set -->
    <?php else: ?>
    <div class="actions">
        <a href="../pages/concrete_receipts.php" class="btn-back">
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
    <script src="../assets/js/central_receipts/get_information.js"></script>
    <style>
    .loading-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.85);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #333;
        font-family: 'Rabar', Arial, sans-serif;
        transition: opacity 0.3s;
    }
    </style>
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

    // Redirect after print dialog closes (for manual print)
    window.addEventListener('afterprint', function() {
        window.location.href = '../pages/concrete_receipts.php?open_add=1';
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



