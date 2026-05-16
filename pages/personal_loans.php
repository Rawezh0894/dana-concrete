<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
require_once '../process/personal_loans/personal_loan_helper.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
if (!personal_loan_can_view()) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        . '<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2></div>';
    exit;
}

$can_manage = personal_loan_can_manage();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قەرزی کەسانی دەرەکی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-5">
    <div class="d-flex flex-column flex-lg-row flex-wrap align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="mb-1" style="color: var(--seafoam-green); font-weight: bold;">قەرزی کەسانی دەرەکی</h2>
            <p class="text-muted small mb-0">کەسەکان کڕیار نین — قەرزدان و وەرگرتنەوە بە دۆلار/دینار، پەیوەست بە قاسە</p>
        </div>
        <?php if ($can_manage): ?>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;" data-bs-toggle="modal" data-bs-target="#addPersonModal">
                <i class="fas fa-user-plus"></i> کەسی نوێ
            </button>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#issueLoanModal">
                <i class="fas fa-hand-holding-usd"></i> دانانی قەرز
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card summary-card card-gradient-success card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <h5 class="card-title">کۆی قەرزی ماوە ($)</h5>
                    <h3 class="card-value" id="summaryRemainingUsd">$0.00</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card summary-card card-gradient-warning card-shadow-medium card-rounded">
                <div class="card-body text-center">
                    <h5 class="card-title">کۆی قەرزی ماوە (د.ع)</h5>
                    <h3 class="card-value" id="summaryRemainingIqd">0</h3>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-5 rounded border bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-bottom bg-light">
            <h3 class="h5 mb-0 fw-bold"><i class="fas fa-list text-primary"></i> قەرزە چالاکەکان</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center mb-0" id="activeLoansTable">
                <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                    <tr>
                        <th>#</th>
                        <th>کەس</th>
                        <th>بەروار</th>
                        <th>ماوە ($)</th>
                        <th>ماوە (د.ع)</th>
                        <?php if ($can_manage): ?><th>کردار</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="active-loans-tbody">
                    <tr><td colspan="<?= $can_manage ? 6 : 5 ?>" class="py-4 text-muted">بارکردن...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded border bg-white shadow-sm p-3">
        <h3 class="h5 fw-bold mb-3"><i class="fas fa-users"></i> لیستی کەسەکان</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center" id="personsTable">
                <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                    <tr>
                        <th>#</th>
                        <th>ناو</th>
                        <th>مۆبایل</th>
                        <th>ماوە ($)</th>
                        <th>ماوە (د.ع)</th>
                        <?php if ($can_manage): ?><th>کردار</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="persons-tbody"></tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($can_manage): ?>
<!-- Add person -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPersonForm">
        <div class="modal-header">
          <h5 class="modal-title">زیادکردنی کەس</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">ناو</label><input type="text" class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">مۆبایل</label><input type="text" class="form-control" name="mobile"></div>
          <div class="mb-3"><label class="form-label">تێبینی</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--seafoam-green);">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit person -->
<div class="modal fade" id="editPersonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editPersonForm">
        <input type="hidden" name="id" id="edit_person_id">
        <div class="modal-header">
          <h5 class="modal-title">دەستکاری کەس</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">ناو</label><input type="text" class="form-control" name="name" id="edit_person_name" required></div>
          <div class="mb-3"><label class="form-label">مۆبایل</label><input type="text" class="form-control" name="mobile" id="edit_person_mobile"></div>
          <div class="mb-3"><label class="form-label">تێبینی</label><textarea class="form-control" name="notes" id="edit_person_notes" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Issue loan -->
<div class="modal fade" id="issueLoanModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="issueLoanForm">
        <div class="modal-header">
          <h5 class="modal-title">دانانی قەرز (دەرچوون لە قاسە)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-start" dir="rtl">
          <div class="alert alert-info small">پارەکە لە قاسە دەردەچێت؛ ئەم کەسە قەرزاری ئێمە دەبێت.</div>
          <div class="mb-3">
            <label class="form-label">کەس</label>
            <select class="form-select" name="person_id" id="issue_person_id" required></select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">بڕی قەرز ($)</label>
              <input type="number" class="form-control" name="loan_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">بڕی قەرز (د.ع)</label>
              <input type="number" class="form-control" name="loan_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">بەروار</label>
              <input type="date" class="form-control" name="loan_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">تێبینی</label>
              <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary fw-bold">تۆمارکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Repay loan -->
<div class="modal fade" id="repayLoanModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="repayLoanForm">
        <input type="hidden" name="loan_id" id="repay_loan_id">
        <div class="modal-header">
          <h5 class="modal-title">وەرگرتنەوەی قەرز (هاتوو بۆ قاسە)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-start" dir="rtl">
          <div class="rounded border border-success bg-success bg-opacity-10 p-3 mb-3">
            <strong id="repay_person_display"></strong>
            <div class="small mt-1">ماوە: <span id="repay_rem_usd"></span> $ | <span id="repay_rem_iqd"></span> د.ع</div>
          </div>
          <p class="small text-muted">نموونە: قەرز 720$ — دەدات 750$ — باقی 30$ (یان بە دینار لە خانەی باقی)</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">پارەی وەرگیراو ($)</label>
              <input type="number" class="form-control repay-calc" id="received_usd" name="received_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">پارەی وەرگیراو (د.ع)</label>
              <input type="number" class="form-control repay-calc" id="received_iqd" name="received_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">باقی ($)</label>
              <input type="number" class="form-control repay-calc" id="change_back_usd" name="change_back_usd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">باقی (د.ع)</label>
              <input type="number" class="form-control repay-calc" id="change_back_iq" name="change_back_iq" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">نرخی دۆلار (د.ع بۆ 100$)</label>
              <div class="input-group">
                <input type="number" class="form-control repay-calc" id="dolar_rate" name="dolar_rate" min="1" step="1" value="150000">
                <button type="button" class="btn btn-outline-secondary" id="btnFetchRate" title="نوێکردنەوەی نرخ"><i class="fas fa-sync"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">بەروار</label>
              <input type="date" class="form-control" name="repayment_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label text-muted small">پاش کەمکردنەوەی باقی (بە دۆلار)</label>
              <input type="text" class="form-control" id="repay_net_preview" readonly>
            </div>
            <div class="col-12">
              <label class="form-label">تێبینی</label>
              <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success fw-bold" style="background: var(--seafoam-green);">تۆمارکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
window.PERSONAL_LOAN_CAN_MANAGE = <?= $can_manage ? 'true' : 'false' ?>;
</script>
<script src="../assets/js/personal_loans/personal_loans.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>
