<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Helper functions
function getMaterialTypeColor($type) {
    $colors = [
        'black_sand' => 'dark',
        'brown_sand' => 'warning',
        'gravel' => 'secondary',
        'cement' => 'primary',
        'medicine' => 'danger',
        'gas' => 'info',
        'other' => 'light'
    ];
    return $colors[$type] ?? 'light';
}

function getMaterialTypeName($type) {
    $names = [
        'black_sand' => 'لمی ڕەش',
        'brown_sand' => 'لمی کەسارە',
        'gravel' => 'چەو',
        'cement' => 'چیمەنتۆ',
        'medicine' => 'دەرمان',
        'gas' => 'گاز',
        'other' => 'تر'
    ];
    return $names[$type] ?? 'تر';
}

function getUnitTypeIcon($unitType) {
    $icons = [
        'کارتۆن' => 'fa-box',
        'دانە' => 'fa-cube',
        'بەرمیل' => 'fa-drum',
        'دەبە' => 'fa-bucket',
        'لیتر' => 'fa-tint'
    ];
    return $icons[$unitType] ?? 'fa-ruler';
}

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

// Load warehouse materials data
$materials = $pdo->query("
    SELECT 
        wm.id, 
        wm.name, 
        wm.name_ku, 
        wm.type, 
        wm.base_unit,
        wm.conversion_factor,
        wm.description,
        ut.name_ku as unit_type_name,
        wi.quantity,
        wi.available_quantity,
        wi.average_price_usd,
        wi.average_price_iqd,
        wi.total_value_usd,
        wi.total_value_iqd
    FROM warehouse_materials wm
    LEFT JOIN unit_types ut ON wm.unit_type_id = ut.id
    LEFT JOIN warehouse_inventory wi ON wm.id = wi.material_id
    WHERE wm.is_active = 1
    ORDER BY wm.name_ku ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Load unit types for dropdown
$unitTypes = $pdo->query("SELECT id, name_ku, description FROM unit_types WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کۆگا (کەل و پەل) - سیستەمی یەکەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .unit-info {
            background: #f8f9fa;
            border-left: 4px solid var(--seafoam-green);
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .conversion-panel {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .price-calculation {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .unit-type-icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
        .material-type-badge {
            font-size: 0.8em;
            padding: 2px 8px;
        }
    </style>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
                    <i class="fas fa-warehouse"></i> کۆگا (کەل و پەل) - سیستەمی یەکەکان
                </h2>
                <small class="text-muted">سیستەمی نوێی کۆگا بە پشتگیری یەکە جیاوازەکان</small>
            </div>
            <?php if (hasPermission('add_material')): ?>
            <button class="btn" data-bs-toggle="modal" data-bs-target="#addMaterialModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">
                <i class="fas fa-plus"></i> زیادکردنی کاڵا
            </button>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow card-gradient-info">
                    <div class="card-body">
                        <i class="fas fa-boxes card-icon"></i>
                        <h6 class="card-title">کۆی کاڵاکان</h6>
                        <div class="fs-4 fw-bold" id="total-materials"><?= count($materials) ?></div>
                        <small class="text-light">ژمارەی هەموو کاڵاکان</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow card-gradient-success">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign card-icon"></i>
                        <h6 class="card-title">کۆی نرخ</h6>
                        <div class="fs-4 fw-bold" id="total-value">$0</div>
                        <small class="text-light">کۆی نرخی کۆگا</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow card-gradient-warning">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle card-icon"></i>
                        <h6 class="card-title">کاڵای کەم</h6>
                        <div class="fs-4 fw-bold" id="low-stock">0</div>
                        <small class="text-light">کاڵای کەم لە ستۆک</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center shadow card-gradient-danger">
                    <div class="card-body">
                        <i class="fas fa-times-circle card-icon"></i>
                        <h6 class="card-title">کاڵای تەواو</h6>
                        <div class="fs-4 fw-bold" id="out-of-stock">0</div>
                        <small class="text-light">کاڵای تەواو لە ستۆک</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Materials Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center" id="materialTable">
                <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                    <tr>
                        <th>#</th>
                        <th>ناوی کاڵا</th>
                        <th>جۆری کاڵا</th>
                        <th>یەکە</th>
                        <th>بڕی بەردەست</th>
                        <th>یەکەی بنەڕەت</th>
                        <th>نرخی تێکڕا (دۆلار)</th>
                        <th>نرخی تێکڕا (دینار)</th>
                        <th>کۆی نرخ (دۆلار)</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($materials) === 0): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <br>
                            <span class="text-muted">هیچ کاڵایەک نییە</span>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $i => $mat): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($mat['name_ku']) ?></strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($mat['name']) ?></small>
                            </td>
                            <td>
                                <span class="badge material-type-badge bg-<?= getMaterialTypeColor($mat['type']) ?>">
                                    <?= getMaterialTypeName($mat['type']) ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas <?= getUnitTypeIcon($mat['unit_type_name']) ?> unit-type-icon"></i>
                                <?= htmlspecialchars($mat['unit_type_name']) ?>
                            </td>
                            <td>
                                <span class="fw-bold"><?= number_format($mat['available_quantity'] ?? 0, 2) ?></span>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($mat['base_unit']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($mat['base_unit']) ?></span>
                            </td>
                            <td>$<?= number_format($mat['average_price_usd'] ?? 0, 4) ?></td>
                            <td><?= number_format($mat['average_price_iqd'] ?? 0, 0) ?> د.ع</td>
                            <td>$<?= number_format($mat['total_value_usd'] ?? 0, 2) ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info" onclick="viewMaterial(<?= $mat['id'] ?>)" title="بینین">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (hasPermission('edit_material')): ?>
                                    <button class="btn btn-sm btn-primary" onclick="editMaterial(<?= $mat['id'] ?>)" title="نوێکردنەوە">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (hasPermission('delete_material')): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteMaterial(<?= $mat['id'] ?>)" title="سڕینەوە">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Material Modal -->
    <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addMaterialForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMaterialModalLabel">
                            <i class="fas fa-plus-circle"></i> زیادکردنی کاڵای نوێ
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Basic Information -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                                    <i class="fas fa-info-circle"></i> زانیاری سەرەکی
                                </h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name_ku" class="form-label">ناوی کاڵا (کوردی) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name_ku" name="name_ku" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">ناوی کاڵا (ئینگلیزی)</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">جۆری کاڵا <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">هەڵبژێرە</option>
                                    <option value="black_sand">لمی ڕەش</option>
                                    <option value="brown_sand">لمی کەسارە</option>
                                    <option value="gravel">چەو</option>
                                    <option value="cement">چیمەنتۆ</option>
                                    <option value="medicine">دەرمان</option>
                                    <option value="gas">گاز</option>
                                    <option value="other">تر</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_type_id" class="form-label">جۆری یەکە <span class="text-danger">*</span></label>
                                <select class="form-select" id="unit_type_id" name="unit_type_id" required>
                                    <option value="">هەڵبژێرە</option>
                                    <?php foreach ($unitTypes as $unitType): ?>
                                        <option value="<?= $unitType['id'] ?>"><?= htmlspecialchars($unitType['name_ku']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Unit Conversion Settings -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                                    <i class="fas fa-exchange-alt"></i> ڕێکخستنی یەکەکان
                                </h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="base_unit" class="form-label">یەکەی بنەڕەت <span class="text-danger">*</span></label>
                                <select class="form-select" id="base_unit" name="base_unit" required>
                                    <option value="">هەڵبژێرە</option>
                                    <option value="kg">کیلۆگرام (kg)</option>
                                    <option value="liter">لیتر (L)</option>
                                    <option value="piece">دانە</option>
                                    <option value="meter">مەتر (m)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="conversion_factor" class="form-label">فاکتەری گۆڕانکاری</label>
                                <input type="number" class="form-control" id="conversion_factor" name="conversion_factor" min="0.0001" step="0.0001" value="1.0000">
                                <small class="form-text text-muted">فاکتەری گۆڕانکاری بۆ یەکەی بنەڕەت</small>
                            </div>
                        </div>

                        <!-- Unit Type Specific Fields -->
                        <div id="unitTypeFields" class="conversion-panel" style="display: none;">
                            <!-- Dynamic fields will be loaded here -->
                        </div>

                        <!-- Description -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="description" class="form-label">وەسف</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="وەسفی کاڵا..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">
                            <i class="fas fa-save"></i> زیادکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Material Modal -->
    <div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editMaterialForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMaterialModalLabel">
                            <i class="fas fa-edit"></i> نوێکردنەوەی کاڵا
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Edit form content will be loaded dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">
                            <i class="fas fa-save"></i> نوێکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Material Modal -->
    <div class="modal fade" id="viewMaterialModal" tabindex="-1" aria-labelledby="viewMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMaterialModalLabel">
                        <i class="fas fa-eye"></i> وردەکاری کاڵا
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- View content will be loaded dynamically -->
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
    
    <script>
        // Helper functions
        function getMaterialTypeColor(type) {
            const colors = {
                'black_sand': 'dark',
                'brown_sand': 'warning',
                'gravel': 'secondary',
                'cement': 'primary',
                'medicine': 'danger',
                'gas': 'info',
                'other': 'light'
            };
            return colors[type] || 'light';
        }

        function getMaterialTypeName(type) {
            const names = {
                'black_sand': 'لمی ڕەش',
                'brown_sand': 'لمی کەسارە',
                'gravel': 'چەو',
                'cement': 'چیمەنتۆ',
                'medicine': 'دەرمان',
                'gas': 'گاز',
                'other': 'تر'
            };
            return names[type] || 'تر';
        }

        function getUnitTypeIcon(unitType) {
            const icons = {
                'کارتۆن': 'fa-box',
                'دانە': 'fa-cube',
                'بەرمیل': 'fa-drum',
                'دەبە': 'fa-bucket',
                'لیتر': 'fa-tint'
            };
            return icons[unitType] || 'fa-ruler';
        }

        // Unit type change handler
        $('#unit_type_id').on('change', function() {
            const unitTypeId = $(this).val();
            const unitTypeFields = $('#unitTypeFields');
            
            if (!unitTypeId) {
                unitTypeFields.hide();
                return;
            }

            // Load unit type specific fields
            $.ajax({
                url: '../process/add_material/get_unit_type_fields.php',
                method: 'POST',
                data: { unit_type_id: unitTypeId },
                success: function(response) {
                    unitTypeFields.html(response).show();
                },
                error: function() {
                    unitTypeFields.html('<div class="alert alert-warning">هەڵە لە بارکردنی ڕێکخستنەکان</div>').show();
                }
            });
        });

        // Form submission
        $('#addMaterialForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: '../process/add_material/add.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵە لە پەیوەندی بە سێرڤەرەوە',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        });

        // View material
        function viewMaterial(id) {
            $.ajax({
                url: '../process/add_material/get_material.php',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        $('#viewMaterialModal .modal-body').html(response.html);
                        $('#viewMaterialModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        });
                    }
                }
            });
        }

        // Edit material
        function editMaterial(id) {
            $.ajax({
                url: '../process/add_material/get_material.php',
                method: 'POST',
                data: { id: id, action: 'edit' },
                success: function(response) {
                    if (response.success) {
                        $('#editMaterialModal .modal-body').html(response.html);
                        $('#editMaterialModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        });
                    }
                }
            });
        }

        // Delete material
        function deleteMaterial(id) {
            Swal.fire({
                title: 'دڵنیای لە سڕینەوە؟',
                text: "ئەم کردارە ناتوانرێت گەڕێنرێتەوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بسڕەوە!',
                cancelButtonText: 'پاشگەزبوونەوە'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../process/add_material/delete.php',
                        method: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'سڕایەوە!',
                                    text: response.message,
                                    confirmButtonText: 'باشە'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: response.message,
                                    confirmButtonText: 'باشە'
                                });
                            }
                        }
                    });
                }
            });
        }

        // Calculate summary statistics
        function calculateSummaryStats() {
            let totalValue = 0;
            let lowStock = 0;
            let outOfStock = 0;

            <?php foreach ($materials as $mat): ?>
                totalValue += <?= $mat['total_value_usd'] ?? 0 ?>;
                if (<?= $mat['available_quantity'] ?? 0 ?> <= 0) {
                    outOfStock++;
                } else if (<?= $mat['available_quantity'] ?? 0 ?> < 10) {
                    lowStock++;
                }
            <?php endforeach; ?>

            $('#total-value').text('$' + totalValue.toFixed(2));
            $('#low-stock').text(lowStock);
            $('#out-of-stock').text(outOfStock);
        }

        // Initialize page
        $(document).ready(function() {
            calculateSummaryStats();
        });
    </script>
</body>
</html>
