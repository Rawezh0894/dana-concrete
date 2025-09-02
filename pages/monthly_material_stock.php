<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
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
    <link href="../assets/css/comon/rabar_font.css" rel="stylesheet">
    <style>
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .main-content {
            background: transparent;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .page-header h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .filter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .filter-section .form-label {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        
        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 1rem;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
            color: white;
        }
        
        .filter-section .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .card-gradient-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .card-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.3);
        }
        
        .card-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
        }
        
        .card-animate-hover {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .card-animate-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .fs-4 {
            font-size: 2rem !important;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .table-responsive {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
        }
        
        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1rem;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        .btn-record {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-record:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-light {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: bold;
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-light:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        
        .btn-outline-light {
            border: 2px solid rgba(255,255,255,0.5);
            color: white;
            font-weight: bold;
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.8);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 20px 25px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .text-muted {
            color: #666 !important;
            font-size: 0.95rem;
        }
        
        .text-center.text-muted {
            padding: 40px;
            font-size: 1.1rem;
        }
        
        .text-center.text-muted i {
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .number {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .text-end {
            text-align: right !important;
        }
        
        /* Animation for loading */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card, .filter-section, .page-header {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 15px;
            color: #2c3e50;
            font-size: 0.95rem;
            padding: 15px;
        }
        
        .alert-info i {
            color: #667eea;
        }
        
        .alert-info strong {
            color: #667eea;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 2rem;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .filter-section {
                padding: 20px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .btn-record {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }
        
        /* Loading animation */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .fa-spinner {
            color: #667eea;
        }
        
        .text-danger {
            color: #e74c3c !important;
        }
        
        .text-danger i {
            color: #e74c3c;
        }
        
        /* Enhanced table styling */
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .table tbody tr:nth-child(odd) {
            background-color: white;
        }
        
        /* Custom scrollbar for table */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
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
                        <div class="card-header">
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
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>تێبینی:</strong> ئەمە بڕی ئێستای مەوادەکان بۆ مانگی دیاریکراو تۆمار دەکات
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-enhanced gradient-primary text-white fade-in-up">
                        <div class="card-body">
                            <i class="fas fa-database card-icon"></i>
                            <h6 class="card-title">کۆی شوێنەکان</h6>
                            <div class="fs-4 fw-bold" id="total-bins">0</div>
                            <small class="text-light">کۆی شوێنەکانی مەواد</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-enhanced gradient-success text-white fade-in-up">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt card-icon"></i>
                            <h6 class="card-title">مانگە تۆمارکراوەکان</h6>
                            <div class="fs-4 fw-bold" id="recorded-months">0</div>
                            <small class="text-light">کۆی مانگە تۆمارکراوەکان</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-enhanced gradient-warning text-white fade-in-up">
                        <div class="card-body">
                            <i class="fas fa-chart-line card-icon"></i>
                            <h6 class="card-title">کۆی بڕی ئێستا</h6>
                            <div class="fs-4 fw-bold" id="current-total-amount">0</div>
                            <small class="text-light">کۆی بڕی ئێستای مەوادەکان</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center shadow card-enhanced gradient-info text-white fade-in-up">
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
                    <div class="card shadow card-enhanced">
                        <div class="card-header gradient-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>مێژووی بڕی مەوادەکان</h5>
                            <button type="button" class="btn btn-light btn-sm btn-enhanced" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-1"></i>ئیکسپۆرتی Excel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive custom-scrollbar">
                                <table class="table table-enhanced" id="stockHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>شوێن</th>
                                            <th>جۆری مەواد</th>
                                            <th>بڕ (کیلۆ)</th>
                                            <th>کۆی بەها</th>
                                            <th>نرخی ناوەند</th>
                                            <th>مانگ</th>
                                            <th>بەرواری تۆمارکردن</th>
                                            <th>تۆمارکراو لەلایەن</th>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/monthly_stock/monthly_stock.js"></script>
</body>
</html>
