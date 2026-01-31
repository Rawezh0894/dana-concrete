// Multiple submission prevention flag
let submittingExpense = false;
let isSplitMode = false;
let carOptionsHtml = '';
let materialOptionsHtml = ''; // For storing material selections

// Main form submission handler
const addExpenseForm = document.getElementById('addExpenseForm');
if (addExpenseForm) {
    addExpenseForm.onsubmit = async function (e) {
        e.preventDefault();

        // Prevent multiple submissions
        if (submittingExpense) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }

        // Set submitting flag and disable submit button
        submittingExpense = true;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
        }

        const invoiceNumber = document.getElementById('invoice_number').value.trim();
        const expenseTypeVal = document.getElementById('expense_type')?.value;
        const materialLines = []; // Define materialLines here to be available for scope

        // Collect material lines early to check if we need to auto-generate invoice
        if (expenseTypeVal === 'بەکارهێنانی کاڵای کۆگا' && !isSplitMode) {
            const mainMaterialId = document.getElementById('material_id')?.value;
            // Main Main Form Line
            if (mainMaterialId && mainMaterialId.trim() !== '') {
                const qty = parseFloat(document.getElementById('material_quantity')?.value || 0);
                const priceIqd = parseFloat(document.getElementById('material_purchase_price_iqd')?.value || 0);
                const priceUsd = parseFloat(document.getElementById('material_purchase_price_usd')?.value || 0);
                const totalCost = parseFloat(document.getElementById('material_total_cost')?.value || 0);
                // Allow qty > 0 check
                if (qty > 0) {
                    materialLines.push({
                        material_id: mainMaterialId,
                        material_quantity: qty,
                        usage_unit_type: document.getElementById('usage_unit_type')?.value || '',
                        material_purchase_price_iqd: priceIqd,
                        material_purchase_price_usd: priceUsd,
                        material_total_cost: totalCost
                    });
                }
            }
            // Dynamic Rows
            document.querySelectorAll('.material-line-row').forEach(row => {
                const mid = row.querySelector('.line-material-id')?.value;
                const qty = parseFloat(row.querySelector('.line-quantity')?.value || 0);
                const priceIqd = parseFloat(row.querySelector('.line-price-iqd')?.value || 0);
                const priceUsd = parseFloat(row.querySelector('.line-price-usd')?.value || 0);
                const totalCost = parseFloat(row.querySelector('.line-total-cost')?.value || 0);
                if (mid && mid.trim() !== '' && qty > 0) {
                    materialLines.push({
                        material_id: mid,
                        material_quantity: qty,
                        usage_unit_type: row.querySelector('.line-unit-type')?.value || '',
                        material_purchase_price_iqd: priceIqd,
                        material_purchase_price_usd: priceUsd,
                        material_total_cost: totalCost
                    });
                }
            });
        }

        // Invoice Number Validation (Skip if auto-generating for Multi-Item)
        // If invoice number is empty AND we have material lines, we skip client-side dup check on invoice number (Backend will generate it)
        const shouldSkipInvoiceCheck = (invoiceNumber === '' && materialLines.length > 0);

        if (!shouldSkipInvoiceCheck && invoiceNumber) {
            // Check for duplicate in current table (client-side)
            const table = document.getElementById('otherExpensesTable');
            let duplicate = false;
            if (table && table.tBodies[0]) {
                for (let row of table.tBodies[0].rows) {
                    if (row.cells[7] && row.cells[7].innerText.trim() === invoiceNumber) {
                        duplicate = true;
                        break;
                    }
                }
            }
            if (duplicate) {
                Swal.fire('هەڵە!', 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!', 'error');
                resetSubmitState(submitBtn, originalBtnText);
                return;
            }
        }

        // Stock Availability Check (Client Side Message)
        const errorMessage = document.querySelector('.material-availability-message.text-danger');
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'ناتوانرێت خەرجی تۆمار بکرێت - بڕی پێویست لە کۆگا نەماوە',
                confirmButtonText: 'باشە'
            });
            resetSubmitState(submitBtn, originalBtnText);
            return;
        }

        const formData = new FormData(addExpenseForm);

        // --- SPLIT MODE HANDLING ---
        if (isSplitMode) {
            const splits = [];
            document.querySelectorAll('.invoice-split-row').forEach(row => {
                const rowType = row.querySelector('.split-row-type').value;
                const carId = row.querySelector('.split-car-id')?.value;
                const materialId = row.querySelector('.split-material-id')?.value;
                const quantity = row.querySelector('.split-quantity')?.value || 0;
                const amountIqd = row.querySelector('.split-amount-iqd').value;
                const amountUsd = row.querySelector('.split-amount-usd').value;

                if (rowType === 'car' && carId) {
                    splits.push({
                        type: 'car',
                        car_id: carId,
                        amount_iqd: amountIqd || 0,
                        amount_usd: amountUsd || 0
                    });
                } else if (rowType === 'stock' && materialId) {
                    splits.push({
                        type: 'stock',
                        material_id: materialId,
                        quantity: quantity,
                        usage_unit_type: row.querySelector('.split-unit-type')?.value || '',
                        amount_iqd: amountIqd || 0,
                        amount_usd: amountUsd || 0
                    });
                }
            });

            if (splits.length === 0) {
                Swal.fire('هەڵە!', 'تکایە لانیکەم بڕگەیەک هەڵبژێرە لە دابەشکردنەکەدا!', 'error');
                resetSubmitState(submitBtn, originalBtnText);
                return;
            }
            formData.append('invoice_splits', JSON.stringify(splits));
            formData.set('car_id', ''); // Clear main car_id
            formData.set('material_id', '');
        }

        // --- MULTI-ITEM WAREHOUSE USE HANDLING ---
        // Verify if we collected lines earlier
        if (materialLines.length > 0) {
            formData.append('material_lines', JSON.stringify(materialLines));

            // Recalculate Totals based on lines
            const totalCostSum = materialLines.reduce((s, l) => s + (parseFloat(l.material_total_cost) || 0), 0);
            const currencyType = document.getElementById('currency_type')?.value || 'دینار';

            // Logic: Assign total to appropriate currency, reset other
            if (currencyType === 'دۆلار') {
                formData.set('amount_usd', totalCostSum);
                formData.set('amount_iqd', '0');
                formData.set('paid_usd', totalCostSum); // Assume paid full for now or take input? Input might be 0.
                // Actually, preserve user input for paid if they entered it, OR set equal to total?
                // Usually for expenses, it's fully paid unless 'Credit'.
                // Ideally, we sum up the cost and SET the amount fields.
                // But the user might have manually entered paid amount.
                // Let's set amount = total.
            } else {
                formData.set('amount_iqd', totalCostSum);
                formData.set('amount_usd', '0');
            }

            // Update Paid/Remaining logic based on payment type
            // If user didn't touch paid fields, we might want to auto-fill them? 
            // The prompt says "Collection of total and setting amount_iqd/usd and paid/remaining based on that total".
            // So we should enforce it.
            if (currencyType === 'دۆلار') {
                formData.set('amount_usd', totalCostSum);
                formData.set('amount_iqd', '0');

                const paidUsdInput = document.getElementById('paid_usd')?.value || 0;
                // If payment is Cash, Paid = Amount. If Credit, Paid = Input.
                const pType = document.getElementById('payment_type')?.value;
                if (pType === 'نەقد') {
                    formData.set('paid_usd', totalCostSum);
                    formData.set('remaining_usd', '0');
                } else {
                    formData.set('paid_usd', paidUsdInput);
                    formData.set('remaining_usd', totalCostSum - paidUsdInput);
                }

                formData.set('paid_iqd', '0');
                formData.set('remaining_iqd', '0');

            } else {
                // IQD
                formData.set('amount_iqd', totalCostSum);
                formData.set('amount_usd', '0');

                const paidIqdInput = document.getElementById('paid_iqd')?.value || 0;
                const pType = document.getElementById('payment_type')?.value;
                if (pType === 'نەقد') {
                    formData.set('paid_iqd', totalCostSum);
                    formData.set('remaining_iqd', '0');
                } else {
                    formData.set('paid_iqd', paidIqdInput);
                    formData.set('remaining_iqd', totalCostSum - paidIqdInput);
                }

                formData.set('paid_usd', '0');
                formData.set('remaining_usd', '0');
            }

            // Remove single line items to avoid double processing if backend is confused (though backend checks lines first)
            // formData.delete('material_id'); // Backend checks lines first, so this is fine.
        }

        // --- STANDARD FORM FIELDS APPEND ---
        // (Pre-existing logic for optional fields)
        if (document.getElementById('gas_liters')) {
            formData.append('gas_liters', document.getElementById('gas_liters').value);
        }
        if (document.getElementById('expense_type')) {
            formData.append('expense_type', document.getElementById('expense_type').value);
        }
        // Force append material_id from main form if it exists, though Backend prioritizes material_lines
        if (document.getElementById('material_id') && document.getElementById('material_id').value) {
            formData.append('material_id', document.getElementById('material_id').value);
        }
        if (document.getElementById('usage_unit_type')) {
            formData.append('usage_unit_type', document.getElementById('usage_unit_type').value || '');
        }

        // --- VALIDATION START ---
        const paymentTypeVal = formData.get('payment_type');
        const remIqd = parseFloat(formData.get('remaining_iqd') || 0);
        const remUsd = parseFloat(formData.get('remaining_usd') || 0);
        const totalRem = remIqd + remUsd;

        if (paymentTypeVal === 'قەرز') {
            if (totalRem == 0) {
                Swal.fire('هەڵە!', 'بۆ مامەڵەی قەرز، نابێت پارەی ماوە سفر بێت!', 'error');
                resetSubmitState(submitBtn, originalBtnText);
                return;
            }
        } else if (paymentTypeVal === 'نەقد') {
            if (totalRem > 0) {
                // Allow small rounding errors?
                if (totalRem > 1) { // 1 Dinar tolerance
                    Swal.fire('هەڵە!', 'بۆ مامەڵەی نەقد، نابێت هیچ پارەیەک بمێنێتەوە!', 'error');
                    resetSubmitState(submitBtn, originalBtnText);
                    return;
                }
            }
        }

        try {
            const res = await fetch('../process/other_expenses/add_expenses.php', {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

            const data = await res.json();

            if (data.success) {
                Swal.fire('سەرکەوتوو!', 'خەرجی تر زیادکرا', 'success');
                var modal = bootstrap.Modal.getInstance(document.getElementById('addExpenseModal'));
                modal.hide();
                if (typeof reloadOtherExpenses === 'function') {
                    reloadOtherExpenses();
                } else if (typeof loadOtherExpenses === 'function') {
                    loadOtherExpenses();
                }
                addExpenseForm.reset();
            } else {
                Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        } finally {
            resetSubmitState(submitBtn, originalBtnText);
        }
    }
}

function resetSubmitState(btn, originalText) {
    submittingExpense = false;
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function populateSelect(url, selectId, selectedId) {
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const result = await res.json();

        // Handle various response formats
        let data = [];
        if (Array.isArray(result)) data = result;
        else if (result && result.success && Array.isArray(result.data)) data = result.data;
        else if (result && result.success && Array.isArray(result.materials)) data = result.materials;
        else if (result && typeof result === 'object' && !Array.isArray(result)) {
            const keys = Object.keys(result);
            if (keys.length > 0 && !isNaN(keys[0])) data = Object.values(result);
        }

        const select = document.getElementById(selectId);
        if (!select) return;

        select.innerHTML = '';
        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = '-- هەلبژێرە --';
        select.appendChild(placeholderOpt);

        if (data && data.length > 0) {
            data.forEach(item => {
                if (item && (item.id !== undefined || item.value !== undefined)) {
                    const opt = document.createElement('option');
                    opt.value = item.id || item.value;
                    opt.textContent = item.name || item.text || item.label;
                    if (selectedId && String(opt.value) === String(selectedId)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                }
            });
        }

        // Trigger changes for Select2 coherence
        if ($(select).hasClass('select2-hidden-accessible')) {
            $(select).trigger('change');
        }
        select.dispatchEvent(new Event('change'));

    } catch (err) {
        console.error(`Error populating #${selectId}:`, err);
    }
}

// Fetch USD Rate
async function fetchAndSetUsdRate() {
    try {
        const response = await fetch('../process/other_expenses/get_usd_rate.php');
        const data = await response.json();
        if (data.success && data.rate) {
            document.getElementById('exchange_rate').value = data.rate;
        } else {
            if (data.default_rate) document.getElementById('exchange_rate').value = data.default_rate;
        }
    } catch (error) {
        document.getElementById('exchange_rate').value = '139250';
    }
}

const addExpenseModal = document.getElementById('addExpenseModal');
if (addExpenseModal) {
    addExpenseModal.addEventListener('show.bs.modal', function () {
        populateSelect('../process/other_expenses/select_persons.php', 'person_id');
        populateSelect('../process/other_expenses/select_employees.php', 'employee_id');
        populateSelect('../process/other_expenses/select_cars.php', 'car_id').then(() => {
            carOptionsHtml = document.getElementById('car_id').innerHTML;
        });
        populateSelect('../process/other_expenses/select_materials.php', 'material_id').then(() => {
            materialOptionsHtml = document.getElementById('material_id').innerHTML;
        });

        isSplitMode = false;
        document.getElementById('splitItemsContainer').style.display = 'none';
        document.getElementById('singleCarContainer').style.display = 'block';
        document.getElementById('invoiceSplitsList').innerHTML = '';
        document.getElementById('toggleSplitCars').innerText = 'دابەشکردن';

        // Reset Multi-Lines
        const multiMatContainer = document.getElementById('multiMaterialLinesContainer');
        if (multiMatContainer) multiMatContainer.style.display = 'none';
        const materialLinesList = document.getElementById('materialLinesList');
        if (materialLinesList) materialLinesList.innerHTML = '';

        fetchAndSetUsdRate();
    });
}

// --- ROBUST EVENT LISTENER FOR EXPENSE TYPE ---
// Use jQuery delegation to ensure robust handling
$(document).ready(function () {
    $(document).on('change', '#expense_type', function () {
        const val = $(this).val();
        const container = document.getElementById('multiMaterialLinesContainer');
        const list = document.getElementById('materialLinesList');

        if (val === 'بەکارهێنانی کاڵای کۆگا') {
            if (container) container.style.display = 'block';
        } else {
            if (container) container.style.display = 'none';
            if (list) list.innerHTML = '';
        }

    });

    // Also bind to Add Button
    $(document).on('click', '#addMaterialLineBtn', addMaterialLineRow);

    // Remove Row Delegation
    $(document).on('click', '.remove-material-line', function () {
        $(this).closest('.material-line-row').remove();
        // Update totals?
        // We might need a function to update grand totals based on all lines.
        // Currently the main submission does it.
    });

    // Calculation delegation for dynamic rows
    $(document).on('input', '.line-quantity, .line-price-iqd, .line-price-usd', function () {
        const row = $(this).closest('.material-line-row');
        const qty = parseFloat(row.find('.line-quantity').val() || 0);
        const priceIqd = parseFloat(row.find('.line-price-iqd').val() || 0);
        const priceUsd = parseFloat(row.find('.line-price-usd').val() || 0);

        const price = priceIqd > 0 ? priceIqd : priceUsd;
        const total = qty * price;
        row.find('.line-total-cost').val(total.toFixed(2));
    });
});


function addMaterialLineRow() {
    const list = document.getElementById('materialLinesList');
    if (!list) return;
    const row = document.createElement('div');
    row.className = 'material-line-row row g-2 mb-2 align-items-end border-bottom pb-2';

    // Ensure we have options
    const options = materialOptionsHtml || '<option value="">-- هەلبژێرە --</option>';

    row.innerHTML = `
        <div class="col-md-3">
            <label class="form-label small">کاڵا</label>
            <select class="form-control form-control-sm line-material-id select2-dynamic">
                ${options}
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">بڕ</label>
            <input type="number" step="0.01" class="form-control form-control-sm line-quantity" value="0" min="0">
        </div>
        <div class="col-md-2">
            <label class="form-label small">یەکە</label>
            <select class="form-control form-control-sm line-unit-type">
                <option value="دانە">دانە</option>
                <option value="کارتۆن">کارتۆن</option>
                <option value="بەرمیل">بەرمیل</option>
                <option value="دەبە">دەبە</option>
                <option value="لیتر">لیتر</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small">نرخ د.ع</label>
            <input type="number" step="0.01" class="form-control form-control-sm line-price-iqd" value="0" min="0">
        </div>
        <div class="col-md-1">
            <label class="form-label small">نرخ $</label>
            <input type="number" step="0.01" class="form-control form-control-sm line-price-usd" value="0" min="0">
        </div>
        <div class="col-md-2">
            <label class="form-label small">کۆی نرخ</label>
            <input type="number" step="0.01" class="form-control form-control-sm line-total-cost" value="0" readonly>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger remove-material-line"><i class="fas fa-trash"></i></button>
        </div>
    `;
    list.appendChild(row);

    // Init Select2
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(row.querySelector('.select2-dynamic')).select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addExpenseModal'),
            dir: 'rtl'
        });

        // Add change listener to fetch price for new row
        $(row.querySelector('.line-material-id')).on('change', function () {
            const mid = $(this).val();
            if (mid) {
                // Fetch details
                fetch(`../process/other_expenses/get_material_details.php?material_id=${mid}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            const m = d.data;
                            if (m.currency_type === 'دۆلار') {
                                row.querySelector('.line-price-usd').value = m.purchase_price_usd || 0;
                                row.querySelector('.line-price-iqd').value = 0;
                            } else {
                                row.querySelector('.line-price-iqd').value = m.purchase_price_iqd || 0;
                                row.querySelector('.line-price-usd').value = 0;
                            }
                        }
                    });
            }
        });
    }
}

// Split Mode Toggling (Legacy/Separate feature)
document.getElementById('toggleSplitCars')?.addEventListener('click', function () {
    isSplitMode = !isSplitMode;
    const splitContainer = document.getElementById('splitItemsContainer');
    const singleContainer = document.getElementById('singleCarContainer');
    const multiMatContainer = document.getElementById('multiMaterialLinesContainer');

    if (isSplitMode) {
        splitContainer.style.display = 'block';
        singleContainer.style.display = 'none';
        if (multiMatContainer) multiMatContainer.style.display = 'none'; // Hide multi-mat in split mode to avoid confusion

        this.innerText = 'گەڕانەوە';
        this.classList.replace('btn-outline-primary', 'btn-outline-secondary');

        // Add first row if empty
        if (document.getElementById('invoiceSplitsList').children.length === 0) {
            addInvoiceSplitRow();
        }
    } else {
        splitContainer.style.display = 'none';
        singleContainer.style.display = 'block';
        this.innerText = 'دابەشکردن';
        this.classList.replace('btn-outline-secondary', 'btn-outline-primary');

        // Restore multi-mat if expense type matches
        const et = document.getElementById('expense_type')?.value;
        if (et === 'بەکارهێنانی کاڵای کۆگا' && multiMatContainer) {
            multiMatContainer.style.display = 'block';
        }
    }
});

document.getElementById('addSplitRow')?.addEventListener('click', addInvoiceSplitRow);

function addInvoiceSplitRow() {
    const list = document.getElementById('invoiceSplitsList');
    const row = document.createElement('div');
    row.className = 'invoice-split-row row g-2 mb-2 align-items-end border-bottom pb-2';
    row.innerHTML = `
        <div class="col-md-2">
            <label class="form-label small">جۆر</label>
            <select class="form-control form-control-sm split-row-type">
                <option value="car">خەرجی سەیارە</option>
                <option value="stock">کڕینی کۆگا</option>
            </select>
        </div>
        <div class="col-md-3 entity-container">
            <label class="form-label small">سەیارە</label>
            <select class="form-control form-control-sm split-car-id select2-dynamic">
                ${carOptionsHtml}
            </select>
        </div>
        <div class="col-md-2 qty-container" style="display: none;">
            <div class="row g-1">
                <div class="col-6">
                    <label class="form-label small">بڕ (Qty)</label>
                    <input type="number" step="0.01" class="form-control form-control-sm split-quantity" value="0">
                </div>
                <div class="col-6">
                    <label class="form-label small">یەکە</label>
                    <select class="form-control form-control-sm split-unit-type">
                        <option value="دانە">دانە</option>
                        <option value="کارتۆن">کارتۆن</option>
                        <option value="بەرمیل">بەرمیل</option>
                        <option value="دەبە">دەبە</option>
                        <option value="لیتر">لیتر</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small">دینار</label>
            <input type="number" step="0.01" class="form-control form-control-sm split-amount-iqd" value="0">
        </div>
        <div class="col-md-2">
            <label class="form-label small">دۆلار</label>
            <input type="number" step="0.01" class="form-control form-control-sm split-amount-usd" value="0">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger remove-split-row"><i class="fas fa-trash"></i></button>
        </div>
    `;
    list.appendChild(row);

    const typeSelect = row.querySelector('.split-row-type');
    const entityContainer = row.querySelector('.entity-container');
    const qtyContainer = row.querySelector('.qty-container');

    typeSelect.addEventListener('change', function () {
        if (this.value === 'car') {
            entityContainer.innerHTML = `<label class="form-label small">سەیارە</label><select class="form-control form-control-sm split-car-id select2-dynamic">${carOptionsHtml}</select>`;
            qtyContainer.style.display = 'none';
        } else {
            entityContainer.innerHTML = `<label class="form-label small">کاڵا</label><select class="form-control form-control-sm split-material-id select2-dynamic">${materialOptionsHtml}</select>`;
            qtyContainer.style.display = 'block';
        }
        initSelect2InRow(row);
    });

    // Removal
    row.querySelector('.remove-split-row').addEventListener('click', function () {
        row.remove();
        updateTotalsFromSplits();
    });
    // Totals
    row.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', updateTotalsFromSplits);
    });

    initSelect2InRow(row);
}

function initSelect2InRow(row) {
    const selects = row.querySelectorAll('.select2-dynamic');
    selects.forEach(select => {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(select).select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#addExpenseModal'),
                dir: 'rtl'
            });
        }
    });
}

function updateTotalsFromSplits() {
    if (!isSplitMode) return;
    let totalIqd = 0;
    let totalUsd = 0;
    document.querySelectorAll('.invoice-split-row').forEach(row => {
        totalIqd += parseFloat(row.querySelector('.split-amount-iqd').value || 0);
        totalUsd += parseFloat(row.querySelector('.split-amount-usd').value || 0);
    });
    document.getElementById('amount_iqd').value = totalIqd.toFixed(2);
    document.getElementById('amount_usd').value = totalUsd.toFixed(2);
    if (typeof updateRemaining === 'function') updateRemaining();
}

// Add Person Handler (Simplified)
const addPersonForm = document.getElementById('addPersonForm');
if (addPersonForm) {
    addPersonForm.onsubmit = async function (e) {
        e.preventDefault();
        const formData = new FormData(addPersonForm);
        try {
            const res = await fetch('../process/other_expenses/add_person.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو!', 'کەس زیادکرا', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addPersonModal')).hide();
                const personSelect = document.getElementById('person_id');
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.name;
                option.selected = true;
                personSelect.appendChild(option);
                // Trigger change
                if ($(personSelect).hasClass('select2-hidden-accessible')) $(personSelect).trigger('change');
                personSelect.dispatchEvent(new Event('change'));
                addPersonForm.reset();
            }
        } catch (err) { Swal.fire('هەڵە', 'Error', 'error'); }
    }
}
