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
        
        /* Professional Filter Section Styles */
        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 123, 255, 0.1);
        }
        
        .filter-header {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--seafoam-green);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            background: #ffffff;
            border: 2px solid #e9ecef;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
            min-width: 100px;
            text-align: center;
        }
        
        .filter-tab:hover {
            border-color: var(--seafoam-green);
            color: var(--seafoam-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, var(--seafoam-green), #00cec9);
            border-color: var(--seafoam-green);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .date-range-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .date-range-header {
            text-align: center;
            margin-bottom: 1rem;
            color: #495057;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .date-input-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .date-input-wrapper {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .date-input-wrapper:focus-within {
            border-color: var(--seafoam-green);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .date-input-wrapper label {
            margin: 0 0.5rem 0 0;
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
            min-width: 30px;
        }
        
        .date-input-wrapper input {
            border: none;
            background: transparent;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #495057;
            min-width: 140px;
        }
        
        .date-input-wrapper input:focus {
            outline: none;
            background: #ffffff;
        }
        
        .filter-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-clear-filters {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .btn-clear-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .btn-change-rate {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border: none;
            color: #212529;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-change-rate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            color: #212529;
        }
        
        /* Professional Reports Section Styles */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8, #138496) !important;
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545, #c82333) !important;
        }
        
        /* Chart Section Styles */
        .chart-section {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 400px;
            position: relative;
        }
        
        .chart-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .chart-section h5 {
            color: var(--seafoam-green);
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .chart-section canvas {
            border-radius: 8px;
            max-height: 300px !important;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Chart Grid Layout */
        .charts-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }
        
        @media (max-width: 768px) {
            .filter-section {
                padding: 1.5rem;
                margin: 1rem 0;
            }
            
            .filter-tabs {
                gap: 0.25rem;
            }
            
            .filter-tab {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
                min-width: 80px;
            }
            
            .date-input-group {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .date-input-wrapper {
                width: 100%;
                max-width: 300px;
            }
            
            .filter-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-clear-filters,
            .btn-change-rate {
                width: 100%;
                max-width: 250px;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .stat-item {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .stat-item i {
                font-size: 1.5rem !important;
            }
            
            .stat-item h6 {
                font-size: 0.8rem;
            }
            
            .stat-item .h4 {
                font-size: 1.25rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-section {
                height: 350px;
            }
            
            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Professional Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <i class="fa fa-filter me-2"></i>فلتەری ڕاپۆرت
        </div>
        
        <!-- Quick Filter Tabs -->
        <div class="filter-tabs" id="report-date-filter">
            <button type="button" class="filter-tab active" data-filter="year">
                <i class="fa fa-calendar-year me-1"></i>ئەم ساڵ
            </button>
            <button type="button" class="filter-tab" data-filter="month">
                <i class="fa fa-calendar-alt me-1"></i>ئەم مانگ
            </button>
            <button type="button" class="filter-tab" data-filter="week">
                <i class="fa fa-calendar-week me-1"></i>ئەم هەفتە
            </button>
            <button type="button" class="filter-tab" data-filter="today">
                <i class="fa fa-calendar-day me-1"></i>ئەمڕۆ
            </button>
        </div>
        
        <!-- Date Range Section -->
        <div class="date-range-section">
            <div class="date-range-header">
                <i class="fa fa-calendar-range me-2"></i>بڕی بەرواری تایبەت
            </div>
            <div class="date-input-group">
                <div class="date-input-wrapper">
                    <label>لە:</label>
                    <input type="date" id="from-date" name="from-date">
                </div>
                <div class="date-input-wrapper">
                    <label>بۆ:</label>
                    <input type="date" id="to-date" name="to-date">
                </div>
            </div>
        </div>
        
        <!-- Filter Actions -->
        <div class="filter-actions">
            <button class="btn-clear-filters" id="clear-filters-btn" type="button">
                <i class="fa fa-times me-1"></i>پاککردنەوەی فلتەرەکان
            </button>
            <button class="btn-change-rate" id="change-rate-btn" data-bs-toggle="modal" data-bs-target="#exchangeRateModal">
                <i class="fa fa-dollar-sign me-1"></i>گۆڕینی نرخی دۆلار
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
    

    
    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6 col-md-12">
            <div class="card p-3 shadow-sm chart-section">
                <h5 class="mb-3">ستۆک بە جۆری ماتریاڵ</h5>
                <div class="chart-container">
                    <canvas id="chart-stock-material"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12">
            <div class="card p-3 shadow-sm chart-section">
                <h5 class="mb-3">گۆڕانکاری داهات و خەرجی بە مانگ</h5>
                <div class="chart-container">
                    <canvas id="chart-income-by-month-year"></canvas>
                </div>
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
