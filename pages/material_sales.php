<?php
/**
 * Material Sales History Page
 * مێژووی فرۆشتنی کاڵاکان
 */
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check permissions (reuse view_materials or add specific permission if needed)
if (!hasPermission('view_materials')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get materials for dropdown filter
$materials = $pdo->query("SELECT id, name FROM list_materials ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مێژووی فرۆشتنی کاڵاکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .summary-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .table thead th {
            background-color: var(--kelly-green) !important;
            color: white !important;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>مێژووی فرۆشتنی کاڵاکان</h4>
            <small class="text-muted">لیستی هەموو کاڵا فرۆشراوەکان</small>
        </div>
        <a href="add_material.php" class="btn btn-primary">
            <i class="bi bi-arrow-right me-1"></i> گەڕانەوە بۆ کاڵاکان
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">لە بەروار</label>
                    <input type="date" class="form-control" id="filterFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label">بۆ بەروار</label>
                    <input type="date" class="form-control" id="filterTo">
                </div>
                <div class="col-md-3">
                    <label class="form-label">کاڵا</label>
                    <select class="form-select" id="filterMaterial">
                        <option value="">هەموو</option>
                        <?php foreach ($materials as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">جۆری کڕیار</label>
                    <select class="form-select" id="filterBuyerType">
                        <option value="">هەموو</option>
                        <option value="customer">کڕیار</option>
                        <option value="company">کۆمپانیا</option>
                        <option value="outsider">کەسی دەرەوە</option>
                    </select>
                </div>
                <div class="col-12 text-end">
                     <button class="btn btn-secondary" id="clearFilters">
                        <i class="bi bi-x-circle me-1"></i> پاککردنەوە
                    </button>
                    <button class="btn btn-primary" id="applyFilters">
                        <i class="bi bi-filter me-1"></i> پاڵاوتن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="salesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کاڵا</th>
                            <th>کڕیار</th>
                            <th>بڕ</th>
                            <th>یەکە</th>
                            <th>نرخ</th>
                            <th>کۆی گشتی</th>
                            <th>دراو</th>
                            <th>بەروار</th>
                            <th>تێبینی</th>
                            <th>کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data populated by DataTables -->
                    </tbody>
                    <tfoot>
                         <tr>
                            <th colspan="6" class="text-end">کۆی گشتی:</th>
                            <th id="totalSum">0</th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editSaleForm">
                <input type="hidden" name="id" id="edit_sale_id">
                <input type="hidden" name="old_quantity" id="edit_old_quantity">
                <input type="hidden" name="old_unit" id="edit_old_unit">
                <input type="hidden" name="material_id" id="edit_material_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">دەستکاری فرۆشتن</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        ئاگاداربە: گۆڕینی بڕ یان یەکە کاریگەری دەبێت لەسەر کۆگای کاڵا.
                    </div>
                    
                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label class="form-label">کاڵا</label>
                            <input type="text" class="form-control" id="edit_material_name" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">بەروار</label>
                            <input type="date" class="form-control" name="date" id="edit_date" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">بڕ</label>
                            <input type="number" class="form-control" name="quantity" id="edit_quantity" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">یەکە</label>
                            <select class="form-select" name="unit" id="edit_unit" required>
                                <!-- Populated by JS -->
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">جۆری دراو</label>
                            <select class="form-select" name="currency" id="edit_currency" required>
                                <option value="USD">دۆلار</option>
                                <option value="IQD">دینار</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">نرخی تاک</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">کۆی گشتی</label>
                            <input type="number" class="form-control" name="total_price" id="edit_total_price" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">تێبینی</label>
                        <textarea class="form-control" name="note" id="edit_note" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/add_material/sales_history.js"></script>

</body>
</html>
