<?php
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!hasPermission('view_other_expenses')) {
    header('Location: ../pages/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەکارهێنانی گاز | دانا کۆنکریت</title>
    
    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css?v=1.0.3" rel="stylesheet">
    <link href="../assets/css/nav.css?v=1.0.3" rel="stylesheet">
    <link href="../assets/css/comon/style.css?v=1.0.3" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css?v=1.0.3" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --gas-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --summary-blue: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --summary-green: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Rabar', sans-serif;
        }
        
        .main-content {
            margin-right: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .premium-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .premium-card:hover {
            transform: translateY(-5px);
        }
        
        .gradient-header {
            background: var(--primary-gradient);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .summary-card {
            border: none;
            border-radius: 15px;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .summary-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
            border-radius: 15px;
        }
        
        .ag-theme-alpine {
            --ag-header-background-color: #f1f3f5;
            --ag-header-foreground-color: #495057;
            --ag-row-hover-color: #e9ecef;
            --ag-border-radius: 10px;
        }
        
        .btn-premium {
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-add {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }
        
        .btn-add:hover {
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.4);
            color: white;
        }
        
        .gas-stats-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            left: 20px;
            bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .main-content { margin-right: 0; }
        }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header & Stats -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold"><i class="fas fa-gas-pump me-2 text-primary"></i>بەکارهێنانی گاز بۆ سەیارەکان</h2>
                <p class="text-muted">بەڕێوەبردنی خەرجی و بڕی گازی بەکارهاتوو</p>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-premium btn-add" data-bs-toggle="modal" data-bs-target="#addGasModal">
                    <i class="fas fa-plus me-2"></i>تۆمارکردنی بەکارهێنانی نوێ
                </button>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="summary-card p-4 h-100" style="background: var(--summary-blue);">
                    <h6>کۆی گشتی گاز (لیتر)</h6>
                    <h2 id="totalGasLiters" class="fw-bold">0</h2>
                    <i class="fas fa-oil-can gas-stats-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card p-4 h-100" style="background: var(--summary-green);">
                    <h6>کۆی تێچوو (دینار)</h6>
                    <h2 id="totalGasCost" class="fw-bold">0</h2>
                    <i class="fas fa-money-bill-wave gas-stats-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card p-4 h-100" style="background: var(--gas-gradient);">
                    <h6>نرخی ئێستای گاز (لیتر)</h6>
                    <h2 id="currentGasPrice" class="fw-bold">0</h2>
                    <i class="fas fa-tag gas-stats-icon"></i>
                </div>
            </div>
        </div>
        
        <!-- Filters Card -->
        <div class="card premium-card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">لە بەرواری</label>
                        <input type="date" id="filterDateFrom" class="form-control rounded-3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">بۆ بەرواری</label>
                        <input type="date" id="filterDateTo" class="form-control rounded-3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">سەیارە</label>
                        <select id="filterCar" class="form-select rounded-3">
                            <option value="">هەموو سەیارەکان</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button id="btnFilter" class="btn btn-primary w-100 rounded-3">
                            <i class="fas fa-search me-2"></i>فلتەر
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Table Card -->
        <div class="card premium-card">
            <div class="card-body p-0">
                <div id="gasUsageGrid" class="ag-theme-alpine" style="height: 500px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Gas Modal -->
<div class="modal fade" id="addGasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="gradient-header">
                <h5 class="modal-title m-0"><i class="fas fa-gas-pump me-2"></i>تۆمارکردنی بەکارهێنانی گاز</h5>
            </div>
            <form id="addGasForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">سەیارە</label>
                        <select name="car_id" id="modal_car_id" class="form-select rounded-3" required>
                            <option value="">هەڵبژێرە...</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">بڕی گاز (لیتر)</label>
                            <input type="number" step="0.01" name="gas_liters" id="modal_gas_liters" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نرخی لیتر (دینار)</label>
                            <input type="number" name="gas_price" id="modal_gas_price" class="form-control rounded-3" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">کۆی تێچوو (دینار)</label>
                        <input type="text" id="modal_total_cost_display" class="form-control rounded-3 bg-light" readonly value="0">
                        <input type="hidden" name="gas_total_cost" id="modal_gas_total_cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">بەروار</label>
                        <input type="date" name="date" class="form-control rounded-3" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="submit" class="btn btn-primary btn-premium rounded-3">پاشکەوتکردن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Gas Modal -->
<div class="modal fade" id="editGasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="gradient-header" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%);">
                <h5 class="modal-title m-0"><i class="fas fa-edit me-2"></i>نوێکردنەوەی بەکارهێنانی گاز</h5>
            </div>
            <form id="editGasForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">سەیارە</label>
                        <select name="car_id" id="edit_car_id" class="form-select rounded-3" required>
                            <option value="">هەڵبژێرە...</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">بڕی گاز (لیتر)</label>
                            <input type="number" step="0.01" name="gas_liters" id="edit_gas_liters" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نرخی لیتر (دینار)</label>
                            <input type="number" name="gas_price" id="edit_gas_price" class="form-control rounded-3" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">کۆی تێچوو (دینار)</label>
                        <input type="text" id="edit_total_cost_display" class="form-control rounded-3 bg-light" readonly value="0">
                        <input type="hidden" name="gas_total_cost" id="edit_gas_total_cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">بەروار</label>
                        <input type="date" name="date" id="edit_date" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="submit" class="btn btn-primary btn-premium rounded-3" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border: none;">نوێکردنەوە</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- External JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AG Grid JS v31+ -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Module JS -->
<script src="../assets/js/gas_usage/ag_grid_gas.js?v=1.0.1"></script>
<script src="../assets/js/gas_usage/gas_usage.js?v=1.0.1"></script>

</body>
</html>
