<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_sale')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
$customers = $pdo->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرۆشتنی قەرز</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">فرۆشتنی قەرز</h2>
    </div>
    <div class="row mb-3">
      <div class="col-md-3">
        <label>لە بەروار:</label>
        <input type="date" id="filter_from" class="form-control">
      </div>
      <div class="col-md-3">
        <label>بۆ بەروار:</label>
        <input type="date" id="filter_to" class="form-control">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
      </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center " id="creditSaleTable">
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
                    <th>بڕ</th>
                    <th>نرخی یەکە</th>
                    <th>کۆی نرخ</th>
                    <th>پارەی دراو بە دینار</th>
                    <th>پارەی دراو بە دۆلار</th>
                    <th>پارەی ماوە</th>
                    <th>نرخی ١٠٠ دۆلار</th>
                    <th>تێبینی</th>
                    <th>داشکاندن</th>
                </tr>
            </thead>
            <tbody>
                <!-- Credit sales will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/select2_script.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/sale/select_credit_sale.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html> 