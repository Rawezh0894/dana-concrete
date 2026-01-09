<?php
/**
 * Raw Material Sales Page
 * فرۆشتنی مەوادی خام (چەو، لم، چیمەنتۆ، دەرمان، گاز)
 */
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!hasPermission('view_raw_material_sales')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get customers for dropdown
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get companies for dropdown
$companies = $pdo->query("SELECT id, name FROM company ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get bins/silos with available quantity and average price from purchases
$bins = $pdo->query("
    SELECT 
        bs.id, bs.name, bs.type, bs.material_type, bs.amount as available_quantity,
        CASE 
            WHEN bs.material_type IN ('چیمەنتۆ', 'دەرمان') THEN 'دۆلار'
            ELSE 'دینار'
        END as currency_type,
        -- Get average price from purchases table (only from year 2026 onwards)
        COALESCE(
            (SELECT 
                CASE 
                    WHEN bs.material_type IN ('چیمەنتۆ', 'دەرمان') THEN
                        SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) / NULLIF(SUM(p.kg), 0)
                    ELSE
                        (SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) / NULLIF(SUM(p.kg), 0)) * (SELECT COALESCE(value, 150000) FROM settings WHERE name = 'exchange_rate' LIMIT 1) / 100
                END
            FROM purchases p
            JOIN materials m ON p.material_id = m.id
            WHERE m.name = bs.material_type 
            AND p.kg > 0
            AND YEAR(p.date) >= 2026), 
            -- Fallback: if no purchases in 2026, get all purchases
            (SELECT 
                CASE 
                    WHEN bs.material_type IN ('چیمەنتۆ', 'دەرمان') THEN
                        SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) / NULLIF(SUM(p.kg), 0)
                    ELSE
                        (SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) END) / NULLIF(SUM(p.kg), 0)) * (SELECT COALESCE(value, 150000) FROM settings WHERE name = 'exchange_rate' LIMIT 1) / 100
                END
            FROM purchases p
            JOIN materials m ON p.material_id = m.id
            WHERE m.name = bs.material_type AND p.kg > 0),
            0
        ) as average_price
    FROM bins_silos bs
    ORDER BY bs.type, bs.name
")->fetchAll(PDO::FETCH_ASSOC);

