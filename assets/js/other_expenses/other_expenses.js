/**
 * Currency field visibility / validation for add & edit other-expense modals.
 * formKey: 'add' | 'edit'
 */
function otherExpenseFieldIds(formKey) {
    const p = formKey === 'edit' ? 'edit_' : '';
    return {
        currency: p + 'currency_type',
        amountIqd: p + 'amount_iqd',
        amountUsd: p + 'amount_usd',
        paidIqd: p + 'paid_iqd',
        paidUsd: p + 'paid_usd',
        remainingIqd: p + 'remaining_iqd',
        remainingUsd: p + 'remaining_usd',
        exchangeRate: p + 'exchange_rate',
        paymentType: p + 'payment_type'
    };
}

function otherExpenseColFor(inputId) {
    const el = document.getElementById(inputId);
    return el ? el.closest('.col-md-4') : null;
}

function otherExpenseSetColVisible(col, visible) {
    if (!col) return;
    col.style.display = visible ? '' : 'none';
}

window.applyOtherExpenseCurrencyFields = function (formKey) {
    const ids = otherExpenseFieldIds(formKey);
    const currencyEl = document.getElementById(ids.currency);
    if (!currencyEl) return;

    const currency = currencyEl.value;
    const showIqd = currency === 'دینار' || currency === 'تێکەڵ';
    const showUsd = currency === 'دۆلار' || currency === 'تێکەڵ';
    const showExchange = currency === 'تێکەڵ';

    const iqdIds = [ids.amountIqd, ids.paidIqd, ids.remainingIqd];
    const usdIds = [ids.amountUsd, ids.paidUsd, ids.remainingUsd];

    iqdIds.forEach(function (id) {
        const input = document.getElementById(id);
        const col = otherExpenseColFor(id);
        otherExpenseSetColVisible(col, showIqd);
        if (input) {
            input.disabled = !showIqd;
            if (!showIqd) {
                input.value = '0';
            }
        }
    });

    usdIds.forEach(function (id) {
        const input = document.getElementById(id);
        const col = otherExpenseColFor(id);
        otherExpenseSetColVisible(col, showUsd);
        if (input) {
            input.disabled = !showUsd;
            if (!showUsd) {
                input.value = '0';
            }
        }
    });

    const exInput = document.getElementById(ids.exchangeRate);
    const exCol = otherExpenseColFor(ids.exchangeRate);
    otherExpenseSetColVisible(exCol, showExchange);
    if (exInput) {
        exInput.disabled = !showExchange;
    }

    if (formKey === 'add' && typeof window.updateRemaining === 'function') {
        window.updateRemaining();
    }
    if (formKey === 'edit' && typeof window.updateEditRemaining === 'function') {
        window.updateEditRemaining();
    }
};

window.validateOtherExpenseCurrencyAmounts = function (formKey) {
    const ids = otherExpenseFieldIds(formKey);
    const currency = (document.getElementById(ids.currency) || {}).value || 'دینار';
    const amountIqd = parseFloat((document.getElementById(ids.amountIqd) || {}).value) || 0;
    const amountUsd = parseFloat((document.getElementById(ids.amountUsd) || {}).value) || 0;
    const paidIqd = parseFloat((document.getElementById(ids.paidIqd) || {}).value) || 0;
    const paidUsd = parseFloat((document.getElementById(ids.paidUsd) || {}).value) || 0;

    if (currency === 'دینار') {
        if (amountIqd <= 0) {
            return { ok: false, msg: 'بۆ دینار، بڕی پارە بە دینار دەبێت گەورەتر بێت لە سفر.' };
        }
        if (paidIqd < 0) {
            return { ok: false, msg: 'پارەی دراو بە دینار نادروستە.' };
        }
    } else if (currency === 'دۆلار') {
        if (amountUsd <= 0) {
            return { ok: false, msg: 'بۆ دۆلار، بڕی پارە بە دۆلار دەبێت گەورەتر بێت لە سفر.' };
        }
        if (paidUsd < 0) {
            return { ok: false, msg: 'پارەی دراو بە دۆلار نادروستە.' };
        }
    } else if (currency === 'تێکەڵ') {
        if (amountIqd <= 0 && amountUsd <= 0) {
            return { ok: false, msg: 'بۆ تێکەڵ، لانیکەم یەک بڕ (دینار یان دۆلار) پێویستە.' };
        }
    }
    return { ok: true };
};

window.initOtherExpenseAddDefaults = function () {
    const expenseType = document.getElementById('expense_type');
    if (expenseType) {
        expenseType.value = 'خەرجی تر';
        if (typeof toggleGasMaterialFields === 'function') {
            toggleGasMaterialFields('خەرجی تر', 'add');
        }
    }
    const currencyType = document.getElementById('currency_type');
    if (currencyType && !currencyType.value) {
        currencyType.value = 'دینار';
    }
    applyOtherExpenseCurrencyFields('add');
};

