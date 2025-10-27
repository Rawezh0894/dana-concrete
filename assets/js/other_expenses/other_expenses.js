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

document.addEventListener('DOMContentLoaded', function() {
    // Delegated click handlers to ensure buttons work after any table re-render/pagination
    const table = document.getElementById('otherExpensesTable');
    if (table) {
        table.addEventListener('click', function(event) {
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
            
            // Fetch and populate exchange rate
            fetchAndPopulateExchangeRate();
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
            
            // Use the exchange rate from the input field
            if (exRate === 0) {
                // If exchange rate is not set, try to get it from the field
                const exchangeRateField = document.getElementById('exchange_rate');
                if (exchangeRateField && exchangeRateField.value) {
                    exRate = parseFloat(exchangeRateField.value);
                }
            }
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

    // Handle expense type change for add form (works with Select2)
    $(document).on('change', '#expense_type', function() {
        toggleGasMaterialFields(this.value, 'add');
    });

    // Handle expense type change for edit form (works with Select2)
    $(document).on('change', '#edit_expense_type', function() {
        toggleGasMaterialFields(this.value, 'edit');
    });

    // Add event listeners for material selection
    const addMaterialSelect = document.getElementById('material_id');
    const editMaterialSelect = document.getElementById('edit_material_id');
    
    if (addMaterialSelect) {
        addMaterialSelect.addEventListener('change', function() {
            const materialId = this.value;
            if (materialId) {
                populateMaterialPrices(materialId, 'add');
                // Clear any existing quantity and recalculate
                const quantityField = document.getElementById('material_quantity');
                if (quantityField) {
                    quantityField.value = '';
                    clearMaterialAvailabilityMessage('add');
                    clearMaterialUnitInfo('add');
                    clearBaseQuantityDisplay('add');
                }
                // Clear usage unit selection when material changes
                const usageUnitField = document.getElementById('usage_unit_type');
                if (usageUnitField) {
                    usageUnitField.value = '';
                }
                // Clear material unit info
                clearMaterialUnitInfo('add');
            } else {
                clearMaterialAvailabilityMessage('add');
                clearMaterialUnitInfo('add');
                clearBaseQuantityDisplay('add');
                // Clear usage unit and reset prices when material is cleared
                const usageUnitField = document.getElementById('usage_unit_type');
                if (usageUnitField) {
                    usageUnitField.value = '';
                    usageUnitField.innerHTML = '<option value="">یەکەی بەکارهێنان هەڵبژێرە</option>';
                }
                // Clear material unit info
                clearMaterialUnitInfo('add');
                // Clear price fields
                const iqdPriceField = document.getElementById('material_purchase_price_iqd');
                const usdPriceField = document.getElementById('material_purchase_price_usd');
                const totalCostField = document.getElementById('material_total_cost');
                if (iqdPriceField) iqdPriceField.value = '';
                if (usdPriceField) usdPriceField.value = '';
                if (totalCostField) {
                    totalCostField.value = '';
                    // Reset placeholder
                    if (totalCostField.hasAttribute('data-original-placeholder')) {
                        totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
                    }
                }
            }
        });
    }
    
    if (editMaterialSelect) {
        editMaterialSelect.addEventListener('change', function() {
            const materialId = this.value;
            if (materialId) {
                populateMaterialPrices(materialId, 'edit');
                // Clear any existing quantity and recalculate
                const quantityField = document.getElementById('edit_material_quantity');
                if (quantityField) {
                    quantityField.value = '';
                    clearMaterialAvailabilityMessage('edit');
                    clearMaterialUnitInfo('edit');
                    clearBaseQuantityDisplay('edit');
                }
                // Clear usage unit selection when material changes
                const usageUnitField = document.getElementById('edit_usage_unit_type');
                if (usageUnitField) {
                    usageUnitField.value = '';
                }
                // Clear material unit info
                clearMaterialUnitInfo('edit');
            } else {
                clearMaterialAvailabilityMessage('edit');
                clearMaterialUnitInfo('edit');
                clearBaseQuantityDisplay('edit');
                // Clear usage unit and reset prices when material is cleared
                const usageUnitField = document.getElementById('edit_usage_unit_type');
                if (usageUnitField) {
                    usageUnitField.value = '';
                    usageUnitField.innerHTML = '<option value="">یەکەی بەکارهێنان هەڵبژێرە</option>';
                }
                // Clear material unit info
                clearMaterialUnitInfo('edit');
                // Clear price fields
                const iqdPriceField = document.getElementById('edit_material_purchase_price_iqd');
                const usdPriceField = document.getElementById('edit_material_purchase_price_usd');
                const totalCostField = document.getElementById('edit_material_total_cost');
                if (iqdPriceField) iqdPriceField.value = '';
                if (usdPriceField) usdPriceField.value = '';
                if (totalCostField) {
                    totalCostField.value = '';
                    // Reset placeholder
                    if (totalCostField.hasAttribute('data-original-placeholder')) {
                        totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
                    }
                }
            }
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
            // Calculate and display base quantity
            calculateAndDisplayBaseQuantity('add');
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
            // Calculate and display base quantity
            calculateAndDisplayBaseQuantity('edit');
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
                        
                        // Display material unit information
                        displayMaterialUnitInfo(material, formType);
                        
                        // Populate usage unit type options
                        populateUsageUnitOptions(material, formType);
                        
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
                        
                        // Update prices based on current usage unit if selected
                        const usageUnitField = document.getElementById(prefix + 'usage_unit_type');
                        if (usageUnitField && usageUnitField.value) {
                            console.log('Usage unit already selected, updating prices');
                            updateMaterialPricesByUsageUnit(formType);
                        }
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

    // Function to populate usage unit type options
    window.populateUsageUnitOptions = function(material, formType) {
        try {
            const prefix = formType === 'edit' ? 'edit_' : '';
            const usageUnitSelect = document.getElementById(prefix + 'usage_unit_type');
            
            if (!usageUnitSelect) {
                console.warn('Usage unit type select not found:', prefix + 'usage_unit_type');
                return;
            }
            
            // Clear existing options and reset prices
            usageUnitSelect.innerHTML = '<option value="">یەکەی بەکارهێنان هەڵبژێرە</option>';
            
            // Reset material prices to base prices when usage unit is cleared
            const materialIdField = document.getElementById(prefix + 'material_id');
            if (materialIdField && materialIdField.value) {
                // Reset prices to base material prices
                const iqdPriceField = document.getElementById(prefix + 'material_purchase_price_iqd');
                const usdPriceField = document.getElementById(prefix + 'material_purchase_price_usd');
                const totalCostField = document.getElementById(prefix + 'material_total_cost');
                
                if (material.currency_type === 'دۆلار') {
                    if (usdPriceField) usdPriceField.value = material.purchase_price_usd || '';
                    if (iqdPriceField) iqdPriceField.value = '';
                } else {
                    if (iqdPriceField) iqdPriceField.value = material.purchase_price_iqd || '';
                    if (usdPriceField) usdPriceField.value = '';
                }
                
                // Reset total cost field placeholder
                if (totalCostField && totalCostField.hasAttribute('data-original-placeholder')) {
                    totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
                }
                
                // Recalculate total cost
                calculateMaterialTotalCost(formType);
            }
            
            const materialUnitType = material.unit_type;
            const piecesPerCarton = material.pieces_per_carton;
            const litersPerBarrel = material.liters_per_barrel;
            const litersPerBucket = material.liters_per_bucket;
            
            // Add options based on material unit type
            if (materialUnitType === 'کارتۆن') {
                usageUnitSelect.innerHTML += '<option value="کارتۆن">کارتۆن</option>';
                if (piecesPerCarton && piecesPerCarton > 0) {
                    usageUnitSelect.innerHTML += '<option value="دانە">دانە</option>';
                }
            } else if (materialUnitType === 'بەرمیل') {
                usageUnitSelect.innerHTML += '<option value="بەرمیل">بەرمیل</option>';
                if (litersPerBarrel && litersPerBarrel > 0) {
                    usageUnitSelect.innerHTML += '<option value="لیتر">لیتر</option>';
                }
                if (litersPerBucket && litersPerBucket > 0) {
                    usageUnitSelect.innerHTML += '<option value="دەبە">دەبە</option>';
                }
            } else if (materialUnitType === 'دەبە') {
                usageUnitSelect.innerHTML += '<option value="دەبە">دەبە</option>';
                if (litersPerBucket && litersPerBucket > 0) {
                    usageUnitSelect.innerHTML += '<option value="لیتر">لیتر</option>';
                }
            } else if (materialUnitType === 'لیتر') {
                usageUnitSelect.innerHTML += '<option value="لیتر">لیتر</option>';
            } else if (materialUnitType === 'دانە') {
                usageUnitSelect.innerHTML += '<option value="دانە">دانە</option>';
            }
            
            console.log('Populated usage unit options for material unit type:', materialUnitType);
            
        } catch (error) {
            console.error('Error in populateUsageUnitOptions:', error);
        }
    }

    // Function to display material unit information
    window.displayMaterialUnitInfo = function(material, formType) {
        try {
            const prefix = formType === 'edit' ? 'edit_' : '';
            const quantityField = document.getElementById(prefix + 'material_quantity');
            
            if (!quantityField) return;
            
            // Remove any existing unit info
            clearMaterialUnitInfo(formType);
            
            // Create unit info element
            const unitInfoDiv = document.createElement('div');
            unitInfoDiv.className = 'material-unit-info';
            unitInfoDiv.style.fontSize = '12px';
            unitInfoDiv.style.marginTop = '5px';
            unitInfoDiv.style.color = '#6c757d';
            
            let unitInfoText = `یەکە: ${material.unit_type}`;
            
            // Add conversion information based on unit type
            if (material.unit_type === 'کارتۆن' && material.pieces_per_carton) {
                unitInfoText += ` (${material.pieces_per_carton} دانە لە کارتۆن)`;
            } else if (material.unit_type === 'بەرمیل' && material.liters_per_barrel) {
                unitInfoText += ` (${material.liters_per_barrel} لیتر لە بەرمیل)`;
            } else if (material.unit_type === 'دەبە' && material.liters_per_bucket) {
                unitInfoText += ` (${material.liters_per_bucket} لیتر لە دەبە)`;
            }
            
            unitInfoText += ' - بڕی بنەڕەتی بە دانە/لیتر هەژمار دەکرێت';
            
            unitInfoDiv.textContent = unitInfoText;
            
            // Add unit info after quantity field
            quantityField.parentNode.appendChild(unitInfoDiv);
            
        } catch (error) {
            console.error('Error in displayMaterialUnitInfo:', error);
        }
    }

    // Function to clear material unit info
    window.clearMaterialUnitInfo = function(formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const quantityField = document.getElementById(prefix + 'material_quantity');
        
        if (!quantityField) return;
        
        // Remove existing unit info
        const existingUnitInfo = quantityField.parentNode.querySelector('.material-unit-info');
        if (existingUnitInfo) {
            existingUnitInfo.remove();
        }
        
        // Reset total cost field placeholder
        const totalCostField = document.getElementById(prefix + 'material_total_cost');
        if (totalCostField && totalCostField.hasAttribute('data-original-placeholder')) {
            totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
        }
    }

    // Function to calculate and display base quantity
    window.calculateAndDisplayBaseQuantity = function(formType) {
        try {
            const prefix = formType === 'edit' ? 'edit_' : '';
            const quantityField = document.getElementById(prefix + 'material_quantity');
            const materialSelect = document.getElementById(prefix + 'material_id');
            const usageUnitSelect = document.getElementById(prefix + 'usage_unit_type');
            
            if (!quantityField || !materialSelect || !usageUnitSelect) return;
            
            const quantity = parseFloat(quantityField.value) || 0;
            const materialId = materialSelect.value;
            const usageUnitType = usageUnitSelect.value;
            
            if (quantity <= 0 || !materialId || !usageUnitType) {
                clearBaseQuantityDisplay(formType);
                return;
            }
            
            // Get material details to calculate base quantity
            fetch(`../process/other_expenses/get_material_details.php?material_id=${materialId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const material = data.data;
                        let baseQuantity = quantity;
                        
                        // Calculate base quantity based on usage unit type and material unit type
                        if (usageUnitType === 'کارتۆن' && material.unit_type === 'کارتۆن' && material.pieces_per_carton) {
                            baseQuantity = quantity * material.pieces_per_carton;
                        } else if (usageUnitType === 'دانە' && material.unit_type === 'کارتۆن') {
                            baseQuantity = quantity;
                        } else if (usageUnitType === 'بەرمیل' && material.unit_type === 'بەرمیل' && material.liters_per_barrel) {
                            baseQuantity = quantity * material.liters_per_barrel;
                        } else if (usageUnitType === 'لیتر' && material.unit_type === 'بەرمیل') {
                            baseQuantity = quantity;
                        } else if (usageUnitType === 'دەبە' && material.unit_type === 'بەرمیل' && material.liters_per_bucket) {
                            baseQuantity = quantity * material.liters_per_bucket;
                        } else if (usageUnitType === 'دەبە' && material.unit_type === 'دەبە' && material.liters_per_bucket) {
                            baseQuantity = quantity * material.liters_per_bucket;
                        } else if (usageUnitType === 'لیتر' && material.unit_type === 'دەبە') {
                            baseQuantity = quantity;
                        } else if (usageUnitType === 'لیتر' && material.unit_type === 'لیتر') {
                            baseQuantity = quantity;
                        } else if (usageUnitType === 'دانە' && material.unit_type === 'دانە') {
                            baseQuantity = quantity;
                        } else {
                            baseQuantity = quantity;
                        }
                        
                        displayBaseQuantity(baseQuantity, usageUnitType, formType);
                    }
                })
                .catch(error => {
                    console.error('Error calculating base quantity:', error);
                });
                
        } catch (error) {
            console.error('Error in calculateAndDisplayBaseQuantity:', error);
        }
    }

    // Function to display base quantity
    window.displayBaseQuantity = function(baseQuantity, unitType, formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const quantityField = document.getElementById(prefix + 'material_quantity');
        
        if (!quantityField) return;
        
        // Remove any existing base quantity display
        clearBaseQuantityDisplay(formType);
        
        // Create base quantity display element
        const baseQuantityDiv = document.createElement('div');
        baseQuantityDiv.className = 'base-quantity-display';
        baseQuantityDiv.style.fontSize = '11px';
        baseQuantityDiv.style.marginTop = '3px';
        baseQuantityDiv.style.color = '#0d6efd';
        baseQuantityDiv.style.fontWeight = 'bold';
        
        let baseUnit = 'دانە';
        if (unitType === 'بەرمیل' || unitType === 'دەبە') {
            baseUnit = 'لیتر';
        }
        
        baseQuantityDiv.textContent = `بڕی بنەڕەتی: ${baseQuantity.toFixed(2)} ${baseUnit}`;
        
        // Add base quantity display after quantity field
        quantityField.parentNode.appendChild(baseQuantityDiv);
    }

    // Function to clear base quantity display
    window.clearBaseQuantityDisplay = function(formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const quantityField = document.getElementById(prefix + 'material_quantity');
        
        if (!quantityField) return;
        
        // Remove existing base quantity display
        const existingBaseQuantity = quantityField.parentNode.querySelector('.base-quantity-display');
        if (existingBaseQuantity) {
            existingBaseQuantity.remove();
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
            const usageUnitField = document.getElementById(prefix + 'usage_unit_type');
            
            console.log('Fields found:', {
                quantityField: !!quantityField,
                iqdPriceField: !!iqdPriceField,
                usdPriceField: !!usdPriceField,
                totalCostField: !!totalCostField,
                usageUnitField: !!usageUnitField
            });
            
            if (!quantityField || !totalCostField) {
                console.warn('Required fields not found for material total cost calculation');
                return;
            }
            
            const quantity = parseFloat(quantityField.value) || 0;
            const iqdPrice = parseFloat(iqdPriceField?.value) || 0;
            const usdPrice = parseFloat(usdPriceField?.value) || 0;
            const usageUnit = usageUnitField?.value || '';
            
            console.log('Values:', { quantity, iqdPrice, usdPrice, usageUnit });
            
            // Use whichever price is available (IQD or USD)
            const price = iqdPrice > 0 ? iqdPrice : usdPrice;
            
            // Calculate total cost: quantity × price
            const totalCost = quantity * price;
            
            console.log('Calculation:', { quantity, price, totalCost, usageUnit });
            
            // Update the total cost field
            totalCostField.value = totalCost.toFixed(2);
            console.log('Updated material total cost field with:', totalCost.toFixed(2));
            
            // Display usage unit info in total cost field
            if (usageUnit && totalCostField) {
                const unitInfo = ` (${usageUnit})`;
                if (!totalCostField.hasAttribute('data-original-placeholder')) {
                    totalCostField.setAttribute('data-original-placeholder', totalCostField.placeholder || '');
                }
                totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder') + unitInfo;
            }
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

    // Function to update material prices based on usage unit
    window.updateMaterialPricesByUsageUnit = function(formType) {
        try {
            console.log('updateMaterialPricesByUsageUnit called with:', { formType });
            
            const prefix = formType === 'edit' ? 'edit_' : '';
            
            const materialIdField = document.getElementById(prefix + 'material_id');
            const usageUnitField = document.getElementById(prefix + 'usage_unit_type');
            const quantityField = document.getElementById(prefix + 'material_quantity');
            
            if (!materialIdField || !usageUnitField || !quantityField) {
                console.warn('Required fields not found for price update');
                return;
            }
            
            const materialId = materialIdField.value;
            const usageUnit = usageUnitField.value;
            const quantity = parseFloat(quantityField.value) || 0;
            
            if (!materialId || !usageUnit) {
                console.log('Material ID or usage unit not selected');
                return;
            }
            
            console.log('Updating prices for:', { materialId, usageUnit, quantity });
            
            // Fetch material details to get conversion rates
            fetch(`../process/other_expenses/get_material_details.php?material_id=${materialId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const material = data.data;
                        console.log('Material details:', material);
                        
                        // Calculate price based on usage unit
                        let newPrice = 0;
                        let priceField = null;
                        
                        if (material.currency_type === 'دۆلار') {
                            priceField = document.getElementById(prefix + 'material_purchase_price_usd');
                            newPrice = calculatePriceByUsageUnit(material, usageUnit, 'usd');
                        } else {
                            priceField = document.getElementById(prefix + 'material_purchase_price_iqd');
                            newPrice = calculatePriceByUsageUnit(material, usageUnit, 'iqd');
                        }
                        
                        if (priceField) {
                            priceField.value = newPrice.toFixed(2);
                            console.log('Updated price field with:', newPrice.toFixed(2));
                            
                            // Recalculate total cost
                            calculateMaterialTotalCost(formType);
                        }
                    } else {
                        console.error('Error fetching material details:', data.msg);
                    }
                })
                .catch(error => {
                    console.error('Error updating material prices:', error);
                });
                
        } catch (error) {
            console.error('Error in updateMaterialPricesByUsageUnit:', error);
        }
    }

    // Helper function to calculate price based on usage unit
    function calculatePriceByUsageUnit(material, usageUnit, currencyType) {
        const basePrice = currencyType === 'usd' ? material.purchase_price_usd : material.purchase_price_iqd;
        const materialUnitType = material.unit_type;
        
        console.log('Calculating price for:', { usageUnit, materialUnitType, basePrice });
        
        // If usage unit is same as material unit type, return base price
        if (usageUnit === materialUnitType) {
            return basePrice;
        }
        
        // Calculate conversion based on unit types
        if (materialUnitType === 'کارتۆن' && usageUnit === 'دانە') {
            // Convert from carton to pieces
            const piecesPerCarton = material.pieces_per_carton || 1;
            return basePrice / piecesPerCarton;
        } else if (materialUnitType === 'بەرمیل' && usageUnit === 'لیتر') {
            // Convert from barrel to liters
            const litersPerBarrel = material.liters_per_barrel || 1;
            return basePrice / litersPerBarrel;
        } else if (materialUnitType === 'بەرمیل' && usageUnit === 'دەبە') {
            // Convert from barrel to bucket
            const litersPerBucket = material.liters_per_bucket || 1;
            const litersPerBarrel = material.liters_per_barrel || 1;
            return (basePrice / litersPerBarrel) * litersPerBucket;
        } else if (materialUnitType === 'دەبە' && usageUnit === 'لیتر') {
            // Convert from bucket to liters
            const litersPerBucket = material.liters_per_bucket || 1;
            return basePrice / litersPerBucket;
        }
        
        // If no conversion found, return base price
        console.warn('No conversion found for:', { usageUnit, materialUnitType });
        return basePrice;
    }

    // Function to check material availability
    window.checkMaterialAvailability = function(formType) {
        try {
            const prefix = formType === 'edit' ? 'edit_' : '';
            const materialSelect = document.getElementById(prefix + 'material_id');
            const quantityField = document.getElementById(prefix + 'material_quantity');
            const usageUnitSelect = document.getElementById(prefix + 'usage_unit_type');
            
            if (!materialSelect || !quantityField || !usageUnitSelect) return;
            
            const materialId = materialSelect.value;
            const quantity = parseFloat(quantityField.value) || 0;
            const usageUnitType = usageUnitSelect.value;
            
            if (!materialId || quantity <= 0 || !usageUnitType) {
                clearMaterialAvailabilityMessage(formType);
                return;
            }
            
            // Check material availability using the new endpoint with usage unit type
            fetch(`../process/other_expenses/check_material_availability.php?material_id=${materialId}&quantity=${quantity}&usage_unit_type=${usageUnitType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.available) {
                            let baseUnit = 'دانە';
                            if (data.unit_type === 'بەرمیل' || data.unit_type === 'دەبە') {
                                baseUnit = 'لیتر';
                            }
                            
                            const message = `بڕی پێویست لە کۆگا هەیە. بڕی بەردەست: ${data.available_quantity} ${baseUnit}، بڕی پێویست: ${data.base_required_quantity} ${baseUnit}`;
                            showMaterialAvailabilityMessage(formType, 'success', message);
                        } else {
                            let baseUnit = 'دانە';
                            if (data.unit_type === 'بەرمیل' || data.unit_type === 'دەبە') {
                                baseUnit = 'لیتر';
                            }
                            
                            const message = `بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: ${data.available_quantity} ${baseUnit}، بڕی پێویست: ${data.base_required_quantity} ${baseUnit}`;
                            showMaterialAvailabilityMessage(formType, 'error', message);
                        }
                    } else {
                        showMaterialAvailabilityMessage(formType, 'error', data.msg || 'هەڵە لە پشکنینی بەردەستبوونی کاڵا');
                    }
                })
                .catch(error => {
                    console.error('Error checking material availability:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        materialId,
                        quantity,
                        usageUnitType,
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
                if (fieldId.includes('material_') || fieldId.includes('usage_unit_type')) {
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
                } else if (fieldId.includes('material_') || fieldId.includes('usage_unit_type')) {
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

    // Function to handle expense type changes
    window.handleExpenseTypeChange = function(formType) {
        const prefix = formType === 'edit' ? 'edit_' : '';
        const expenseTypeSelect = document.getElementById(prefix + 'expense_type');
        
        if (!expenseTypeSelect) return;
        
        const expenseType = expenseTypeSelect.value;
        
        // Clear all material-related information when expense type changes
        clearMaterialAvailabilityMessage(formType);
        clearMaterialUnitInfo(formType);
        clearBaseQuantityDisplay(formType);
        
        // Show/hide relevant fields based on expense type
        const materialFields = document.querySelectorAll(`[id^="${prefix}material_"]`);
        const gasFields = document.querySelectorAll(`[id^="${prefix}gas_"]`);
        
        if (expenseType === 'بەکارهێنانی کاڵای کۆگا') {
            // Show material fields, hide gas fields
            materialFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'block';
                }
            });
            gasFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'none';
                }
            });
        } else if (expenseType === 'بەکارهێنانی گاز') {
            // Show gas fields, hide material fields
            materialFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'none';
                }
            });
            gasFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'block';
                }
            });
        } else {
            // Hide both material and gas fields for other expense types
            materialFields.forEach(field => {
                if (field.parentElement) {
                    field.parentElement.style.display = 'none';
                }
            });
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
        addExpenseTypeSelect.addEventListener('change', function() {
            handleExpenseTypeChange('add');
        });
    }
    
    if (editExpenseTypeSelect) {
        editExpenseTypeSelect.addEventListener('change', function() {
            handleExpenseTypeChange('edit');
        });
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

    // Add event listeners for usage unit type changes
    const addUsageUnitSelect = document.getElementById('usage_unit_type');
    const editUsageUnitSelect = document.getElementById('edit_usage_unit_type');
    
    if (addUsageUnitSelect) {
        addUsageUnitSelect.addEventListener('change', function() {
            // Recalculate base quantity when usage unit changes
            calculateAndDisplayBaseQuantity('add');
            // Check material availability with new unit
            checkMaterialAvailability('add');
            // Update material prices based on usage unit
            updateMaterialPricesByUsageUnit('add');
        });
    }
    
    if (editUsageUnitSelect) {
        editUsageUnitSelect.addEventListener('change', function() {
            // Recalculate base quantity when usage unit changes
            calculateAndDisplayBaseQuantity('edit');
            // Check material availability with new unit
            checkMaterialAvailability('edit');
            // Update material prices based on usage unit
            updateMaterialPricesByUsageUnit('edit');
        });
    }
    
    // Add event listeners for usage unit type clearing
    if (addUsageUnitSelect) {
        addUsageUnitSelect.addEventListener('input', function() {
            if (!this.value) {
                // Reset prices to base material prices when usage unit is cleared
                const materialIdField = document.getElementById('material_id');
                if (materialIdField && materialIdField.value) {
                    // Fetch material details and reset prices
                    fetch(`../process/other_expenses/get_material_details.php?material_id=${materialIdField.value}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const material = data.data;
                                const iqdPriceField = document.getElementById('material_purchase_price_iqd');
                                const usdPriceField = document.getElementById('material_purchase_price_usd');
                                const totalCostField = document.getElementById('material_total_cost');
                                
                                if (material.currency_type === 'دۆلار') {
                                    if (usdPriceField) usdPriceField.value = material.purchase_price_usd || '';
                                    if (iqdPriceField) iqdPriceField.value = '';
                                } else {
                                    if (iqdPriceField) iqdPriceField.value = material.purchase_price_iqd || '';
                                    if (usdPriceField) usdPriceField.value = '';
                                }
                                
                                // Reset total cost field placeholder
                                if (totalCostField && totalCostField.hasAttribute('data-original-placeholder')) {
                                    totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
                                }
                                
                                // Recalculate total cost
                                calculateMaterialTotalCost('add');
                            }
                        });
                }
            }
        });
    }
    
    if (editUsageUnitSelect) {
        editUsageUnitSelect.addEventListener('input', function() {
            if (!this.value) {
                // Reset prices to base material prices when usage unit is cleared
                const materialIdField = document.getElementById('edit_material_id');
                if (materialIdField && materialIdField.value) {
                    // Fetch material details and reset prices
                    fetch(`../process/other_expenses/get_material_details.php?material_id=${materialIdField.value}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const material = data.data;
                                const iqdPriceField = document.getElementById('edit_material_purchase_price_iqd');
                                const usdPriceField = document.getElementById('edit_material_purchase_price_usd');
                                const totalCostField = document.getElementById('edit_material_total_cost');
                                
                                if (material.currency_type === 'دۆلار') {
                                    if (usdPriceField) usdPriceField.value = material.purchase_price_usd || '';
                                    if (iqdPriceField) iqdPriceField.value = '';
                                } else {
                                    if (iqdPriceField) iqdPriceField.value = material.purchase_price_iqd || '';
                                    if (usdPriceField) usdPriceField.value = '';
                                }
                                
                                // Reset total cost field placeholder
                                if (totalCostField && totalCostField.hasAttribute('data-original-placeholder')) {
                                    totalCostField.placeholder = totalCostField.getAttribute('data-original-placeholder');
                                }
                                
                                // Recalculate total cost
                                calculateMaterialTotalCost('edit');
                            }
                        });
                }
            }
        });
    }
});

// Export functions are now in separate file: export_functions.js
