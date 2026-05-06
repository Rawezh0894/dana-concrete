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
    <title>ڕاپۆرتی خەرجیەکانی سەیارەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
            width: 100%;
            padding-left: 15px;
            padding-right: 15px;
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
                size: A4 landscape;
                margin: 1.5cm;
            }
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: white !important;
                font-family: 'Rabar', 'Noto Sans Arabic', sans-serif !important;
            }
            .sidebar, .navbar, .filter-section, .btn-print, .btn-premium, .no-print {
                display: none !important;
                visibility: hidden !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
                position: static !important;
            }
            .container-custom {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .report-card {
                box-shadow: none !important;
                border: 1px solid #eee !important;
                margin-bottom: 20px !important;
            }
            .stat-card {
                box-shadow: none !important;
                border: 1px solid #eee !important;
                padding: 1rem !important;
            }
            .print-header {
                display: block !important;
                margin-bottom: 30px !important;
            }
        }

        /* Print Header Style */
        .print-header {
            display: none;
            border-bottom: 3px solid #1e293b;
            padding-bottom: 15px;
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
            <!-- Print-Only Header -->
            <div class="print-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-0">کارگەی کۆنکرێتی دانا</h2>
                        <h4 class="mb-1">ڕاپۆرتی خەرجیەکانی سەیارەکان</h4>
                        <p class="mb-0 text-muted small">کاتی چاپکردن: <?= date('Y-m-d H:i') ?></p>
                    </div>
                    <img src="../assets/images/logo.png" height="70" style="filter: grayscale(1);">
                </div>
            </div>

            <!-- Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">ڕاپۆرتی خەرجیەکانی سەیارەکان</h2>
                    <p class="mb-0 opacity-75">بەدواداچوونی ورد بۆ پارچە یەدەگە بەکارهاتووەکان بەپێی سەیارە</p>
                </div>
                <button class="btn btn-light btn-premium btn-print no-print" onclick="printReport()">
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
                        <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">بۆ بەرواری</label>
                        <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d') ?>">
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

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="stat-card d-flex align-items-center justify-content-between border-start border-4 border-primary">
                        <div>
                            <p class="text-muted small fw-bold mb-1">کۆی گشتی تێچوو (دۆلار)</p>
                            <h3 class="fw-bold mb-0 text-primary" id="totalValueUSD">$0.00</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-dollar-sign text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card d-flex align-items-center justify-content-between border-start border-4 border-success">
                        <div>
                            <p class="text-muted small fw-bold mb-1">کۆی گشتی تێچوو (دینار)</p>
                            <h3 class="fw-bold mb-0 text-success" id="totalValueIQD">0 د.ع</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-money-bill-wave text-success fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="report-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-table me-2 text-primary"></i>لیستی خەرجییە تێکەڵەکان</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="reportTable">
                            <thead>
                                <tr>
                                    <th>بەروار</th>
                                    <th>ناوی پارچە / پۆلێن</th>
                                    <th>سەیارە</th>
                                    <th>بڕ</th>
                                    <th class="text-primary">تێچوو (دۆلار)</th>
                                    <th class="text-success">تێچوو (دینار)</th>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let reportTable = null;

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
            let html = '<option value="">هەموو پۆلێنەکان</option>';
            if (data.success) {
                data.data.forEach(cat => {
                    html += `<option value="${cat.name_ku}">${cat.name_ku}</option>`;
                });
            }
            // Add Gas Usage manually
            html += '<option value="بەکارهێنانی گاز">بەکارهێنانی گاز</option>';
            $('#c_select').html(html);
        }

        async function loadReport() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData).toString();
            
            // Show loading state
            if (reportTable) {
                reportTable.destroy();
                reportTable = null;
            }
            $('#reportData').html('<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">تکایە چاوەڕوان بە...</div></td></tr>');

            try {
                const res = await fetch(`../process/inventory/get_usage_report.php?${params}`);
                const result = await res.json();
                
                if (result.success) {
                    renderTable(result.data);
                    $('#totalValueUSD').text('$' + Number(result.total_usd).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#totalValueIQD').text(Number(result.total_iqd).toLocaleString() + ' د.ع');
                    
                    // Initialize DataTable
                    initializeDataTable();
                }
            } catch (error) {
                console.error('Error loading report:', error);
                $('#reportData').html('<tr><td colspan="6" class="text-center py-4 text-danger">هەڵەیەک لە بارکردنی داتاکان ڕوویدا</td></tr>');
            }
        }

        function initializeDataTable() {
            reportTable = $('#reportTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ku.json',
                    // Manual fallbacks if URL fails
                    search: "گەڕان:",
                    lengthMenu: "پیشاندان _MENU_ تۆمار",
                    info: "پیشاندانی _START_ بۆ _END_ لە کۆی _TOTAL_ تۆمار",
                    infoEmpty: "هیچ تۆمارێک نییە",
                    infoFiltered: "(فلتەرکراوە لە کۆی _MAX_ تۆمار)",
                    paginate: {
                        first: "یەکەم",
                        previous: "پێشوو",
                        next: "دواتر",
                        last: "کۆتایی"
                    }
                },
                order: [[0, 'desc']], // Default sort by date
                pageLength: 25,
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
                drawCallback: function() {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm');
                }
            });
        }

        function renderTable(data) {
            let html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="6" class="text-center py-4">هیچ زانیارییەک نەدۆزرایەوە</td></tr>';
            } else {
                data.forEach(row => {
                    html += `
                        <tr>
                            <td>${row.date}</td>
                            <td>
                                <div class="fw-bold">${row.name}</div>
                                <div class="text-muted small">${row.category}</div>
                            </td>
                            <td>${row.vehicle}</td>
                            <td>${row.qty}</td>
                            <td class="fw-bold text-primary">${row.cost_usd > 0 ? '$' + Number(row.cost_usd).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                            <td class="fw-bold text-success">${row.cost_iqd > 0 ? Number(row.cost_iqd).toLocaleString() + ' د.ع' : '-'}</td>
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

        function printReport() {
            // "The other way" - Opening a new window for printing to avoid UI/CSS conflicts
            const printContent = `
                <!DOCTYPE html>
                <html lang="ku" dir="rtl">
                <head>
                    <meta charset="UTF-8">
                    <title>ڕاپۆرتی خەرجیەکانی سەیارەکان</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
                    <style>
                        @font-face {
                            font-family: 'Rabar';
                            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
                        }
                        body { 
                            font-family: 'Rabar', sans-serif; 
                            background: white; 
                            padding: 2.5rem;
                            color: #1a1a1a;
                        }
                        .print-header {
                            border-bottom: 3px solid #1e293b;
                            padding-bottom: 1.5rem;
                            margin-bottom: 2rem;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }
                        .summary-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 1.5rem;
                            margin-bottom: 2rem;
                        }
                        .summary-box {
                            padding: 1.5rem;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            text-align: center;
                        }
                        .summary-label {
                            color: #64748b;
                            font-size: 0.9rem;
                            margin-bottom: 0.5rem;
                            font-weight: bold;
                        }
                        .summary-value {
                            font-size: 1.5rem;
                            font-weight: 800;
                            color: #1e293b;
                        }
                        .table-premium {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 1rem;
                        }
                        .table-premium th {
                            background: #f8fafc !important;
                            color: #1e293b;
                            padding: 12px;
                            border: 1px solid #e2e8f0;
                            font-size: 0.85rem;
                            text-align: center;
                        }
                        .table-premium td {
                            padding: 10px;
                            border: 1px solid #e2e8f0;
                            text-align: center;
                            vertical-align: middle;
                        }
                        .badge-type {
                            border: 1px solid #1e293b;
                            padding: 2px 8px;
                            border-radius: 4px;
                            font-size: 0.8rem;
                            font-weight: bold;
                        }
                        @media print {
                            body { padding: 0; }
                            @page { size: A4 landscape; margin: 1cm; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <div>
                            <h1 style="margin:0; font-weight:800;">کارگەی کۆنکرێتی دانا</h1>
                            <h3 style="margin:5px 0; color:#334155;">ڕاپۆرتی خەرجیەکانی سەیارەکان</h3>
                            <p style="margin:0; color:#64748b;">ڕێککەوت: ${new Date().toLocaleDateString('ku-IQ')}</p>
                        </div>
                        <img src="../assets/images/logo.png" height="80" style="filter: grayscale(1);">
                    </div>

                    <div class="summary-grid">
                        <div class="summary-box">
                            <div class="summary-label">کۆی گشتی تێچوو (دۆلار)</div>
                            <div class="summary-value" style="color: #2563eb;">${$('#totalValueUSD').text()}</div>
                        </div>
                        <div class="summary-box">
                            <div class="summary-label">کۆی گشتی تێچوو (دینار)</div>
                            <div class="summary-value" style="color: #059669;">${$('#totalValueIQD').text()}</div>
                        </div>
                    </div>

                    <h4 style="margin-bottom:1rem; border-right:4px solid #1e293b; padding-right:10px;">لیستی وردەکارییەکان</h4>
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>بەروار</th>
                                <th>ناوی پارچە / پۆلێن</th>
                                <th>سەیارە</th>
                                <th>بڕ</th>
                                <th>دۆلار</th>
                                <th>دینار</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${$('#reportData').html()}
                        </tbody>
                    </table>

                    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
                        <div style="text-align:center; flex:1;">
                            <div style="border-top:1px solid #000; width:150px; margin:0 auto; padding-top:5px;">واژۆی ژمێریار</div>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <div style="border-top:1px solid #000; width:150px; margin:0 auto; padding-top:5px;">واژۆی شۆفێر</div>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <div style="border-top:1px solid #000; width:150px; margin:0 auto; padding-top:5px;">مۆر و واژۆی کارگە</div>
                        </div>
                    </div>

                    <script>
                        window.onload = function() {
                            window.print();
                            // window.onafterprint = function() { window.close(); };
                        };
                    <\/script>
                </body>
                </html>
            `;

            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.write(printContent);
            printWindow.document.close();
        }
    </script>
</body>
</html>