// Function to fetch and populate exchange rate in modals
async function fetchAndPopulateExchangeRate() {
    const exchangeRateInput = document.getElementById('exchange_rate');
    const editExchangeRateInput = document.getElementById('edit_exchange_rate');

    if (!exchangeRateInput && !editExchangeRateInput) return;

    try {
        // Try backend API first
        const response = await fetch('../process/other_expenses/get_usd_rate.php');

        if (response.ok) {
            const responseText = await response.text();
            if (responseText && responseText.trim() !== '') {
                const data = JSON.parse(responseText);

                if (data.success && data.rate) {
                    const rate = data.rate;
                    if (exchangeRateInput) exchangeRateInput.value = rate;
                    if (editExchangeRateInput) editExchangeRateInput.value = rate;
                    console.log('Exchange rate populated from backend API:', rate);
                    return;
                }
            }
        }

        // Fallback to direct API
        console.log('Trying direct API for exchange rate...');
        const alternativeResponse = await fetch('https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk');
        const alternativeData = await alternativeResponse.json();

        if (alternativeData && alternativeData.value) {
            const rate = alternativeData.value;
            if (exchangeRateInput) exchangeRateInput.value = rate;
            if (editExchangeRateInput) editExchangeRateInput.value = rate;
            console.log('Exchange rate populated from direct API:', rate);
            return;
        }

        // Use default value
        const defaultRate = 139250;
        if (exchangeRateInput) exchangeRateInput.value = defaultRate;
        if (editExchangeRateInput) editExchangeRateInput.value = defaultRate;
        console.log('Using default exchange rate:', defaultRate);

    } catch (error) {
        console.error('Error fetching exchange rate for modal:', error);

        // Use default value on error
        const defaultRate = 139250;
        if (exchangeRateInput) exchangeRateInput.value = defaultRate;
        if (editExchangeRateInput) editExchangeRateInput.value = defaultRate;
        console.log('Using default exchange rate due to error:', defaultRate);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Delegated click handlers to ensure buttons work after any table re-render/pagination
    const table = document.getElementById('otherExpensesTable');
    if (table) {
        table.addEventListener('click', function (event) {
            const target = event.target.closest('button');
            if (!target) return;
            // Delete
            if (target.classList.contains('delete-expense')) {
                const id = target.dataset.id;
                console.log('Delegated delete clicked', { id });
                if (typeof deleteExpense === 'function') {
                    deleteExpense(id);
                } else {
                    console.error('deleteExpense is not available on window');
                }
                return;
            }
            // Edit
            if (target.classList.contains('edit-expense')) {
                const id = target.dataset.id;
                console.log('Delegated edit clicked', { id });
                if (typeof openEditModalById === 'function') {
                    openEditModalById(id);
                } else {
                    console.error('openEditModalById is not available on window');
                }
            }
        });
    }


    const currencyType = document.getElementById('currency_type');
    const amountIqd = document.getElementById('amount_iqd');
    const amountUsd = document.getElementById('amount_usd');
    const paidIqd = document.getElementById('paid_iqd');
    const paidUsd = document.getElementById('paid_usd');
    const remainingIqd = document.getElementById('remaining_iqd');
    const remainingUsd = document.getElementById('remaining_usd');
    const dateInput = document.getElementById('date');
    const exchangeRate = document.getElementById('exchange_rate');
    const addExpenseModal = document.getElementById('addExpenseModal');
    if (addExpenseModal && dateInput) {
        addExpenseModal.addEventListener('show.bs.modal', function () {
            if (typeof initOtherExpenseAddDefaults === 'function') {
                initOtherExpenseAddDefaults();
            }
            // Only set today's date if the field is empty
            if (!dateInput.value) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                dateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            // Only fetch and populate exchange rate if it's empty or 0
            const exRate = document.getElementById('exchange_rate');
            if (!exRate || !exRate.value || parseFloat(exRate.value) === 0) {
                fetchAndPopulateExchangeRate();
            }
        });
    }

    // Initialize date on page load if it's empty
    if (dateInput && !dateInput.value) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        function handleCurrencyChange() {
            applyOtherExpenseCurrencyFields('add');
        }
        window.updateRemaining = function () {
            const paymentType = document.getElementById('payment_type');
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
        currencyType.addEventListener('change', handleCurrencyChange);
        
        const paymentTypeSelect = document.getElementById('payment_type');
        if (paymentTypeSelect) {
            paymentTypeSelect.addEventListener('change', updateRemaining);
        }

        [amountIqd, paidIqd, amountUsd, paidUsd, exchangeRate].forEach(input => {
            if (input) input.addEventListener('input', updateRemaining);
        });
        handleCurrencyChange();
    }

    // Default expense type + currency fields on first load
    if (typeof initOtherExpenseAddDefaults === 'function') {
        initOtherExpenseAddDefaults();
    }

    // Handle expense type change for add form
    const expenseType = document.getElementById('expense_type');
    if (expenseType) {
        expenseType.addEventListener('change', function () {
            toggleGasMaterialFields(this.value, 'add');
        });
    }

    // Handle expense type change for edit form
    const editExpenseType = document.getElementById('edit_expense_type');
    if (editExpenseType) {
        editExpenseType.addEventListener('change', function () {
            toggleGasMaterialFields(this.value, 'edit');
        });
    }









    // Function to populate gas purchase price from bins_silos


    // Function to toggle gas and material fields visibility
    window.toggleGasMaterialFields = function (expenseType, formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const gasMaterialFields = document.querySelectorAll('.gas-material-field');

        // Get all form fields that should be hidden for warehouse material usage
        const fieldsToHideForWarehouse = [
            'person_id', 'payment_type', 'currency_type',
            'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate',
            'remaining_iqd', 'remaining_usd'
        ];

        if (expenseType === 'خەرجی تر') {
            // Hide gas and material fields for "Other expenses"
            gasMaterialFields.forEach(field => {
                field.style.display = 'none';
                field.classList.remove('show');
            });
            // Show all other fields
            fieldsToHideForWarehouse.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.getElementById(prefix + fieldName);
                if (field) {
                    const container = field.closest('.warehouse-hidden-field');
                    if (container) {
                        container.classList.remove('hide');
                    }
                }
            });

            // Clear gas price messages
            clearGasPriceMessage(formType);



        } else if (expenseType === 'خواردنگە' || expenseType === 'ئۆفیس') {
            // Show all fields for "خواردنگە" and "ئۆفیس" (same as "خەرجی تر")
            gasMaterialFields.forEach(field => {
                field.style.display = 'none';
                field.classList.remove('show');
            });
            // Show all other fields
            fieldsToHideForWarehouse.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.getElementById(prefix + fieldName);
                if (field) {
                    const container = field.closest('.warehouse-hidden-field');
                    if (container) {
                        container.classList.remove('hide');
                    }
                }
            });

            // Clear gas price messages
            clearGasPriceMessage(formType);
        } else {
            // Hide all gas and material fields for empty or other selections
            gasMaterialFields.forEach(field => {
                field.style.display = 'none';
                field.classList.remove('show');
            });
            // Show all other fields
            fieldsToHideForWarehouse.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.getElementById(prefix + fieldName);
                if (field) {
                    const container = field.closest('.warehouse-hidden-field');
                    if (container) {
                        container.classList.remove('hide');
                    }
                }
            });

            // Clear gas price messages
            clearGasPriceMessage(formType);
        }
        // Function to toggle car field visibility
        function toggleCarField(expenseType, formType) {
            const carField = document.getElementById((formType === 'edit' ? 'edit_' : '') + 'car_id');
            if (carField) {
                const container = carField.closest('.col-md-3, .col-md-4');
                if (expenseType === 'خواردنگە' || expenseType === 'ئۆفیس') {
                    if (container) container.style.display = 'none';
                } else {
                    if (container) container.style.display = '';
                }
            }
        }
        toggleCarField(expenseType, formType);
    }

    // Function to handle expense type changes




    // Initialize field visibility when modals are shown
    /*
    if (addExpenseModal) {
        addExpenseModal.addEventListener('show.bs.modal', function () {
            // This was causing resets, handled by initial form state now
            // toggleGasMaterialFields('', 'add');
        });
    }
    */

    const editExpenseModal = document.getElementById('editExpenseModal');
    if (editExpenseModal) {
        editExpenseModal.addEventListener('show.bs.modal', function () {
            // For edit modal, we want to reset based on current selection which happens in openEditModalById
        });
    }




});

// Function to generate a unique invoice number based on timestamp
function generateUniqueInvoiceNumber() {
    const now = new Date();
    const timestamp = now.getTime().toString().slice(-8); // Get last 8 digits of timestamp
    const random = Math.floor(Math.random() * 100); // Add 2 random digits
    return 'INV-' + timestamp + random;
}

// Add event listeners for the generate invoice buttons
document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.getElementById('generateInvoiceBtn');
    const editGenerateBtn = document.getElementById('editGenerateInvoiceBtn');

    if (generateBtn) {
        generateBtn.addEventListener('click', function () {
            const invoiceInput = document.getElementById('invoice_number');
            if (invoiceInput) {
                invoiceInput.value = generateUniqueInvoiceNumber();
            }
        });
    }

    if (editGenerateBtn) {
        editGenerateBtn.addEventListener('click', function () {
            const invoiceInput = document.getElementById('edit_invoice_number');
            if (invoiceInput) {
                invoiceInput.value = generateUniqueInvoiceNumber();
            }
        });
    }
});

// Export functions are now in separate file: export_functions.js
