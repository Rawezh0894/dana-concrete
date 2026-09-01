<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_expenses.php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

// Fetch active trucks for the dropdown
$trucks = $pdo->query("SELECT id, truck_name, plate_number FROM factory_trucks WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Filter inputs from GET
$filter_truck_id = isset($_GET['filter_truck_id']) ? trim($_GET['filter_truck_id']) : '';
$filter_invoice = isset($_GET['filter_invoice']) ? trim($_GET['filter_invoice']) : '';
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$filter_amount_usd = (isset($_GET['filter_amount_usd']) && $_GET['filter_amount_usd'] !== '' && $_GET['filter_amount_usd'] !== '0') ? (float)$_GET['filter_amount_usd'] : null;
$filter_amount_iqd = (isset($_GET['filter_amount_iqd']) && $_GET['filter_amount_iqd'] !== '' && $_GET['filter_amount_iqd'] !== '0') ? (float)$_GET['filter_amount_iqd'] : null;
$filter_note = isset($_GET['filter_note']) ? trim($_GET['filter_note']) : '';

// Build SQL query dynamically
$sql = "SELECT te.*, ft.truck_name FROM truck_expenses te JOIN factory_trucks ft ON te.truck_id = ft.id WHERE 1=1";
$params = [];

if ($filter_truck_id !== '') {
    $sql .= " AND te.truck_id = :truck_id";
    $params[':truck_id'] = $filter_truck_id;
}
if ($filter_invoice !== '' && $filter_invoice !== '0000') {
    $sql .= " AND te.invoice_number LIKE :invoice";
    $params[':invoice'] = '%' . $filter_invoice . '%';
}
if ($filter_date !== '') {
    $sql .= " AND te.date = :date";
    $params[':date'] = $filter_date;
}
if ($filter_amount_usd !== null) {
    $sql .= " AND te.amount_usd = :amount_usd";
    $params[':amount_usd'] = $filter_amount_usd;
}
if ($filter_amount_iqd !== null) {
    $sql .= " AND te.amount_iqd = :amount_iqd";
    $params[':amount_iqd'] = $filter_amount_iqd;
}
if ($filter_note !== '') {
    $sql .= " AND te.note LIKE :note";
    $params[':note'] = '%' . $filter_note . '%';
}

$sql .= " ORDER BY te.date DESC, te.id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خەرجییەکانی تڕێلە | دانە کۆنکریت</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet" />
    <link href="../assets/css/nav.css" rel="stylesheet" />
    <link href="../assets/css/comon/style.css" rel="stylesheet" />
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/table.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet" />

    <style>
        :root { --expense-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        body { background-color: #f7f9fc; font-family: 'Rabar', sans-serif; }
        .page-header { background: var(--expense-gradient); padding: 2.5rem 2rem; border-radius: 0 0 40px 40px; color: white; margin-bottom: 2rem; }
        .main-card { border: none; border-radius: 25px; box-shadow: 0 8px 30px rgba(0,0,0,0.04); margin-top: -3.5rem; }
        .filter-card { border: none; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .form-control, .form-select { border-radius: 12px; padding: 12px; border: 1px solid #ddd; }
        .btn-submit { background: var(--expense-gradient); border: none; color: white; font-weight: 700; padding: 12px 30px; border-radius: 12px; }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header container-fluid text-center">
        <h1 class="fw-bold mb-0">بەڕێوەبردنی خەرجییەکانی تڕێلە</h1>
        <p class="opacity-75">تۆمارکردن و دەستکاریکردنی خەرجییە ناوخۆییەکانی بارهەڵگرەکان</p>
    </div>

    <div class="container mb-5">
        <div class="card main-card overflow-hidden mb-4">
            <div class="card-body p-4">
                <form id="truckExpenseForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">تڕێلە</label>
                            <select name="truck_id" class="form-select" required>
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach($trucks as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['truck_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">ژمارەی پسوڵە (Optional)</label>
                            <input type="text" name="invoice_number" class="form-control" placeholder="0000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بەروار</label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بڕی پارە (دۆلار)</label>
                            <input type="number" step="0.01" name="amount_usd" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بڕی پارە (دینار)</label>
                            <input type="number" step="1" name="amount_iqd" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تێبینی / وردەکاری خەرجی</label>
                            <input type="text" name="note" class="form-control" placeholder="وەک: گۆڕینی ڕۆن و فلتەر" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-submit w-100" id="saveExpenseBtn">تۆمارکردن</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card filter-card bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-filter me-2"></i>فلتەرکردنی خەرجییەکان
                </h5>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="resetFilterBtn">
                    <i class="fas fa-undo me-1"></i>پاککردنەوە
                </button>
            </div>
            <div class="card-body p-4">
                <form id="truckExpenseFilterForm" method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">تڕێلە</label>
                            <select name="filter_truck_id" id="filter_truck_id" class="form-select">
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach($trucks as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $filter_truck_id == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['truck_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">ژمارەی پسوڵە (Optional)</label>
                            <input type="text" name="filter_invoice" id="filter_invoice" class="form-control" placeholder="0000" value="<?= htmlspecialchars($filter_invoice) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بەروار</label>
                            <input type="date" name="filter_date" id="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بڕی پارە (دۆلار)</label>
                            <input type="number" step="0.01" name="filter_amount_usd" id="filter_amount_usd" class="form-control" value="<?= $filter_amount_usd !== null ? $filter_amount_usd : '0' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">بڕی پارە (دینار)</label>
                            <input type="number" step="1" name="filter_amount_iqd" id="filter_amount_iqd" class="form-control" value="<?= $filter_amount_iqd !== null ? $filter_amount_iqd : '0' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تێبینی / وردەکاری خەرجی</label>
                            <input type="text" name="filter_note" id="filter_note" class="form-control" placeholder="وەک: گۆڕینی ڕۆن و فلتەر" value="<?= htmlspecialchars($filter_note) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3">
                                <i class="fas fa-search me-1"></i>فلتەرکردن
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>تڕێلە</th>
                                <th>پسوڵە</th>
                                <th>بەروار</th>
                                <th>بڕ (USD)</th>
                                <th>بڕ (IQD)</th>
                                <th>تێبینی</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($expenses as $exp): ?>
                            <tr class="expense-row" data-truck-id="<?= $exp['truck_id'] ?>" data-invoice="<?= htmlspecialchars($exp['invoice_number'] ?: '') ?>" data-date="<?= $exp['date'] ?>" data-usd="<?= $exp['amount_usd'] ?>" data-iqd="<?= $exp['amount_iqd'] ?>" data-note="<?= htmlspecialchars(mb_strtolower($exp['note'])) ?>">
                                <td class="fw-bold"><?= htmlspecialchars($exp['truck_name']) ?></td>
                                <td><?= htmlspecialchars($exp['invoice_number'] ?: '---') ?></td>
                                <td><?= $exp['date'] ?></td>
                                <td class="text-danger fw-bold">$<?= number_format($exp['amount_usd'], 2) ?></td>
                                <td class="text-secondary"><?= number_format($exp['amount_iqd']) ?> دینار</td>
                                <td><?= htmlspecialchars($exp['note']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info rounded-circle me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($exp)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle" onclick="deleteExpense(<?= $exp['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold">دەستکاریکردنی خەرجی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExpenseForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">تڕێلە</label>
                        <select name="truck_id" id="edit_truck_id" class="form-select" required>
                            <?php foreach($trucks as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['truck_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ژمارەی پسوڵە</label>
                            <input type="text" name="invoice_number" id="edit_invoice_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">بەروار</label>
                            <input type="date" name="date" id="edit_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">بڕ (USD)</label>
                            <input type="number" step="0.01" name="amount_usd" id="edit_amount_usd" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">بڕ (IQD)</label>
                            <input type="number" step="1" name="amount_iqd" id="edit_amount_iqd" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">تێبینی</label>
                        <input type="text" name="note" id="edit_note" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="updateBtn">نوێکردنەوە</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $('#truckExpenseForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../process/truck_expenses/add.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) location.reload();
                else Swal.fire('هەڵە', res.msg, 'error');
            }
        });
    });

    function openEditModal(exp) {
        $('#edit_id').val(exp.id);
        $('#edit_truck_id').val(exp.truck_id);
        $('#edit_invoice_number').val(exp.invoice_number);
        $('#edit_date').val(exp.date);
        $('#edit_amount_usd').val(exp.amount_usd);
        $('#edit_amount_iqd').val(exp.amount_iqd);
        $('#edit_note').val(exp.note);
        $('#editExpenseModal').modal('show');
    }

    $('#editExpenseForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../process/truck_expenses/update.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) location.reload();
                else Swal.fire('هەڵە', res.msg, 'error');
            }
        });
    });

    function deleteExpense(id) {
        Swal.fire({
            title: 'دڵنیای؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'سڕینەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/truck_expenses/delete.php', {id: id}, function(res) {
                    if(res.success) location.reload();
                }, 'json');
            }
        });
    }

    // Live JS Filtering logic
    function applyLiveFilter() {
        let truckId = $('#filter_truck_id').val();
        let invoice = $('#filter_invoice').val().trim().toLowerCase();
        let dateVal = $('#filter_date').val();
        let amountUsdVal = $('#filter_amount_usd').val();
        let amountIqdVal = $('#filter_amount_iqd').val();
        let amountUsd = parseFloat(amountUsdVal);
        let amountIqd = parseFloat(amountIqdVal);
        let note = $('#filter_note').val().trim().toLowerCase();

        $('tbody tr.expense-row').each(function() {
            let rowTruck = $(this).data('truck-id') + '';
            let rowInvoice = ($(this).data('invoice') + '').toLowerCase();
            let rowDate = $(this).data('date') + '';
            let rowUsd = parseFloat($(this).data('usd')) || 0;
            let rowIqd = parseFloat($(this).data('iqd')) || 0;
            let rowNote = ($(this).data('note') + '').toLowerCase();

            let show = true;

            if (truckId && rowTruck !== truckId) show = false;
            if (invoice && invoice !== '0000' && !rowInvoice.includes(invoice)) show = false;
            if (dateVal && rowDate !== dateVal) show = false;
            if (amountUsdVal !== '' && amountUsdVal !== '0' && !isNaN(amountUsd) && rowUsd !== amountUsd) show = false;
            if (amountIqdVal !== '' && amountIqdVal !== '0' && !isNaN(amountIqd) && rowIqd !== amountIqd) show = false;
            if (note && !rowNote.includes(note)) show = false;

            if (show) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    $('#filter_truck_id, #filter_invoice, #filter_date, #filter_amount_usd, #filter_amount_iqd, #filter_note').on('input change', function() {
        applyLiveFilter();
    });

    $('#resetFilterBtn').on('click', function() {
        $('#filter_truck_id').val('');
        $('#filter_invoice').val('');
        $('#filter_date').val('');
        $('#filter_amount_usd').val('0');
        $('#filter_amount_iqd').val('0');
        $('#filter_note').val('');
        applyLiveFilter();
    });
</script>
</body>
</html>

