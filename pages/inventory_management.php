<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی کۆگا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Rabar';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }

        :root {
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.2);
            --primary-accent: #3b82f6;
            --secondary-accent: #6366f1;
            --header-gradient: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --stat-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Rabar', 'Outfit', 'Noto Sans Arabic', sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }

        .main-content {
            margin-right: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .page-header {
            background: var(--header-gradient);
            padding: 2.5rem;
            border-radius: 24px;
            color: white;
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .inventory-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 2rem;
        }

        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1);
        }

        .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            padding: 1.5rem !important;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .nav-pills {
            background: white;
            padding: 0.5rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            display: inline-flex;
            margin-bottom: 2.5rem;
        }

        .nav-pills .nav-link {
            border-radius: 12px;
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
            border: none;
            margin: 0 2px;
        }

        .nav-pills .nav-link.active {
            background: var(--primary-accent) !important;
            color: white !important;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .nav-pills .nav-link:not(.active):hover {
            background: #f8fafc;
            color: #1e293b;
        }

        .stat-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.025em;
            padding: 1.2rem 1rem;
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: white;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .purchase-row {
            background: white;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 1.25rem;
            transition: all 0.2s ease;
        }

        .purchase-row:hover {
            border-color: var(--primary-accent);
            box-shadow: var(--card-shadow);
        }

        .btn-premium {
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-premium {
            background: var(--primary-accent);
            border: none;
            color: white;
        }

        .btn-primary-premium:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .badge-premium {
            padding: 0.5em 1em;
            border-radius: 8px;
            font-weight: 600;
        }

        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 1.5rem 2rem;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 1.5rem 2rem;
        }

        /* RTL Specifics */
        .me-1 { margin-left: 0.25rem !important; margin-right: 0 !important; }
        .me-2 { margin-left: 0.5rem !important; margin-right: 0 !important; }
        .ms-1 { margin-right: 0.25rem !important; margin-left: 0 !important; }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-1">بەڕێوەبردنی کۆگا</h1>
                    <p class="opacity-75 mb-0">کۆنترۆڵکردنی سەرجەم کاڵاکان، کڕینەکان و دەرچووەکانی کۆگا</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
                    <button class="btn btn-light btn-premium text-info" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
                        <i class="fas fa-list-alt"></i> پۆلێنەکان
                    </button>
                    <button class="btn btn-light btn-premium text-info" data-bs-toggle="modal" data-bs-target="#manageUnitsModal">
                        <i class="fas fa-ruler-combined"></i> یەکەکان
                    </button>
                    <button class="btn btn-light btn-premium text-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus"></i> کاڵای نوێ
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills" id="inventoryTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="stock-tab" data-bs-toggle="pill" data-bs-target="#stock-panel">
                    <i class="fas fa-boxes"></i> کۆگا و کاڵا
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="purchase-tab" data-bs-toggle="pill" data-bs-target="#purchase-panel">
                    <i class="fas fa-shopping-cart"></i> تۆمارکردنی کڕین
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="issue-tab" data-bs-toggle="pill" data-bs-target="#issue-panel">
                    <i class="fas fa-shuttle-van"></i> دەرکردن بۆ سەیارە
                </button>
            </li>
        </ul>

        <div class="tab-content" id="inventoryTabsContent">
            <!-- Stock Panel -->
            <div class="tab-pane fade show active" id="stock-panel">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="inventory-card p-4">
                            <div class="stat-badge bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <h6 class="text-muted fw-bold">کۆی جۆری کاڵاکان</h6>
                            <h3 class="fw-bold mb-0" id="totalItemCount">0</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="inventory-card p-4">
                            <div class="stat-badge bg-success bg-opacity-10 text-success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h6 class="text-muted fw-bold">کۆی بەهای کۆگا (USD)</h6>
                            <h3 class="fw-bold mb-0" id="totalStockValue">$0.00</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="inventory-card p-4">
                            <div class="stat-badge bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h6 class="text-muted fw-bold">کاڵا کەمبووەکان</h6>
                            <h3 class="fw-bold mb-0" id="lowStockCount">0</h3>
                        </div>
                    </div>
                </div>

                <div class="inventory-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list-ul me-2 text-primary"></i>لیستی گشتی کۆگا</span>
                        <button class="btn btn-sm btn-light border-0" onclick="loadStock()">
                            <i class="fas fa-sync-alt text-muted"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="stockTable">
                                <thead>
                                    <tr>
                                        <th>ناوی کاڵا</th>
                                        <th>پۆلێن</th>
                                        <th>بڕی ماوە</th>
                                        <th>تێکڕای نرخ (USD)</th>
                                        <th>کۆی بەها (USD)</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody id="stockData">
                                    <!-- Dynamic Data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Entry Panel -->
            <div class="tab-pane fade" id="purchase-panel">
                <div class="inventory-card">
                    <div class="card-header">
                        <span><i class="fas fa-plus-circle me-2 text-success"></i>تۆمارکردنی کڕینی نوێ</span>
                    </div>
                    <div class="card-body p-4">
                        <form id="purchaseForm">
                            <div class="row g-4 mb-5">
                                <div class="col-md-3">
                                    <label class="form-label fw-600">ژمارەی پسوڵە</label>
                                    <input type="text" name="invoice_number" class="form-control" required placeholder="بۆ نموونە: INV-001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">ناوی فرۆشیار (کەسانی خەرجی تر)</label>
                                    <select name="person_id" id="supplier_select" class="form-select select2" required>
                                        <option value="">-- هەڵبژێرە --</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">بەرواری کڕین</label>
                                    <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">نرخی ١٠٠ دۆلار (دینار)</label>
                                    <input type="number" name="exchange_rate" id="p_exchange_rate" class="form-control" value="150000" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">لیستی کاڵاکانی پێویست</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPurchaseRow()">
                                    <i class="fas fa-plus me-1"></i>زیادکردنی ڕیز
                                </button>
                            </div>
                            
                            <div id="purchaseItemsList">
                                <!-- Row Template -->
                            </div>
                            
                            <div class="text-start mt-4">
                                <hr class="opacity-10">
                                <button type="submit" class="btn btn-premium btn-primary-premium">
                                    <i class="fas fa-save"></i> هەڵگرتنی تۆمارەکە
                                </button>
                            </div>
                        </form>

                        <hr class="my-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>مێژووی کڕینەکان</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="purchaseTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>بەروار</th>
                                        <th>ژ. پسوڵە</th>
                                        <th>فرۆشیار</th>
                                        <th>کۆی بڕ (USD)</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseData">
                                    <!-- Dynamic Data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Issue/Issuance Panel -->
            <div class="tab-pane fade" id="issue-panel">
                <div class="inventory-card mb-4">
                    <div class="card-header">
                        <span><i class="fas fa-share-square me-2 text-danger"></i>دەرکردنی کاڵا بۆ سەیارە</span>
                    </div>
                    <div class="card-body p-4">
                        <form id="issueForm">
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-600">سەیارە</label>
                                    <select name="vehicle_id" id="issue_vehicle_id" class="form-select select2" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-600">بەروار</label>
                                    <input type="date" name="issued_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">لیستی کاڵاکان</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="addIssueRow()">
                                    <i class="fas fa-plus me-1"></i>زیادکردنی کاڵا
                                </button>
                            </div>

                            <div id="issueItemsList" class="mb-3">
                                <!-- Dynamic Rows -->
                            </div>

                            <div class="text-start mt-4">
                                <hr class="opacity-10">
                                <button type="submit" class="btn btn-premium btn-primary-premium bg-danger border-0">
                                    <i class="fas fa-paper-plane"></i> پەسەندکردنی دەرکردنی گشتی
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="inventory-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2 text-info"></i>دوایین جوڵەکانی دەرکردن</span>
                        <div style="min-width: 200px;">
                                <select id="vehicleFilter" class="form-select form-select-sm" onchange="loadMaintenanceReport()">
                                    <option value="">هەموو سەیارەکان</option>
                                </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="maintenanceSummary" class="bg-light p-3 border-bottom d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">کۆی تێچووی چاککردنەوە:</span>
                                <span class="h5 mb-0 fw-bold text-primary" id="vehicleTotalCost">$0.00</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>تاریخ</th>
                                        <th>سەیارە</th>
                                        <th>کاڵا</th>
                                        <th>بڕ</th>
                                        <th>تێچوو</th>
                                    </tr>
                                </thead>
                                <tbody id="issuanceData">
                                    <!-- Dynamic Data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">کاڵایەکی نوێ زیاد بکە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addItemForm">
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-600">ناوی کاڵا</label>
                            <input type="text" name="name" class="form-control" required placeholder="ناوی پارچە یان مەواد بنووسە">
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-600">پۆلێن</label>
                                <select name="category" id="item_category_select" class="form-select" required>
                                    <!-- Categories will be here -->
                                </select>
                            </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-600">یەکەی سەرەکی (بچووک)</label>
                                <select name="unit" id="item_unit_select" class="form-select" required>
                                    <!-- Units will be here -->
                                </select>
                                <small class="text-muted">نموونە: دانە، لیتر</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600">یەکەی دووەم (گەورە - ئیختیاری)</label>
                                <select name="secondary_unit" id="item_secondary_unit_select" class="form-select">
                                    <option value="">نییە</option>
                                    <!-- Units will be here -->
                                </select>
                                <small class="text-muted">نموونە: کارتۆن، بەرمیل</small>
                            </div>
                        </div>
                        <div class="mb-0 animate__animated animate__fadeIn d-none" id="conversionFactorDiv">
                            <label class="form-label fw-600">ڕێژەی گۆڕین (Conversion Factor)</label>
                            <div class="input-group">
                                <span class="input-group-text small">یەک یەکەی گەورە = </span>
                                <input type="number" step="0.0001" name="conversion_factor" class="form-control" value="1" placeholder="چەند لە یەکەی بچووک؟">
                                <span class="input-group-text small" id="primaryUnitLabel">یەکە</span>
                            </div>
                            <small class="text-info"><i class="fas fa-info-circle me-1"></i> بۆ نموونە: ئەگەر یەکەی گەورە بەرمیل بێت و بچووک لیتر، بنووسە ٢٠٠</small>
                        </div>

                        <hr class="my-4 opacity-10">
                        <h6 class="fw-bold mb-3"><i class="fas fa-warehouse me-1"></i> باری موجودی کۆگا (Stock Status)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">بڕی موجود (بچووک)</label>
                                <input type="number" step="0.01" name="current_qty" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">تێکڕای نرخ (USD)</label>
                                <input type="number" step="0.0001" name="avg_cost_usd" class="form-control" value="0">
                            </div>
                        </div>
                        <div id="openingStockSection">
                            <hr class="my-4 opacity-10">
                            <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-1"></i> بڕی سەرەتایی (Opening Stock)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">بڕی دەسپێک</label>
                                    <input type="number" step="0.01" name="opening_qty" class="form-control" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">نرخی دەسپێک</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="opening_cost" class="form-control" value="0">
                                        <select name="opening_currency" class="form-select" style="max-width: 90px;">
                                            <option value="USD">$ USD</option>
                                            <option value="IQD">IQD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="openingExchangeRateDiv" style="display:none;">
                                    <label class="form-label small text-muted">نرخی ١٠٠دۆلار (دینار)</label>
                                    <input type="number" name="opening_exchange_rate" class="form-control" value="150000">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-premium" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                        <button type="submit" class="btn btn-primary-premium btn-premium">تۆمارکردنی کاڵا</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage Categories Modal -->
    <div class="modal fade" id="manageCategoriesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">بەڕێوەبردنی پۆلێنەکان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addCategoryForm" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="name_ku" class="form-control" placeholder="پۆلێنی نوێ (نموونە: ڕۆن)" required>
                            <button type="submit" class="btn btn-primary">زیادکردن</button>
                        </div>
                    </form>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ناو</th>
                                    <th class="text-end">کردار</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesListData">
                                <!-- Categories dynamic list -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Units Modal -->
    <div class="modal fade" id="manageUnitsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">بەڕێوەبردنی یەکەکان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addUnitForm" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="name_ku" class="form-control" placeholder="یەکەی نوێ (نموونە: دانە)" required>
                            <button type="submit" class="btn btn-primary">زیادکردن</button>
                        </div>
                    </form>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ناو</th>
                                    <th class="text-end">کردار</th>
                                </tr>
                            </thead>
                            <tbody id="unitsListData">
                                <!-- Units dynamic list -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/comon/table-controler.js"></script>

    <script>
        let itemsGlobal = [];
        let unitsGlobal = [];
        let categoriesGlobal = [];

        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });
            
            loadUnits();
            loadCategories();
            loadItems();
            loadVehicles();
            loadStock();
            loadIssuances();
            loadPurchases();
            loadSuppliers();
            addPurchaseRow(); // Initial row
            addIssueRow(); // Initial row for issuance
        });

        async function loadUnits() {
            const res = await fetch('../process/inventory/get_units.php');
            const data = await res.json();
            if (data.success) {
                unitsGlobal = data.data;
                updateUnitUI();
            }
        }

        async function loadCategories() {
            const res = await fetch('../process/inventory/get_categories.php');
            const data = await res.json();
            if (data.success) {
                categoriesGlobal = data.data;
                updateCategoryUI();
            }
        }

        function updateUnitUI() {
            let html = '';
            let options = '<option value="">-- هەڵبژێرە --</option>';
            unitsGlobal.forEach(unit => {
                html += `
                    <tr>
                        <td>${unit.name_ku}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteUnit(${unit.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                options += `<option value="${unit.name_ku}">${unit.name_ku}</option>`;
            });
            $('#unitsListData').html(html);
            $('#item_unit_select').html(options);
            $('#item_secondary_unit_select').html(options);
            $('#item_unit_select').on('change', function() {
                $('#primaryUnitLabel').text($(this).val());
            });
            $('#item_secondary_unit_select').on('change', function() {
                if ($(this).val()) {
                    $('#conversionFactorDiv').removeClass('d-none');
                } else {
                    $('#conversionFactorDiv').addClass('d-none');
                }
            });

            // Handle currency change in Add Item modal
            $('select[name="opening_currency"]').on('change', function() {
                if ($(this).val() === 'IQD') {
                    $('#openingExchangeRateDiv').fadeIn();
                } else {
                    $('#openingExchangeRateDiv').fadeOut();
                }
            });
        }

        function updateCategoryUI() {
            let html = '';
            let options = '<option value="">-- هەڵبژێرە --</option>';
            categoriesGlobal.forEach(cat => {
                html += `
                    <tr>
                        <td>${cat.name_ku}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteCategory(${cat.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                options += `<option value="${cat.name_ku}">${cat.name_ku}</option>`;
            });
            $('#categoriesListData').html(html);
            $('#item_category_select').html(options);
        }

        async function loadSuppliers() {
            const res = await fetch('../process/other_expenses/select_persons.php');
            const persons = await res.json();
            let html = '<option value="">-- هەڵبژێرە --</option>';
            persons.forEach(person => {
                html += `<option value="${person.id}">${person.name}</option>`;
            });
            $('#supplier_select').html(html);
        }

        $('#addUnitForm').submit(async function(e) {
            e.preventDefault();
            const res = await fetch('../process/inventory/add_unit.php', {
                method: 'POST',
                body: new FormData(this)
            });
            const data = await res.json();
            if (data.success) {
                this.reset();
                loadUnits();
            }
        });

        $('#addCategoryForm').submit(async function(e) {
            e.preventDefault();
            const res = await fetch('../process/inventory/add_category.php', {
                method: 'POST',
                body: new FormData(this)
            });
            const data = await res.json();
            if (data.success) {
                this.reset();
                loadCategories();
            }
        });

        async function deleteUnit(id) {
            if (!confirm('ئایا دڵنیای لە سڕینەوەی ئەم یەکەیە؟')) return;
            const formData = new FormData();
            formData.append('id', id);
            const res = await fetch('../process/inventory/delete_unit.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                loadUnits();
            }
        }

        async function deleteCategory(id) {
            if (!confirm('ئایا دڵنیای لە سڕینەوەی ئەم پۆلێنە؟')) return;
            const formData = new FormData();
            formData.append('id', id);
            const res = await fetch('../process/inventory/delete_category.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                loadCategories();
            }
        }

        async function loadItems() {
            const res = await fetch('../process/inventory/get_items.php');
            const data = await res.json();
            if (data.success) {
                itemsGlobal = data.data;
                updateItemDropdowns();
            }
        }

        async function loadVehicles() {
            const res = await fetch('../process/other_expenses/select_cars.php');
            const data = await res.json();
            const cars = data.data || data;
            
            let html = '<option value="">-- سەیارە هەڵبژێرە --</option>';
            cars.forEach(car => {
                html += `<option value="${car.id}">${car.name}</option>`;
            });
            $('#issue_vehicle_id').html(html);
            $('#vehicleFilter').append(html);
        }

        function updateItemDropdowns() {
            let html = '<option value="">-- هەڵبژێرە --</option>';
            itemsGlobal.forEach(item => {
                html += `<option value="${item.id}" data-unit="${item.unit}" data-sunit="${item.secondary_unit || ''}" data-factor="${item.conversion_factor}">${item.name}</option>`;
            });
            
            $('.p-item-select, .i-item-select').each(function() {
                const currentVal = $(this).val();
                $(this).html(html).val(currentVal);
            });
        }

        function addIssueRow() {
            const container = $('#issueItemsList');
            const index = container.children().length;
            
            let options = '<option value="">-- هەڵبژێرە --</option>';
            itemsGlobal.forEach(item => {
                options += `<option value="${item.id}" data-unit="${item.unit}" data-sunit="${item.secondary_unit || ''}">${item.name}</option>`;
            });

            const row = `
                <div class="purchase-row border-danger border-opacity-10 animate__animated animate__fadeInUp">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">ناوی کاڵا</label>
                            <select name="items[${index}][item_id]" class="form-select i-item-select" onchange="updateIssueUnit(this)" required>
                                ${options}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">بڕی دەرکردن</label>
                            <input type="number" step="0.01" name="items[${index}][qty]" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">یەکە</label>
                            <select name="items[${index}][unit_used]" class="form-select i-unit-select" required>
                                <option value="">--</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-outline-danger border-0 h-100 px-3" onclick="$(this).closest('.purchase-row').fadeOut(200, function(){ $(this).remove(); })">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(row);
        }

        function updateIssueUnit(select) {
            const selected = $(select).find(':selected');
            const unit = selected.data('unit');
            const sunit = selected.data('sunit');
            const unitSelect = $(select).closest('.row').find('.i-unit-select');
            
            let html = `<option value="${unit}">${unit} (بچووک)</option>`;
            if (sunit) {
                html += `<option value="${sunit}">${sunit} (گەورە)</option>`;
            }
            unitSelect.html(html);
        }

        // Add unit selection to purchase rows
        function addPurchaseRow() {
            const container = $('#purchaseItemsList');
            const index = container.children().length;
            
            let options = '<option value="">-- هەڵبژێرە --</option>';
            itemsGlobal.forEach(item => {
                options += `<option value="${item.id}" data-unit="${item.unit}" data-sunit="${item.secondary_unit || ''}">${item.name}</option>`;
            });

            const row = `
                <div class="purchase-row animate__animated animate__fadeInUp">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">ناو و جۆری کاڵا</label>
                            <select name="items[${index}][item_id]" class="form-select p-item-select" onchange="updatePurchaseUnit(this)" required>
                                ${options}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">بڕ</label>
                            <input type="number" step="0.01" name="items[${index}][qty]" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">یەکە</label>
                            <select name="items[${index}][unit_used]" class="form-select p-unit-select" required>
                                <option value="">--</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">نرخی تاک</label>
                            <input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">دراو</label>
                            <select name="items[${index}][currency]" class="form-select" required>
                                <option value="IQD">IQD (دینار)</option>
                                <option value="USD" selected>USD (دۆلار)</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-outline-danger border-0 h-100 px-3" onclick="$(this).closest('.purchase-row').fadeOut(200, function(){ $(this).remove(); })">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(row);
        }

        function updatePurchaseUnit(select) {
            const selected = $(select).find(':selected');
            const unit = selected.data('unit');
            const sunit = selected.data('sunit');
            const unitSelect = $(select).closest('.row').find('.p-unit-select');
            
            let html = `<option value="${unit}">${unit}</option>`;
            if (sunit) {
                html += `<option value="${sunit}">${sunit}</option>`;
            }
            unitSelect.html(html);
        }

        $('#addItemForm').submit(async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const isEdit = formData.get('item_id');
            const url = isEdit ? '../process/inventory/update_item.php' : '../process/inventory/add_item.php';
            
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو',
                    text: data.msg,
                    confirmButtonText: 'باشە'
                });
                $('#addItemModal').modal('hide');
                loadItems();
                loadStock();
                this.reset();
                delete formData.item_id; // Clean up
                $('#addItemModal .modal-title').text('کاڵایەکی نوێ زیاد بکە');
                $('#conversionFactorDiv').addClass('d-none');
            }
        });

        function openEditItemModal(item) {
            const modal = $('#addItemModal');
            modal.find('.modal-title').text('دەستکاری کاڵا: ' + item.name);
            modal.find('input[name="name"]').val(item.name);
            modal.find('select[name="category"]').val(item.category);
            modal.find('select[name="unit"]').val(item.unit);
            modal.find('select[name="secondary_unit"]').val(item.secondary_unit);
            modal.find('input[name="conversion_factor"]').val(item.conversion_factor);
            
            // Set current stock and cost
            modal.find('input[name="current_qty"]').val(item.current_qty);
            modal.find('input[name="avg_cost_usd"]').val(item.avg_cost_usd);

            // Add hidden item_id if not exists
            if (!modal.find('input[name="item_id"]').length) {
                modal.find('form').append(`<input type="hidden" name="item_id" value="${item.item_id}">`);
            } else {
                modal.find('input[name="item_id"]').val(item.item_id);
            }
            
            // Hide initial opening stock section during edit
            $('#openingStockSection').hide();
            
            if (item.secondary_unit) {
                $('#conversionFactorDiv').removeClass('d-none');
                $('#primaryUnitLabel').text(item.unit);
            } else {
                $('#conversionFactorDiv').addClass('d-none');
            }
            
            modal.modal('show');
        }

        // Reset modal on close
        $('#addItemModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $(this).find('input[name="item_id"]').remove();
            $('#openingStockSection').show();
            $(this).find('.modal-title').text('کاڵایەکی نوێ زیاد بکە');
        });

        async function deleteItem(id) {
            const result = await Swal.fire({
                title: 'ئایا دڵنیایت؟',
                text: "ئەم کاڵایە دەسڕێتەوە بە هەموو داتاکانی کۆگاوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بیسڕەوە',
                cancelButtonText: 'پاشگەزبوونەوە'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('../process/inventory/delete_item.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سڕایەوە!', data.msg, 'success');
                    loadItems();
                    loadStock();
                } else {
                    Swal.fire('هەڵە', data.msg, 'error');
                }
            }
        }

        $('#purchaseForm').submit(async function(e) {
            e.preventDefault();
            const res = await fetch('../process/inventory/add_purchase.php', {
                method: 'POST',
                body: new FormData(this)
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو', data.msg, 'success');
                this.reset();
                $('#purchaseItemsList').empty();
                addPurchaseRow();
                loadStock();
                loadPurchases();
            } else {
                Swal.fire('هەڵە', data.msg, 'error');
            }
        });

        async function deletePurchase(id) {
            const result = await Swal.fire({
                title: 'دڵنیایت؟',
                text: "ئەم کڕینە دەسڕێتەوە و بڕی کاڵاکان لە کۆگا کەمدەبنەوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'بەڵی، بیسڕەوە'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('../process/inventory/delete_purchase.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سڕایەوە', data.msg, 'success');
                    loadStock();
                    loadPurchases();
                } else {
                    Swal.fire('هەڵە', data.msg, 'error');
                }
            }
        }

        async function loadStock() {
            const res = await fetch('../process/inventory/get_stock.php');
            const data = await res.json();
            if (data.success) {
                let totalValuationSum = 0;
                let lowStock = 0;
                
                const tableData = data.data.map((item, idx) => {
                    const totalValuation = (item.current_qty * item.avg_cost_usd);
                    totalValuationSum += totalValuation;
                    if(item.current_qty <= 5) lowStock++;

                    return {
                        '#': '',
                        name: `<strong>${item.name}</strong>`,
                        category: `<span class="badge bg-light text-dark border">${item.category}</span>`,
                        current_qty: `<span class="fw-bold ${item.current_qty <= 5 ? 'text-danger' : 'text-primary'}">${Number(item.current_qty).toLocaleString()} ${item.unit}</span>`,
                        avg_cost_usd: `$${Number(item.avg_cost_usd).toLocaleString(undefined, {minimumFractionDigits: 3})}`,
                        total_valuation: `<span class="fw-bold">$${Number(totalValuation).toLocaleString()}</span>`,
                        actions: `
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn btn-sm btn-outline-primary border-0" onclick='openEditItemModal(${JSON.stringify(item).replace(/'/g, "&apos;")})'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${item.issuance_count == 0 ? `
                                    <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteItem(${item.item_id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : `
                                    <button class="btn btn-sm btn-outline-secondary border-0" disabled title="بۆ سەیارە بەکارهاتووە، ناتوانرێت بسڕدرێتەوە">
                                        <i class="fas fa-trash opacity-50"></i>
                                    </button>
                                `}
                            </div>
                        `
                    };
                });
                
                TableController.renderWithPagination('#stockTable', tableData, ['name', 'category', 'current_qty', 'avg_cost_usd', 'total_valuation', 'actions']);
                
                $('#totalItemCount').text(data.data.length);
                $('#totalStockValue').text('$' + Number(totalValuationSum).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#lowStockCount').text(lowStock).addClass(lowStock > 0 ? 'text-danger' : '');
            }
        }

        async function loadIssuances() {
            const res = await fetch('../process/inventory/get_issuances.php');
            const data = await res.json();
            if (data.success) {
                const tableData = data.data.map((row, idx) => {
                    const totalCost = (row.qty * row.cost_usd_at_time).toFixed(2);
                    return {
                        '#': '',
                        issued_date: row.issued_date,
                        car_name: `<span class="fw-bold text-dark">${row.car_name}</span>`,
                        item_name: row.item_name,
                        qty: `<span class="badge bg-light text-primary border">${row.qty}</span>`,
                        cost: `<span class="fw-bold text-success">$${Number(totalCost).toLocaleString()}</span>`,
                        actions: `
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteIssuance(${row.id})">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `
                    };
                });
                TableController.renderWithPagination('#issuanceData', tableData, ['#', 'issued_date', 'car_name', 'item_name', 'qty', 'cost', 'actions']);
            }
        }

        async function deleteIssuance(id) {
            const result = await Swal.fire({
                title: 'دڵنیایت؟',
                text: "ئەم دەرکردنە دەسڕێتەوە و بڕی کاڵاکە دەگەڕێتەوە بۆ کۆگا!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'بەڵێ، بیسڕەوە'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('../process/inventory/delete_issuance.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سڕایەوە', data.msg, 'success');
                    loadStock();
                    loadIssuances();
                } else {
                    Swal.fire('هەڵە', data.msg, 'error');
                }
            }
        }


        async function loadMaintenanceReport() {
            const vehicleId = $('#vehicleFilter').val();
            if(!vehicleId) {
                $('#maintenanceSummary').addClass('d-none');
                return;
            }
            
            const res = await fetch(`../process/inventory/get_vehicle_maintenance.php?vehicle_id=${vehicleId}`);
            const data = await res.json();
            if (data.success) {
                $('#vehicleTotalCost').text(`$${Number(data.total_cost).toLocaleString()}`);
                $('#maintenanceSummary').removeClass('d-none');
            }
        }
    </script>
</body>
</html>
