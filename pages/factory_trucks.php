<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

// Check permission if you have a specific one, else use a general one
// if (!hasPermission('view_factory_trucks')) { ... }

$trucks = $pdo->query("SELECT * FROM factory_trucks ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بارهەڵگرەکانی کارگە | دانە کۆنکریت</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet" />
    <link href="../assets/css/nav.css" rel="stylesheet" />
    <link href="../assets/css/comon/style.css" rel="stylesheet" />
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/table.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet" />

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.95);
            --premium-gradient: linear-gradient(135deg, var(--seafoam-green), #00cec9);
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Rabar', sans-serif;
        }

        .page-header {
            background: var(--premium-gradient);
            padding: 3rem 2rem;
            border-radius: 0 0 30px 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 206, 201, 0.2);
            margin-bottom: -3rem;
        }

        .truck-card {
            background: white;
            border: none;
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .truck-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .truck-icon-wrapper {
            width: 70px;
            height: 70px;
            background: var(--premium-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 8px 20px rgba(0, 206, 201, 0.3);
        }

        .add-btn {
            background: white;
            color: var(--seafoam-green);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .add-btn:hover {
            transform: scale(1.05);
            background: #f8f9fa;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-top: 5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }

        .modal-content {
            border-radius: 25px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--premium-gradient);
            color: white;
            border: none;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 206, 201, 0.1);
            border-color: var(--seafoam-green);
        }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header container-fluid">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-5 fw-bold mb-0">بارهەڵگرەکانی کارگە</h1>
                <p class="opacity-75 mt-2">بەڕێوەبردنی فلیتی بارهەڵگرەکانی ناوخۆی دانە کۆنکریت</p>
            </div>
            <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addTruckModal">
                <i class="fas fa-plus-circle me-2"></i> زیادکردنی بارهەڵگر
            </button>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                    <i class="fas fa-truck-monster text-primary fs-4"></i>
                </div>
                <h4 class="mb-0 fw-bold">لیستی فلیتی کارگە</h4>
            </div>

            <div class="table-responsive">
                <table class="table table-hover custom-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ناوى بارهەڵگر</th>
                            <th>ژمارەی تەبلێ</th>
                            <th>ناوی شۆفێر</th>
                            <th>حاڵەت</th>
                            <th class="text-center">کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trucks)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">هیچ بارهەڵگرێک تۆمار نەکراوە</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach($trucks as $truck): ?>
                        <tr>
                            <td><?= $truck['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="fas fa-truck text-muted"></i>
                                    </div>
                                    <span class="fw-bold"><?= htmlspecialchars($truck['truck_name']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border p-2"><?= htmlspecialchars($truck['plate_number'] ?: '---') ?></span></td>
                            <td><?= htmlspecialchars($truck['driver_name'] ?: 'دیاری نەکراوە') ?></td>
                            <td>
                                <?php if($truck['is_active']): ?>
                                    <span class="status-badge bg-success bg-opacity-10 text-success">چالاک</span>
                                <?php else: ?>
                                    <span class="status-badge bg-danger bg-opacity-10 text-danger">ڕاگیراو</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-info rounded-circle" data-bs-toggle="tooltip" title="دەستکاری">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="deleteTruck(<?= $truck['id'] ?>)" data-bs-toggle="tooltip" title="سڕینەوە">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Truck Modal -->
<div class="modal fade" id="addTruckModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">تۆمارکردنی بارهەڵگرێکی نوێ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTruckForm">
                <div class="modal-body p-4">
                    <div class="truck-icon-wrapper">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ناوى بارهەڵگر <span class="text-danger">*</span></label>
                        <input type="text" name="truck_name" class="form-control" placeholder="وەک: تڕێلەی عەبە" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ژمارەی تەبلێ</label>
                            <input type="text" name="plate_number" class="form-control" placeholder="وەک: 123456">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ناوی شۆفێر</label>
                            <input type="text" name="driver_name" class="form-control" placeholder="ناوی شۆفێر">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold" id="saveBtn">
                        <i class="fas fa-save me-2"></i> پاشەکەوت بکە
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Handle form submission
    $('#addTruckForm').on('submit', function(e) {
        e.preventDefault();
        const saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> پاشەکەوت دەکرێت...');

        $.ajax({
            url: '../process/factory_trucks/add.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو',
                        text: 'بارهەڵگرەکە بە سەرکەوتوویی تۆمار کرا',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('هەڵە', res.msg, 'error');
                    saveBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> پاشەکەوت بکە');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'پەیوەندی لەکار کەوت', 'error');
                saveBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> پاشەکەوت بکە');
            }
        });
    });

    // Delete Truck
    function deleteTruck(id) {
        Swal.fire({
            title: 'دڵنیای؟',
            text: "ئەم بارهەڵگرە لە فلیتی سڕ دەکرێتەوە!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/factory_trucks/delete.php', {id: id}, function(res) {
                    if(res.success) {
                        Swal.fire('سڕایەوە!', 'بارهەڵگرەکە بە سەرکەوتوویی سڕایەوە.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('هەڵە', res.msg, 'error');
                    }
                }, 'json');
            }
        });
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>

</body>
</html>