// Get exchange rate from settings
$exchangeRateStmt = $pdo->query("SELECT value FROM settings WHERE name = 'exchange_rate' LIMIT 1");
$exchangeRate = $exchangeRateStmt->fetchColumn() ?: 150000;
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرۆشتنی مەوادی خام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css" rel="stylesheet">
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    
    <style>
        .currency-badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
            border-radius: 0.25rem;
        }
        .currency-usd {
            background-color: #28a745;
            color: white;
        }
        .currency-iqd {
            background-color: #17a2b8;
            color: white;
        }
        .buyer-type-badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
        }
        .profit-positive {
            color: #28a745;
            font-weight: bold;
        }
        .profit-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .bin-info {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .buyer-fields {
            transition: all 0.3s ease;
        }
        .hidden {
            display: none !important;
        }
        .summary-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .table thead th {
            background-color: var(--kelly-green) !important;
            color: var(--seafoam-green) !important;
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-box-seam me-2"></i>فرۆشتنی مەوادی خام</h4>
            <small class="text-muted">فرۆشتنی چەو، لم، چیمەنتۆ، دەرمان، گاز بە کیلۆگرام</small>
        </div>
        <?php if (hasPermission('add_raw_material_sales')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
            <i class="bi bi-plus-lg me-1"></i> زیادکردنی فرۆشتن
        </button>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4" id="summaryCards">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card card-gradient-success">
                <div class="card-body text-center text-white">
                    <i class="bi bi-receipt fs-3 mb-2"></i>
                    <h6>کۆی فرۆشتنەکان</h6>
                    <h4 id="totalSales">0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card card-gradient-info">
                <div class="card-body text-center text-white">
                    <i class="bi bi-box fs-3 mb-2"></i>
                    <h6>کۆی بڕی فرۆشراو</h6>
                    <h4 id="totalQuantity">0 کگم</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card card-gradient-primary">
                <div class="card-body text-center text-white">
                    <i class="bi bi-currency-dollar fs-3 mb-2"></i>
                    <h6>داهات (دۆلار)</h6>
                    <h4 id="totalRevenueUSD">$0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card card-gradient-warning">
                <div class="card-body text-center text-white">
                    <i class="bi bi-cash-stack fs-3 mb-2"></i>
                    <h6>داهات (دینار)</h6>
                    <h4 id="totalRevenueIQD">0 د.ع</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card card-gradient-danger">
                <div class="card-body text-center text-white">
                    <i class="bi bi-exclamation-triangle fs-3 mb-2"></i>
                    <h6>قەرزی ماوە</h6>
                    <h4 id="totalRemaining">$0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card summary-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                <div class="card-body text-center text-white">
                    <i class="bi bi-graph-up-arrow fs-3 mb-2"></i>
                    <h6>کۆی قازانج</h6>
                    <h4 id="totalProfit">$0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">لە بەروار</label>
                    <input type="date" class="form-control" id="filterFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label">بۆ بەروار</label>
                    <input type="date" class="form-control" id="filterTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label">جۆری کڕیار</label>
                    <select class="form-select" id="filterBuyerType">
                        <option value="">هەموو</option>
                        <option value="کڕیار">کڕیار</option>
                        <option value="کۆمپانیا">کۆمپانیا</option>
                        <option value="دەرەوە">دەرەوە</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">جۆری مەواد</label>
                    <select class="form-select" id="filterMaterial">
                        <option value="">هەموو</option>
                        <option value="چەو">چەو</option>
                        <option value="لمی کەسارە">لمی کەسارە</option>
                        <option value="لمی ڕەش">لمی ڕەش</option>
                        <option value="چیمەنتۆ">چیمەنتۆ</option>
                        <option value="دەرمان">دەرمان</option>
                        <option value="گاز">گاز</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">جۆری پارەدان</label>
                    <select class="form-select" id="filterPayment">
                        <option value="">هەموو</option>
                        <option value="نەقد">نەقد</option>
                        <option value="قەرز">قەرز</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" id="clearFilters">
                        <i class="bi bi-x-circle me-1"></i> پاککردنەوە
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center" id="salesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ژ.پسووڵە</th>
                            <th>بەروار</th>
                            <th>کڕیار</th>
                            <th>بین/سایلۆ</th>
                            <th>مەواد</th>
                            <th>بڕ (کگم)</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی نرخ</th>
                            <th>دراو</th>
                            <th>ماوە</th>
                            <th>قازانج</th>
                            <th>کردارەکان</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <tr>
                            <td colspan="13" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">چاوەڕوانبە...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addSaleForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>زیادکردنی فرۆشتنی مەوادی خام</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Invoice & Date -->
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی پسووڵە <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="invoice_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بەروار <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Buyer Type -->
                        <div class="col-md-12">
                            <label class="form-label">جۆری کڕیار <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="buyer_type" id="buyerCustomer" value="کڕیار">
                                <label class="btn btn-outline-primary" for="buyerCustomer"><i class="bi bi-person me-1"></i>کڕیار</label>
                                
                                <input type="radio" class="btn-check" name="buyer_type" id="buyerCompany" value="کۆمپانیا">
                                <label class="btn btn-outline-success" for="buyerCompany"><i class="bi bi-building me-1"></i>کۆمپانیا</label>
                                
                                <input type="radio" class="btn-check" name="buyer_type" id="buyerExternal" value="دەرەوە" checked>
                                <label class="btn btn-outline-secondary" for="buyerExternal"><i class="bi bi-person-badge me-1"></i>دەرەوە</label>
                            </div>
                        </div>

                        <!-- Customer Fields -->
                        <div class="col-md-12 buyer-fields hidden" id="customerFields">
                            <label class="form-label">کڕیار</label>
                            <select class="form-select select2-customer" name="customer_id">
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Company Fields -->
                        <div class="col-md-12 buyer-fields hidden" id="companyFields">
                            <label class="form-label">کۆمپانیا</label>
                            <select class="form-select select2-company" name="company_id">
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- External Buyer Fields -->
                        <div class="col-md-6 buyer-fields" id="externalNameField">
                            <label class="form-label">ناوی کڕیار</label>
                            <input type="text" class="form-control" name="external_buyer_name" placeholder="ناوی کڕیاری دەرەوە">
                        </div>
                        <div class="col-md-6 buyer-fields" id="externalPhoneField">
                            <label class="form-label">ژمارەی مۆبایل</label>
                            <input type="text" class="form-control" name="external_buyer_phone" placeholder="07xxxxxxxxx">
                        </div>

                        <!-- Bin Selection -->
                        <div class="col-md-12">
                            <label class="form-label">بین/سایلۆ <span class="text-danger">*</span></label>
                            <select class="form-select" name="bin_id" id="binSelect" required>
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($bins as $b): ?>
                                <option value="<?= $b['id'] ?>" 
                                        data-material="<?= htmlspecialchars($b['material_type']) ?>"
                                        data-available="<?= $b['available_quantity'] ?>"
                                        data-price="<?= $b['average_price'] ?>"
                                        data-currency="<?= $b['currency_type'] ?>">
                                    <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['material_type']) ?>) - 
                                    <?= number_format($b['available_quantity'], 2) ?> کگم
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="bin-info mt-1" id="binInfo"></div>
                        </div>

                        <!-- Quantity & Price -->
                        <div class="col-md-4">
                            <label class="form-label">بڕ (کیلۆگرام) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity_kg" step="0.0001" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="unit_price" step="0.000001" min="0" required>
                                <span class="input-group-text" id="currencyLabel">د.ع</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="total_price" step="0.0001" readonly>
                                <span class="input-group-text" id="totalCurrencyLabel">د.ع</span>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="col-md-4">
                            <label class="form-label">جۆری پارەدان</label>
                            <select class="form-select" name="payment_type">
                                <option value="نەقد">نەقد</option>
                                <option value="قەرز">قەرز</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">بڕی دراو</label>
                            <input type="number" class="form-control" name="paid_amount" step="0.0001" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نرخی ١٠٠ دۆلار</label>
                            <input type="number" class="form-control" name="exchange_rate" value="<?= $exchangeRate ?>">
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12">
                            <label class="form-label">تێبینی</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> زیادکردن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editSaleForm">
                <input type="hidden" name="id" id="editSaleId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>نوێکردنەوەی فرۆشتن</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Invoice & Date -->
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی پسووڵە <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="invoice_number" id="editInvoiceNumber" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بەروار <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" id="editSaleDate" required>
                        </div>

                        <!-- Buyer Type -->
                        <div class="col-md-12">
                            <label class="form-label">جۆری کڕیار <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="buyer_type" id="editBuyerCustomer" value="کڕیار">
                                <label class="btn btn-outline-primary" for="editBuyerCustomer"><i class="bi bi-person me-1"></i>کڕیار</label>
                                
                                <input type="radio" class="btn-check" name="buyer_type" id="editBuyerCompany" value="کۆمپانیا">
                                <label class="btn btn-outline-success" for="editBuyerCompany"><i class="bi bi-building me-1"></i>کۆمپانیا</label>
                                
                                <input type="radio" class="btn-check" name="buyer_type" id="editBuyerExternal" value="دەرەوە">
                                <label class="btn btn-outline-secondary" for="editBuyerExternal"><i class="bi bi-person-badge me-1"></i>دەرەوە</label>
                            </div>
                        </div>

                        <!-- Customer Fields -->
                        <div class="col-md-12 edit-buyer-fields hidden" id="editCustomerFields">
                            <label class="form-label">کڕیار</label>
                            <select class="form-select" name="customer_id" id="editCustomerId">
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Company Fields -->
                        <div class="col-md-12 edit-buyer-fields hidden" id="editCompanyFields">
                            <label class="form-label">کۆمپانیا</label>
                            <select class="form-select" name="company_id" id="editCompanyId">
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- External Buyer Fields -->
                        <div class="col-md-6 edit-buyer-fields hidden" id="editExternalNameField">
                            <label class="form-label">ناوی کڕیار</label>
                            <input type="text" class="form-control" name="external_buyer_name" id="editExternalBuyerName">
                        </div>
                        <div class="col-md-6 edit-buyer-fields hidden" id="editExternalPhoneField">
                            <label class="form-label">ژمارەی مۆبایل</label>
                            <input type="text" class="form-control" name="external_buyer_phone" id="editExternalBuyerPhone">
                        </div>

                        <!-- Bin Selection -->
                        <div class="col-md-12">
                            <label class="form-label">بین/سایلۆ <span class="text-danger">*</span></label>
                            <select class="form-select" name="bin_id" id="editBinId" required>
                                <option value="">هەڵبژێرە...</option>
                                <?php foreach ($bins as $b): ?>
                                <option value="<?= $b['id'] ?>" 
                                        data-material="<?= htmlspecialchars($b['material_type']) ?>"
                                        data-available="<?= $b['available_quantity'] ?>"
                                        data-price="<?= $b['average_price'] ?>"
                                        data-currency="<?= $b['currency_type'] ?>">
                                    <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['material_type']) ?>) - 
                                    <?= number_format($b['available_quantity'], 2) ?> کگم
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="bin-info mt-1" id="editBinInfo"></div>
                        </div>

                        <!-- Quantity & Price -->
                        <div class="col-md-4">
                            <label class="form-label">بڕ (کیلۆگرام) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity_kg" id="editQuantityKg" step="0.0001" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="unit_price" id="editUnitPrice" step="0.000001" min="0" required>
                                <span class="input-group-text" id="editCurrencyLabel">د.ع</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="total_price" id="editTotalPrice" step="0.0001" readonly>
                                <span class="input-group-text" id="editTotalCurrencyLabel">د.ع</span>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="col-md-4">
                            <label class="form-label">جۆری پارەدان</label>
                            <select class="form-select" name="payment_type" id="editPaymentType">
                                <option value="نەقد">نەقد</option>
                                <option value="قەرز">قەرز</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">بڕی دراو</label>
                            <input type="number" class="form-control" name="paid_amount" id="editPaidAmount" step="0.0001" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نرخی ١٠٠ دۆلار</label>
                            <input type="number" class="form-control" name="exchange_rate" id="editExchangeRate">
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12">
                            <label class="form-label">تێبینی</label>
                            <textarea class="form-control" name="notes" id="editNotes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> نوێکردنەوە
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="../assets/js/swalAlert.js"></script>

<script>
    // Pass permissions to JavaScript
    window.userPermissions = {
        canAdd: <?= hasPermission('add_raw_material_sales') ? 'true' : 'false' ?>,
        canEdit: <?= hasPermission('update_raw_material_sales') ? 'true' : 'false' ?>,
        canDelete: <?= hasPermission('delete_raw_material_sales') ? 'true' : 'false' ?>
    };
</script>
<script src="../assets/js/raw_material_sales/main.js"></script>
</body>
</html>
