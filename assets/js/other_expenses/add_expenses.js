// Multiple submission prevention flag
let submittingExpense = false;
let isSplitMode = false;
let carOptionsHtml = '';
let materialOptionsHtml = ''; // For storing material selections

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
        if (invoiceNumber) {
            // Check for duplicate in current table (client-side)
            const table = document.getElementById('otherExpensesTable');
            let duplicate = false;
            if (table) {
                for (let row of table.tBodies[0].rows) {
                    if (row.cells[7] && row.cells[7].innerText.trim() === invoiceNumber) {
                        duplicate = true;
                        break;
                    }
                }
            }
            if (duplicate) {
                Swal.fire('هەڵە!', 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!', 'error');
                submittingExpense = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                return;
            }

            // Check if there's an error message indicating insufficient material
            const errorMessage = document.querySelector('.material-availability-message.text-danger');
            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'ناتوانرێت خەرجی تۆمار بکرێت - بڕی پێویست لە کۆگا نەماوە',
                    confirmButtonText: 'باشە'
                });
                submittingExpense = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                return;
            }
        }
        const formData = new FormData(addExpenseForm);

        // If split mode is active, collect split data
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
                submittingExpense = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                return;
            }
            formData.append('invoice_splits', JSON.stringify(splits));
            formData.set('car_id', ''); // Clear main car_id
            formData.set('material_id', '');
        }

        // Add gas_liters if present in the form
        if (document.getElementById('gas_liters')) {
            formData.append('gas_liters', document.getElementById('gas_liters').value);
        }
        // Add new fields
        if (document.getElementById('expense_type')) {
            formData.append('expense_type', document.getElementById('expense_type').value);
        }
        if (document.getElementById('material_id')) {
            const materialId = document.getElementById('material_id').value;
            // Only append if not empty
            if (materialId && materialId.trim() !== '') {
                formData.append('material_id', materialId);
            }
        }
        if (document.getElementById('material_quantity')) {
            formData.append('material_quantity', document.getElementById('material_quantity').value);
        }
        if (document.getElementById('usage_unit_type')) {
            const usageUnitType = document.getElementById('usage_unit_type').value;
            // Always append usage_unit_type - empty string will be converted to null on server
            formData.append('usage_unit_type', usageUnitType || '');
            console.log('Usage unit type being sent:', usageUnitType || '');
        }
        if (document.getElementById('material_purchase_price_iqd')) {
            formData.append('material_purchase_price_iqd', document.getElementById('material_purchase_price_iqd').value);
        }
        if (document.getElementById('material_purchase_price_usd')) {
            formData.append('material_purchase_price_usd', document.getElementById('material_purchase_price_usd').value);
        }
        if (document.getElementById('material_total_cost')) {
            formData.append('material_total_cost', document.getElementById('material_total_cost').value);
        }
        if (document.getElementById('gas_purchase_price_input')) {
            formData.append('gas_purchase_price_input', document.getElementById('gas_purchase_price_input').value);
        }
        if (document.getElementById('gas_total_cost')) {
            formData.append('gas_total_cost', document.getElementById('gas_total_cost').value);
        }
        // Add payment_type
        if (document.getElementById('payment_type')) {
            const paymentType = document.getElementById('payment_type').value;
            if (paymentType && paymentType.trim() !== '') {
                formData.append('payment_type', paymentType);
            } else {
                formData.append('payment_type', 'نەقد'); // Default value
            }
        } else {
            formData.append('payment_type', 'نەقد'); // Default value
        }

        if (document.getElementById('currency_type')) {
            const currencyType = document.getElementById('currency_type').value;
            if (currencyType && currencyType.trim() !== '') {
                formData.append('currency_type', currencyType);
            } else {
                formData.append('currency_type', 'دینار'); // Default value
            }
        } else {
            formData.append('currency_type', 'دینار'); // Default value
        }

        // --- VALIDATION START ---
        const paymentTypeVal = formData.get('payment_type');
        const remIqd = parseFloat(formData.get('remaining_iqd') || 0);
        const remUsd = parseFloat(formData.get('remaining_usd') || 0);
        const totalRem = remIqd + remUsd;

        if (paymentTypeVal === 'قەرز') {
            if (totalRem == 0) {
                Swal.fire('هەڵە!', 'بۆ مامەڵەی قەرز، نابێت پارەی ماوە سفر بێت!', 'error');
                submittingExpense = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                return;
            }
        } else if (paymentTypeVal === 'نەقد') {
            if (totalRem > 0) {
                Swal.fire('هەڵە!', 'بۆ مامەڵەی نەقد، نابێت هیچ پارەیەک بمێنێتەوە (پارەی ماوە دەبێت 0 بێت)!', 'error');
                submittingExpense = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
                return;
            }
        }
        // --- VALIDATION END ---
        try {
            console.log('Submitting expense form...');
            console.log('Form data entries:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            const res = await fetch('../process/other_expenses/add_expenses.php', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', res.status);
            console.log('Response headers:', res.headers);

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            console.log('Response data:', data);

            if (data.success) {
                console.log('Expense added successfully');
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
                console.error('Server returned error:', data.msg);
                Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
            }
        } catch (err) {
            console.error('Error submitting expense form:', err);
            console.error('Error details:', {
                message: err.message,
                stack: err.stack,
                name: err.name
            });
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        } finally {
            // Reset submitting flag and restore submit button
            submittingExpense = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }
}

