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
                                        <th>دوا گۆڕانکاری</th>
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
                                    <label class="form-label fw-600">ناوی فرۆشیار</label>
                                    <input type="text" name="supplier_name" id="supplier_name" class="form-control" list="supplierList" placeholder="وەک: کۆمپانیای ئەحمەد">
                                    <datalist id="supplierList">
                                        <!-- Person names will be here -->
                                    </datalist>
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
                    </div>
                </div>
            </div>

            <!-- Issue/Issuance Panel -->
            <div class="tab-pane fade" id="issue-panel">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="inventory-card h-100">
                            <div class="card-header">
                                <span><i class="fas fa-share-square me-2 text-danger"></i>دەرکردنی کاڵا</span>
                            </div>
                            <div class="card-body p-4">
                                <form id="issueForm">
                                    <div class="mb-4">
                                        <label class="form-label fw-600">کاڵا</label>
                                        <select name="item_id" id="issue_item_id" class="form-select select2" required></select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-600">سەیارە</label>
                                        <select name="vehicle_id" id="issue_vehicle_id" class="form-select select2" required></select>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-6">
                                            <label class="form-label fw-600">بڕ</label>
                                            <input type="number" step="0.01" name="qty" class="form-control" required placeholder="0.00">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-600">بەروار</label>
                                            <input type="date" name="issued_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-premium w-100 btn-primary-premium bg-danger">
                                        <i class="fas fa-paper-plane"></i> پەسەندکردنی دەرکردن
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8">
                        <div class="inventory-card h-100">
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
                            <div class="col-md-6">
                                <label class="form-label fw-600">یەکە (Unit)</label>
                                <select name="unit" id="item_unit_select" class="form-select" required>
                                    <!-- Units will be here -->
                                </select>
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

    <script>
        let itemsGlobal = [];
        let unitsGlobal = [];
        let categoriesGlobal = [];

        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#issue-panel'),
                width: '100%'
            });
            
            loadUnits();
            loadCategories();
            loadItems();
            loadVehicles();
            loadStock();
            loadIssuances();
            loadSuppliers();
            addPurchaseRow(); // Initial row
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
            let html = '';
            persons.forEach(person => {
                html += `<option value="${person.name}">`;
            });
            $('#supplierList').html(html);
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
                html += `<option value="${item.id}">${item.name} (${item.unit})</option>`;
            });
            $('#issue_item_id').html(html);
            $('.p-item-select').each(function() {
                const currentVal = $(this).val();
                $(this).html(html).val(currentVal);
            });
        }

        function addPurchaseRow() {
            const container = $('#purchaseItemsList');
            const index = container.children().length;
            
            let options = '<option value="">-- هەڵبژێرە --</option>';
            itemsGlobal.forEach(item => {
                options += `<option value="${item.id}">${item.name}</option>`;
            });

            const row = `
                <div class="purchase-row animate__animated animate__fadeInUp">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">ناو و جۆری کاڵا</label>
                            <select name="items[${index}][item_id]" class="form-select p-item-select" required>
                                ${options}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">بڕ</label>
                            <input type="number" step="0.01" name="items[${index}][qty]" class="form-control" placeholder="0.00" required>
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
                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-outline-danger border-0 h-100 px-3" onclick="$(this).closest('.purchase-row').fadeOut(200, function(){ $(this).remove(); })">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(row);
        }

        $('#addItemForm').submit(async function(e) {
            e.preventDefault();
            const res = await fetch('../process/inventory/add_item.php', {
                method: 'POST',
                body: new FormData(this)
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
                this.reset();
            }
        });

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
            } else {
                Swal.fire('هەڵە', data.msg, 'error');
            }
        });

        $('#issueForm').submit(async function(e) {
            e.preventDefault();
            const res = await fetch('../process/inventory/issue_item.php', {
                method: 'POST',
                body: new FormData(this)
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو', data.msg, 'success');
                this.reset();
                loadStock();
                loadIssuances();
            } else {
                Swal.fire('هەڵە', data.msg, 'error');
            }
        });

        async function loadStock() {
            const res = await fetch('../process/inventory/get_stock.php');
            const data = await res.json();
            if (data.success) {
                let html = '';
                let totalValuationSum = 0;
                let lowStock = 0;
                
                data.data.forEach(item => {
                    const totalValuation = (item.current_qty * item.avg_cost_usd);
                    totalValuationSum += totalValuation;
                    
                    if(item.current_qty <= 5) lowStock++;

                    html += `
                        <tr>
                            <td class="fw-bold">${item.name}</td>
                            <td><span class="badge bg-light text-dark border">${item.category}</span></td>
                            <td class="fw-bold ${item.current_qty <= 5 ? 'text-danger' : 'text-primary'}">
                                ${Number(item.current_qty).toLocaleString()} ${item.unit}
                            </td>
                            <td>$${Number(item.avg_cost_usd).toLocaleString(undefined, {minimumFractionDigits: 3})}</td>
                            <td class="fw-bold">$${Number(totalValuation).toLocaleString()}</td>
                            <td class="text-muted small">${item.last_updated}</td>
                        </tr>
                    `;
                });
                
                $('#stockData').html(html);
                $('#totalItemCount').text(data.data.length);
                $('#totalStockValue').text('$' + Number(totalValuationSum).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#lowStockCount').text(lowStock).addClass(lowStock > 0 ? 'text-danger' : '');
            }
        }

        async function loadIssuances() {
            const res = await fetch('../process/inventory/get_issuances.php');
            const data = await res.json();
            if (data.success) {
                let html = '';
                data.data.forEach(row => {
                    const totalCost = (row.qty * row.cost_usd_at_time).toFixed(2);
                    html += `
                        <tr>
                            <td>${row.issued_date}</td>
                            <td class="fw-bold text-dark">${row.car_name}</td>
                            <td>${row.item_name}</td>
                            <td><span class="badge bg-light text-primary border">${row.qty}</span></td>
                            <td class="fw-bold text-success">$${Number(totalCost).toLocaleString()}</td>
                        </tr>
                    `;
                });
                $('#issuanceData').html(html);
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
