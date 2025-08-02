<?php
session_start();
require_once '../config/db_conected.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی گشتی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/reports.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Rabar', sans-serif; background: #f8faf5; }
        .card-value { font-size: 2.1rem; font-weight: bold; color: #003b73; }
        .card-currency { font-size: 1.1rem; color: #888; }
        .dashboard-title { font-size: 2rem; font-weight: bold; color: var(--seafoam-green); margin: 2rem 0 1.5rem 0; text-align: center; }
        @media (max-width: 768px) {
            .dashboard-title { font-size: 1.3rem; }
        }
        #change-rate-btn {
            box-shadow: 0 2px 8px #ff980020;
            transition: background 0.15s, color 0.15s;
        }
        #change-rate-btn:hover {
            background: #ffc107;
            color: #003b73;
        }
        @media (max-width: 768px) {
            #change-rate-btn {
                width: 100%;
                margin-top: 1rem;
                margin-left: 0;
            }
            .dashboard-title {
                width: 100%;
                text-align: center;
                margin-bottom: 0.7rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
            <div class="dashboard-title mb-0">ڕاپۆرتی دارایی</div>
            <button class="btn btn-warning btn-sm ms-2 mt-2 mt-lg-0" id="change-rate-btn" data-bs-toggle="modal" data-bs-target="#exchangeRateModal" style="font-weight:bold;">
                <i class="fa fa-dollar-sign me-1"></i> گۆڕینی نرخ
            </button>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-center align-items-center">
            <div class="btn-group" id="report-date-filter" role="group" aria-label="Report Date Filter">
                <button type="button" class="btn btn-outline-primary active" data-filter="year">ئەم ساڵ</button>
                <button type="button" class="btn btn-outline-primary" data-filter="month">ئەم مانگ</button>
                <button type="button" class="btn btn-outline-primary" data-filter="week">ئەم هەفتە</button>
                <button type="button" class="btn btn-outline-primary" data-filter="today">ئەمڕۆ</button>
            </div>
        </div>
    </div>
    <!-- Date Range Filter -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-center align-items-center">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text">لە</span>
                <input type="date" class="form-control" id="from-date" name="from-date">
                <span class="input-group-text">بۆ</span>
                <input type="date" class="form-control" id="to-date" name="to-date">
            </div>
            <button class="btn btn-outline-danger ms-2" id="clear-filters-btn" type="button">
                <i class="fa fa-times"></i> پاککردنەوە
            </button>
        </div>
    </div>
    <!-- Exchange Rate Modal -->
    <div class="modal fade" id="exchangeRateModal" tabindex="-1" aria-labelledby="exchangeRateModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form id="exchange-rate-form">
            <div class="modal-header">
              <h5 class="modal-title" id="exchangeRateModalLabel">گۆڕینی نرخی ١٠٠ دۆلار بە دینار</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="usd_iqd_rate" class="form-label">نرخی ١٠٠ دۆلار بە دینار:</label>
                <input type="number" min="10000" step="1" class="form-control" id="usd_iqd_rate" name="usd_iqd_rate" required>
                <div class="form-text mt-1">ئەم نرخە بۆ هەموو هەژمارکردنەکان بەکاردێت.</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
              <button type="submit" class="btn btn-success">پاشەکەوت</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="row" id="dashboard-summary-cards" style="margin-bottom:2rem;">
        <!-- Cards will be rendered here by JS -->
    </div>
    <div class="row g-4 mb-4">
        <div class="col-lg-6 col-md-12">
            <div class="card p-3 shadow-sm">
                <h5 class="mb-3 text-center">ستۆک بە جۆری ماتریاڵ</h5>
                <canvas id="chart-stock-material" height="180"></canvas>
            </div>
        </div>
        <div class="col-lg-6 col-md-12">
            <div class="card p-3 shadow-sm">
                <h5 class="mb-3 text-center">گۆڕانکاری داهات بە مانگ و ساڵ</h5>
                <canvas id="chart-income-by-month-year" height="180"></canvas>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/reporst/get_information.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/reporst/chart.js"></script>
<script>
// Fetch and set the current rate
fetch('../process/reporst/get_information.php')
  .then(res => res.json())
  .then(result => {
    if(result.success && result.data.usd_iqd_rate) {
      document.getElementById('usd_iqd_rate').value = result.data.usd_iqd_rate;
    }
  });
// Handle form submit
const form = document.getElementById('exchange-rate-form');
form.addEventListener('submit', function(e) {
  e.preventDefault();
  const rate = document.getElementById('usd_iqd_rate').value;
  fetch('../process/reporst/set_rate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'usd_iqd_rate=' + encodeURIComponent(rate)
  })
  .then(res => res.json())
  .then(result => {
    if(result.success) {
      Swal.fire('سەرکەوتوو!', 'نرخی نوێ پاشەکەوت کرا.', 'success').then(() => location.reload());
    } else {
      Swal.fire('هەڵە!', result.error || 'هەڵەیەک ڕویدا.', 'error');
    }
  });
});
</script>
</body>
</html>