async function populateSelect(url, selectId, selectedId) {
    try {
        console.log(`[populateSelect] Initializing for #${selectId} using URL: ${url}`);
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const result = await res.json();
        console.log(`[populateSelect] Received data for #${selectId}:`, result);

        let data = [];
        if (Array.isArray(result)) {
            data = result;
        } else if (result && result.success && Array.isArray(result.data)) {
            data = result.data;
        } else if (result && result.success && Array.isArray(result.materials)) {
            data = result.materials;
        } else if (result && typeof result === 'object' && !Array.isArray(result)) {
            const keys = Object.keys(result);
            if (keys.length > 0 && !isNaN(keys[0])) {
                data = Object.values(result);
            }
        }

        const select = document.getElementById(selectId);
        if (!select) {
            console.error(`[populateSelect] Element #${selectId} NOT FOUND in DOM`);
            return;
        }

        select.innerHTML = '';
        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = '-- هەلبژێرە --';
        select.appendChild(placeholderOpt);

        if (!data || data.length === 0) {
            console.warn(`[populateSelect] No data items found for #${selectId}`);
        } else {
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

        console.log(`[populateSelect] Successfully added ${data ? data.length : 0} options to #${selectId}`);

        if ($(select).hasClass('select2-hidden-accessible')) {
            console.log(`[populateSelect] Triggering Select2 update for #${selectId}`);
            $(select).trigger('change');
        }

        console.log(`[populateSelect] Dispatching native change event for #${selectId}`);
        select.dispatchEvent(new Event('change'));
    } catch (err) {
        console.error(`[populateSelect] SEVERE ERROR for #${selectId}:`, err);
    }
}

// Function to fetch and set USD exchange rate
async function fetchAndSetUsdRate() {
    try {
        const response = await fetch('../process/other_expenses/get_usd_rate.php');
        const data = await response.json();

        if (data.success && data.rate) {
            document.getElementById('exchange_rate').value = data.rate;
            console.log('USD rate fetched successfully:', data.rate);
        } else {
            console.warn('Failed to fetch USD rate:', data.error || 'Unknown error');
            // Set default rate if API fails
            if (data.default_rate) {
                document.getElementById('exchange_rate').value = data.default_rate;
                console.log('Using default USD rate:', data.default_rate);
            }
        }
    } catch (error) {
        console.error('Error fetching USD rate:', error);
        // Set default rate on error
        document.getElementById('exchange_rate').value = '139250';
        console.log('Using fallback USD rate: 139250');
    }
}

const addExpenseModal = document.getElementById('addExpenseModal');
if (addExpenseModal) {
    addExpenseModal.addEventListener('show.bs.modal', function () {
        populateSelect('../process/other_expenses/select_persons.php', 'person_id');
        populateSelect('../process/other_expenses/select_employees.php', 'employee_id');
        populateSelect('../process/other_expenses/select_cars.php', 'car_id').then(() => {
            // Save car options for future rows
            const carSelect = document.getElementById('car_id');
            carOptionsHtml = carSelect.innerHTML;
        });
        populateSelect('../process/other_expenses/select_materials.php', 'material_id').then(() => {
            // Save material options for future rows
            const materialSelect = document.getElementById('material_id');
            materialOptionsHtml = materialSelect.innerHTML;
        });

        // Reset split mode
        isSplitMode = false;
        document.getElementById('splitItemsContainer').style.display = 'none';
        document.getElementById('singleCarContainer').style.display = 'block';
        document.getElementById('invoiceSplitsList').innerHTML = '';
        document.getElementById('toggleSplitCars').innerText = 'دابەشکردن';

        // Fetch and set USD exchange rate when modal opens
        fetchAndSetUsdRate();
    });
}

// Split Mode Toggling
document.getElementById('toggleSplitCars')?.addEventListener('click', function () {
    isSplitMode = !isSplitMode;
    const splitContainer = document.getElementById('splitItemsContainer');
    const singleContainer = document.getElementById('singleCarContainer');

    if (isSplitMode) {
        splitContainer.style.display = 'block';
        singleContainer.style.display = 'none';
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
    }
});

document.getElementById('addSplitRow')?.addEventListener('click', addInvoiceSplitRow);

function addInvoiceSplitRow() {
    const list = document.getElementById('invoiceSplitsList');
    const rowId = Date.now();
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

    // Switch between Car/Material
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

    initSelect2InRow(row);

    // Attach removal logic
    row.querySelector('.remove-split-row').addEventListener('click', function () {
        row.remove();
        updateTotalsFromSplits();
    });

    // Attach update totals logic
    row.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', updateTotalsFromSplits);
    });
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

    // Trigger updateRemaining if it exists in other_expenses.js
    if (typeof updateRemaining === 'function') {
        updateRemaining();
    }
}

// Multiple submission prevention flag for add person
let submittingPerson = false;
const addPersonForm = document.getElementById('addPersonForm');
if (addPersonForm) {
    addPersonForm.onsubmit = async function (e) {
        e.preventDefault();

        // Prevent multiple submissions
        if (submittingPerson) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }

        // Set submitting flag and disable submit button
        submittingPerson = true;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
        }

        const formData = new FormData(addPersonForm);
        try {
            const res = await fetch('../process/other_expenses/add_person.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو!', 'کەس زیادکرا', 'success');
                var modal = bootstrap.Modal.getInstance(document.getElementById('addPersonModal'));
                modal.hide();
                // Add new person to select
                const personSelect = document.getElementById('person_id');
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.name;
                option.selected = true;
                personSelect.appendChild(option);
                // Update Select2
                if ($(personSelect).hasClass('select2-hidden-accessible')) {
                    $(personSelect).trigger('change');
                }
                // Trigger native change event
                personSelect.dispatchEvent(new Event('change'));
                addPersonForm.reset();
            } else {
                Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
            }
        } catch (err) {
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        } finally {
            // Reset submitting flag and restore submit button
            submittingPerson = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }
}
