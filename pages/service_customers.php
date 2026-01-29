<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('view_service_customers')) {
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
    <title>کڕیارانی خزمەتگوزاری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    
    <!-- AG Grid CSS -->
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">
    <link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--seafoam-green);">لیستی کڕیارانی خزمەتگوزاری</h2>
            <div>
                <!-- Add context-specific actions if needed -->
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4" id="service-customer-summary">
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-info card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-users card-icon"></i>
                        <h6 class="card-title">کۆی کڕیاران</h6>
                        <div class="fs-4 fw-bold" id="summary_total_customers">0</div>
                        <small class="text-light">کڕیارانی چالاک لە خزمەتگوزاری</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-success card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-file-invoice-dollar card-icon"></i>
                        <h6 class="card-title">کۆی داهات</h6>
                        <div class="fs-4 fw-bold" id="summary_total_revenue">0.00 $</div>
                        <small class="text-light">کۆی بەهای خزمەتگوزارییەکان</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-danger card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-hand-holding-usd card-icon"></i>
                        <h6 class="card-title">کۆی قەرز</h6>
                        <div class="fs-4 fw-bold" id="summary_total_balance">0.00 $</div>
                        <small class="text-light">بڕی پارەی ماوە لە لای کڕیار</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- AG Grid Container -->
        <div class="table-responsive">
            <div id="serviceCustomersGrid" class="ag-grid-container ag-theme-alpine" style="height: 600px;"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="../assets/js/comon/ag_grid_base.js" nonce="<?php echo $csp_nonce; ?>"></script>
    
    <script nonce="<?php echo $csp_nonce; ?>">
        const serviceCustomersColumnDefs = [
            {
                headerName: 'کردارەکان',
                pinned: 'left',
                width: 100,
                cellRenderer: function(params) {
                    return `<a href="service_customer_profile.php?id=${params.data.id}" class="btn btn-primary btn-sm"><i class="fas fa-user-circle"></i> پرۆفایل</a>`;
                }
            },
            { field: 'name', headerName: 'ناوی کڕیار', sortable: true, resizable: true },
            { field: 'mobile1', headerName: 'مۆبایل ١', sortable: true, resizable: true },
            { field: 'receipts_count', headerName: 'ژ.پسوڵە', sortable: true, valueFormatter: p => p.value || 0 },
            { 
                field: 'total_usd', 
                headerName: 'کۆی گشتی', 
                sortable: true, 
                valueFormatter: p => '$ ' + (p.value || 0).toLocaleString() 
            },
            { 
                field: 'total_paid_usd', 
                headerName: 'بڕی دراو', 
                sortable: true, 
                valueFormatter: p => '$ ' + (p.value || 0).toLocaleString() 
            },
            { 
                field: 'balance', 
                headerName: 'بڕی ماوە', 
                sortable: true, 
                cellStyle: p => ({ color: p.value > 0.01 ? '#dc3545' : '#198754', fontWeight: 'bold' }),
                valueFormatter: p => '$ ' + (p.value || 0).toLocaleString() 
            }
        ];

        let gridApi;

        document.addEventListener('DOMContentLoaded', function() {
            const gridDiv = document.querySelector('#serviceCustomersGrid');
            const gridOptions = {
                columnDefs: serviceCustomersColumnDefs,
                rowData: [],
                defaultColDef: { flex: 1, minWidth: 100 },
                pagination: true,
                paginationPageSize: 20
            };
            gridApi = agGrid.createGrid(gridDiv, gridOptions);
            loadData();
        });

        async function loadData() {
            try {
                const res = await fetch('../process/service_customers/select_service_customers.php');
                const result = await res.json();
                if (result.success) {
                    gridApi.setGridOption('rowData', result.data);
                    
                    // Update Summary
                    let totalCust = result.data.length;
                    let totalRev = result.data.reduce((acc, curr) => acc + curr.total_usd, 0);
                    let totalBal = result.data.reduce((acc, curr) => acc + curr.balance, 0);

                    $('#summary_total_customers').text(totalCust);
                    $('#summary_total_revenue').text(totalRev.toLocaleString() + ' $');
                    $('#summary_total_balance').text(totalBal.toLocaleString() + ' $');
                }
            } catch (error) {
                console.error('Error loading service customers:', error);
            }
        }
    </script>
</body>
</html>
