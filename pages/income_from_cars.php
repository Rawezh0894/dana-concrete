<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_income_from_cars')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کاروان حیسابی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-container h5 {
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }
        .chart-wrapper {
            height: 400px;
            position: relative;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .chart-wrapper {
                height: 300px;
            }
        }
    </style>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>کاروان حیسابی</h3>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="refreshData()" title="نوێکردنەوە">
                <i class="fa fa-refresh"></i> نوێکردنەوە
            </button>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fa fa-file-excel"></i> دانەوە بۆ Excel
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-truck card-icon"></i>
                    <h6 class="card-title">کۆی سەیارەکان</h6>
                    <div class="fs-4 fw-bold" id="totalCars">0</div>
                    <small class="text-light">سەیارەی بەکارهێنراو</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-user card-icon"></i>
                    <h6 class="card-title">کۆی شۆفێران</h6>
                    <div class="fs-4 fw-bold" id="totalDrivers">0</div>
                    <small class="text-light">شۆفێری بەکارهێنراو</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-cube card-icon"></i>
                    <h6 class="card-title">کۆی مەتر سێج</h6>
                    <div class="fs-4 fw-bold" id="totalMeters">0</div>
                    <small class="text-light">م³ بارکراو</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center shadow card-gradient-danger card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-receipt card-icon"></i>
                    <h6 class="card-title">کۆی پسوڵەکان</h6>
                    <div class="fs-4 fw-bold" id="totalReceipts">0</div>
                    <small class="text-light">پسوڵەی بەکارهێنراو</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">سەیارەی میکسەر</label>
                <select id="mixerCarFilter" class="form-control select2">
                    <option value="">هەموو سەیارەکان</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">شۆفێری میکسەر</label>
                <select id="mixerDriverFilter" class="form-control select2">
                    <option value="">هەموو شۆفێران</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">سەیارەی پۆمپ</label>
                <select id="pumpCarFilter" class="form-control select2">
                    <option value="">هەموو سەیارەکان</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">شۆفێری پۆمپ</label>
                <select id="pumpDriverFilter" class="form-control select2">
                    <option value="">هەموو شۆفێران</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">لە بەروار</label>
                <input type="date" id="fromDate" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">بۆ بەروار</label>
                <input type="date" id="toDate" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">کڕیار</label>
                <select id="customerFilter" class="form-control select2">
                    <option value="">هەموو کڕیارەکان</option>
                </select>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="loadData()">
                    <i class="fa fa-search"></i> گەڕان
                </button>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h5>کاروان حیسابی بە پێی مەتر سێج</h5>
                <div class="chart-wrapper">
                    <canvas id="carsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5>داهاتی شۆفێران بە پێی مەتر سێج</h5>
                <div class="chart-wrapper">
                    <canvas id="driversChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-search-container">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark">وردەکاری کاروان حیسابی</label>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted" id="tableInfo">کۆی: 0 تۆمار</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" id="incomeTable">
                <thead>
                    <tr>
                        <th>ژمارەی پسوڵە</th>
                        <th>کڕیار</th>
                        <th>شوێن</th>
                        <th>بڕ (م³)</th>
                        <th>سەیارەی میکسەر</th>
                        <th>شۆفێری میکسەر</th>
                        <th>سەیارەی پۆمپ</th>
                        <th>شۆفێری پۆمپ</th>
                        <th>بەروار</th>
                        <th>وەرگر</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/income_from_cars/income_from_cars.js" nonce="<?php echo $csp_nonce; ?>"></script>

</body>
</html>
