// Function to fetch and update USD exchange rate display
async function updateUsdRateDisplay() {
    const usdRateElement = document.getElementById('usdExchangeRate');
    const refreshBtn = document.getElementById('refreshUsdRate');
    const refreshIcon = refreshBtn ? refreshBtn.querySelector('i') : null;

    // Show loading state
    if (usdRateElement) {
        usdRateElement.textContent = 'جێبەجێکردن...';
    }
    if (refreshBtn && refreshIcon) {
        refreshIcon.classList.add('fa-spin');
        refreshBtn.disabled = true;
    }

    try {
        const response = await fetch('../process/other_expenses/get_usd_rate.php');

        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Check if response has content
        const responseText = await response.text();
        if (!responseText || responseText.trim() === '') {
            throw new Error('Empty response from server');
        }

        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parsing error:', jsonError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }

        if (usdRateElement) {
            if (data.success && data.rate) {
                usdRateElement.textContent = data.rate.toLocaleString() + ' د.ع';
                // console.log('USD rate display updated:', data.rate);

                // Show success notification
                Swal.fire({
                    icon: 'success',
                    title: 'نرخی دۆلار نوێکرایەوە',
                    text: `نرخی ١٠٠ دۆلار: ${data.rate.toLocaleString()} دینار`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                console.warn('Failed to fetch USD rate for display:', data.error || 'Unknown error');
                if (data.default_rate) {
                    usdRateElement.textContent = data.default_rate.toLocaleString() + ' د.ع';
                    // console.log('Using default USD rate for display:', data.default_rate);
                } else {
                    usdRateElement.textContent = '139250 د.ع';
                }
            }
        }
    } catch (error) {
        console.error('Error updating USD rate display:', error);

        // Try alternative API endpoint as fallback
        try {
            console.log('Trying alternative API endpoint...');
            const alternativeResponse = await fetch('https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk');
            const alternativeData = await alternativeResponse.json();

            if (alternativeData && alternativeData.value) {
                if (usdRateElement) {
                    usdRateElement.textContent = alternativeData.value.toLocaleString() + ' د.ع';
                    console.log('USD rate updated from alternative API:', alternativeData.value);
                }
                return; // Success, don't show error
            }
        } catch (fallbackError) {
            console.error('Alternative API also failed:', fallbackError);
        }

        // Use fallback value
        if (usdRateElement) {
            usdRateElement.textContent = '139250 د.ع';
            console.log('Using fallback USD rate for display: 139250');
        }

        // Show error notification only if it's a manual refresh
        if (refreshBtn && refreshBtn.disabled) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە لە وەرگرتنی نرخی دۆلار',
                text: 'نەتوانرا نرخی دۆلار وەربگیرێت',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    } finally {
        // Remove loading state
        if (refreshBtn && refreshIcon) {
            refreshIcon.classList.remove('fa-spin');
            refreshBtn.disabled = false;
        }
    }
}

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
    // Update USD rate display when page loads
    updateUsdRateDisplay();

    // Add refresh button event listener
    const refreshUsdRateBtn = document.getElementById('refreshUsdRate');
    if (refreshUsdRateBtn) {
        refreshUsdRateBtn.addEventListener('click', function () {
            updateUsdRateDisplay();
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





    // Add event listeners for gas total cost calculation
    const addGasLitersField = document.getElementById('gas_liters');
    const addGasPriceField = document.getElementById('gas_purchase_price_input');

    if (addGasLitersField) {
        addGasLitersField.addEventListener('input', function () {
            calculateGasTotalCost('add');
        });
    }
    if (addGasPriceField) {
        addGasPriceField.addEventListener('input', function () {
            calculateGasTotalCost('add');
        });
    }



    // Add event listeners for gas total cost calculation in edit form
    const editGasLitersField = document.getElementById('edit_gas_liters');
    const editGasPriceField = document.getElementById('edit_gas_purchase_price_input');

    if (editGasLitersField) {
        editGasLitersField.addEventListener('input', function () {
            calculateGasTotalCost('edit');
        });
    }
    if (editGasPriceField) {
        editGasPriceField.addEventListener('input', function () {
            calculateGasTotalCost('edit');
        });
    }



    // Function to populate gas purchase price from bins_silos
    window.populateGasPurchasePrice = function (formType) {
        try {
            console.log('populateGasPurchasePrice called with:', { formType });

            const prefix = formType === 'edit' ? 'edit_' : '';
            const gasPurchasePriceField = document.getElementById(prefix + 'gas_purchase_price_input');

            console.log('Looking for gas purchase price field:', prefix + 'gas_purchase_price_input');

            if (!gasPurchasePriceField) {
                console.warn('Gas purchase price field not found:', prefix + 'gas_purchase_price_input');
                return;
            }

            console.log('Gas purchase price field found, fetching average price...');

            // Fetch average gas price from bins_silos
            fetch('../process/other_expenses/get_gas_average_price.php')
                .then(response => {
                    console.log('Gas price response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Gas price data received:', data);
                    if (data.success) {
                        // Populate the gas purchase price field
                        const price = data.average_price.toFixed(2);
                        gasPurchasePriceField.value = price;
                        console.log('Populated gas purchase price field with:', price);

                        // Calculate gas total cost after populating price
                        console.log('Calling calculateGasTotalCost');
                        calculateGasTotalCost(formType);

                        // Show success message
                        showGasPriceMessage(formType, 'success', data.msg);
                    } else {
                        console.error('Error in gas price response:', data.msg);
                        // Show error message
                        showGasPriceMessage(formType, 'error', data.msg);
                    }
                })
                .catch(error => {
                    console.error('Error fetching gas price:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        formType
                    });
                    showGasPriceMessage(formType, 'error', 'هەڵە لە وەرگرتنی نرخی گاز');
                });
        } catch (error) {
            console.error('Unexpected error in populateGasPurchasePrice:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                formType
            });
        }
    }

    // Function to show gas price message
    window.showGasPriceMessage = function (formType, type, message) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const gasPurchasePriceField = document.getElementById(prefix + 'gas_purchase_price_input');

        if (!gasPurchasePriceField) return;

        // Remove any existing message
        clearGasPriceMessage(formType);

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `gas-price-message ${type === 'success' ? 'text-success' : 'text-danger'}`;
        messageDiv.style.fontSize = '12px';
        messageDiv.style.marginTop = '5px';
        messageDiv.textContent = message;

        // Add message after gas purchase price field
        gasPurchasePriceField.parentNode.appendChild(messageDiv);

        // Add visual feedback to field
        if (type === 'error') {
            gasPurchasePriceField.style.borderColor = '#dc3545';
        } else {
            gasPurchasePriceField.style.borderColor = '#198754';
        }
    }

    // Function to clear gas price message
    window.clearGasPriceMessage = function (formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const gasPurchasePriceField = document.getElementById(prefix + 'gas_purchase_price_input');

        if (!gasPurchasePriceField) return;

        // Remove existing message
        const existingMessage = gasPurchasePriceField.parentNode.querySelector('.gas-price-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Reset field border
        gasPurchasePriceField.style.borderColor = '';
    }

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
        } else if (expenseType === 'بەکارهێنانی کاڵای کۆگا') {
            // Show only material fields for "Warehouse material usage"
            gasMaterialFields.forEach(field => {
                const fieldId = field.querySelector('input, select')?.id || '';
                if (fieldId.includes('material_') || fieldId.includes('usage_unit_type')) {
                    field.style.display = 'block';
                    field.classList.add('show');
                } else {
                    field.style.display = 'none';
                    field.classList.remove('show');
                }
            });

        } else if (expenseType === 'بەکارهێنانی گاز') {
            // Show gas fields for "Gas usage"
            gasMaterialFields.forEach(field => {
                const fieldId = field.querySelector('input, select')?.id || '';
                if (fieldId.includes('gas_') || fieldId.includes('gas_liters')) {
                    field.style.display = 'block';
                    field.classList.add('show');
                } else {
                    field.style.display = 'none';
                    field.classList.remove('show');
                }
            });
            // Hide specified fields for gas usage
            const fieldsToHideForGas = [
                'person_id', 'payment_type', 'currency_type',
                'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate',
                'remaining_iqd', 'remaining_usd'
            ];
            fieldsToHideForGas.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.getElementById(prefix + fieldName);
                if (field) {
                    const container = field.closest('.warehouse-hidden-field') || field.closest('.gas-material-field');
                    if (container) {
                        container.classList.add('hide');
                    }
                }
            });

            // Populate gas purchase price from bins_silos
            populateGasPurchasePrice(formType);

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
    window.handleExpenseTypeChange = function (formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const expenseTypeSelect = document.getElementById(prefix + 'expense_type');

        if (!expenseTypeSelect) return;

        const expenseType = expenseTypeSelect.value;



        // Show/hide relevant fields based on expense type
        const materialFields = document.querySelectorAll(`[id^="${prefix}material_"]`);
        const gasFields = document.querySelectorAll(`[id^="${prefix}gas_"]`);


        } else if (expenseType === 'بەکارهێنانی گاز') {
            // Show gas fields, hide material fields
            gasFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'block';
                }
            });
        } else {
            // Hide gas fields for other expense types
            gasFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'none';
                }
            });
        }
    }

    // Add event listeners for expense type changes
    const addExpenseTypeSelect = document.getElementById('expense_type');
    const editExpenseTypeSelect = document.getElementById('edit_expense_type');

    if (addExpenseTypeSelect) {
        addExpenseTypeSelect.addEventListener('change', function () {
            handleExpenseTypeChange('add');
        });
    }

    if (editExpenseTypeSelect) {
        editExpenseTypeSelect.addEventListener('change', function () {
            handleExpenseTypeChange('edit');
        });
    }

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

    // Update USD rate display every 5 minutes
    setInterval(updateUsdRateDisplay, 5 * 60 * 1000);


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
