<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_other_expenses')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خەرجی سەیارەکان</title>
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
        .summary-card {
            background: var(--kelly-green);
            color: var(--seafoam-green);
            border: none;
            font-weight: bold;
        }
        .summary-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--seafoam-green);
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">خەرجی سەیارەکان</h2>
    </div>
    <div class="row mb-3 align-items-end g-3">
        <div class="col-md-3">
            <label for="monthFilter" class="form-label mb-0">فلتەر بە مانگ:</label>
            <input type="month" id="monthFilter" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="yearFilter" class="form-label mb-0">فلتەر بە ساڵ:</label>
            <select id="yearFilter" class="form-control">
                <option value="">هەموو ساڵەکان</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
        </div>
    </div>
    <div class="row mb-4" id="car-expenses-summary">
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow summary-card">
                <div class="card-body">
                    <h5 class="card-title">کۆی گشتی خەرجی بە دۆلار</h5>
                    <span id="summary_total_usd" class="summary-value">$0</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow summary-card">
                <div class="card-body">
                    <h5 class="card-title">کۆی گشتی خەرجی بە دینار</h5>
                    <span id="summary_total_iqd" class="summary-value">0 د.ع</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow summary-card">
                <div class="card-body">
                    <h5 class="card-title">ژمارەی سەیارەکان</h5>
                    <span id="summary_count" class="summary-value">0</span>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="carExpensesTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناوی سەیارە</th>
                    <th>کۆی خەرجی بە دینار</th>
                    <th>کۆی خەرجی بە دۆلار</th>
                    <th>ژمارەی خەرجیەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded by JS -->
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/cars_expenses/select.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
