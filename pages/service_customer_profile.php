<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ناسنامەی کڕیار نەدۆزرایەوە!");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("کڕیارەکە بوونی نییە!");
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پڕۆفایلی کڕیار - خزمەتگوزاری</title>
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
    
    <style>
        body { font-family: 'Rabar', sans-serif !important; }
        .ag-theme-alpine, .ag-header-cell-text, .ag-cell, .ag-floating-filter-input {
            font-family: 'Rabar', sans-serif !important;
        }
        .nav-tabs .nav-link { color: var(--seafoam-green) !important; font-weight: bold; }
        .nav-tabs .nav-link.active { background: var(--seafoam-green) !important; color: #fff !important; }
        .balance-alert { border-radius: 10px; font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-5">
        <div class="mb-4">
            <a href="service_customers.php" class="btn btn-secondary btn-sm mb-3">
                <i class="fas fa-arrow-right"></i> گەڕانەوە بۆ لیستی کڕیاران
            </a>
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="fw-bold" style="color: var(--seafoam-green);">پڕۆفایلی: <?= htmlspecialchars($customer['name']) ?></h2>
                <button class="btn btn-success fw-bold" id="openAddPaymentBtn">
                    <i class="fas fa-hand-holding-usd me-2"></i> وەرگرتنەوەی قەرز
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-info card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-file-invoice-dollar card-icon"></i>
                        <h6 class="card-title">کۆی داهات</h6>
                        <div class="fs-4 fw-bold" id="profile_total_usd">0.00 $</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-success card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-check-circle card-icon"></i>
                        <h6 class="card-title">کۆی دراو (گشتی)</h6>
                        <div class="fs-4 fw-bold" id="profile_total_paid">0.00 $</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow card-gradient-danger card-animate-hover">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle card-icon"></i>
                        <h6 class="card-title">قەرزی ماوە</h6>
                        <div class="fs-4 fw-bold" id="profile_balance">0.00 $</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for History -->
        <ul class="nav nav-tabs mb-4" id="serviceProfileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receiptsContent" type="button" role="tab">پسوڵەکان</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#paymentsContent" type="button" role="tab">مێژووی دانەوەکان</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="receiptsContent" role="tabpanel">
                <div id="serviceReceiptsGrid" class="ag-grid-container ag-theme-alpine" style="height: 500px;"></div>
            </div>
            <div class="tab-pane fade" id="paymentsContent" role="tabpanel">
                <div id="serviceDebtGrid" class="ag-grid-container ag-theme-alpine" style="height: 500px;"></div>
            </div>
        </div>
    </div>

    <!-- Add Debt Payment Modal -->
    <div class="modal fade" id="addDebtPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addDebtPaymentForm">
                    <input type="hidden" name="customer_id" value="<?= $id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">وەرگرتنەوەی قەرزی خزمەتگوزاری</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-primary d-flex justify-content-between mb-3 balance-alert">
                            <span>کۆی قەرزی ئێستا:</span>
                            <span id="modal_current_debt">0.00 $</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-bold">بەرواری وەرگرتن</label>
                            <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">بڕ بە دۆلار (USD)</label>
                                <input type="number" class="form-control calc-debt" id="add_paid_usd" name="paid_usd" step="0.01" value="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">بڕ بە دینار (IQD)</label>
                                <input type="number" class="form-control calc-debt" id="add_paid_iqd" name="paid_iqd" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-bold">نرخی سەرف (١٠٠ دۆلار)</label>
                            <input type="number" class="form-control calc-debt" id="add_exchange_rate" name="exchange_rate" value="150000">
                        </div>
                        <div class="alert alert-secondary d-flex justify-content-between mb-3 balance-alert">
                            <span>قەرز دوای دانەوە:</span>
                            <span id="modal_remaining_debt">0.00 $</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-bold">تێبینی</label>
                            <textarea class="form-control" name="note" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-success fw-bold">پاشەکەوتکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Debt Payment Modal -->
    <div class="modal fade" id="editDebtPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editDebtPaymentForm">
                    <input type="hidden" name="id" id="edit_payment_id">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">دەستکاریکردنی دانەوەی قەرز</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-bold">بەرواری وەرگرتن</label>
                            <input type="date" class="form-control" name="payment_date" id="edit_payment_date" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">بڕ بە دۆلار (USD)</label>
                                <input type="number" class="form-control" id="edit_paid_usd" name="paid_usd" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-bold">بڕ بە دینار (IQD)</label>
                                <input type="number" class="form-control" id="edit_paid_iqd" name="paid_iqd">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-bold">نرخی سەرف (١٠٠ دۆلار)</label>
                            <input type="number" class="form-control" id="edit_exchange_rate" name="exchange_rate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-bold">تێبینی</label>
                            <textarea class="form-control" id="edit_note" name="note" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                        <button type="submit" class="btn btn-primary fw-bold">نوێکردنەوە</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
    
    <script nonce="<?php echo $csp_nonce; ?>">
        const customerId = <?= $id ?>;
        let receiptsGridApi, debtGridApi;
        let currentBalance = 0;

        const receiptCols = [
            {
                headerName: 'کردارەکان',
                width: 100,
                pinned: 'left',
                cellRenderer: params => {
                    return `<button class="btn btn-sm btn-info print-receipt" data-id="${params.data.id}"><i class="fas fa-print"></i></button>`;
                }
            },
            { field: 'receipt_number', headerName: 'ژ.پسوڵە', width: 120 },
            { 
                field: 'created_at', 
                headerName: 'بەروار', 
                width: 150,
                valueFormatter: params => {
                    if (!params.value) return '-';
                    const d = new Date(params.value);
                    return isNaN(d) ? params.value : d.toLocaleString('en-GB').replace(',', '');
                }
            },
            { field: 'location', headerName: 'شوێن' },
            { field: 'receiver_name', headerName: 'وەرگر' },
            { field: 'meter_amount', headerName: 'مەتر', width: 100, valueFormatter: p => (parseFloat(p.value)||0).toFixed(2) + ' m³' },
            { field: 'total_price', headerName: 'کۆی بەها', valueFormatter: p => '$ ' + (parseFloat(p.value)||0).toLocaleString() },
            { field: 'total_paid', headerName: 'دراوە (لەکاتی کار)', valueFormatter: p => '$ ' + (parseFloat(p.value)||0).toLocaleString() }
        ];

        const debtCols = [
            {
                headerName: 'کردارەکان',
                width: 120,
                pinned: 'left',
                cellRenderer: params => {
                    return `
                        <button class="btn btn-sm btn-warning edit-debt" data-id="${params.data.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-debt" data-id="${params.data.id}"><i class="fas fa-trash"></i></button>
                    `;
                }
            },
            { field: 'payment_date', headerName: 'بەروار' },
            { field: 'paid_usd', headerName: 'دراو (USD)', valueFormatter: p => '$ ' + (parseFloat(p.value)||0).toLocaleString() },
            { field: 'paid_iqd', headerName: 'دراو (IQD)', valueFormatter: p => (parseFloat(p.value)||0).toLocaleString() + ' د.ع' },
            { field: 'exchange_rate', headerName: 'نرخی سەرف' },
            { field: 'note', headerName: 'تێبینی', flex: 1, maxWidth: 400 }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            receiptsGridApi = agGrid.createGrid(document.querySelector('#serviceReceiptsGrid'), {
                columnDefs: receiptCols,
                defaultColDef: { width: 140, maxWidth: 150, sortable: true, resizable: true },
                rowData: []
            });

            debtGridApi = agGrid.createGrid(document.querySelector('#serviceDebtGrid'), {
                columnDefs: debtCols,
                defaultColDef: { width: 140, maxWidth: 150, sortable: true, resizable: true },
                rowData: []
            });

            loadProfileData();
        });

        async function loadProfileData() {
            try {
                const res = await fetch(`../process/service_customer_profile/select_profile_data.php?customer_id=${customerId}`);
                const data = await res.json();
                if (data.success) {
                    receiptsGridApi.setGridOption('rowData', data.receipts);
                    debtGridApi.setGridOption('rowData', data.debt_payments);
                    
                    currentBalance = parseFloat(data.summary.balance);
                    $('#profile_total_usd').text(data.summary.total_revenue.toLocaleString() + ' $');
                    $('#profile_total_paid').text(data.summary.total_paid.toLocaleString() + ' $');
                    $('#profile_balance').text(currentBalance.toLocaleString() + ' $');
                }
            } catch (e) { console.error(e); }
        }

        // Live calculation and Validation for Add Modal
        $('#openAddPaymentBtn').on('click', function() {
            if (currentBalance <= 0.01) {
                Swal.fire('ئاگاداری', 'ئەم کڕیارە هیچ قەرزێکی لەسەر نییە، ناتوانیت دانەوە تۆمار بکەیت.', 'warning');
                return;
            }
            $('#modal_current_debt').text(currentBalance.toLocaleString() + ' $');
            $('#modal_remaining_debt').text(currentBalance.toLocaleString() + ' $');
            $('#addDebtPaymentModal').modal('show');
        });

        $('.calc-debt').on('input', function() {
            const usd = parseFloat($('#add_paid_usd').val()) || 0;
            const iqd = parseFloat($('#add_paid_iqd').val()) || 0;
            const rate = parseFloat($('#add_exchange_rate').val()) || 1;
            
            const totalPaid = usd + (iqd / rate);
            const remaining = currentBalance - totalPaid;
            
            $('#modal_remaining_debt').text(remaining.toLocaleString() + ' $');
            if (remaining < -0.01) {
                $('#modal_remaining_debt').addClass('text-success').removeClass('text-danger');
            } else {
                $('#modal_remaining_debt').removeClass('text-success').addClass('text-danger');
            }
        });

        $('#addDebtPaymentForm').on('submit', async function(e) {
            e.preventDefault();
            const usd = parseFloat($('#add_paid_usd').val()) || 0;
            const iqd = parseFloat($('#add_paid_iqd').val()) || 0;
            const rate = parseFloat($('#add_exchange_rate').val()) || 1;
            const totalPaid = usd + (iqd / rate);

            if (totalPaid <= 0) {
                 Swal.fire('هەڵە', 'تکایە بڕی پارە دیاری بکە', 'error');
                 return;
            }

            const formData = new FormData(this);
            try {
                const res = await fetch('../process/service_customer_profile/add_service_debt_payment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('سەرکەوتوو', result.message, 'success');
                    $('#addDebtPaymentModal').modal('hide');
                    this.reset();
                    loadProfileData();
                } else {
                    Swal.fire('هەڵە', result.message, 'error');
                }
            } catch (e) { console.error(e); }
        });

        // Edit Payment
        $(document).on('click', '.edit-debt', function() {
            const id = $(this).data('id');
            const rowData = debtGridApi.getRowNode($(this).closest('.ag-row').attr('row-id'))?.data;
            // Note: Since we use custom buttons, let's find the data from the cachedData or API
            // For simplicity, let's grab from the grid row if available or re-fetch
            const allRows = [];
            debtGridApi.forEachNode(node => allRows.push(node.data));
            const payment = allRows.find(r => r.id == id);

            if (payment) {
                $('#edit_payment_id').val(payment.id);
                $('#edit_payment_date').val(payment.payment_date);
                $('#edit_paid_usd').val(payment.paid_usd);
                $('#edit_paid_iqd').val(payment.paid_iqd);
                $('#edit_exchange_rate').val(payment.exchange_rate);
                $('#edit_note').val(payment.note);
                $('#editDebtPaymentModal').modal('show');
            }
        });

        $('#editDebtPaymentForm').on('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('../process/service_customer_profile/update_service_debt_payment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('سەرکەوتوو', result.message, 'success');
                    $('#editDebtPaymentModal').modal('hide');
                    loadProfileData();
                } else {
                    Swal.fire('هەڵە', result.message, 'error');
                }
            } catch (e) { console.error(e); }
        });

        // Delete Payment
        $(document).on('click', '.delete-debt', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'دڵنیایت کەت دەتەوێت ئەم دانەوەیە بسڕیتەوە؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بسڕەوە',
                cancelButtonText: 'نەخێر'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    try {
                        const res = await fetch('../process/service_customer_profile/delete_service_debt_payment.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.success) {
                            Swal.fire('سڕایەوە', data.message, 'success');
                            loadProfileData();
                        } else {
                            Swal.fire('هەڵە', data.message, 'error');
                        }
                    } catch (e) { console.error(e); }
                }
            });
        });
        
        // Handle Print Receipt
        $(document).on('click', '.print-receipt', function() {
            const id = $(this).data('id');
            // assuming print_service_receipt.php exists or similar structure
            window.open(`print_service_receipt.php?id=${id}`, '_blank');
        });
    </script>
</body>
</html>
