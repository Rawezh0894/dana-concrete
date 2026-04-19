<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

// هێنانی هەموو پۆلێنەکان
$categories = $pdo->query("SELECT * FROM transaction_categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی جۆرەکانی مامەڵە</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <style>
        body { font-family: 'Rabar', sans-serif; background-color: #f4f6f9; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-primary { background-color: var(--seafoam-green); border: none; }
        .btn-primary:hover { background-color: var(--kelly-green); }
    </style>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--seafoam-green);">پۆلێنکردنی مامەڵەکانی دارایی</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="prepareAdd()">
                <i class="fa fa-plus me-1"></i> زیادکردنی جۆری نوێ
            </button>
        </div>

        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>ناوی پۆلێن</th>
                                <th>جۆری بەکارهێنان</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $cat): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td>
                                        <?php if($cat['type'] === 'INFLOW'): ?>
                                            <span class="badge bg-success">فۆرمی هاتن (Inflow)</span>
                                        <?php elseif($cat['type'] === 'OUTFLOW'): ?>
                                            <span class="badge bg-danger">فۆرمی چوون (Outflow)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">هەردوو فۆرم (Both)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="prepareEdit(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>', '<?= $cat['type'] ?>')">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $cat['id'] ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
             <a href="user_wallets.php" class="btn btn-secondary"><i class="fa fa-arrow-right"></i> گەڕانەوە بۆ قاسە</a>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form id="categoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalTitle">زیادکردنی جۆر</h5>
                        <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="categoryId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">ناوی پۆلێنکردن</label>
                            <input type="text" name="name" id="categoryName" class="form-control" placeholder="بۆ نموونە: قەرزی کۆن" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">لە کام فۆرمدا دەربکەوێت؟</label>
                            <select name="type" id="categoryType" class="form-select" required>
                                <option value="INFLOW">تەنها لە زیادکردنی پارە (Inflow)</option>
                                <option value="OUTFLOW">تەنها لە ڕاکێشانی پارە (Outflow)</option>
                                <option value="BOTH">لە هەردووکیاندا دەربکەوێت</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">لابردن</button>
                        <button type="submit" class="btn btn-primary px-4">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function prepareAdd() {
            $('#modalTitle').text('زیادکردنی جۆری نوێ');
            $('#formAction').val('add');
            $('#categoryForm')[0].reset();
        }

        function prepareEdit(id, name, type) {
            $('#modalTitle').text('دەستکاری کردنی جۆر');
            $('#formAction').val('edit');
            $('#categoryId').val(id);
            $('#categoryName').val(name);
            $('#categoryType').val(type);
            $('#categoryModal').modal('show');
        }

        $('#categoryForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '../process/user_wallets/category_process.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        });

        function deleteCategory(id) {
            if (confirm('ئایا دڵنیایت لە سڕینەوەی ئەم پۆلێنە؟')) {
                $.ajax({
                    url: '../process/user_wallets/category_process.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
