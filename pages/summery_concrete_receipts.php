<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to view concrete receipts summary
if (!hasPermission('view_concrete_receipts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        . '<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        . '</div>';
    exit;
}

$customers = $pdo->query("SELECT id, name, mobile1 FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پوختەی پسووڵەکانی کۆنکرێت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/summery_concrete_receipts.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">پوختەی پسووڵەکانی کۆنکرێت</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-1"></i>هەناردەکردن
                </button>
                <button class="btn btn-outline-success" onclick="printSummary()">
                    <i class="fas fa-print me-1"></i>پرینت
                </button>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row mb-4" id="summary-cards">
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100 summary-card">
                    <div class="card-body">
                        <h5 class="card-title">کۆی گشتی پسووڵەکان</h5>
                        <div class="summary-value" id="total_receipts" style="color:var(--seafoam-green);">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100 summary-card">
                    <div class="card-body">
                        <h5 class="card-title">کۆی گشتی بڕی مەتر سێجا</h5>
                        <div class="summary-value" id="total_meter_cubic" style="color:var(--kelly-green);">0</div>
                        <div class="summary-unit">م³</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100 summary-card">
                    <div class="card-body">
                        <h5 class="card-title">کۆی کڕیاران</h5>
                        <div class="summary-value" id="total_customers" style="color:#1976d2;">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100 summary-card">
                    <div class="card-body">
                        <h5 class="card-title">کۆی فۆرمۆلاکان</h5>
                        <div class="summary-value" id="total_formulas" style="color:#ff6b35;">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="row g-3 mb-4 filter-section" id="filters">
            <div class="col-md-3">
                <select class="form-select" id="filter_customer_id">
                    <option value="">کڕیار: هەموو</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filter_formula_id">
                    <option value="">فۆرمۆلا: هەموو</option>
                    <?php foreach ($formulas as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" id="filter_date_from" placeholder="لە بەرواری">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" id="filter_date_to" placeholder="بۆ بەرواری">
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="filter_today">
                        <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="filter_reset">
                        <i class="fas fa-redo me-1"></i>ڕیفڕێش
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Summary Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center summary-table" id="summaryTable">
                <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                    <tr>
                        <th>#</th>
                        <th>ناوی کڕیار</th>
                        <th>ژمارە تەلەفۆن</th>
                        <th>کۆی پسووڵەکان</th>
                        <th>کۆی بڕی مەتر سێجا</th>
                        <th>فۆرمۆلاکان</th>
                        <th>شوێنەکان</th>
                        <th>وەرگرەکان</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade customer-details-modal" id="customerDetailsModal" tabindex="-1" aria-labelledby="customerDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerDetailsModalLabel">وردەکاری کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="customerDetailsContent">
                        <div class="customer-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>ناوی کڕیار: <strong id="modal_customer_name">-</strong></h6>
                                    <h6>ژمارە تەلەفۆن: <strong id="modal_customer_phone">-</strong></h6>
                                </div>
                                <div class="col-md-6">
                                    <h6>کۆی پسووڵەکان: <strong id="modal_total_receipts">0</strong></h6>
                                    <h6>کۆی بڕی مەتر سێجا: <strong id="modal_total_meter">0 م³</strong></h6>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>ژمارەی پسووڵە</th>
                                        <th>بەروار</th>
                                        <th>شوێن</th>
                                        <th>وەرگر</th>
                                        <th>بڕی مەتر سێجا</th>
                                        <th>فۆرمۆلا</th>
                                    </tr>
                                </thead>
                                <tbody id="modal_receipts_table">
                                    <!-- Receipts will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert.js"></script>
    <script src="../assets/js/comon/table-controler.js"></script>
    <script src="../assets/js/summery_concrete_receipts/get_informations.js"></script>
    <script src="../assets/js/summery_concrete_receipts/filter.js"></script>
</body>
</html>
