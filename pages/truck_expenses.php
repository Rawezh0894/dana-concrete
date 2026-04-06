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

// Fetch recent expenses
$expenses = $pdo->query("SELECT te.*, ft.truck_name FROM truck_expenses te JOIN factory_trucks ft ON te.truck_id = ft.id ORDER BY te.date DESC, te.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خەرجییەکانی تڕێلە | دانە کۆنکریت</title>
    
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
            --expense-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        body { background-color: #f7f9fc; font-family: 'Rabar', sans-serif; }
        .page-header {
            background: var(--expense-gradient);
            padding: 3rem 2rem;
            border-radius: 0 0 40px 40px;
            color: white;
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.2);
            margin-bottom: 2rem;
        }
        .main-card {
            border: none;
            border-radius: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
            margin-top: -4rem;
        }
        .form-label { font-weight: 700; color: #444; }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #ddd;
        }
        .btn-submit {
            background: var(--expense-gradient);
            border: none;
            color: white;
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4); }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header container-fluid">
        <div class="container text-center">
            <h1 class="display-6 fw-bold mb-0">داخڵکردنی خەرجییەکانی تڕێلە</h1>
            <p class="opacity-75">خەرجییەکانی سووتەمەنی، چاککردنەوە و مەسرووفاتی تڕێلەکان لێرە تۆمار بکە</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <!-- Add Expense Form -->
            <div class="col-lg-12">
                <div class="card main-card overflow-hidden">
                    <div class="card-header bg-white p-4 border-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="fas fa-file-invoice-dollar text-danger fs-4"></i>
                            </div>
                            <h4 class="mb-0 fw-bold">تۆمارکردنی خەرجی نوێ</h4>
                        </div>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <form id="truckExpenseForm">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label">بۆ کام تڕێلە؟ <span class="text-danger">*</span></label>
                                    <select name="truck_id" class="form-select" required>
                                        <option value="">-- تڕێلە هەڵبژێرە --</option>
                                        <?php foreach($trucks as $truck): ?>
                                            <option value="<?= $truck['id'] ?>"><?= htmlspecialchars($truck['truck_name']) ?> (<?= htmlspecialchars($truck['plate_number']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">جۆری خەرجی <span class="text-danger">*</span></label>
                                    <select name="expense_type" class="form-select" required>
                                        <option value="">-- جۆر هەڵبژێرە --</option>
                                        <option value="سووتەمەنی (گاز)">سووتەمەنی (گاز)</option>
                                        <option value="چاککردنەوە و مەیمون">چاککردنەوە و مەیمون</option>
                                        <option value="ڕۆن گۆڕین">ڕۆن گۆڕین</option>
                                        <option value="پاتری و تایە">پاتری و تایە</option>
                                        <option value="موقافە">موقافە</option>
                                        <option value="مەسرەفی تفرەقە">مەسرەفی تفرەقە</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">بەروار <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">بڕی پارە (دۆلار) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount_usd" class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">بڕی پارە (دینار)</label>
                                    <input type="number" step="1" name="amount_iqd" class="form-control" placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">نووسین / تێبینی</label>
                                    <input type="text" name="note" class="form-control" placeholder="وەک: پسۆڵەی چاککردنەوەی بڕێگ">
                                </div>
                                <div class="col-12 text-center mt-5">
                                    <hr class="opacity-10 mb-4" />
                                    <button type="submit" class="btn btn-submit" id="saveExpenseBtn">
                                        <i class="fas fa-check-circle me-1"></i> پاشەکەوتی خەرجی بکە
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses Table -->
            <div class="col-lg-12 mt-4">
                <div class="card border-0 rounded-4 shadow-sm">
                    <div class="card-header bg-white p-4 border-0">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-history text-muted me-2"></i> خەرجییە نوێیەکان</h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="table-responsive">
                            <table class="table custom-table table-hover">
                                <thead>
                                    <tr>
                                        <th>تڕێلە</th>
                                        <th>جۆر</th>
                                        <th>بەروار</th>
                                        <th>بڕ (USD)</th>
                                        <th>بڕ (IQD)</th>
                                        <th>تێبینی</th>
                                        <th>کردار</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($expenses as $exp): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($exp['truck_name']) ?></td>
                                        <td><span class="badge bg-light text-dark p-2 rounded-3 border"><?= htmlspecialchars($exp['expense_type']) ?></span></td>
                                        <td><?= $exp['date'] ?></td>
                                        <td class="text-danger fw-bold">$<?= number_format($exp['amount_usd'], 2) ?></td>
                                        <td class="text-secondary"><?= number_format($exp['amount_iqd']) ?> دینار</td>
                                        <td class="small text-muted"><?= htmlspecialchars($exp['note']) ?></td>
                                        <td>
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
        </div>
    </div>
</main>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $('#truckExpenseForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveExpenseBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> پاشەکەوت دەکرێت...');

        $.ajax({
            url: '../process/truck_expenses/add.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire('سەرکەوتوو', 'خەرجییە ناوخۆییەکە بە سەرکەوتوویی تۆمار کرا', 'success').then(() => location.reload());
                } else {
                    Swal.fire('هەڵە', res.msg, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> پاشەکەوتی خەرجی بکە');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'Server Error', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> پاشەکەوتی خەرجی بکە');
            }
        });
    });

    function deleteExpense(id) {
        Swal.fire({
            title: 'دڵنیای؟',
            text: "ئەم خەرجییە بە تەواوی دەسڕێتەوە!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'بەڵێ، بیسڕەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/truck_expenses/delete.php', {id: id}, function(res) {
                    if(res.success) location.reload();
                }, 'json');
            }
        });
    }
</script>
</body>
</html>
