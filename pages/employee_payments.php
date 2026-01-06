<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_employee_payment')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Fetch employees for dropdown
$employees = $pdo->query('SELECT id, name, salary FROM employees ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پارەدان بە کارمەندەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/summary_cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color: var(--seafoam-green); font-weight: bold;">مووچە و باڵانسی کارمەندان (Payroll & HR)</h2>
            <p class="text-muted">بەڕێوەبردنی مووچە، پاداشت، و باڵانسی کارمەندەکان</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#makePaymentModal">
                <i class="fas fa-hand-holding-usd"></i> خەرجکردنی پارە (Payment)
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal" style="background: var(--seafoam-green); font-weight: bold;">
                <i class="fas fa-file-invoice-dollar"></i> تۆمارکردنی مووچە (Payroll)
            </button>
        </div>
    </div>
    
    <!-- Summary Cards (Balances) -->
    <div class="row mb-4" id="balance-cards">
        <!-- Will be populated by JS to show Total Company Debt / Total Paid this month etc -->
         <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-wallet card-icon"></i>
                    <h6 class="card-title">کۆی باڵانسی کارمەندان (قەرز)</h6>
                    <div class="fs-4 fw-bold" id="total-balance-debt">0 د.ع</div>
                    <small class="text-light">بڕی پارەی ماوە لای کۆمپانیا</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-file-invoice card-icon"></i>
                    <h6 class="card-title">کۆی مووچەی ئەم مانگە</h6>
                    <div class="fs-4 fw-bold" id="total-payroll-month">0 د.ع</div>
                    <small class="text-light">بەپێی پسووڵەکانی مووچە</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center shadow card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave-alt card-icon"></i>
                    <h6 class="card-title">کۆی پارەی دراو (Cash Out)</h6>
                    <div class="fs-4 fw-bold" id="total-paid-month">0 د.ع</div>
                    <small class="text-light">لە ئەم مانگەدا</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-3" id="hrTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#payroll" type="button" role="tab" aria-selected="true">
                <i class="fas fa-list-alt"></i> پسووڵەکانی مووچە (Payrolls)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab" aria-selected="false">
                <i class="fas fa-book"></i> تۆماری جووڵەکان (Ledger)
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="hrTabsContent">
        
        <!-- Payroll Tab (Existing Table) -->
        <div class="tab-pane fade show active" id="payroll" role="tabpanel" aria-labelledby="payroll-tab">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-select" id="month-filter">
                        <option value="">هەموو مانگەکان</option>
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="employee-filter">
                        <option value="">هەموو کارمەندەکان</option>
                        <!-- Populated by JS -->
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="employeePaymentsTable">
                    <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                        <tr>
                            <th>#</th>
                            <th>کارمەند</th>
                            <th>مووچە (د.ع)</th>
                            <th>کاروانحیسابی</th>
                            <th>بەخشیش (د.ع)</th>
                            <th>کۆی شایستە</th>
                            <th>مانگ</th>
                            <th>بەروار</th>
                            <th>کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Payments loaded by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ledger Tab (New) -->
        <div class="tab-pane fade" id="ledger" role="tabpanel" aria-labelledby="ledger-tab">
             <div class="table-responsive">
                <table class="table table-striped table-hover align-middle text-center" id="ledgerTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>کارمەند</th>
                            <th>جۆر (Type)</th>
                            <th>بڕ (Amount)</th>
                            <th>کردار (Operation)</th>
                            <th>بەروار</th>
                            <th>تێبینی</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody">
                        <!-- Loaded via JS -->
                        <tr><td colspan="7">تکایە چاوەڕوان بە...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<!-- Add Payroll Modal (Accrual) -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPaymentModalLabel">تۆمارکردنی مووچە (Accrual)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
             ئەم بەشە بۆ دیاریکردنی شایستەی داراییە (Salary Accrual)، وەک قەرز دەچێتە سەر باڵانسی کارمەند.
          </div>
          <div class="mb-3">
            <label for="employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
              <div class="col-md-6 mb-3">
                <label for="salary" class="form-label">مووچەی بنەڕەتی (د.ع)</label>
                <input type="number" class="form-control" id="salary" name="salary" step="any" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="karwanhisabi" class="form-label">کاروانحیسابی / دەرماڵە</label>
                <input type="number" class="form-control" id="karwanhisabi" name="karwanhisabi" step="any" value="0">
              </div>
          </div>
          <div class="mb-3">
            <label for="bonus" class="form-label">بەخشیش / ئۆڤەرتایم (د.ع)</label>
            <input type="number" class="form-control" id="bonus" name="bonus" step="any" value="0">
          </div>
          <div class="mb-3">
            <label for="total_add" class="form-label fw-bold">کۆی گشتی (دەچێتە سەر باڵانس)</label>
            <input type="text" class="form-control fw-bold" id="total_add" readonly>
          </div>
          <div class="mb-3">
            <label for="pay_month" class="form-label">مانگی مووچە</label>
            <input type="month" class="form-control" id="pay_month" name="pay_month" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success">تۆمارکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Make Payment Modal (Cash Out) -->
<div class="modal fade" id="makePaymentModal" tabindex="-1" aria-labelledby="makePaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="makePaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="makePaymentModalLabel">خەرجکردنی پارە (Payment)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
             ئەمە پارە لە قاسە دەردەکات و لە باڵانسی کارمەند کەمی دەکاتەوە.
          </div>
          <div class="mb-3">
            <label for="pay_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="pay_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="payment_amount" class="form-label">بڕی پارە (د.ع)</label>
            <input type="number" class="form-control" id="payment_amount" name="amount" step="any" required placeholder="0.00">
          </div>
          <div class="mb-3">
            <label for="payment_date" class="form-label">بەروار</label>
            <input type="date" class="form-control" id="payment_date" name="date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label for="payment_note" class="form-label">تێبینی</label>
            <textarea class="form-control" id="payment_note" name="note" rows="2" placeholder="بۆ نموونە: پێشەکینە / مووچە"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary">خەرجکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<!-- Keeping existing JS for table control -->
<script src="../assets/js/comon/table-controler.js"></script>
<!-- Existing scripts for Payroll add/edit -->
<script src="../assets/js/employee_payments/add.js"></script>
<script src="../assets/js/employee_payments/select.js"></script>
<script src="../assets/js/employee_payments/update.js"></script>
<script src="../assets/js/employee_payments/delete.js"></script>
<script src="../assets/js/employee_payments/summary.js"></script>

<!-- New JS for Ledger and Payments -->
<script>
$(function() {
    // Select2
    $('#employee_id, #edit_employee_id, #pay_employee_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#addPaymentModal') // Adjust per modal
    });
    // Fix select2 in other modals
    $('#pay_employee_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#makePaymentModal')
    });


    // Calculate Total in Add Modal
    function calcTotalAdd() {
        var salary = parseFloat($('#salary').val()) || 0;
        var karwan = parseFloat($('#karwanhisabi').val()) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var total = salary + karwan + bonus;
        $('#total_add').val(total.toLocaleString('en-US') + ' د.ع');
    }
    $('#salary, #karwanhisabi, #bonus').on('input keyup change', calcTotalAdd);
    
    // Auto fill Salary
    $('#employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || 0;
        $('#salary').val(salary);
        calcTotalAdd();
    });

    // Handle Make Payment Form
    $('#makePaymentForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: '../process/employee_payments/make_payment.php', // We will create this
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire('سەرکەوتوو', res.message, 'success');
                    $('#makePaymentModal').modal('hide');
                    $('#makePaymentForm')[0].reset();
                    // Reload Ledger if active
                    loadLedger();
                } else {
                    Swal.fire('هەڵە', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'ڕوویدا لە کاتی پەیوەندیکردن', 'error');
            }
        });
    });

    // Load Ledger Tab
    $('button[data-bs-target="#ledger"]').on('shown.bs.tab', function (e) {
        loadLedger();
    });

    function loadLedger() {
        $('#ledgerTableBody').html('<tr><td colspan="7">...</td></tr>');
        $.ajax({
            url: '../process/employee_payments/get_ledger.php', // We will create this
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                var html = '';
                if(data.length > 0) {
                    data.forEach(function(row, index) {
                        var colorClass = row.operation === 'credit' ? 'text-success' : 'text-danger';
                        var opText = row.operation === 'credit' ? 'زیادکردن (Salary)' : 'لێدەرکردن (Payment)';
                        html += `<tr>
                            <td>${index+1}</td>
                            <td>${row.employee_name}</td>
                            <td>${row.type}</td>
                            <td class="${colorClass} fw-bold">${parseFloat(row.amount).toLocaleString()}</td>
                            <td>${opText}</td>
                            <td>${row.transaction_date}</td>
                            <td>${row.description || ''}</td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="7">هیچ داتایەک نییە</td></tr>';
                }
                $('#ledgerTableBody').html(html);
            }
        });
    }

    // Refresh Balances on Load
    updateBalances();
    function updateBalances() {
        // Fetch total debts/payrolls
         $.ajax({
            url: '../process/employee_payments/get_balances_summary.php', // We will create this
            dataType: 'json',
            success: function(res) {
                $('#total-balance-debt').text(parseFloat(res.total_balance).toLocaleString() + ' د.ع');
                $('#total-payroll-month').text(parseFloat(res.total_payroll).toLocaleString() + ' د.ع');
                $('#total-paid-month').text(parseFloat(res.total_paid).toLocaleString() + ' د.ع');
            }
        });
    }
});
</script>
</body>
</html>
