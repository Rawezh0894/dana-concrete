<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

$user_id = $_SESSION['user_id'];

// ڕاستەوخۆ دیفاولت قاسە دەیکەینەوە بۆ بەکارهێنەر گەر یەکەم جاری بێت
$pdo->exec("INSERT IGNORE INTO wallets (user_id, currency_code) VALUES ($user_id, 'USD'), ($user_id, 'IQD')");

// هێنانی باڵانسەکان
$stmt = $pdo->prepare("SELECT currency_code, balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$usd_balance = floatval($wallets['USD'] ?? 0);
$iqd_balance = floatval($wallets['IQD'] ?? 0);

// هێنانی جۆرەکانی مامەڵە (Categories)
$stmt = $pdo->query("SELECT id, name, type FROM transaction_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قاسەی بەکارهێنەر - User Wallets</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
    <style>
        body { font-family: 'Rabar', sans-serif; background-color: #f4f6f9; }
        .wallet-card { border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; border: none;}
        .wallet-card:hover { transform: translateY(-5px); }
        .currency-symbol { font-size: 1.5rem; font-weight: bold; opacity: 0.8; }
        .balance { font-size: 2.5rem; font-weight: bold; }
        .quick-action { background-color: var(--seafoam-green); color: white; font-weight: bold; border-radius: 8px; padding: 10px 20px; transition: all 0.3s ease; border: none; }
        .quick-action:hover { background-color: var(--kelly-green); color: white; transform: scale(1.05); }
    </style>
</head>
<body dir="rtl">
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">بەڕێوەبردنی قاسە (Cashbox)</h2>
            <div class="quick-actions d-flex gap-2">
                <button class="btn quick-action" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setFormAction('inflow')"><i class="fa fa-plus"></i> زیادکردنی پارە</button>
                <button class="btn quick-action" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setFormAction('outflow')"><i class="fa fa-minus"></i> ڕاکێشانی پارە</button>
                <button class="btn quick-action bg-warning text-dark" data-bs-toggle="modal" data-bs-target="#exchangeModal"><i class="bi bi-arrow-left-right"></i> ئاڵوگۆڕی دراو</button>
            </div>
        </div>
        
        <!-- Balance Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card wallet-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">باڵانسی دۆلار (USD)</h5>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <span class="currency-symbol">$</span>
                            <span class="balance" id="usdBalance" data-balance="<?= $usd_balance ?>"><?= number_format($usd_balance, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card wallet-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">باڵانسی دینار (IQD)</h5>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <span class="currency-symbol">د.ع</span>
                            <span class="balance" id="iqdBalance" data-balance="<?= $iqd_balance ?>"><?= number_format($iqd_balance, 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold" style="color: var(--seafoam-green);">دواین چالاکییەکان</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>بەروار</th>
                                <th>جۆر</th>
                                <th>هۆکار</th>
                                <th>بڕی USD</th>
                                <th>بڕی IQD</th>
                                <th>تێبینی</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("
                                SELECT t.id, t.created_at, t.type as trans_type, t.category_id, tc.name as category_name,
                                (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'USD' LIMIT 1) as usd_amount,
                                (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'IQD' LIMIT 1) as iqd_amount,
                                (SELECT description FROM ledger_entries WHERE transaction_id = t.id LIMIT 1) as description
                                FROM transactions t
                                LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                                WHERE t.created_by = ?
                                ORDER BY t.created_at DESC LIMIT 20
                            ");
                            $stmt->execute([$user_id]);
                            $transactions = $stmt->fetchAll();

                            foreach ($transactions as $tx): 
                                $is_exchange = $tx['trans_type'] === 'EXCHANGE';
                            ?>
                                <tr>
                                    <td><small class="text-muted"><?= $tx['created_at'] ?></small></td>
                                    <td>
                                        <?php if($is_exchange): ?>
                                            <span class="badge bg-warning text-dark">ئاڵوگۆڕ 💱</span>
                                        <?php elseif(($tx['usd_amount'] ?? 0) > 0 || ($tx['iqd_amount'] ?? 0) > 0): ?>
                                            <span class="badge bg-success">هاتن 📥</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">دەرچوون 📤</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-bold text-primary"><?= htmlspecialchars($tx['category_name'] ?? ($is_exchange ? 'ئاڵوگۆڕ' : 'بی جۆر')) ?></span></td>
                                    <td dir="ltr" class="fw-bold <?= ($tx['usd_amount'] ?? 0) > 0 ? 'text-success' : (($tx['usd_amount'] ?? 0) < 0 ? 'text-danger' : 'text-muted') ?>">
                                        <?= $tx['usd_amount'] ? number_format(abs($tx['usd_amount']), 2) . ' $' : '-' ?>
                                    </td>
                                    <td dir="ltr" class="fw-bold <?= ($tx['iqd_amount'] ?? 0) > 0 ? 'text-success' : (($tx['iqd_amount'] ?? 0) < 0 ? 'text-danger' : 'text-muted') ?>">
                                        <?= $tx['iqd_amount'] ? number_format(abs($tx['iqd_amount']), 0) . ' IQD' : '-' ?>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($tx['description'] ?? '') ?></td>
                                    <td>
                                        <?php if(!$is_exchange): ?>
                                            <button class="btn btn-sm btn-outline-info border-0" onclick='prepareEdit(<?= json_encode($tx) ?>)'>
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteTransaction(<?= $tx['id'] ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- actionModal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form id="actionForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="actionModalLabel">مامەڵەی قاسە</h5>
                        <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formActionType">
                        <input type="hidden" name="transaction_id" id="editTxId">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold">هۆکاری مامەڵە</label>
                            <select name="category_id" id="actionCategory" class="form-select" required>
                                <option value="">هەڵبژاردن...</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">بڕی پارە بە هەردوو دراو</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small text-success">بڕی دۆلار ($)</label>
                                            <input type="number" name="amount_usd" id="amountUSD" class="form-control" step="0.01" placeholder="0.00">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-primary">بڕی دینار (IQD)</label>
                                            <input type="number" name="amount_iqd" id="amountIQD" class="form-control" step="250" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold">تێبینی</label>
                            <textarea name="description" id="actionDesc" class="form-control" rows="2" placeholder="تێبینی..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لابردن</button>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">تۆمارکردن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Exchange Modal (Optional Update) -->
    <div class="modal fade" id="exchangeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow text-center">
                <form id="exchangeForm">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">ئاڵوگۆڕی دراو</h5>
                        <button type="button" class="btn-close m-0 ms-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="exchange">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-3">
                                <label class="mb-2 fw-bold text-danger">لە پارەی</label>
                                <select name="from_currency" id="exFrom" class="form-select" required>
                                    <option value="USD">دۆلار (USD)</option>
                                    <option value="IQD">دینار (IQD)</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-center fs-3 mb-3 text-muted">➡️</div>
                            <div class="col-md-5 mb-3">
                                <label class="mb-2 fw-bold text-success">بۆ پارەی</label>
                                <select name="to_currency" id="exTo" class="form-select" required>
                                    <option value="IQD" selected>دینار (IQD)</option>
                                    <option value="USD">دۆلار (USD)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small">بڕی خەرجکراو</label>
                                <input type="number" name="exchange_amount" id="exAmount" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small">سێرعی گۆڕینەوە</label>
                                <input type="number" name="exchange_rate" class="form-control" step="0.0001" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لابردن</button>
                        <button type="submit" class="btn btn-warning px-5 fw-bold text-dark">گۆڕینەوە</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setFormAction(type) {
            $('#formActionType').val(type);
            $('#editTxId').val('');
            $('#submitBtn').text('تۆمارکردن');
            
            const filterType = (type === 'inflow') ? 'INFLOW' : 'OUTFLOW';
            $('#actionCategory option').each(function() {
                const catType = $(this).data('type');
                if (catType && catType !== 'BOTH' && catType !== filterType) $(this).hide();
                else $(this).show();
            });
            $('#actionCategory, #amountUSD, #amountIQD, #actionDesc').val('');

            if(type === 'inflow'){
                $('#actionModalLabel').text('تۆمارکردنی زیادکردنی پارە');
                $('#actionModal .modal-header').removeClass('bg-danger bg-info').addClass('bg-success text-white');
            } else {
                $('#actionModalLabel').text('تۆمارکردنی خەرجی / ڕاکێشان');
                $('#actionModal .modal-header').removeClass('bg-success bg-info').addClass('bg-danger text-white');
            }
        }

        function prepareEdit(tx) {
            $('#actionModal').modal('show');
            $('#actionModalLabel').text('دەستکاری کردنی مامەڵە');
            $('#actionModal .modal-header').removeClass('bg-success bg-danger').addClass('bg-info text-dark');
            
            $('#formActionType').val('edit_transaction');
            $('#editTxId').val(tx.id);
            $('#actionCategory').val(tx.category_id);
            $('#amountUSD').val(Math.abs(tx.usd_amount || 0));
            $('#amountIQD').val(Math.abs(tx.iqd_amount || 0));
            $('#actionDesc').val(tx.description);
            $('#submitBtn').text('نوێکردنەوە');
            
            // Show all categories for edit
            $('#actionCategory option').show();
        }

        function deleteTransaction(id) {
            if (!confirm('ئایا دڵنیایت لە سڕینەوەی ئەم مامەڵەیە؟ هەموو بڕە پارەکان لە باڵانسەکەت پێچەوانە دەبنەوە.')) return;
            $.ajax({
                url: '../process/user_wallets/process.php',
                type: 'POST',
                data: { action: 'delete_transaction', transaction_id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('هەڵە: ' + res.message);
                }
            });
        }

        $('#actionForm').submit(function(e) {
            e.preventDefault();
            submitData($(this));
        });

        $('#exchangeForm').submit(function(e) {
            e.preventDefault();
            submitData($(this));
        });

        function submitData(form) {
            $.ajax({
                url: '../process/user_wallets/process.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) location.reload();
                    else alert('کێشە: ' + res.message);
                },
                error: function() { alert('هەڵە لە سێرڤەر'); }
            });
        }
    </script>
</body>
</html>
