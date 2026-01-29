<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_purchase')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

// Get bins for filter dropdown
$bins_stmt = $pdo->query("SELECT id, name, material_type FROM bins_silos ORDER BY name");
$bins = $bins_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مێژووی بڕی مەوادەکان - مانگانە</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/comon/forms.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <style>
        .filter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        .card-gradient-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .card-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .card-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .card-animate-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-animate-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-record {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-record:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        /* Table Controller Styles */
        .table-search-input {
            margin-top: 5px;
            font-size: 0.85rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
        }
        
        .table-search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .table-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
        }
        
        .table-pagination button {
            min-width: 35px;
            height: 35px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .table-pagination button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        
        .page-size-selector {
            margin-bottom: 10px;
            max-width: 100px;
        }
        
        .table-empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .table-empty-state i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        /* Selected row styling */
        .table tbody tr.selected {
            background-color: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
        }
        
        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-4">
                        <h2><i class="fas fa-history me-2"></i>مێژووی بڕی مەوادەکان - مانگانە</h2>
                        <p class="text-muted">پیشاندانی مێژووی بڕی مەوادەکان لە کۆتا ڕۆژی هەر مانگێک</p>
                    </div>
                </div>
            </div>

            <!-- Record Monthly Stock Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-save me-2"></i>تۆمارکردنی بڕی مەوادەکان</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="month_year" class="form-label">مانگ و ساڵ:</label>
                                    <input type="month" class="form-control" id="month_year" name="month_year">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-record" onclick="recordMonthlyStock()">
                                        <i class="fas fa-save me-2"></i>تۆمارکردنی بڕی مەوادەکان
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        ئەمە بڕی ئێستای مەوادەکان بۆ مانگی دیاریکراو تۆمار دەکات
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-gradient-info card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-database card-icon"></i>
                            <h6 class="card-title">کۆی شوێنەکان</h6>
                            <div class="fs-4 fw-bold" id="total-bins">0</div>
                            <small class="text-light">کۆی شوێنەکانی مەواد</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-gradient-success card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt card-icon"></i>
                            <h6 class="card-title">مانگە تۆمارکراوەکان</h6>
                            <div class="fs-4 fw-bold" id="recorded-months">0</div>
                            <small class="text-light">کۆی مانگە تۆمارکراوەکان</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-gradient-warning card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-chart-line card-icon"></i>
                            <h6 class="card-title">کۆی بڕی ئێستا</h6>
                            <div class="fs-4 fw-bold" id="current-total-amount">0</div>
                            <small class="text-light">کۆی بڕی ئێستای مەوادەکان</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-gradient-info card-animate-hover">
                        <div class="card-body">
                            <i class="fas fa-dollar-sign card-icon"></i>
                            <h6 class="card-title">کۆی بەهای ئێستا</h6>
                            <div class="fs-4 fw-bold" id="current-total-value">0</div>
                            <small class="text-light">کۆی بەهای ئێستای مەوادەکان</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="filter_bin" class="form-label">شوێن:</label>
                        <select class="form-select" id="filter_bin">
                            <option value="">هەموو شوێنەکان</option>
                            <?php foreach ($bins as $bin): ?>
                                <option value="<?php echo $bin['id']; ?>">
                                    <?php echo htmlspecialchars($bin['name'] . ' (' . $bin['material_type'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="filter_start_date" class="form-label">بەرواری دەستپێک:</label>
                        <input type="date" class="form-control" id="filter_start_date">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="filter_end_date" class="form-label">بەرواری کۆتایی:</label>
                        <input type="date" class="form-control" id="filter_end_date">
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-light me-2" onclick="applyFilters()">
                            <i class="fas fa-filter me-1"></i>فلتەر
                        </button>
                        <button type="button" class="btn btn-outline-light" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>پاککردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stock History Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>مێژووی بڕی مەوادەکان</h5>
                            <button type="button" class="btn btn-light btn-sm" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="stockHistoryTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>شوێن</th>
                                            <th>جۆری مەواد</th>
                                            <th>بڕ (کیلۆ)</th>
                                            <th>کۆی بەها</th>
                                            <th>نرخی ناوەند</th>
                                            <th>مانگ</th>
                                            <th>بەرواری تۆمارکردن</th>
                                            <th>تۆمارکراو لەلایەن</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/monthly_stock/monthly_stock.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>
