<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
if (!hasPermission('view_cash_box')) {
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
    <title>قاسەکە</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/comon/summary_cards.css" rel="stylesheet">
    <link href="../assets/css/cash_box_custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">

  <!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0" style="color:var(--seafoam-green);font-weight:bold;">
      <i class="fas fa-cash-register me-2"></i>قاسەکە
    </h2>
    <div class="d-flex flex-wrap justify-content-end gap-2">
      <?php if (hasPermission('add_cash_box') || hasPermission('delete_cash_box')): ?>
      <button class="btn btn-danger" id="deleteAllCashBoxBtn">
        <i class="fas fa-trash-alt me-1"></i>سڕینەوەی هەموو مامەڵەکان
      </button>
      <?php endif; ?>
      <button class="btn btn-outline-secondary" id="printReportBtn">
        <i class="fas fa-print me-1"></i>چاپ / PDF
      </button>
      <button class="btn btn-success" id="exportExcelBtn">
        <i class="fas fa-file-excel me-1"></i>Excel
      </button>
      <?php if (hasPermission('add_cash_box')): ?>
      <button class="btn" data-bs-toggle="modal" data-bs-target="#addCashBoxModal"
              style="background:var(--seafoam-green);color:white;font-weight:bold;">
        <i class="fas fa-plus me-1"></i>زیادکردن
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══════════════════ BALANCE HERO ═══════════════════ -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card cash-box-balance-hero shadow border-0 overflow-hidden">
        <div class="card-body p-4">
          <div class="row align-items-center g-4">
            <div class="col-lg-3 text-center">
              <p class="text-uppercase small text-white-50 mb-1 letter-spacing">کۆی باڵانس</p>
              <h4 class="text-white fw-bold mb-0">قاسەکە</h4>
              <p class="text-white-50 small mt-1 mb-0" id="cashBoxTxCount">بارکردن...</p>
            </div>
            <div class="col-lg-9">
              <div class="row g-2 text-center">
                <div class="col-6 col-md-3">
                  <div class="cash-box-stat-tile rounded-3 p-3 h-100">
                    <div class="small text-muted mb-1">باڵانس دۆلار</div>
                    <div class="fs-5 fw-bold text-success" id="cashBoxTotalBalanceUsd">$0.00</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="cash-box-stat-tile rounded-3 p-3 h-100">
                    <div class="small text-muted mb-1">باڵانس دینار</div>
                    <div class="fs-5 fw-bold text-success" id="cashBoxTotalBalanceIqd">0 د.ع</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="cash-box-stat-tile rounded-3 p-3 h-100">
                    <div class="small text-muted mb-1">نزیکەی کۆ (دۆلار)</div>
                    <div class="fs-5 fw-bold text-dark" id="cashBoxTotalBalanceCombined">$0.00</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="cash-box-stat-tile rounded-3 p-3 h-100">
                    <div class="small text-muted mb-1">نرخی ١٠٠ دۆلار</div>
                    <div class="fs-5 fw-bold text-dark" id="dollarRate">0 د.ع</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ FINANCIAL FLOW CARDS ═══════════════════ -->
  <div class="row mb-4" id="cashBoxSummaryCards">
    <!-- Inflow -->
    <div class="col-lg-4 col-md-6 mb-3">
      <div class="card shadow border-0 cashbox-flow-card cashbox-inflow-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="cashbox-flow-icon bg-success bg-opacity-10 rounded-circle me-3">
              <i class="fas fa-arrow-down text-success fs-4"></i>
            </div>
            <div>
              <div class="small text-muted">کۆی داهات (هاتوو)</div>
              <div class="fw-bold text-success fs-5" id="totalInflowUsd">$0.00</div>
            </div>
          </div>
          <div class="text-muted small" id="totalInflowIqd">0 د.ع</div>
        </div>
      </div>
    </div>
    <!-- Outflow -->
    <div class="col-lg-4 col-md-6 mb-3">
      <div class="card shadow border-0 cashbox-flow-card cashbox-outflow-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="cashbox-flow-icon bg-danger bg-opacity-10 rounded-circle me-3">
              <i class="fas fa-arrow-up text-danger fs-4"></i>
            </div>
            <div>
              <div class="small text-muted">کۆی خەرج (ڕۆشتوو)</div>
              <div class="fw-bold text-danger fs-5" id="totalOutflowUsd">$0.00</div>
            </div>
          </div>
          <div class="text-muted small" id="totalOutflowIqd">0 د.ع</div>
        </div>
      </div>
    </div>
    <!-- Net -->
    <div class="col-lg-4 col-md-6 mb-3">
      <div class="card shadow border-0 cashbox-flow-card cashbox-net-card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="cashbox-flow-icon bg-primary bg-opacity-10 rounded-circle me-3">
              <i class="fas fa-balance-scale text-primary fs-4"></i>
            </div>
            <div>
              <div class="small text-muted">باڵانسی خالص (نێت)</div>
              <div class="fw-bold fs-5" id="totalNetUsd">$0.00</div>
            </div>
          </div>
          <div class="text-muted small" id="totalNetIqd">0 د.ع</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ QUICK FILTERS ═══════════════════ -->
  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <span class="text-muted small me-1"><i class="fas fa-bolt me-1"></i>فلتەری خێرا:</span>
    <button class="btn btn-sm btn-outline-secondary quick-filter-btn" data-range="today">ئەمڕۆ</button>
    <button class="btn btn-sm btn-outline-secondary quick-filter-btn" data-range="week">ئەم هەفتەیە</button>
    <button class="btn btn-sm btn-outline-secondary quick-filter-btn" data-range="month">ئەم مانگە</button>
    <button class="btn btn-sm btn-outline-secondary quick-filter-btn" data-range="year">ئەم ساڵە</button>
    <button class="btn btn-sm btn-outline-dark quick-filter-btn" data-range="all">هەموو</button>
  </div>

  <!-- ═══════════════════ FILTERS CARD ═══════════════════ -->
  <div class="card border-0 shadow-sm mb-4 cash-box-filters-card">
    <div class="card-body p-3">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-5">
          <label for="cashBoxSearch" class="form-label fw-semibold mb-1">
            <i class="fas fa-search me-1 text-secondary"></i> گەڕان
          </label>
          <input type="search" id="cashBoxSearch" class="form-control"
                 placeholder="تێبینی یان بەروار (نمونە: قەرز، 2025-08)" autocomplete="off">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label for="filter_from" class="form-label">لە بەروار</label>
          <input type="date" id="filter_from" class="form-control">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label for="filter_to" class="form-label">بۆ بەروار</label>
          <input type="date" id="filter_to" class="form-control">
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
          <button class="btn btn-secondary flex-grow-1" id="clearFilterBtn" type="button">
            <i class="fas fa-eraser me-1"></i> پاک
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ DAILY CLOSING (COLLAPSIBLE) ═══════════════════ -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header cashbox-closing-header d-flex justify-content-between align-items-center"
         data-bs-toggle="collapse" data-bs-target="#dailyClosingPanel" style="cursor:pointer;">
      <span class="fw-semibold">
        <i class="fas fa-calendar-check me-2"></i>باڵانسی داخستنی رۆژانە
      </span>
      <i class="fas fa-chevron-down cashbox-chevron" id="closingChevron"></i>
    </div>
    <div class="collapse" id="dailyClosingPanel">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle text-center mb-0" id="dailyClosingTable">
            <thead style="background:var(--kelly-green);color:var(--seafoam-green);">
              <tr>
                <th>بەروار</th>
                <th>مامەڵەکان</th>
                <th><i class="fas fa-arrow-down text-success me-1"></i>داهات دۆلار</th>
                <th><i class="fas fa-arrow-up text-danger me-1"></i>خەرج دۆلار</th>
                <th><i class="fas fa-arrow-down text-success me-1"></i>داهات دینار</th>
                <th><i class="fas fa-arrow-up text-danger me-1"></i>خەرج دینار</th>
                <th>باڵانسی داخستن دۆلار</th>
                <th>باڵانسی داخستن دینار</th>
              </tr>
            </thead>
            <tbody id="dailyClosingBody">
              <tr><td colspan="8" class="text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin me-2"></i>بارکردن...
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ TRANSACTIONS TABLE ═══════════════════ -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center" id="cashBoxTable">
      <thead style="background:var(--kelly-green);color:var(--seafoam-green);">
        <tr>
          <th>#</th>
          <th>بەروار</th>
          <th>جۆری مامەڵە</th>
          <th>هاتوو/ڕۆشتوو</th>
          <th>بڕ (دینار)</th>
          <th>بڕ (دۆلار)</th>
          <th>دراو</th>
          <th>باڵانس ئێستا</th>
          <th>تێبینی</th>
          <th>لەلایەن</th>
          <th>کات</th>
          <th>کردارەکان</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div><!-- /container-fluid -->


<!-- ═══════════════════════════════════════════════════════
     ADD MODAL
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="addCashBoxModal" tabindex="-1" aria-labelledby="addCashBoxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addCashBoxForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addCashBoxModalLabel">زیادکردنی مامەڵەی قاسەکە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Insufficient-balance alert (hidden by default) -->
          <div class="alert alert-danger d-none" id="addBalanceWarning" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>باڵانس پێبوو نییە:</strong> <span id="addBalanceWarningText"></span>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="type" class="form-label">جۆری مامەڵە</label>
              <select class="form-select" id="type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="deposit">زیادکردن (داهات)</option>
                <option value="withdraw">کەمکردنەوە (خەرج)</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="amount_usd" name="amount_usd" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="currency" class="form-label">جۆری دراو</label>
              <select class="form-select" id="currency" name="currency" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
            <div class="col-md-12 mb-3">
              <label for="note" class="form-label">تێبینی <span class="text-danger">*</span></label>
              <textarea class="form-control" id="note" name="note" rows="4"
                        placeholder="نمونە: وەرگرتنی پارە لە کڕیار (ناو، پڕۆژە، مۆڵەت)" required minlength="10"></textarea>
              <small class="form-text text-muted">کەمترین ١٠ پیت پێویستە</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background:var(--seafoam-green);color:white;font-weight:bold;">
            <i class="fas fa-plus me-1"></i>زیادکردن
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     EDIT MODAL
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="editCashBoxModal" tabindex="-1" aria-labelledby="editCashBoxModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editCashBoxForm">
        <input type="hidden" id="edit_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title" id="editCashBoxModalLabel">نوێکردنەوەی مامەڵەی قاسەکە</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-select" id="edit_type" name="type" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="deposit">زیادکردن (داهات)</option>
                <option value="withdraw">کەمکردنەوە (خەرج)</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" class="form-control" id="edit_amount_iqd" name="amount_iqd" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" class="form-control" id="edit_amount_usd" name="amount_usd" min="0" step="0.01" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_currency" class="form-label">جۆری دراو</label>
              <select class="form-select" id="edit_currency" name="currency" required>
                <option value="">-- هەڵبژێرە --</option>
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
            <div class="col-md-12 mb-3">
              <label for="edit_note" class="form-label">تێبینی <span class="text-danger">*</span></label>
              <textarea class="form-control" id="edit_note" name="note" rows="4" required minlength="10"></textarea>
              <small class="form-text text-muted">کەمترین ١٠ پیت پێویستە</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background:var(--seafoam-green);color:white;font-weight:bold;">
            <i class="fas fa-save me-1"></i>نوێکردنەوە
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     AUDIT LOG MODAL
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="auditLogModal" tabindex="-1" aria-labelledby="auditLogModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header cashbox-audit-header">
        <h5 class="modal-title" id="auditLogModalLabel">
          <i class="fas fa-history me-2"></i>مێژووی گۆڕانکاری
          <span class="badge bg-secondary ms-2" id="auditLogTxId"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="auditLogContent" class="p-3">
          <div class="text-center py-4 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>بارکردن...
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/comon/table-controler.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/add.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/select.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/delete.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/update.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/summary.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/audit.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/cash_box/daily_closing.js" nonce="<?php echo $csp_nonce; ?>"></script>

<script nonce="<?php echo $csp_nonce; ?>">
// Quick filter buttons
$(document).on('click', '.quick-filter-btn', function () {
    var range = $(this).data('range');
    var today  = new Date();
    var y = today.getFullYear(), m = today.getMonth(), d = today.getDate();
    var fmt = function(dt) {
        return dt.toISOString().split('T')[0];
    };
    var from = '', to = '';
    if (range === 'today') {
        from = to = fmt(today);
    } else if (range === 'week') {
        var day  = today.getDay();
        var diff = (day + 1) % 7; // Saturday = first day of week (Kurdish calendar)
        from = fmt(new Date(y, m, d - diff));
        to   = fmt(new Date(y, m, d - diff + 6));
    } else if (range === 'month') {
        from = fmt(new Date(y, m, 1));
        to   = fmt(new Date(y, m + 1, 0));
    } else if (range === 'year') {
        from = fmt(new Date(y, 0, 1));
        to   = fmt(new Date(y, 11, 31));
    }
    $('#filter_from').val(from);
    $('#filter_to').val(to);
    $('.quick-filter-btn').removeClass('active');
    $(this).addClass('active');
    loadCashBoxEntriesFiltered(1);
    if (typeof updateCashBoxSummary === 'function') {
        updateCashBoxSummary(from, to, getCashBoxSearchValue());
    }
    if (typeof loadDailyClosing === 'function') {
        loadDailyClosing(from, to, getCashBoxSearchValue());
    }
});

// Open daily closing panel → load data
$('#dailyClosingPanel').on('show.bs.collapse', function () {
    $('#closingChevron').addClass('rotated');
    if (typeof loadDailyClosing === 'function') {
        loadDailyClosing($('#filter_from').val(), $('#filter_to').val(), getCashBoxSearchValue());
    }
}).on('hide.bs.collapse', function () {
    $('#closingChevron').removeClass('rotated');
});
</script>
</body>
</html>
