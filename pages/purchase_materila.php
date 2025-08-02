<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!hasPermission('view_materials')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Note: add_material permission is checked in the UI, not here
// Users with only view_materials permission can still access the page

// Load materials and persons data for initial dropdown population
$materials = $pdo->query("SELECT id, name, unit_type, pieces_per_carton, bags_per_barrel, liters_per_bag, liters_per_barrel, price_per_piece, price_per_liter, purchase_price_usd, purchase_price_iqd FROM list_materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$persons = $pdo->query("SELECT id, name FROM other_expense_persons ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کڕینی کاڵاکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/purchase_materilas.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">کڕینی کاڵاکان</h2>
            <?php if (hasPermission('add_material')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addPurchaseModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">+ زیادکردنی کڕین</button>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4" id="summary-cards">
            <div class="col-md-4 mb-3">
                <div class="card text-center shadow  card-gradient-info card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-shopping-bag card-icon"></i>
                        <h6 class="card-title">کۆی کڕینەکان</h6>
                        <div class="fs-4 fw-bold" id="total-purchases">0</div>
                        <small class="text-light">ژمارەی هەموو کڕینەکان</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center shadow  card-gradient-success card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign card-icon"></i>
                        <h6 class="card-title">کۆی نرخی کڕینەکان</h6>
                        <div class="fs-4 fw-bold" id="total-purchase-value">$0</div>
                        <small class="text-light">کۆی نرخی هەموو کڕینەکان</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-users card-icon"></i>
                        <h6 class="card-title">درووشیارەکان</h6>
                        <div class="fs-4 fw-bold" id="total-suppliers">0</div>
                        <small class="text-light">ژمارەی دروشیارەکان</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label>لە بەروار:</label>
                <input type="date" id="filter_from" class="form-control">
            </div>
            <div class="col-md-3">
                <label>بۆ بەروار:</label>
                <input type="date" id="filter_to" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
            </div>
        </div>

        <!-- Purchase Materials Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="purchaseMaterialsTable">
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>ژمارەی پسووڵە</th>
                            <th>درووشیار</th>
                            <th>بەروار</th>
                            <th>کۆی کاڵاکان</th>
                            <th>کۆی نرخ</th>
                            <th>جۆری دراو</th>
                            <th>تێبینی</th>
                            <th>کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Purchase Materials Modal -->
    <div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="addPurchaseForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPurchaseModalLabel">زیادکردنی کڕینی کاڵاکان</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Receipt Details Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">وردەکاری پسووڵە</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="receipt_number" class="form-label">ژمارەی پسووڵە <i class="fas fa-magic text-info" title="ئۆتۆماتیکی"></i></label>
                                <input type="text" class="form-control" id="receipt_number" name="receipt_number" required readonly>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="person_id" class="form-label">درووشیار</label>
                                <select class="form-select" id="person_id" name="person_id" required>
                                    <option value="">هەڵبژێرە</option>
                                    <?php foreach ($persons as $person): ?>
                                        <option value="<?= $person['id'] ?>"><?= htmlspecialchars($person['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="purchase_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="currency_type" class="form-label">جۆری دراو</label>
                                <select class="form-select" id="currency_type" name="currency_type" required>
                                    <option value="">هەڵبژێرە</option>
                                    <option value="دینار">دینار</option>
                                    <option value="دۆلار">دۆلار</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="usd_to_iqd_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
                                <input type="number" class="form-control" id="usd_to_iqd_rate" name="usd_to_iqd_rate" min="0" step="0.01" placeholder="139250" value="139250">
                            </div>
                        </div>

                        <!-- Additional Costs Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">تێچووە زیادەکان</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="transfer_loss" class="form-label">تێچووی گواستەنەوە</label>
                                <input type="number" class="form-control" id="transfer_loss" name="transfer_loss" min="0" step="0.01" placeholder="0.00" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="other_loss" class="form-label">تێچووی تر</label>
                                <input type="number" class="form-control" id="other_loss" name="other_loss" min="0" step="0.01" placeholder="0.00" value="0">
                            </div>
                        </div>

                        <!-- Materials Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کاڵاکان</h6>
                            </div>
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="materialsTable">
                                        <thead style="background: var(--light-gray);">
                                            <tr>
                                                <th>کاڵا</th>
                                                <th>جۆری یەکە</th>
                                                <th>بڕ</th>
                                                <th>نرخی یەکە بە دۆلار</th>
                                                <th>نرخی یەکە بە دینار</th>
                                                <th>کۆی نرخ بە دۆلار</th>
                                                <th>کۆی نرخ بە دینار</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody id="materialsTableBody">
                                            <!-- Materials will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addMaterialRow">
                                    <i class="fas fa-plus"></i> زیادکردنی کاڵا
                                </button>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کۆی گشتی</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">کۆی نرخ بە دۆلار</label>
                                <input type="number" class="form-control" id="total_usd" name="total_usd" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">کۆی نرخ بە دینار</label>
                                <input type="number" class="form-control" id="total_iqd" name="total_iqd" readonly>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="notes" name="notes" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Purchase Materials Modal -->
    <div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-labelledby="editPurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="editPurchaseForm">
                    <input type="hidden" id="edit_purchase_id" name="edit_purchase_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPurchaseModalLabel">نوێکردنەوەی کڕینی کاڵاکان</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Receipt Details Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">وردەکاری پسووڵە</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="edit_receipt_number" class="form-label">ژمارەی پسووڵە <i class="fas fa-magic text-info" title="ئۆتۆماتیکی"></i></label>
                                <input type="text" class="form-control" id="edit_receipt_number" name="edit_receipt_number" required readonly>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="edit_person_id" class="form-label">درووشیار</label>
                                <select class="form-select" id="edit_person_id" name="edit_person_id" required>
                                    <option value="">هەڵبژێرە</option>
                                    <?php foreach ($persons as $person): ?>
                                        <option value="<?= $person['id'] ?>"><?= htmlspecialchars($person['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="edit_purchase_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="edit_purchase_date" name="edit_purchase_date" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="edit_currency_type" class="form-label">جۆری دراو</label>
                                <select class="form-select" id="edit_currency_type" name="edit_currency_type" required>
                                    <option value="">هەڵبژێرە</option>
                                    <option value="دینار">دینار</option>
                                    <option value="دۆلار">دۆلار</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="edit_usd_to_iqd_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
                                <input type="number" class="form-control" id="edit_usd_to_iqd_rate" name="edit_usd_to_iqd_rate" min="0" step="0.01" placeholder="139250" value="139250">
                            </div>
                        </div>

                        <!-- Additional Costs Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">تێچووە زیادەکان</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_transfer_loss" class="form-label">تێچووی گواستەنەوە</label>
                                <input type="number" class="form-control" id="edit_transfer_loss" name="edit_transfer_loss" min="0" step="0.01" placeholder="0.00" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_other_loss" class="form-label">تێچووی تر</label>
                                <input type="number" class="form-control" id="edit_other_loss" name="edit_other_loss" min="0" step="0.01" placeholder="0.00" value="0">
                            </div>
                        </div>

                        <!-- Materials Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کاڵاکان</h6>
                            </div>
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="editMaterialsTable">
                                        <thead style="background: var(--light-gray);">
                                            <tr>
                                                <th>کاڵا</th>
                                                <th>جۆری یەکە</th>
                                                <th>بڕ</th>
                                                <th>نرخی یەکە بە دۆلار</th>
                                                <th>نرخی یەکە بە دینار</th>
                                                <th>کۆی نرخ بە دۆلار</th>
                                                <th>کۆی نرخ بە دینار</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody id="editMaterialsTableBody">
                                            <!-- Materials will be loaded here for editing -->
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="editAddMaterialRow">
                                    <i class="fas fa-plus"></i> زیادکردنی کاڵا
                                </button>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کۆی گشتی</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">کۆی نرخ بە دۆلار</label>
                                <input type="number" class="form-control" id="edit_total_usd" name="edit_total_usd" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">کۆی نرخ بە دینار</label>
                                <input type="number" class="form-control" id="edit_total_iqd" name="edit_total_iqd" readonly>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="edit_notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="edit_notes" name="edit_notes" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Purchase Materials Modal -->
    <div class="modal fade" id="viewPurchaseModal" tabindex="-1" aria-labelledby="viewPurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPurchaseModalLabel">وردەکاری کڕینی کاڵاکان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Receipt Details Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">وردەکاری پسووڵە</h6>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">ژمارەی پسووڵە:</label>
                            <div class="form-control-plaintext" id="view_receipt_number"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">درووشیار:</label>
                            <div class="form-control-plaintext" id="view_person_name"></div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">بەروار:</label>
                            <div class="form-control-plaintext" id="view_purchase_date"></div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">جۆری دراو:</label>
                            <div class="form-control-plaintext" id="view_currency_type"></div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">نرخی 100 دۆلار بە دینار:</label>
                            <div class="form-control-plaintext" id="view_usd_to_iqd_rate"></div>
                        </div>
                    </div>

                    <!-- Additional Costs Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">تێچووە زیادەکان</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">تێچووی گواستەنەوە:</label>
                            <div class="form-control-plaintext" id="view_transfer_loss"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">تێچووی تر:</label>
                            <div class="form-control-plaintext" id="view_other_loss"></div>
                        </div>
                    </div>

                    <!-- Materials Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کاڵاکان</h6>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="viewMaterialsTable">
                                    <thead style="background: var(--light-gray);">
                                        <tr>
                                            <th>#</th>
                                            <th>کاڵا</th>
                                            <th>جۆری یەکە</th>
                                            <th>بڕ</th>
                                            <th>نرخی یەکە بە دۆلار</th>
                                            <th>نرخی یەکە بە دینار</th>
                                            <th>کۆی نرخ بە دۆلار</th>
                                            <th>کۆی نرخ بە دینار</th>
                                        </tr>
                                    </thead>
                                    <tbody id="viewMaterialsTableBody">
                                        <!-- Materials will be loaded here for viewing -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">کۆی گشتی</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">کۆی نرخ بە دۆلار:</label>
                            <div class="form-control-plaintext" id="view_total_usd"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">کۆی نرخ بە دینار:</label>
                            <div class="form-control-plaintext" id="view_total_iqd"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">تێبینی:</label>
                            <div class="form-control-plaintext" id="view_notes"></div>
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
    <script src="../assets/js/comon/select2_script.js"></script>
    
    <script>
        // Pass permissions to JavaScript
        window.userPermissions = {
            canAdd: <?php echo hasPermission('add_material') ? 'true' : 'false'; ?>,
            canEdit: <?php echo hasPermission('edit_material') ? 'true' : 'false'; ?>,
            canDelete: <?php echo hasPermission('delete_material') ? 'true' : 'false'; ?>
        };
        
        // Pass initial data to JavaScript
        window.initialMaterials = <?php echo json_encode($materials); ?>;
        window.initialPersons = <?php echo json_encode($persons); ?>;
    </script>
    
    <script src="../assets/js/purchase_materilas/add_purchase.js"></script>
    <script src="../assets/js/purchase_materilas/select_purchase.js"></script>
    <script src="../assets/js/purchase_materilas/update_purchase.js"></script>
    <script src="../assets/js/purchase_materilas/delete_purchase.js"></script>
    <script src="../assets/js/purchase_materilas/summary_cards.js"></script>
</body>
</html>
