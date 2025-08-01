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
                console.log('USD rate display updated:', data.rate);
                
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
                    console.log('Using default USD rate for display:', data.default_rate);
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

document.addEventListener('DOMContentLoaded', function() {
    // Update USD rate display when page loads
    updateUsdRateDisplay();
    
    // Add refresh button event listener
    const refreshUsdRateBtn = document.getElementById('refreshUsdRate');
    if (refreshUsdRateBtn) {
        refreshUsdRateBtn.addEventListener('click', function() {
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
        });
    }
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        // Set default option for currency type
        currencyType.insertAdjacentHTML('afterbegin', '<option value="" selected hidden>-- هەلبژێرە --</option>');
        currencyType.value = '';
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
            if (currencyType.value === 'دینار') {
                // If paid_usd entered, convert to IQD and add to paid_iqd
                if (paidUsdVal > 0) {
                    paidIqdVal += paidUsdVal * (exRate / 100);
                }
                remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
            } else if (currencyType.value === 'دۆلار') {
                // If paid_iqd entered, convert to USD and add to paid_usd
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

    // Handle expense type change for add form
    const expenseType = document.getElementById('expense_type');
    if (expenseType) {
        expenseType.addEventListener('change', function() {
            toggleGasMaterialFields(this.value, 'add');
        });
    }

    // Handle expense type change for edit form
    const editExpenseType = document.getElementById('edit_expense_type');
    if (editExpenseType) {
        editExpenseType.addEventListener('change', function() {
            toggleGasMaterialFields(this.value, 'edit');
        });
    }

    // Handle material selection for add form
    const addMaterialSelect = document.getElementById('material_id');
    if (addMaterialSelect) {
        addMaterialSelect.addEventListener('change', function() {
            populateMaterialPrices(this.value, 'add');
        });
    }

    // Handle material selection for edit form
    const editMaterialSelect = document.getElementById('edit_material_id');
    if (editMaterialSelect) {
        editMaterialSelect.addEventListener('change', function() {
            populateMaterialPrices(this.value, 'edit');
        });
    }

    // Add event listeners for automatic total cost calculation
    const addQuantityField = document.getElementById('material_quantity');
    const addIqdPriceField = document.getElementById('material_purchase_price_iqd');
    const addUsdPriceField = document.getElementById('material_purchase_price_usd');
    
    if (addQuantityField) {
        addQuantityField.addEventListener('input', function() {
            calculateMaterialTotalCost('add');
            // Check material availability when quantity changes
            checkMaterialAvailability('add');
        });
    }
    if (addIqdPriceField) {
        addIqdPriceField.addEventListener('input', function() {
            calculateMaterialTotalCost('add');
        });
    }
    if (addUsdPriceField) {
        addUsdPriceField.addEventListener('input', function() {
            calculateMaterialTotalCost('add');
        });
    }

    // Add event listeners for gas total cost calculation
    const addGasLitersField = document.getElementById('gas_liters');
    const addGasPriceField = document.getElementById('gas_purchase_price_input');
    
    if (addGasLitersField) {
        addGasLitersField.addEventListener('input', function() {
            calculateGasTotalCost('add');
        });
    }
    if (addGasPriceField) {
        addGasPriceField.addEventListener('input', function() {
            calculateGasTotalCost('add');
        });
    }

    // Add event listeners for edit form
    const editQuantityField = document.getElementById('edit_material_quantity');
    const editIqdPriceField = document.getElementById('edit_material_purchase_price_iqd');
    const editUsdPriceField = document.getElementById('edit_material_purchase_price_usd');
    
    if (editQuantityField) {
        editQuantityField.addEventListener('input', function() {
            calculateMaterialTotalCost('edit');
            // Check material availability when quantity changes
            checkMaterialAvailability('edit');
        });
    }
    if (editIqdPriceField) {
        editIqdPriceField.addEventListener('input', function() {
            calculateMaterialTotalCost('edit');
        });
    }
    if (editUsdPriceField) {
        editUsdPriceField.addEventListener('input', function() {
            calculateMaterialTotalCost('edit');
        });
    }

    // Add event listeners for gas total cost calculation in edit form
    const editGasLitersField = document.getElementById('edit_gas_liters');
    const editGasPriceField = document.getElementById('edit_gas_purchase_price_input');
    
    if (editGasLitersField) {
        editGasLitersField.addEventListener('input', function() {
            calculateGasTotalCost('edit');
        });
    }
    if (editGasPriceField) {
        editGasPriceField.addEventListener('input', function() {
            calculateGasTotalCost('edit');
        });
    }

    // Function to populate material purchase prices
    window.populateMaterialPrices = function(materialId, formType) {
        try {
            console.log('populateMaterialPrices called with:', { materialId, formType });
            
            if (!materialId) {
                console.log('No material ID provided, returning early');
                return;
            }

            const prefix = formType === 'edit' ? 'edit_' : '';
            console.log('Using prefix:', prefix);
            
            fetch(`../process/other_expenses/get_material_details.php?material_id=${materialId}`)
                .then(response => {
                    console.log('Material details response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Material details data received:', data);
                    if (data.success) {
                        const material = data.data;
                        console.log('Processing material:', material);
                        
                        // Populate price fields based on currency type
                        if (material.currency_type === 'دۆلار') {
                            // If material currency is USD, populate USD field
                            const usdField = document.getElementById(prefix + 'material_purchase_price_usd');
                            if (usdField) {
                                usdField.value = material.purchase_price_usd || '';
                                console.log('Populated USD field with:', material.purchase_price_usd);
                            } else {
                                console.warn('USD field not found:', prefix + 'material_purchase_price_usd');
                            }
                            // Clear IQD field
                            const iqdField = document.getElementById(prefix + 'material_purchase_price_iqd');
                            if (iqdField) {
                                iqdField.value = '';
                                console.log('Cleared IQD field');
                            }
                        } else {
                            // If material currency is IQD, populate IQD field
                            const iqdField = document.getElementById(prefix + 'material_purchase_price_iqd');
                            if (iqdField) {
                                iqdField.value = material.purchase_price_iqd || '';
                                console.log('Populated IQD field with:', material.purchase_price_iqd);
                            } else {
                                console.warn('IQD field not found:', prefix + 'material_purchase_price_iqd');
                            }
                            // Clear USD field
                            const usdField = document.getElementById(prefix + 'material_purchase_price_usd');
                            if (usdField) {
                                usdField.value = '';
                                console.log('Cleared USD field');
                            }
                        }
                        
                        // Calculate total cost after populating prices
                        console.log('Calling calculateMaterialTotalCost');
                        calculateMaterialTotalCost(formType);
                    } else {
                        console.error('Error fetching material details:', data.msg);
                    }
                })
                .catch(error => {
                    console.error('Error in populateMaterialPrices:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        materialId,
                        formType
                    });
                });
        } catch (error) {
            console.error('Unexpected error in populateMaterialPrices:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                materialId,
                formType
            });
        }
    }

    // Function to calculate material total cost
    window.calculateMaterialTotalCost = function(formType) {
        try {
            console.log('calculateMaterialTotalCost called with:', { formType });
            
            const prefix = formType === 'edit' ? 'edit_' : '';
            
            const quantityField = document.getElementById(prefix + 'material_quantity');
            const iqdPriceField = document.getElementById(prefix + 'material_purchase_price_iqd');
            const usdPriceField = document.getElementById(prefix + 'material_purchase_price_usd');
            const totalCostField = document.getElementById(prefix + 'material_total_cost');
            
            console.log('Fields found:', {
                quantityField: !!quantityField,
                iqdPriceField: !!iqdPriceField,
                usdPriceField: !!usdPriceField,
                totalCostField: !!totalCostField
            });
            
            if (!quantityField || !totalCostField) {
                console.warn('Required fields not found for material total cost calculation');
                return;
            }
            
            const quantity = parseFloat(quantityField.value) || 0;
            const iqdPrice = parseFloat(iqdPriceField?.value) || 0;
            const usdPrice = parseFloat(usdPriceField?.value) || 0;
            
            console.log('Values:', { quantity, iqdPrice, usdPrice });
            
            // Use whichever price is available (IQD or USD)
            const price = iqdPrice > 0 ? iqdPrice : usdPrice;
            
            // Calculate total cost: quantity × price
            const totalCost = quantity * price;
            
            console.log('Calculation:', { quantity, price, totalCost });
            
            // Update the total cost field
            totalCostField.value = totalCost.toFixed(2);
            console.log('Updated material total cost field with:', totalCost.toFixed(2));
        } catch (error) {
            console.error('Error in calculateMaterialTotalCost:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                formType
            });
        }
    }

    // Function to calculate gas total cost
    window.calculateGasTotalCost = function(formType) {
        try {
            console.log('calculateGasTotalCost called with:', { formType });
            
            const prefix = formType === 'edit' ? 'edit_' : '';
            
            const gasLitersField = document.getElementById(prefix + 'gas_liters');
            const gasPriceField = document.getElementById(prefix + 'gas_purchase_price_input');
            const gasTotalCostField = document.getElementById(prefix + 'gas_total_cost');
            
            console.log('Fields found:', {
                gasLitersField: !!gasLitersField,
                gasPriceField: !!gasPriceField,
                gasTotalCostField: !!gasTotalCostField
            });
            
            if (!gasLitersField || !gasTotalCostField) {
                console.warn('Required fields not found for gas total cost calculation');
                return;
            }
            
            const gasLiters = parseFloat(gasLitersField.value) || 0;
            const gasPrice = parseFloat(gasPriceField?.value) || 0;
            
            console.log('Values:', { gasLiters, gasPrice });
            
            // Calculate total cost: gas liters × gas price
            const totalCost = gasLiters * gasPrice;
            
            console.log('Calculation:', { gasLiters, gasPrice, totalCost });
            
            // Update the total cost field
            gasTotalCostField.value = totalCost.toFixed(2);
            console.log('Updated gas total cost field with:', totalCost.toFixed(2));
        } catch (error) {
            console.error('Error in calculateGasTotalCost:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                formType
            });
        }
    }

    // Function to check material availability
    window.checkMaterialAvailability = function(formType) {
        try {
            console.log('checkMaterialAvailability called with:', { formType });
            
            const prefix = formType === 'edit' ? 'edit_' : '';
            
            const materialSelect = document.getElementById(prefix + 'material_id');
            const quantityField = document.getElementById(prefix + 'material_quantity');
            
            console.log('Fields found:', {
                materialSelect: !!materialSelect,
                quantityField: !!quantityField
            });
            
            if (!materialSelect || !quantityField) {
                console.warn('Required fields not found for material availability check');
                return;
            }
            
            const materialId = materialSelect.value;
            const quantity = parseFloat(quantityField.value) || 0;
            
            console.log('Values:', { materialId, quantity });
            
            // Only check if both material and quantity are provided
            if (!materialId || quantity <= 0) {
                console.log('Material ID or quantity not provided, clearing messages');
                // Clear any previous error messages
                clearMaterialAvailabilityMessage(formType);
                return;
            }
            
            console.log('Checking material availability for:', { materialId, quantity });
            
            // Check availability via AJAX
            fetch(`../process/other_expenses/check_material_availability.php?material_id=${materialId}&required_quantity=${quantity}`)
                .then(response => {
                    console.log('Material availability response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Material availability data received:', data);
                    if (data.success) {
                        // Material is available
                        console.log('Material is available:', data.msg);
                        showMaterialAvailabilityMessage(formType, 'success', data.msg);
                    } else {
                        // Material is not available
                        console.log('Material is not available:', data.msg);
                        showMaterialAvailabilityMessage(formType, 'error', data.msg);
                    }
                })
                .catch(error => {
                    console.error('Error checking material availability:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        materialId,
                        quantity,
                        formType
                    });
                    showMaterialAvailabilityMessage(formType, 'error', 'هەڵە لە پشکنینی بەردەستبوونی کاڵا');
                });
        } catch (error) {
            console.error('Unexpected error in checkMaterialAvailability:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                formType
            });
        }
    }

    // Function to show material availability message
    window.showMaterialAvailabilityMessage = function(formType, type, message) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const quantityField = document.getElementById(prefix + 'material_quantity');
        
        if (!quantityField) return;
        
        // Remove any existing message
        clearMaterialAvailabilityMessage(formType);
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `material-availability-message ${type === 'success' ? 'text-success' : 'text-danger'}`;
        messageDiv.style.fontSize = '12px';
        messageDiv.style.marginTop = '5px';
        messageDiv.textContent = message;
        
        // Add message after quantity field
        quantityField.parentNode.appendChild(messageDiv);
        
        // Add visual feedback to quantity field
        if (type === 'error') {
            quantityField.style.borderColor = '#dc3545';
        } else {
            quantityField.style.borderColor = '#198754';
        }
    }

    // Function to clear material availability message
    window.clearMaterialAvailabilityMessage = function(formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const quantityField = document.getElementById(prefix + 'material_quantity');
        
        if (!quantityField) return;
        
        // Remove existing message
        const existingMessage = quantityField.parentNode.querySelector('.material-availability-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Reset field border
        quantityField.style.borderColor = '';
    }

    // Function to populate gas purchase price from bins_silos
    window.populateGasPurchasePrice = function(formType) {
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
    window.showGasPriceMessage = function(formType, type, message) {
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
    window.clearGasPriceMessage = function(formType) {
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
    window.toggleGasMaterialFields = function(expenseType, formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const gasMaterialFields = document.querySelectorAll('.gas-material-field');
        
        // Get all form fields that should be hidden for warehouse material usage
        const fieldsToHideForWarehouse = [
            'person_id', 'payment_type', 'currency_type', 'invoice_number',
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
                if (fieldId.includes('material_')) {
                    field.style.display = 'block';
                    field.classList.add('show');
                } else {
                    field.style.display = 'none';
                    field.classList.remove('show');
                }
            });
            // Hide specified fields for warehouse material usage
            fieldsToHideForWarehouse.forEach(fieldName => {
                const field = document.getElementById(fieldName) || document.getElementById(prefix + fieldName);
                if (field) {
                    const container = field.closest('.warehouse-hidden-field');
                    if (container) {
                        container.classList.add('hide');
                    }
                }
            });
        } else if (expenseType === 'بەکارهێنانی گاز') {
            // Show gas fields for "Gas usage"
            gasMaterialFields.forEach(field => {
                const fieldId = field.querySelector('input, select')?.id || '';
                if (fieldId.includes('gas_') || fieldId.includes('gas_liters')) {
                    field.style.display = 'block';
                    field.classList.add('show');
                } else if (fieldId.includes('material_')) {
                    field.style.display = 'none';
                    field.classList.remove('show');
                }
            });
            // Hide specified fields for gas usage
            const fieldsToHideForGas = [
                'person_id', 'payment_type', 'currency_type', 'invoice_number',
                'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate',
                'remaining_iqd', 'remaining_usd', 'material_id', 'material_quantity',
                'material_purchase_price_iqd', 'material_purchase_price_usd', 'material_total_cost'
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

    // Initialize field visibility when modals are shown
    if (addExpenseModal) {
        addExpenseModal.addEventListener('show.bs.modal', function() {
            // Hide all gas and material fields initially
            toggleGasMaterialFields('', 'add');
            // Populate material dropdown
            populateSelect('../process/other_expenses/select_materials.php', 'material_id');
        });
    }

    const editExpenseModal = document.getElementById('editExpenseModal');
    if (editExpenseModal) {
        editExpenseModal.addEventListener('show.bs.modal', function() {
            // Hide all gas and material fields initially
            toggleGasMaterialFields('', 'edit');
            // Populate material dropdown
            populateSelect('../process/other_expenses/select_materials.php', 'edit_material_id');
        });
    }
    
    // Update USD rate display every 5 minutes
    setInterval(updateUsdRateDisplay, 5 * 60 * 1000);
});
