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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">پارەدان بە کارمەندەکان</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی پارەدان</button>
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
                    <th>کۆی گشتی</th>
                    <th>مانگ</th>
                    <th>بەروار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Payments will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPaymentModalLabel">زیادکردنی پارەدان</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="salary" class="form-label">مووچە (د.ع)</label>
            <input type="text" class="form-control" id="salary" name="salary" readonly required>
          </div>
          <div class="mb-3">
            <label for="karwanhisabi" class="form-label">کاروانحیسابی</label>
            <input type="text" class="form-control" id="karwanhisabi" name="karwanhisabi" required>
          </div>
          <div class="mb-3">
            <label for="bonus" class="form-label">بەخشیش (د.ع)</label>
            <input type="number" class="form-control" id="bonus" name="bonus" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="total_add" class="form-label">کۆی گشتی (مووچە + کاروانحیسابی + بەخشیش)</label>
            <input type="text" class="form-control" id="total_add" readonly>
          </div>
          <div class="mb-3">
            <label for="pay_month" class="form-label">مانگ</label>
            <input type="month" class="form-control" id="pay_month" name="pay_month" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editPaymentForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editPaymentModalLabel">دەستکاری پارەدان</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_payment_id" name="id">
          <div class="mb-3">
            <label for="edit_employee_id" class="form-label">کارمەند</label>
            <select class="form-select" id="edit_employee_id" name="employee_id" required>
              <option value="">-- هەلبژێرە --</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['salary'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="edit_salary" class="form-label">مووچە (د.ع)</label>
            <input type="text" class="form-control" id="edit_salary" name="salary" readonly required>
          </div>
          <div class="mb-3">
            <label for="edit_karwanhisabi" class="form-label">کاروانحیسابی</label>
            <input type="text" class="form-control" id="edit_karwanhisabi" name="karwanhisabi" required>
          </div>
          <div class="mb-3">
            <label for="edit_bonus" class="form-label">بەخشیش (د.ع)</label>
            <input type="number" class="form-control" id="edit_bonus" name="bonus" min="0" step="0.01" value="0">
          </div>
          <div class="mb-3">
            <label for="total_edit" class="form-label">کۆی گشتی (مووچە + کاروانحیسابی + بەخشیش)</label>
            <input type="text" class="form-control" id="total_edit" readonly>
          </div>
          <div class="mb-3">
            <label for="edit_pay_month" class="form-label">مانگ</label>
            <input type="month" class="form-control" id="edit_pay_month" name="pay_month" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/employee_payments/add.js"></script>
<script src="../assets/js/employee_payments/select.js"></script>
<script src="../assets/js/employee_payments/update.js"></script>
<script src="../assets/js/employee_payments/delete.js"></script>
<script>
$(function() {
    function calcTotalAdd() {
        var salary = parseFloat($('#salary').val()) || 0;
        var karwan = $('#karwanhisabi').val().replace(/,/g, '');
        var karwanVal = parseFloat(karwan) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var total = salary + karwanVal + bonus;
        $('#total_add').val(total.toLocaleString('en-US') + ' د.ع');
    }
    function calcTotalEdit() {
        var salary = parseFloat($('#edit_salary').val()) || 0;
        var karwan = $('#edit_karwanhisabi').val().replace(/,/g, '');
        var karwanVal = parseFloat(karwan) || 0;
        var bonus = parseFloat($('#edit_bonus').val()) || 0;
        var total = salary + karwanVal + bonus;
        $('#total_edit').val(total.toLocaleString('en-US') + ' د.ع');
    }
    $('#salary, #karwanhisabi, #bonus').on('input change', calcTotalAdd);
    $('#edit_salary, #edit_karwanhisabi, #edit_bonus').on('input change', calcTotalEdit);
    // Auto-fill salary in Add Payment Modal
    $('#employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#salary').val(salary);
        calcTotalAdd();
    });
    // Auto-fill salary in Edit Payment Modal
    $('#edit_employee_id').on('change', function() {
        var salary = $(this).find('option:selected').data('salary') || '';
        $('#edit_salary').val(salary);
        calcTotalEdit();
    });
    // Initial calculation
    calcTotalAdd();
    calcTotalEdit();
});
</script>
</body>
</html>
