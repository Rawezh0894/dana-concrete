<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی بەکارهێنانی سەیارە</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Rabar';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }

        :root {
            --primary-accent: #1e293b;
            --secondary-accent: #334155;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --header-gradient: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        body {
            background-color: #f8fafc;
            font-family: 'Rabar', 'Noto Sans Arabic', sans-serif;
            color: #1e293b;
        }

        .main-content {
            margin-right: 260px;
            padding: 2.5rem;
            transition: all 0.3s ease;
        }

        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: var(--header-gradient);
            padding: 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .report-card {
            background: var(--glass-bg);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
            font-weight: 700;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            padding: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .btn-premium {
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4;
                margin: 1.5cm;
            }
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: white !important;
            }
            .sidebar, .navbar, .filter-section, .btn-print, .btn-premium {
                display: none !important;
                visibility: hidden !important;
            }
            .main-content, .container-custom, .report-card, .card-body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
                box-shadow: none !important;
                border: none !important;
                visibility: visible !important;
                float: none !important;
                position: static !important;
            }
            .main-content {
                margin-right: 0 !important;
            }
            .page-header {
                display: block !important;
                background: white !important;
                color: black !important;
                border-bottom: 2px solid #000 !important;
                margin-bottom: 20px !important;
            }
            table {
                width: 100% !important;
                border: 1px solid #000 !important;
            }
            th, td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
                color: black !important;
            }
        }

        @media (max-width: 991.98px) {
            .main-content { margin-right: 0; }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-custom">
            <!-- Header -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">ڕاپۆرتی بەکارهێنانی سەیارە</h2>
                    <p class="mb-0 opacity-75">بەدواداچوونی ورد بۆ پارچە یەدەگە بەکارهاتووەکان بەپێی سەیارە</p>
                </div>
                <button class="btn btn-light btn-premium btn-print" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> پرینتکردنی ڕاپۆرت
                </button>
            </div>

            <!-- Filters Section -->
            <div class="report-card p-4 filter-section">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">سەیارە</label>
                        <select name="vehicle_id" id="v_select" class="form-select select2">
                            <option value="">هەموو سەیارەکان</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">پۆلێنی پارچە</label>
                        <select name="category" id="c_select" class="form-select">
                            <option value="">هەموو پۆلێنەکان</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">لە بەرواری</label>
                        <input type="date" name="from_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">بۆ بەرواری</label>
                        <input type="date" name="to_date" class="form-control">
                    </div>
                    <div class="col-12 mt-4 text-end">
                        <button type="button" class="btn btn-secondary btn-premium me-2" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> پاککردنەوە
                        </button>
                        <button type="submit" class="btn btn-primary btn-premium">
                            <i class="fas fa-filter"></i> فلتەرکردن
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Card -->
            <div class="row mb-4">
                <div class="col-md-4 ms-auto">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small fw-bold mb-1">کۆی گشتی تێچوو</p>
                            <h3 class="fw-bold mb-0 text-primary" id="totalValue">$0.00</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-dollar-sign text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="report-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-2 text-primary"></i>لیستی پارچە بەکارهاتووەکان</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reportTable">
                            <thead>
                                <tr>
                                    <th>بەروار</th>
                                    <th>جۆری تێچوو</th>
                                    <th>ناوی پارچە / مەبەست</th>
                                    <th>پۆلێن / جۆر</th>
                                    <th>سەیارە</th>
                                    <th>بڕ</th>
                                    <th>نرخی تاک</th>
                                    <th>کۆی تێچوو</th>
                                </tr>
                            </thead>
                            <tbody id="reportData">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">بۆ بینینی داتاکان، فلتەر بەکاربهێنە یان گەڕان بکە...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({ width: '100%', dir: 'rtl' });
            loadInitialData();
            loadVehicles();
            loadCategories();

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                loadReport();
            });
        });

        async function loadVehicles() {
            const res = await fetch('../process/other_expenses/select_cars.php');
            const data = await res.json();
            const cars = data.data || data;
            let html = '<option value="">هەموو سەیارەکان</option>';
            cars.forEach(car => {
                html += `<option value="${car.id}">${car.name}</option>`;
            });
            $('#v_select').html(html);
        }

        async function loadCategories() {
            const res = await fetch('../process/inventory/get_categories.php');
            const data = await res.json();
            if (data.success) {
                let html = '<option value="">هەموو پۆلێنەکان</option>';
                data.data.forEach(cat => {
                    html += `<option value="${cat.name_ku}">${cat.name_ku}</option>`;
                });
                $('#c_select').html(html);
            }
        }

        async function loadReport() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData).toString();
            
            const res = await fetch(`../process/inventory/get_usage_report.php?${params}`);
            const result = await res.json();
            
            if (result.success) {
                renderTable(result.data);
                $('#totalValue').text('$' + Number(result.total_cost).toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        }

        function renderTable(data) {
            let html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="8" class="text-center py-4">هیچ زانیارییەک نەدۆزرایەوە</td></tr>';
            } else {
                data.forEach(row => {
                    const typeBadge = row.type === 'گۆڕینی پارچە' 
                        ? '<span class="badge bg-primary bg-opacity-10 text-primary">گۆڕینی پارچە</span>'
                        : '<span class="badge bg-warning bg-opacity-10 text-warning">خەرجی گشتی</span>';
                        
                    html += `
                        <tr>
                            <td>${row.date}</td>
                            <td>${typeBadge}</td>
                            <td class="fw-bold">${row.name}</td>
                            <td><span class="badge bg-light text-dark border">${row.category}</span></td>
                            <td>${row.vehicle}</td>
                            <td>${row.qty}</td>
                            <td>$${Number(row.unit_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="fw-bold text-primary">$${Number(row.total_cost).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });
            }
            $('#reportData').html(html);
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            $('.select2').val('').trigger('change');
            loadInitialData();
        }

        function loadInitialData() {
            loadReport();
        }
    </script>
</body>
</html>
