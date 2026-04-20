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
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #2c3e50, #4ca1af);
            --success-gradient: linear-gradient(135deg, #1d976c, #93f9b9);
            --danger-gradient: linear-gradient(135deg, #eb3349, #f45c43);
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #f1f1f1;
            padding: 1.25rem;
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
        }
        
        .stats-card {
            padding: 1.5rem;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-primary { background: var(--primary-gradient); }
        .stats-success { background: var(--success-gradient); }
        .stats-danger { background: var(--danger-gradient); }
        
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: #6c757d;
        }
        
        .nav-pills .nav-link.active {
            background: var(--primary-gradient);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .purchase-row {
            background: #fff;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-4" style="margin-right: 250px; width: calc(100% - 250px);">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h3 class="fw-bold"><i class="fas fa-warehouse me-2"></i>بەڕێوەبردنی کۆگا</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="fas fa-plus me-1"></i>زیادکردنی کاڵای نوێ
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4" id="inventoryTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="stock-tab" data-bs-toggle="pill" data-bs-target="#stock-panel">
                    <i class="fas fa-boxes me-1"></i>کۆگا و بڕی کاڵا
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="purchase-tab" data-bs-toggle="pill" data-bs-target="#purchase-panel">
                    <i class="fas fa-shopping-cart me-1"></i>تۆمارکردنی کڕین
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="issue-tab" data-bs-toggle="pill" data-bs-target="#issue-panel">
                    <i class="fas fa-shuttle-van me-1"></i>دەرکردن بۆ سەیارە
                </button>
            </li>
        </ul>

        <div class="tab-content" id="inventoryTabsContent">
            <!-- Stock Panel -->
            <div class="tab-pane fade show active" id="stock-panel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">لیستی کاڵاکان و نرخەکان</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadStock()">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="stockTable">
                                <thead>
                                    <tr>
                                        <th>ناوی کاڵا</th>
                                        <th>پۆلێن</th>
                                        <th>بڕی ماوە</th>
                                        <th>تێکڕای نرخی کڕین (USD)</th>
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
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">فۆڕمی تۆمارکردنی کڕینی نوێ</h5>
                    </div>
                    <div class="card-body">
                        <form id="purchaseForm">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">ژمارەی پسوڵە</label>
                                    <input type="text" name="invoice_number" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ناوی فرۆشیار</label>
                                    <input type="text" name="supplier_name" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">بەرواری کڕین</label>
                                    <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">نرخی ١٠٠ دۆلار (دینار)</label>
                                    <input type="number" name="exchange_rate" id="p_exchange_rate" class="form-control" value="150000" required>
                                </div>
                            </div>

                            <h6 class="mb-3 border-bottom pb-2">کاڵاکان</h6>
                            <div id="purchaseItemsList">
                                <!-- Row Template -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm mb-4" onclick="addPurchaseRow()">
                                <i class="fas fa-plus me-1"></i>زیادکردنی ڕیزێک
                            </button>

                            <div class="text-start">
                                <button type="submit" class="btn btn-success px-5">
                                    <i class="fas fa-save me-1"></i>تۆمارکردنی کڕینەکە
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Issue/Issuance Panel -->
            <div class="tab-pane fade" id="issue-panel">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">دەرکردنی کاڵا بۆ سەیارە</h5>
                            </div>
                            <div class="card-body">
                                <form id="issueForm">
                                    <div class="mb-3">
                                        <label class="form-label">کاڵا هەڵبژێرە</label>
                                        <select name="item_id" id="issue_item_id" class="form-select select2" required></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">سەیارە هەڵبژێرە</label>
                                        <select name="vehicle_id" id="issue_vehicle_id" class="form-select select2" required></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">بڕ</label>
                                        <input type="number" step="0.01" name="qty" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">بەروار</label>
                                        <input type="date" name="issued_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-paper-plane me-1"></i>دەرکردن
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">دوایین دەرکردنەکان</h5>
                                <div class="col-md-4">
                                     <select id="vehicleFilter" class="form-select form-select-sm" onchange="loadMaintenanceReport()">
                                         <option value="">هەموو سەیارەکان (تێچوو)</option>
                                     </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="maintenanceSummary" class="alert alert-info py-2 px-3 mb-3" style="display:none;">
                                    کۆی تێچووی چاککردنەوە بۆ ئەم سەیارەیە: <strong id="vehicleTotalCost">$0.00</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>بەروار</th>
                                                <th>سەیارە</th>
                                                <th>کاڵا</th>
                                                <th>بڕ</th>
                                                <th>تێچوو (USD)</th>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">زیادکردنی کاڵای نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addItemForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ناوی کاڵا</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">پۆلێن</label>
                            <select name="category" class="form-select">
                                <option value="Oil">ڕۆن (Oil)</option>
                                <option value="Battery">پاتری (Battery)</option>
                                <option value="Spare Part">پارچەی یەدەگ (Spare Part)</option>
                                <option value="Other">تر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">یەکە (Unit)</label>
                            <input type="text" name="unit" class="form-control" placeholder="pcs, liter, etc.">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">پاشەکەوتکردن</button>
                    </div>
                </form>
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

        $(document).ready(function() {
            loadItems();
            loadVehicles();
            loadStock();
            loadIssuances();
            addPurchaseRow(); // Initial row
        });

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
            
            let html = '<option value="">-- هەڵبژێرە --</option>';
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
            $('.p-item-select').html(html);
        }

        function addPurchaseRow() {
            const container = $('#purchaseItemsList');
            const index = container.children().length;
            
            let options = '<option value="">-- هەڵبژێرە --</option>';
            itemsGlobal.forEach(item => {
                options += `<option value="${item.id}">${item.name}</option>`;
            });

            const row = `
                <div class="purchase-row row g-2">
                    <div class="col-md-4">
                        <select name="items[${index}][item_id]" class="form-select form-select-sm p-item-select" required>
                            ${options}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="items[${index}][qty]" class="form-control form-control-sm" placeholder="بڕ" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control form-control-sm" placeholder="نرخ" required>
                    </div>
                    <div class="col-md-2">
                        <select name="items[${index}][currency]" class="form-select form-select-sm" required>
                            <option value="IQD">IQD</option>
                            <option value="USD" selected>USD</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-start">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('.purchase-row').remove()">
                            <i class="fas fa-times"></i>
                        </button>
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
                Swal.fire('سەرکەوتوو', data.msg, 'success');
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
                data.data.forEach(item => {
                    const totalValuation = (item.current_qty * item.avg_cost_usd).toFixed(2);
                    html += `
                        <tr>
                            <td>${item.name}</td>
                            <td><span class="badge bg-secondary">${item.category}</span></td>
                            <td class="fw-bold text-primary">${Number(item.current_qty).toLocaleString()} ${item.unit}</td>
                            <td>$${Number(item.avg_cost_usd).toLocaleString(undefined, {minimumFractionDigits: 3})}</td>
                            <td>$${Number(totalValuation).toLocaleString()}</td>
                            <td class="text-muted small">${item.last_updated}</td>
                        </tr>
                    `;
                });
                $('#stockData').html(html);
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
                            <td>${row.car_name}</td>
                            <td>${row.item_name}</td>
                            <td>${row.qty}</td>
                            <td>$${Number(totalCost).toLocaleString()}</td>
                        </tr>
                    `;
                });
                $('#issuanceData').html(html);
            }
        }

        async function loadMaintenanceReport() {
            const vehicleId = $('#vehicleFilter').val();
            if(!vehicleId) {
                $('#maintenanceSummary').hide();
                return;
            }
            
            const res = await fetch(`../process/inventory/get_vehicle_maintenance.php?vehicle_id=${vehicleId}`);
            const data = await res.json();
            if (data.success) {
                $('#vehicleTotalCost').text(`$${Number(data.total_cost).toLocaleString()}`);
                $('#maintenanceSummary').show();
            }
        }
    </script>
</body>
</html>
