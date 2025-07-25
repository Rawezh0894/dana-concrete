<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to view concrete receipts
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
                <button class="btn btn-success" id="export_excel">
                    <i class="fas fa-file-excel me-2"></i>ئێکسێل
                </button>
                <button class="btn btn-danger" id="export_pdf">
                    <i class="fas fa-file-pdf me-2"></i>پی دی ئێف
                </button>
                <button class="btn btn-info" id="print_summary">
                    <i class="fas fa-print me-2"></i>پرینت
                </button>
                <a href="concrete_receipts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>گەڕانەوە
                </a>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row mb-4" id="summary-cards">
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title">کۆی گشتی پسووڵەکان</h5>
                        <span id="total_receipts" style="font-size:2rem;font-weight:bold;color: var(--seafoam-green);">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title">کۆی گشتی بڕی مەتر سێجا</h5>
                        <span id="total_meter_cubic" style="font-size:2rem;font-weight:bold;color: var(--kelly-green);">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title">کۆی کڕیاران</h5>
                        <span id="total_customers" style="font-size:2rem;font-weight:bold;color: #1976d2;">0</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title">نرخی مامناوەند</h5>
                        <span id="average_price" style="font-size:2rem;font-weight:bold;color: #ff6b35;">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <select class="form-select" id="filter_customer_id">
                    <option value="">کڕیار: هەموو</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filter_today">
                        <i class="fas fa-calendar-day me-1"></i>ئەمڕۆ
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filter_yesterday">
                        <i class="fas fa-calendar-minus me-1"></i>دوێنێ
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filter_this_week">
                        <i class="fas fa-calendar-week me-1"></i>ئەم هەفتانە
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filter_this_month">
                        <i class="fas fa-calendar-alt me-1"></i>ئەم مانگانە
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="filter_reset">
                        <i class="fas fa-redo me-1"></i>ڕیفڕێش
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Summary Table -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">پوختەی کڕیاران</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="customerSummaryTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>ناوی کڕیار</th>
                                <th>ژمارەی پسووڵەکان</th>
                                <th>کۆی بڕی مەتر سێجا</th>
                                <th>فۆرمۆلا</th>
                                <th>شوێن</th>
                                <th>وەرگر</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1" aria-labelledby="customerDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerDetailsModalLabel">وردەکاری کڕیار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="customerDetailsContent">
                        <!-- Customer details will be loaded here -->
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
    <script src="../assets/js/summery_concrete_receipts/filter.js"></script>
    <script src="../assets/js/summery_concrete_receipts/get_informations.js"></script>
    <script>
        // Load initial data when page loads
        $(document).ready(function() {
            setTimeout(function() {
                if (typeof loadSummaryData === 'function') {
                    loadSummaryData();
                }
            }, 100);
        });
    </script>
</body>
</html>
