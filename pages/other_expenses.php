<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!hasPermission('view_other_expenses')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خەرجی تر</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .gas-material-field {
            display: none;
        }
        .gas-material-field.show {
            display: block;
        }
        .warehouse-hidden-field {
            display: block;
        }
        .warehouse-hidden-field.hide {
            display: none;
        }
    </style>
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">خەرجی تر</h2>
        <?php if (hasPermission('add_other_expenses')): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی خەرجی تر</button>
        <?php endif; ?>
    </div>
    <div class="mb-4 d-flex flex-wrap align-items-center gap-3">
      <div>
        <label for="monthFilter" class="form-label mb-0">فلتەر بە مانگ:</label>
        <input type="month" id="monthFilter" class="form-control" style="width: 180px; display: inline-block;">
      </div>
      <div class="row w-100 mt-3 g-3">
        <div class="col-md-3">
          <div class="card text-center shadow">
            <div class="card-body">
              <h6 class="card-title">کۆی خەرجی نەقد بە دینار</h6>
              <div id="totalCashIqd" class="fs-4 fw-bold">0 د.ع</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center shadow">
            <div class="card-body">
              <h6 class="card-title">کۆی خەرجی نەقد بە دۆلار</h6>
              <div id="totalCashUsd" class="fs-4 fw-bold">$0</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center shadow">
            <div class="card-body">
              <h6 class="card-title">کۆی خەرجی قەرز بە دینار</h6>
              <div id="totalCreditIqd" class="fs-4 fw-bold">0 د.ع</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center shadow">
            <div class="card-body">
              <h6 class="card-title">کۆی خەرجی قەرز بە دۆلار</h6>
              <div id="totalCreditUsd" class="fs-4 fw-bold">$0</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="otherExpensesTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>مەبەست</th>
                    <th>کەس</th>
                    <th>کارمەند</th>
                    <th>سەیارە</th>
                    <th>بڕی گاز (لیتر)</th>
                    <th>جۆری خەرجی</th>
                    <th>کاڵا لە کۆگا</th>
                    <th>بڕی عەدەدی کاڵا</th>
                    <th>نرخی کڕینی کاڵا بە دینار</th>
                    <th>نرخی کڕینی کاڵا بە دۆلار</th>
                    <th>کۆی نرخی کاڵای بەکارهاتوو</th>
                    <th>ئینپوتی نرخی کڕینی گاز</th>
                    <th>کۆی نرخی گازی بەکارهاتوو</th>
                    <th>جۆری مامەڵە</th>
                    <th>جۆری پارە</th>
                    <th>ژمارەی وەسڵ</th>
                    <th>بڕی دینار</th>
                    <th>بڕی دۆلار</th>
                    <th>پارەی دراو دینار</th>
                    <th>پارەی دراو دۆلار</th>
                    <th>نرخی 100 دۆلار</th>
                    <th>ماوە دینار</th>
                    <th>ماوە دۆلار</th>
                    <th>بەروار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Expenses will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addExpenseModalLabel">زیادکردنی خەرجی تر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="purpose" class="form-label">مەبەستی سەرف کردن</label>
            <textarea class="form-control" id="purpose" name="purpose" rows="2"></textarea>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="employee_id" class="form-label">کارمەند</label>
              <select class="form-control" id="employee_id" name="employee_id"></select>
            </div>
            <div class="col-md-4">
              <label for="car_id" class="form-label">سەیارە</label>
              <select class="form-control" id="car_id" name="car_id"></select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="gas_liters" class="form-label">بڕی گاز (لیتر)</label>
              <input type="number" step="0.01" class="form-control" id="gas_liters" name="gas_liters" placeholder="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="expense_type" class="form-label">جۆری خەرجی</label>
              <select class="form-control" id="expense_type" name="expense_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="خەرجی تر">خەرجی تر</option>
                <option value="بەکارهێنانی کاڵای کۆگا">بەکارهێنانی کاڵای کۆگا</option>
                <option value="بەکارهێنانی گاز">بەکارهێنانی گاز</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_id" class="form-label">کاڵا لە کۆگا</label>
              <select class="form-control" id="material_id" name="material_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_quantity" class="form-label">بڕی عەدەدی کاڵا</label>
              <input type="number" step="0.01" class="form-control" id="material_quantity" name="material_quantity" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_purchase_price_iqd" class="form-label">نرخی کڕینی کاڵا بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="material_purchase_price_iqd" name="material_purchase_price_iqd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_purchase_price_usd" class="form-label">نرخی کڕینی کاڵا بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="material_purchase_price_usd" name="material_purchase_price_usd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="material_total_cost" class="form-label">کۆی نرخی کاڵای بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="material_total_cost" name="material_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 gas-material-field">
              <label for="gas_purchase_price_input" class="form-label">ئینپوتی نرخی کڕینی گاز</label>
              <input type="number" step="0.01" class="form-control" id="gas_purchase_price_input" name="gas_purchase_price_input" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="gas_total_cost" class="form-label">کۆی نرخی گازی بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="gas_total_cost" name="gas_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="person_id" class="form-label">کەس</label>
              <div class="input-group">
                <select class="form-control" id="person_id" name="person_id"></select>
                <button class="btn" type="button" id="addPersonBtn" data-bs-toggle="modal" data-bs-target="#addPersonModal" style="background: var(--seafoam-green); color: white; font-weight: bold;">+
                </button>
              </div>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="payment_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-control" id="payment_type" name="payment_type">
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="currency_type" class="form-label">جۆری پارە</label>
              <select class="form-control" id="currency_type" name="currency_type">
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="invoice_number" class="form-label">ژمارەی وەسڵ</label>
              <input type="text" class="form-control" id="invoice_number" name="invoice_number">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="amount_iqd" name="amount_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" value="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="paid_iqd" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="paid_iqd" name="paid_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="paid_usd" name="paid_usd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="exchange_rate" name="exchange_rate" value="150000">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="remaining_iqd" class="form-label">بڕی ماوە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="remaining_iqd" name="remaining_iqd" value="0" readonly>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="remaining_usd" class="form-label">بڕی ماوە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="remaining_usd" name="remaining_usd" value="0" readonly>
            </div>
            <div class="col-md-4">
              <label for="date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="date" name="date">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--lime-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1" aria-labelledby="addPersonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPersonForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPersonModalLabel">زیادکردنی کەس</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="person_name" class="form-label">ناوی کەس</label>
            <input type="text" class="form-control" id="person_name" name="person_name" required>
          </div>
          <div class="mb-3">
            <label for="person_expense_usd" class="form-label">خەرجی کەس بە دۆلار</label>
            <input type="number" step="0.01" class="form-control" id="person_expense_usd" name="expense_usd" value="0">
          </div>
          <div class="mb-3">
            <label for="person_expense_iqd" class="form-label">خەرجی کەس بە دینار</label>
            <input type="number" step="0.01" class="form-control" id="person_expense_iqd" name="expense_iqd" value="0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editExpenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editExpenseModalLabel">دەستکاری خەرجی تر</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="mb-3">
            <label for="edit_purpose" class="form-label">مەبەستی سەرف کردن</label>
            <textarea class="form-control" id="edit_purpose" name="purpose" rows="2"></textarea>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="edit_employee_id" class="form-label">کارمەند</label>
              <select class="form-control" id="edit_employee_id" name="employee_id"></select>
            </div>
            <div class="col-md-4">
              <label for="edit_car_id" class="form-label">سەیارە</label>
              <select class="form-control" id="edit_car_id" name="car_id"></select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_liters" class="form-label">بڕی گاز (لیتر)</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_liters" name="gas_liters" placeholder="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4">
              <label for="edit_expense_type" class="form-label">جۆری خەرجی</label>
              <select class="form-control" id="edit_expense_type" name="expense_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="خەرجی تر">خەرجی تر</option>
                <option value="بەکارهێنانی کاڵای کۆگا">بەکارهێنانی کاڵای کۆگا</option>
                <option value="بەکارهێنانی گاز">بەکارهێنانی گاز</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_id" class="form-label">کاڵا لە کۆگا</label>
              <select class="form-control" id="edit_material_id" name="material_id">
                <option value="">-- هەلبژێرە --</option>
              </select>
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_quantity" class="form-label">بڕی عەدەدی کاڵا</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_quantity" name="material_quantity" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_purchase_price_iqd" class="form-label">نرخی کڕینی کاڵا بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_purchase_price_iqd" name="material_purchase_price_iqd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_purchase_price_usd" class="form-label">نرخی کڕینی کاڵا بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_purchase_price_usd" name="material_purchase_price_usd" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_material_total_cost" class="form-label">کۆی نرخی کاڵای بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="edit_material_total_cost" name="material_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_purchase_price_input" class="form-label">ئینپوتی نرخی کڕینی گاز</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_purchase_price_input" name="gas_purchase_price_input" placeholder="0">
            </div>
            <div class="col-md-4 gas-material-field">
              <label for="edit_gas_total_cost" class="form-label">کۆی نرخی گازی بەکارهاتوو</label>
              <input type="number" step="0.01" class="form-control" id="edit_gas_total_cost" name="gas_total_cost" placeholder="0" readonly>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_person_id" class="form-label">کەس</label>
              <select class="form-control" id="edit_person_id" name="person_id"></select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_payment_type" class="form-label">جۆری مامەڵە</label>
              <select class="form-control" id="edit_payment_type" name="payment_type">
                <option value="نەقد">نەقد</option>
                <option value="قەرز">قەرز</option>
              </select>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_currency_type" class="form-label">جۆری پارە</label>
              <select class="form-control" id="edit_currency_type" name="currency_type">
                <option value="دینار">دینار</option>
                <option value="دۆلار">دۆلار</option>
              </select>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_invoice_number" class="form-label">ژمارەی وەسڵ</label>
              <input type="text" class="form-control" id="edit_invoice_number" name="invoice_number">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_amount_iqd" class="form-label">بڕی پارە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_amount_iqd" name="amount_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_amount_usd" class="form-label">بڕی پارە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_amount_usd" name="amount_usd" value="0">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_paid_iqd" class="form-label">پارەی دراو بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_paid_iqd" name="paid_iqd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_paid_usd" class="form-label">پارەی دراو بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_paid_usd" name="paid_usd" value="0">
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_exchange_rate" class="form-label">نرخی 100 دۆلار بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_exchange_rate" name="exchange_rate" value="150000">
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_remaining_iqd" class="form-label">بڕی ماوە بە دینار</label>
              <input type="number" step="0.01" class="form-control" id="edit_remaining_iqd" name="remaining_iqd" value="0" readonly>
            </div>
            <div class="col-md-4 warehouse-hidden-field">
              <label for="edit_remaining_usd" class="form-label">بڕی ماوە بە دۆلار</label>
              <input type="number" step="0.01" class="form-control" id="edit_remaining_usd" name="remaining_usd" value="0" readonly>
            </div>
            <div class="col-md-4">
              <label for="edit_date" class="form-label">بەروار</label>
              <input type="date" class="form-control" id="edit_date" name="date">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/other_expenses/add_expenses.js"></script>
<script src="../assets/js/other_expenses/select_expenses.js"></script>
<script src="../assets/js/other_expenses/other_expenses.js"></script>
<script src="../assets/js/other_expenses/delete_expenses.js"></script>
<script src="../assets/js/other_expenses/update_expenses.js"></script>
</body>
</html>
