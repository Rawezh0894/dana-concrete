

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
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${yyyy}-${mm}-${dd}`;

            // Fetch and populate exchange rate
            fetchAndPopulateExchangeRate();
        });
    }
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        // Set default option for currency type
        currencyType.insertAdjacentHTML('afterbegin', '<option value="" selected hidden>-- هەلبژێرە --</option>');
        currencyType.value = '';
        function handleCurrencyChange() {
            // Never disable fields, let the user enter both if needed
            amountIqd.disabled = false;
            amountUsd.disabled = false;

            if (currencyType.value === 'دینار') {
                // Keep them enabled but maybe clear the other if empty
                // amountUsd.value = 0; // Don't auto-clear if it has value
            } else if (currencyType.value === 'دۆلار') {
                // amountIqd.value = 0;
            }
            // For 'تێکەڵ', both remain enabled (which they are anyway now)

            updateRemaining();
        }
        window.updateRemaining = function () {
            let amountIqdVal = parseFloat(amountIqd.value) || 0;
            let amountUsdVal = parseFloat(amountUsd.value) || 0;
            let paidIqdVal = parseFloat(paidIqd.value) || 0;
            let paidUsdVal = parseFloat(paidUsd.value) || 0;
            let exRate = parseFloat(exchangeRate.value) || 0;

            if (exRate === 0) {
                const exchangeRateField = document.getElementById('exchange_rate');
                if (exchangeRateField && exchangeRateField.value) {
                    exRate = parseFloat(exchangeRateField.value);
                }
            }

            if (currencyType.value === 'دینار') {
                // Convert everything to IQD for the primary balance check
                // but still allow entering both amounts
                let totalBillInIqd = amountIqdVal + (amountUsdVal * (exRate / 100));
                let totalPaidInIqd = paidIqdVal + (paidUsdVal * (exRate / 100));
                remainingIqd.value = (totalBillInIqd - totalPaidInIqd).toFixed(0);
                remainingUsd.value = 0;
            } else if (currencyType.value === 'دۆلار') {
                // Convert everything to USD for the primary balance check
                let totalBillInUsd = amountUsdVal + (amountIqdVal / (exRate / 100));
                let totalPaidInUsd = paidUsdVal + (paidIqdVal / (exRate / 100));
                remainingUsd.value = (totalBillInUsd - totalPaidInUsd).toFixed(2);
                remainingIqd.value = 0;
            } else if (currencyType.value === 'تێکەڵ') {
                // Calculate both independently
                remainingIqd.value = (amountIqdVal - paidIqdVal).toFixed(0);
                remainingUsd.value = (amountUsdVal - paidUsdVal).toFixed(2);
            } else {
                if (remainingIqd) remainingIqd.value = (amountIqdVal - paidIqdVal).toFixed(0);
                if (remainingUsd) remainingUsd.value = (amountUsdVal - paidUsdVal).toFixed(2);
            }
        }
        currencyType.addEventListener('change', handleCurrencyChange);
        [amountIqd, paidIqd, amountUsd, paidUsd, exchangeRate].forEach(input => {
            if (input) input.addEventListener('input', updateRemaining);
        });
        handleCurrencyChange();
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
    if (addExpenseModal) {
        addExpenseModal.addEventListener('show.bs.modal', function () {
            // Hide all gas and material fields initially
            toggleGasMaterialFields('', 'add');
        });
    }

    const editExpenseModal = document.getElementById('editExpenseModal');
    if (editExpenseModal) {
        editExpenseModal.addEventListener('show.bs.modal', function () {
            // Hide all gas and material fields initially
            toggleGasMaterialFields('', 'edit');
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
