<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!hasPermission('view_recipient')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وەرگرەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="mb-1">وەرگرەکان</h2>
            <p class="text-muted mb-0">بەڕێوەبردنی وەرگرەکان و نوێکردنەوەی زانیارییەکانیان</p>
        </div>
        <?php if (hasPermission('add_recipient')): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRecipientModal" style="background: var(--seafoam-green); font-weight: bold;">
                + زیادکردنی وەرگر
            </button>
        <?php endif; ?>
    </div>

    <div class="row mb-4" id="recipient-summary-cards">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-users card-icon"></i>
                    <h6 class="card-title">کۆی وەرگرەکان</h6>
                    <div class="fs-4 fw-bold" id="total_recipients">0</div>
                    <small class="text-light">وەرگر</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-water card-icon"></i>
                    <h6 class="card-title">وەرگرانی قەرزی مەتری</h6>
                    <div class="fs-4 fw-bold" id="recipients_with_meter">0</div>
                    <small class="text-light">وەرگر</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-cube card-icon"></i>
                    <h6 class="card-title">کۆی بڕی مەتری سەرەتایی</h6>
                    <div class="fs-4 fw-bold" id="total_opening_meter">0 م³</div>
                    <small class="text-light">مەتری مکعب</small>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="recipientsTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناو</th>
                    <th>ژمارەی مۆبایلی یەکەم</th>
                    <th>ژمارەی مۆبایلی دووەم</th>
                    <th>جۆر</th>
                    <th>کۆی بڕی مەتری گیراوی سەرەتایی</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Recipients loaded by JS -->
            </tbody>
        </table>
    </div>
</div>

<?php if (hasPermission('add_recipient')): ?>
<div class="modal fade" id="addRecipientModal" tabindex="-1" aria-labelledby="addRecipientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addRecipientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRecipientModalLabel">زیادکردنی وەرگر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipient_name" class="form-label">ناو</label>
                        <input type="text" class="form-control" id="recipient_name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="recipient_phone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                        <input type="text" class="form-control" id="recipient_phone1" name="phone1">
                    </div>
                    <div class="mb-3">
                        <label for="recipient_phone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                        <input type="text" class="form-control" id="recipient_phone2" name="phone2">
                    </div>
                    <!-- Note: opening_meter_total is not stored in customers table -->
                    <!-- Removed opening_meter_total field as it's not applicable for customers -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">زیادکردن</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (hasPermission('edit_recipient')): ?>
<div class="modal fade" id="editRecipientModal" tabindex="-1" aria-labelledby="editRecipientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editRecipientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRecipientModalLabel">دەستکاری وەرگر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editRecipientId" name="id">
                    <div class="mb-3">
                        <label for="editRecipientName" class="form-label">ناو</label>
                        <input type="text" class="form-control" id="editRecipientName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRecipientPhone1" class="form-label">ژمارەی مۆبایلی یەکەم</label>
                        <input type="text" class="form-control" id="editRecipientPhone1" name="phone1" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRecipientPhone2" class="form-label">ژمارەی مۆبایلی دووەم</label>
                        <input type="text" class="form-control" id="editRecipientPhone2" name="phone2">
                    </div>
                    <!-- Note: opening_meter_total is not stored in customers table -->
                    <!-- Removed opening_meter_total field as it's not applicable for customers -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
    window.recipientPermissions = {
        canAdd: <?php echo hasPermission('add_recipient') ? 'true' : 'false'; ?>,
        canEdit: <?php echo hasPermission('edit_recipient') ? 'true' : 'false'; ?>,
        canDelete: <?php echo hasPermission('delete_recipient') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/summary_cards.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/add.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/select.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/edit.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/delete.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/recipients/recipients.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>

