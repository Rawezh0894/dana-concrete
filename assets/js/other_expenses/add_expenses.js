// Multiple submission prevention flag
let submittingExpense = false;
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
                return;
            }
        }
        const formData = new FormData(addExpenseForm);
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

function populateSelect(url, selectId) {
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '<option value="">-- هەلبژێرە --</option>';
            data.forEach(item => {
                select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
            });
            // Trigger change event for Select2
            $(select).trigger('change');
        });
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
        populateSelect('../process/other_expenses/select_cars.php', 'car_id');
        populateSelect('../process/other_expenses/select_materials.php', 'material_id');

        // Fetch and set USD exchange rate when modal opens
        fetchAndSetUsdRate();
    });
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
                // Trigger change event for Select2
                $(personSelect).trigger('change');
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
