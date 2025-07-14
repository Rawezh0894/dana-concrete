<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_person_other_expenses_profile')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$person_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$person_name = '';
if ($person_id) {
    $stmt = $pdo->prepare('SELECT name FROM other_expense_persons WHERE id = ?');
    $stmt->execute([$person_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $person_name = $row ? $row['name'] : '';
}
?>
<!DOCTYPE html>
<html lang="ku">

<head>
    <meta charset="UTF-8">
    <title>پرۆفایلی کەس</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .nav-tabs .nav-link {
            color: var(--seafoam-green) !important;
        }

        .nav-tabs .nav-link.active {
            background: var(--seafoam-green) !important;
            color: #fff !important;
            border-color: var(--seafoam-green) var(--seafoam-green) #fff !important;
        }
    </style>
</head>

<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
                <?php echo htmlspecialchars($person_name); ?>
            </h2>
        </div>
        <div class="mb-3">
            <a href="person_other_expenses.php" class="btn"
                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">
                <i class="fa fa-arrow-right"></i> گەڕانەوە بۆ لیستی کەسان
            </a>
        </div>
        <div class="row mb-3" id="person-summary-cards">
          <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
              <div class="card-body">
                <h5 class="card-title">کۆی گشتی خەرجی بە دۆلار</h5>
                <span id="summary_total_usd" style="font-size:2rem;font-weight:bold;">0</span>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
              <div class="card-body">
                <h5 class="card-title">کۆی گشتی خەرجی بە دینار</h5>
                <span id="summary_total_iqd" style="font-size:2rem;font-weight:bold;">0</span>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="card text-center shadow">
              <div class="card-body">
                <h5 class="card-title">ژمارەی خەرجیەکان</h5>
                <span id="summary_count" style="font-size:2rem;font-weight:bold;">0</span>
              </div>
            </div>
          </div>
        </div>
        <div class="row mb-4" id="person-cards">
            <!-- Cards will be loaded here by JS -->
        </div>
        <ul class="nav nav-tabs mb-3" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses"
                    type="button" role="tab" aria-controls="expenses" aria-selected="true">مێژووی خەرجیەکان</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="debt-tab" data-bs-toggle="tab" data-bs-target="#debt" type="button"
                    role="tab" aria-controls="debt" aria-selected="false">مێژووی دانەوە</button>
            </li>
        </ul>
        <div class="tab-content" id="profileTabsContent">
            <div class="tab-pane fade show active" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="expensesTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>مەبەست</th>
                                <th>کارمەند</th>
                                <th>سەیارە</th>
                                <th>جۆری مامەڵە</th>
                                <th>جۆری پارە</th>
                                <th>ژمارەی وەسڵ</th>
                                <th>بڕی دینار</th>
                                <th>بڕی دۆلار</th>
                                <th>پارەی دراو دینار</th>
                                <th>پارەی دراو دۆلار</th>
                                <th>نرخی 100 دۆلار</th>
                                <th>ماوە دینار</th>
                                <th>ماوە دۆلار</th>
                                <th>بەروار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Expenses will be loaded here by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="debt" role="tabpanel" aria-labelledby="debt-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">مێژووی دانەوە</h5>
                    <button class="btn"
                        style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;"
                        data-bs-toggle="modal" data-bs-target="#addDebtModal"><i class="fa fa-plus"></i> دانەوە</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="debtTable">
                        <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                            <tr>
                                <th>#</th>
                                <th>بەروار</th>
                                <th>بڕی دۆلار</th>
                                <th>بڕی دینار</th>
                                <th>تێبینی</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Debt payments will be loaded here by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Add Debt Modal -->
        <div class="modal fade" id="addDebtModal" tabindex="-1" aria-labelledby="addDebtModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="addDebtForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDebtModalLabel">دانەوە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="debt_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="debt_date" name="date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                <input type="number" class="form-control" id="debt_amount_usd" name="amount_usd" min="0"
                                    step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="debt_amount_iqd" class="form-label">بڕی دینار</label>
                                <input type="number" class="form-control" id="debt_amount_iqd" name="amount_iqd" min="0"
                                    step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="debt_note" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="debt_note" name="note" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                            <button type="submit" class="btn"
                                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">دانەوە</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit Debt Modal -->
        <div class="modal fade" id="editDebtModal" tabindex="-1" aria-labelledby="editDebtModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editDebtForm">
                        <input type="hidden" id="edit_debt_id" name="id">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editDebtModalLabel">دەستکاری دانەوە</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_debt_date" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="edit_debt_date" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_debt_amount_usd" class="form-label">بڕی دۆلار</label>
                                <input type="number" class="form-control" id="edit_debt_amount_usd" name="amount_usd"
                                    min="0" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="edit_debt_amount_iqd" class="form-label">بڕی دینار</label>
                                <input type="number" class="form-control" id="edit_debt_amount_iqd" name="amount_iqd"
                                    min="0" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="edit_debt_note" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="edit_debt_note" name="note" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                            <button type="submit" class="btn"
                                style="background: var(--seafoam-green); color: #fff; border: none; font-weight: bold;">پاشەکەوتکردن</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const PERSON_ID = <?php echo $person_id; ?>;
        function loadPersonSummaryCards() {
            $.get('../process/person_other_expenses_profile/select_other_expenses.php', { person_id: PERSON_ID, stats: 1 }, function(data) {
                if (!data || !data.stats) return;
                const s = data.stats;
                $('#summary_total_usd').text(Number(s.total_usd).toLocaleString('en-US') + ' $');
                $('#summary_total_iqd').text(Number(s.total_iqd).toLocaleString('en-US') + ' د.ع');
                $('#summary_count').text(s.count);
            }, 'json');
        }
        $(function() { loadPersonSummaryCards(); });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/swalAlert.js"></script>
    <script src="../assets/js/comon/table-controler.js"></script>
    <script src="../assets/js/person_other_expenses_profile/select_other_expenses.js"></script>
    <script src="../assets/js/person_other_expenses_profile/select_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/add_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/update_debt.js"></script>
    <script src="../assets/js/person_other_expenses_profile/delete_debt.js"></script>
</body>

</html>