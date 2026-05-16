async function editExpense(id, data) {


    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key]);
    }
    // Add expense_type
    if (document.getElementById('edit_expense_type')) {
        formData.append('expense_type', document.getElementById('edit_expense_type').value);
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

    if (typeof validateOtherExpenseCurrencyAmounts === 'function') {
        const currencyCheck = validateOtherExpenseCurrencyAmounts('edit');
        if (!currencyCheck.ok) {
            Swal.fire('هەڵە!', currencyCheck.msg, 'error');
            return;
        }
    }

    // --- VALIDATION START ---
    const paymentTypeVal = formData.get('payment_type');
    const remIqd = parseFloat(formData.get('remaining_iqd') || 0);
    const remUsd = parseFloat(formData.get('remaining_usd') || 0);
    const totalRem = remIqd + remUsd;

    if (paymentTypeVal === 'قەرز') {
        if (totalRem == 0) {
            Swal.fire('هەڵە!', 'بۆ مامەڵەی قەرز، نابێت پارەی ماوە سفر بێت!', 'error');
            return;
        }
    } else if (paymentTypeVal === 'نەقد') {
        if (totalRem > 0) {
            Swal.fire('هەڵە!', 'بۆ مامەڵەی نەقد، نابێت هیچ پارەیەک بمێنێتەوە (پارەی ماوە دەبێت 0 بێت)!', 'error');
            return;
        }
    }
    // --- VALIDATION END ---
    formData.append('id', id);

    try {
        /* console.log('Updating expense with ID:', id);
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        } */

        const res = await fetch('../process/other_expenses/update_expenses.php', {
            method: 'POST',
            body: formData
        });

        // console.log('Response status:', res.status);
        // console.log('Response headers:', res.headers);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const result = await res.json();
        // console.log('Response data:', result);

        if (result.success) {
            // console.log('Expense updated successfully');
            Swal.fire('سەرکەوتوو!', 'خەرجیەکە نوێکرایەوە', 'success');
            // Get the updated expense ID to restore position
            const updatedId = id;
            if (typeof reloadOtherExpenses === 'function') {
                reloadOtherExpenses(updatedId);
            } else if (typeof loadOtherExpenses === 'function') {
                loadOtherExpenses();
            }
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
            if (typeof applyOtherExpenseCurrencyFields === 'function') {
                applyOtherExpenseCurrencyFields('edit');
            }
        }
        window.updateEditRemaining = function () {
            const paymentType = document.getElementById('edit_payment_type');
            if (paymentType && paymentType.value === 'نەقد') {
                if (!amountIqd.disabled) {
                    paidIqd.value = amountIqd.value;
                }
                if (!amountUsd.disabled) {
                    paidUsd.value = amountUsd.value;
                }
            }

            const amountIqdVal = parseFloat(amountIqd.value) || 0;
            const amountUsdVal = parseFloat(amountUsd.value) || 0;
            const paidIqdVal = parseFloat(paidIqd.value) || 0;
            const paidUsdVal = parseFloat(paidUsd.value) || 0;

            if (currencyType.value === 'دینار') {
                remainingIqd.value = (amountIqdVal - paidIqdVal).toFixed(0);
                remainingUsd.value = '0';
            } else if (currencyType.value === 'دۆلار') {
                remainingUsd.value = (amountUsdVal - paidUsdVal).toFixed(2);
                remainingIqd.value = '0';
            } else if (currencyType.value === 'تێکەڵ') {
                remainingIqd.value = (amountIqdVal - paidIqdVal).toFixed(0);
                remainingUsd.value = (amountUsdVal - paidUsdVal).toFixed(2);
            } else {
                remainingIqd.value = (amountIqdVal - paidIqdVal).toFixed(0);
                remainingUsd.value = (amountUsdVal - paidUsdVal).toFixed(2);
            }
        };
        const updateRemaining = window.updateEditRemaining;

        if (currencyType._oeCurrencyHandler) {
            currencyType.removeEventListener('change', currencyType._oeCurrencyHandler);
        }
        currencyType._oeCurrencyHandler = handleCurrencyChange;
        currencyType.addEventListener('change', handleCurrencyChange);

        const paymentTypeSelect = document.getElementById('edit_payment_type');
        if (paymentTypeSelect) {
            paymentTypeSelect.addEventListener('change', updateRemaining);
        }

        [amountIqd, paidIqd, amountUsd, paidUsd, exchangeRate].forEach(function (input) {
            if (input) {
                input.addEventListener('input', updateRemaining);
            }
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
                    // console.log('Exchange rate populated for edit from backend API:', data.rate);
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
    editExpenseModal.addEventListener('show.bs.modal', function () {
        setupEditExpenseModal();
        // Fetch and set USD exchange rate when edit modal opens
        fetchAndSetUsdRateForEdit();
    });
}

// Submit handler for edit form
let submittingEditExpense = false;
const editExpenseForm = document.getElementById('editExpenseForm');
if (editExpenseForm) {
    editExpenseForm.addEventListener('submit', async function (e) {
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
