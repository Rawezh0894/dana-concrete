async function editExpense(id, data) {
    // Check if there's an error message indicating insufficient material
    const errorMessage = document.querySelector('.material-availability-message.text-danger');
    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'ناتوانرێت خەرجی نوێ بکرێتەوە - بڕی پێویست لە کۆگا نەماوە',
            confirmButtonText: 'باشە'
        });
        return;
    }

    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key]);
    }
    // Add gas_liters if present in the form
    if (document.getElementById('edit_gas_liters')) {
        formData.append('gas_liters', document.getElementById('edit_gas_liters').value);
    }
    // Add new fields
    if (document.getElementById('edit_expense_type')) {
        formData.append('expense_type', document.getElementById('edit_expense_type').value);
    }
    if (document.getElementById('edit_material_id')) {
        formData.append('material_id', document.getElementById('edit_material_id').value);
    }
    if (document.getElementById('edit_material_quantity')) {
        formData.append('material_quantity', document.getElementById('edit_material_quantity').value);
    }
            if (document.getElementById('edit_usage_unit_type')) {
            const usageUnitType = document.getElementById('edit_usage_unit_type').value;
            // Always append usage_unit_type - empty string will be converted to null on server
            formData.append('usage_unit_type', usageUnitType || '');
        }
    if (document.getElementById('edit_material_purchase_price_iqd')) {
        formData.append('material_purchase_price_iqd', document.getElementById('edit_material_purchase_price_iqd').value);
    }
    if (document.getElementById('edit_material_purchase_price_usd')) {
        formData.append('material_purchase_price_usd', document.getElementById('edit_material_purchase_price_usd').value);
    }
    if (document.getElementById('edit_material_total_cost')) {
        formData.append('material_total_cost', document.getElementById('edit_material_total_cost').value);
    }
    if (document.getElementById('edit_gas_purchase_price_input')) {
        formData.append('gas_purchase_price_input', document.getElementById('edit_gas_purchase_price_input').value);
    }
    if (document.getElementById('edit_gas_total_cost')) {
        formData.append('gas_total_cost', document.getElementById('edit_gas_total_cost').value);
    }
    // Add payment_type
    if (document.getElementById('edit_payment_type')) {
        const paymentType = document.getElementById('edit_payment_type').value;
        if (paymentType && paymentType.trim() !== '') {
            formData.append('payment_type', paymentType);
        } else {
            formData.append('payment_type', 'نەقد'); // Default value
        }
    } else {
        formData.append('payment_type', 'نەقد'); // Default value
    }
    
    // Add currency_type
    if (document.getElementById('edit_currency_type')) {
        const currencyType = document.getElementById('edit_currency_type').value;
        if (currencyType && currencyType.trim() !== '') {
            formData.append('currency_type', currencyType);
        } else {
            formData.append('currency_type', 'دینار'); // Default value
        }
    } else {
        formData.append('currency_type', 'دینار'); // Default value
    }
    formData.append('id', id);
    
    try {
        console.log('Updating expense with ID:', id);
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        const res = await fetch('../process/other_expenses/update_expenses.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const result = await res.json();
        console.log('Response data:', result);
        
        if (result.success) {
            console.log('Expense updated successfully');
            Swal.fire('سەرکەوتوو!', 'خەرجیەکە نوێکرایەوە', 'success');
            if (typeof loadOtherExpenses === 'function') loadOtherExpenses();
        } else {
            console.error('Server returned error:', result.msg);
            Swal.fire('هەڵە!', result.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        console.error('Error updating expense:', err);
        console.error('Error details:', {
            message: err.message,
            stack: err.stack,
            name: err.name,
            expenseId: id
        });
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    }
}
window.editExpense = editExpense;

function setupEditExpenseModal() {
    const currencyType = document.getElementById('edit_currency_type');
    const amountIqd = document.getElementById('edit_amount_iqd');
    const amountUsd = document.getElementById('edit_amount_usd');
    const paidIqd = document.getElementById('edit_paid_iqd');
    const paidUsd = document.getElementById('edit_paid_usd');
    const remainingIqd = document.getElementById('edit_remaining_iqd');
    const remainingUsd = document.getElementById('edit_remaining_usd');
    const exchangeRate = document.getElementById('edit_exchange_rate');
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        function handleCurrencyChange() {
            if (currencyType.value === 'دینار') {
                amountUsd.value = 0;
                amountUsd.disabled = true;
                amountIqd.disabled = false;
            } else if (currencyType.value === 'دۆلار') {
                amountIqd.value = 0;
                amountIqd.disabled = true;
                amountUsd.disabled = false;
            } else {
                amountIqd.disabled = false;
                amountUsd.disabled = false;
            }
            if (remainingIqd) remainingIqd.value = 0;
            if (remainingUsd) remainingUsd.value = 0;
            updateRemaining();
        }
        function updateRemaining() {
            let paidIqdVal = parseFloat(paidIqd.value) || 0;
            let paidUsdVal = parseFloat(paidUsd.value) || 0;
            let exRate = parseFloat(exchangeRate.value) || 0;
            
            // Use the exchange rate from the input field
            if (exRate === 0) {
                // If exchange rate is not set, try to get it from the field
                const exchangeRateField = document.getElementById('edit_exchange_rate');
                if (exchangeRateField && exchangeRateField.value) {
                    exRate = parseFloat(exchangeRateField.value);
                }
            }
            if (currencyType.value === 'دینار') {
                if (paidUsdVal > 0) {
                    paidIqdVal += paidUsdVal * (exRate / 100);
                }
                remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
            } else if (currencyType.value === 'دۆلار') {
                if (paidIqd.value > 0) {
                    paidUsdVal += (parseFloat(paidIqd.value) || 0) / (exRate / 100);
                }
                remainingUsd.value = (parseFloat(amountUsd.value) || 0) - paidUsdVal;
            } else {
                if (remainingIqd) remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
                if (remainingUsd) remainingUsd.value = (parseFloat(amountUsd.value) || 0) - paidUsdVal;
            }
        }
        currencyType.addEventListener('change', handleCurrencyChange);
        [amountIqd, paidIqd, amountUsd, paidUsd, exchangeRate].forEach(input => {
            if (input) input.addEventListener('input', updateRemaining);
        });
        handleCurrencyChange();
    }
}

// Function to fetch and set USD exchange rate for edit modal
async function fetchAndSetUsdRateForEdit() {
    const editExchangeRateInput = document.getElementById('edit_exchange_rate');
    if (!editExchangeRateInput) return;
    
    try {
        // Try backend API first
        const response = await fetch('../process/other_expenses/get_usd_rate.php');
        
        if (response.ok) {
            const responseText = await response.text();
            if (responseText && responseText.trim() !== '') {
                const data = JSON.parse(responseText);
                
                if (data.success && data.rate) {
                    editExchangeRateInput.value = data.rate;
                    console.log('Exchange rate populated for edit from backend API:', data.rate);
                    return;
                }
            }
        }
        
        // Fallback to direct API
        console.log('Trying direct API for edit exchange rate...');
        const alternativeResponse = await fetch('https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk');
        const alternativeData = await alternativeResponse.json();
        
        if (alternativeData && alternativeData.value) {
            editExchangeRateInput.value = alternativeData.value;
            console.log('Exchange rate populated for edit from direct API:', alternativeData.value);
            return;
        }
        
        // Use default value
        const defaultRate = 139250;
        editExchangeRateInput.value = defaultRate;
        console.log('Using default exchange rate for edit:', defaultRate);
        
    } catch (error) {
        console.error('Error fetching exchange rate for edit modal:', error);
        
        // Use default value on error
        const defaultRate = 139250;
        editExchangeRateInput.value = defaultRate;
        console.log('Using default exchange rate for edit due to error:', defaultRate);
    }
}

// Setup when edit modal is shown
const editExpenseModal = document.getElementById('editExpenseModal');
if (editExpenseModal) {
    editExpenseModal.addEventListener('show.bs.modal', function() {
        setupEditExpenseModal();
        // Fetch and set USD exchange rate when edit modal opens
        fetchAndSetUsdRateForEdit();
    });
}

// Submit handler for edit form
let submittingEditExpense = false;
const editExpenseForm = document.getElementById('editExpenseForm');
if (editExpenseForm) {
    editExpenseForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (submittingEditExpense) {
            Swal.fire('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        // Get the expense ID
        const editId = document.getElementById('edit_id').value;
        if (!editId) {
            Swal.fire('هەڵە!', 'ناتوانرێت دەستکاری بکرێت - ID نەدۆزرایەوە', 'error');
            return;
        }
        
        // Set submitting flag and disable submit button
        submittingEditExpense = true;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
        }
        
        // Collect form data
        const formData = new FormData(editExpenseForm);
        
        // Call editExpense function
        try {
            await editExpense(editId, {
                purpose: document.getElementById('edit_purpose').value,
                employee_id: document.getElementById('edit_employee_id').value,
                car_id: document.getElementById('edit_car_id').value,
                person_id: document.getElementById('edit_person_id').value,
                payment_type: document.getElementById('edit_payment_type').value,
                currency_type: document.getElementById('edit_currency_type').value,
                invoice_number: document.getElementById('edit_invoice_number').value,
                amount_iqd: document.getElementById('edit_amount_iqd').value,
                amount_usd: document.getElementById('edit_amount_usd').value,
                paid_iqd: document.getElementById('edit_paid_iqd').value,
                paid_usd: document.getElementById('edit_paid_usd').value,
                exchange_rate: document.getElementById('edit_exchange_rate').value,
                remaining_iqd: document.getElementById('edit_remaining_iqd').value,
                remaining_usd: document.getElementById('edit_remaining_usd').value,
                date: document.getElementById('edit_date').value
            });
            
            // Close the modal after successful update
            const modal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
            if (modal) {
                modal.hide();
            }
        } catch (err) {
            console.error('Error in edit form submission:', err);
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        } finally {
            // Reset submitting flag and restore submit button
            submittingEditExpense = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    });
}
