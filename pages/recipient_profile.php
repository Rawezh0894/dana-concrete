<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_recipient')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

$recipient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($recipient_id <= 0) {
    echo '<!DOCTYPE html>
    <html lang="ku" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>هەڵە - وەرگر دیاری نەکراوە</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="../assets/css/variables.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
    <body>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;background: linear-gradient(135deg, var(--seafoam-green) 0%, var(--kelly-green) 100%);">
            <div class="text-center bg-white p-5 rounded shadow" style="max-width: 500px;">
                <i class="fas fa-exclamation-triangle" style="font-size:5rem;color:#ffc107;margin-bottom:20px;"></i>
                <h2 style="color:#666;margin-bottom:15px;">ناسنامەی وەرگر دیاری نەکراوە</h2>
                <p style="color:#888;margin-bottom:25px;">تکایە وەرگرێک هەڵبژێرە لە لیستی وەرگرەکان</p>
                <a href="recipients.php" class="btn btn-primary" style="background: var(--seafoam-green); border: none; padding: 10px 25px; font-weight: bold;">
                    <i class="fas fa-users"></i> گەڕانەوە بۆ وەرگرەکان
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// First try to get from recipients table
$stmt = $pdo->prepare('SELECT *, "recipient_only" AS recipient_type FROM recipients WHERE id = ?');
$stmt->execute([$recipient_id]);
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

// If not found, try to get from customers table (is_recipient = 1)
if (!$recipient) {
    $stmt = $pdo->prepare('SELECT *, "customer_and_recipient" AS recipient_type FROM customers WHERE id = ? AND is_recipient = 1');
    $stmt->execute([$recipient_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$recipient) {
    echo '<!DOCTYPE html>
    <html lang="ku" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>هەڵە - وەرگر نەدۆزرایەوە</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="../assets/css/variables.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
    <body>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;background: linear-gradient(135deg, var(--seafoam-green) 0%, var(--kelly-green) 100%);">
            <div class="text-center bg-white p-5 rounded shadow" style="max-width: 500px;">
                <i class="fas fa-user-times" style="font-size:5rem;color:#dc3545;margin-bottom:20px;"></i>
                <h2 style="color:#666;margin-bottom:15px;">وەرگر نەدۆزرایەوە</h2>
                <p style="color:#888;margin-bottom:25px;">وەرگرێک بە ناسنامەی '.htmlspecialchars($recipient_id).' بوونی نییە</p>
                <a href="recipients.php" class="btn btn-primary" style="background: var(--seafoam-green); border: none; padding: 10px 25px; font-weight: bold;">
                    <i class="fas fa-users"></i> گەڕانەوە بۆ وەرگرەکان
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

$recipient_name = $recipient['name'];
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرۆفایلی وەرگر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;"><?php echo htmlspecialchars($recipient_name); ?></h2>
                <?php 
                $recipient_type = $recipient['recipient_type'] ?? 'recipient_only';
                if ($recipient_type === 'customer_and_recipient'): 
                ?>
                    <span class="badge bg-success" style="font-size: 0.9rem;">
                        <i class="fas fa-user-check"></i> کڕیار و وەرگر
                    </span>
                <?php else: ?>
                    <span class="badge bg-info" style="font-size: 0.9rem;">
                        <i class="fas fa-user"></i> تەنها وەرگر
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0">پوختەی هەموو فرۆشتنەکان بەم وەرگرەوە پەیوەست بوون</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="recipients.php" class="btn" style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
                <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ وەرگرەکان
            </a>
        </div>
    </div>

    <div class="row mb-4" id="recipient-summary-cards">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-cube card-icon"></i>
                    <h6 class="card-title">کۆی مەتری گەیاندراو</h6>
                    <div class="fs-4 fw-bold" id="total_quantity">0 م³</div>
                    <small class="text-light">سەرجەم مەتری کۆنکرێت</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-file-invoice card-icon"></i>
                    <h6 class="card-title">ژمارەی فرۆشتنەکان</h6>
                    <div class="fs-4 fw-bold" id="sales_count">0</div>
                    <small class="text-light">تۆمارە فرۆشتنەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-wallet card-icon"></i>
                    <h6 class="card-title">کۆی پارەی ماوە</h6>
                    <div class="fs-4 fw-bold" id="total_remaining">$0.00</div>
                    <small class="text-light">بڕی قەرزی ماوە</small>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="recipientSalesTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>کڕیار</th>
                    <th>وەرگر</th>
                    <th>شوێن</th>
                    <th>ژمارەی پسوڵە</th>
                    <th>فۆرمۆلا</th>
                    <th>بەروار</th>
                    <th>جۆری پارەدان</th>
                    <th>بڕ (م³)</th>
                    <th>نرخی یەکە</th>
                    <th>کۆی نرخ</th>
                    <th>پارەی دراو (USD)</th>
                    <th>پارەی دراو (IQD)</th>
                    <th>پارەی ماوە</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>داشکاندن</th>
                    <th>تێبینی</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>
<script nonce="<?php echo $csp_nonce; ?>">
    const RECIPIENT_ID = <?php echo (int)$recipient_id; ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipient_profile/recipient_profile.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipient_profile/select.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>

