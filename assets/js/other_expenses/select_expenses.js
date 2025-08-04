async function loadOtherExpenses() {
    try {
        console.log('Loading other expenses...');
        
        // Get USD rate from the exchange rate input field in the add modal
        let usdRate = 139250; // fallback default
        const exchangeRateInput = document.getElementById('exchange_rate');
        if (exchangeRateInput && exchangeRateInput.value) {
            usdRate = parseFloat(exchangeRateInput.value);
            console.log('Using exchange rate from input field:', usdRate);
        } else {
            // Fallback to API if input field is empty
            try {
                const rateRes = await fetch('../process/purchase_materilas/get_usd_rate.php');
                const rateData = await rateRes.json();
                if (rateData.success && rateData.rate) {
                    usdRate = parseFloat(rateData.rate);
                } else if (rateData.default_rate) {
                    usdRate = parseFloat(rateData.default_rate);
                }
            } catch (e) {
                // fallback to default
            }
        }

        const monthFilter = document.getElementById('monthFilter');
        console.log('Month filter element:', monthFilter);
        
        const res = await fetch('../process/other_expenses/select_expenses.php');
        
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const result = await res.json();
        console.log('Expenses data received:', result);
        
        // Handle both old format (array) and new format (object with success property)
        let data;
        if (Array.isArray(result)) {
            // Old format - direct array
            data = result;
        } else if (result.success && Array.isArray(result.expenses)) {
            // New format - object with success and expenses properties
            data = result.expenses;
        } else {
            console.error('Unexpected data format:', result);
            return;
        }
        
        function formatNumber(num) {
        return Number(num).toLocaleString('en-US');
    }
    function formatUSD(num) {
        return num ? `$${formatNumber(num)}` : '$0';
    }
    function formatIQD(num) {
        return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
    }
    function iqdToUsd(iqd) {
        return usdRate && iqd ? (parseFloat(iqd) / (usdRate / 100)) : 0;
    }
    // Filter by month
    let filtered = data;
    if (monthFilter && monthFilter.value) {
        const [year, month] = monthFilter.value.split('-');
        filtered = data.filter(row => row.date && row.date.startsWith(`${year}-${month}`));
    }
    // Calculate totals including car expenses
    // Note: Material usage has both IQD and USD amounts, gas usage is only IQD, 
    // and other expenses can be either IQD or USD based on currency_type
    let totalCarMaterialCostIQD = 0, totalCarMaterialCostUSD = 0;
    let totalCarGasCost = 0;
    let totalOtherExpensesIQD = 0, totalOtherExpensesUSD = 0;
    
    filtered.forEach(row => {
        // Calculate car material expenses (has both IQD and USD)
        if (row.car_id && row.expense_type === 'بەکارهێنانی کاڵای کۆگا') {
            totalCarMaterialCostIQD += parseFloat(row.material_purchase_price_iqd || 0) * parseFloat(row.material_quantity || 0);
            totalCarMaterialCostUSD += parseFloat(row.material_purchase_price_usd || 0) * parseFloat(row.material_quantity || 0);
        }
        
        // Calculate car gas expenses (only IQD)
        if (row.car_id && row.expense_type === 'بەکارهێنانی گاز') {
            totalCarGasCost += parseFloat(row.gas_total_cost || 0);
        }
        
        // Calculate other expenses (can be IQD or USD based on currency_type) - includes خواردنگە and ئۆفیس
        if (!row.car_id || (row.expense_type !== 'بەکارهێنانی کاڵای کۆگا' && row.expense_type !== 'بەکارهێنانی گاز')) {
            if (row.currency_type === 'دۆلار') {
                totalOtherExpensesUSD += parseFloat(row.amount_usd || 0);
            } else {
                totalOtherExpensesIQD += parseFloat(row.amount_iqd || 0);
            }
        }
    });
    
    // Calculate totals
    const totalCarMaterialCost = totalCarMaterialCostIQD + totalCarMaterialCostUSD;
    const totalCarExpenses = totalCarMaterialCost + totalCarGasCost;
    const totalOtherExpenses = totalOtherExpensesIQD + totalOtherExpensesUSD;
    const totalAllExpenses = totalOtherExpenses + totalCarExpenses;
    
    // Convert IQD to USD for display
    // Formula: USD = IQD / (rate/100) where rate is for 100 USD
    const totalCarMaterialCostUSDConverted = totalCarMaterialCostIQD / (usdRate / 100) + totalCarMaterialCostUSD;
    const totalCarGasCostUSD = totalCarGasCost / (usdRate / 100);
    const totalOtherExpensesUSDConverted = totalOtherExpensesIQD / (usdRate / 100) + totalOtherExpensesUSD;
    const totalCarExpensesUSD = totalCarMaterialCostUSDConverted + totalCarGasCostUSD;
    const totalAllExpensesUSD = totalOtherExpensesUSDConverted + totalCarExpensesUSD;
    
    // Update car expense cards (show only USD)
    document.getElementById('totalCarMaterialCost').innerHTML = `${formatUSD(totalCarMaterialCostUSDConverted)}`;
    document.getElementById('totalCarGasCost').innerHTML = `${formatUSD(totalCarGasCostUSD)}`;
    document.getElementById('totalOtherExpenses').innerHTML = `${formatUSD(totalOtherExpensesUSDConverted)}`;
    document.getElementById('totalCarExpenses').innerHTML = `${formatUSD(totalAllExpensesUSD)}`;
    
    // Update USD exchange rate card
    document.getElementById('usdExchangeRate').innerHTML = `${formatNumber(usdRate)} د.ع`;
    // Table
    const tableData = filtered.map((row, idx) => ({
        '#': idx + 1,
        purpose: row.purpose,
        person_name: row.person_name || '',
        employee_name: row.employee_name || '',
        car_name: row.car_name || '',
        gas_liters: row.gas_liters ? formatNumber(row.gas_liters) : '',
        expense_type: row.expense_type || '',
        material_name: row.material_name || '',
        material_quantity: row.material_quantity ? formatNumber(row.material_quantity) : '',
        material_purchase_price_iqd: row.material_purchase_price_iqd ? formatIQD(row.material_purchase_price_iqd) : '',
        material_purchase_price_usd: row.material_purchase_price_usd ? formatUSD(row.material_purchase_price_usd) : '',
        material_total_cost: row.material_total_cost ? formatNumber(row.material_total_cost) : '',
        gas_purchase_price_input: row.gas_purchase_price_input ? formatIQD(row.gas_purchase_price_input) : '',
        gas_total_cost: row.gas_total_cost ? formatNumber(row.gas_total_cost) : '',
        payment_type: row.payment_type,
        currency_type: row.currency_type,
        invoice_number: row.invoice_number || '',
        amount_iqd: row.amount_iqd ? formatIQD(row.amount_iqd) : '',
        amount_usd: row.amount_usd ? formatUSD(row.amount_usd) : '',
        paid_iqd: row.paid_iqd ? formatIQD(row.paid_iqd) : '',
        paid_usd: row.paid_usd ? formatUSD(row.paid_usd) : '',
        exchange_rate: row.exchange_rate ? formatNumber(row.exchange_rate) : '',
        remaining_iqd: row.remaining_iqd ? formatIQD(row.remaining_iqd) : '',
        remaining_usd: row.remaining_usd ? formatUSD(row.remaining_usd) : '',
        date: row.date,
        actions: `<button class="btn btn-sm btn-danger delete-expense" data-id="${row.id}"><i class="fa fa-trash"></i></button> <button class="btn btn-sm btn-primary edit-expense" data-id="${row.id}"><i class="fa fa-edit"></i></button>`
    }));
    TableController.renderWithPagination('#otherExpensesTable', tableData, [
        '#', 'purpose', 'person_name', 'employee_name', 'car_name', 'gas_liters', 'expense_type', 'material_name', 'material_quantity', 'material_purchase_price_iqd', 'material_purchase_price_usd', 'material_total_cost', 'gas_purchase_price_input', 'gas_total_cost', 'payment_type', 'currency_type',
        'invoice_number', 'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate',
        'remaining_iqd', 'remaining_usd', 'date', 'actions'
    ]);
    // Attach delete event
    setTimeout(() => {
        document.querySelectorAll('.delete-expense').forEach(btn => {
            btn.onclick = function() {
                const id = this.dataset.id;
                if (typeof deleteExpense === 'function') deleteExpense(id);
            };
        });
    }, 0);
    // Attach edit event
    setTimeout(() => {
        document.querySelectorAll('.edit-expense').forEach(btn => {
            btn.onclick = async function() {
                const id = this.dataset.id;
                // Find the row data
                const row = data.find(r => r.id == id);
                if (!row) return;
                // Populate selects
                await populateSelect('../process/other_expenses/select_persons.php', 'edit_person_id', row.person_id);
                await populateSelect('../process/other_expenses/select_employees.php', 'edit_employee_id', row.employee_id);
                await populateSelect('../process/other_expenses/select_cars.php', 'edit_car_id', row.car_id);
                // Populate fields
                document.getElementById('edit_id').value = row.id;
                document.getElementById('edit_purpose').value = row.purpose;
                document.getElementById('edit_payment_type').value = row.payment_type;
                document.getElementById('edit_currency_type').value = row.currency_type;
                document.getElementById('edit_invoice_number').value = row.invoice_number;
                document.getElementById('edit_amount_iqd').value = row.amount_iqd;
                document.getElementById('edit_amount_usd').value = row.amount_usd;
                document.getElementById('edit_paid_iqd').value = row.paid_iqd;
                document.getElementById('edit_paid_usd').value = row.paid_usd;
                document.getElementById('edit_exchange_rate').value = row.exchange_rate;
                document.getElementById('edit_remaining_iqd').value = row.remaining_iqd;
                document.getElementById('edit_remaining_usd').value = row.remaining_usd;
                // Add gas_liters to edit modal if present
                if (document.getElementById('edit_gas_liters')) {
                    document.getElementById('edit_gas_liters').value = row.gas_liters || '';
                }
                // Add new fields to edit modal
                if (document.getElementById('edit_expense_type')) {
                    document.getElementById('edit_expense_type').value = row.expense_type || '';
                    // Trigger change event to show/hide appropriate fields
                    const event = new Event('change');
                    document.getElementById('edit_expense_type').dispatchEvent(event);
                    
                    // If it's a gas usage expense, populate gas price
                    if (row.expense_type === 'بەکارهێنانی گاز') {
                        setTimeout(() => {
                            populateGasPurchasePrice('edit');
                        }, 100);
                    }
                }
                // Populate material dropdown
                if (document.getElementById('edit_material_id')) {
                    populateSelect('../process/other_expenses/select_materials.php', 'edit_material_id', row.material_id);
                    // Populate material prices after a short delay to ensure dropdown is populated
                    setTimeout(() => {
                        if (row.material_id) {
                            populateMaterialPrices(row.material_id, 'edit');
                        }
                    }, 100);
                }
                if (document.getElementById('edit_material_quantity')) {
                    document.getElementById('edit_material_quantity').value = row.material_quantity || '';
                }
                if (document.getElementById('edit_usage_unit_type')) {
                    document.getElementById('edit_usage_unit_type').value = row.usage_unit_type || '';
                }
                if (document.getElementById('edit_material_purchase_price_iqd')) {
                    document.getElementById('edit_material_purchase_price_iqd').value = row.material_purchase_price_iqd || '';
                }
                if (document.getElementById('edit_material_purchase_price_usd')) {
                    document.getElementById('edit_material_purchase_price_usd').value = row.material_purchase_price_usd || '';
                }
                if (document.getElementById('edit_material_total_cost')) {
                    document.getElementById('edit_material_total_cost').value = row.material_total_cost || '';
                }
                if (document.getElementById('edit_gas_purchase_price_input')) {
                    document.getElementById('edit_gas_purchase_price_input').value = row.gas_purchase_price_input || '';
                }
                if (document.getElementById('edit_gas_total_cost')) {
                    document.getElementById('edit_gas_total_cost').value = row.gas_total_cost || '';
                }
                document.getElementById('edit_date').value = row.date;
                
                // Add event listeners for automatic total cost calculation in edit form
                const editQuantityField = document.getElementById('edit_material_quantity');
                const editIqdPriceField = document.getElementById('edit_material_purchase_price_iqd');
                const editUsdPriceField = document.getElementById('edit_material_purchase_price_usd');
                
                if (editQuantityField) {
                    editQuantityField.removeEventListener('input', calculateEditTotalCost);
                    editQuantityField.addEventListener('input', function() {
                        calculateEditTotalCost();
                        checkMaterialAvailability('edit');
                    });
                }

                // Add event listeners for gas total cost calculation in edit form
                const editGasLitersField = document.getElementById('edit_gas_liters');
                const editGasPriceField = document.getElementById('edit_gas_purchase_price_input');
                
                if (editGasLitersField) {
                    editGasLitersField.removeEventListener('input', calculateEditGasTotalCost);
                    editGasLitersField.addEventListener('input', calculateEditGasTotalCost);
                }
                if (editGasPriceField) {
                    editGasPriceField.removeEventListener('input', calculateEditGasTotalCost);
                    editGasPriceField.addEventListener('input', calculateEditGasTotalCost);
                }
                if (editIqdPriceField) {
                    editIqdPriceField.removeEventListener('input', calculateEditTotalCost);
                    editIqdPriceField.addEventListener('input', calculateEditTotalCost);
                }
                if (editUsdPriceField) {
                    editUsdPriceField.removeEventListener('input', calculateEditTotalCost);
                    editUsdPriceField.addEventListener('input', calculateEditTotalCost);
                }
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
                modal.show();
                if (typeof setupEditExpenseModal === 'function') setupEditExpenseModal();
            };
        });
        // Edit form submit
        const editExpenseForm = document.getElementById('editExpenseForm');
        if (editExpenseForm) {
            editExpenseForm.onsubmit = async function(e) {
                e.preventDefault();
                const id = document.getElementById('edit_id').value;
                const data = Object.fromEntries(new FormData(editExpenseForm).entries());
                await editExpense(id, data);
                const modal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
                modal.hide();
            };
        }
    }, 0);
    } catch (err) {
        console.error('Error loading other expenses:', err);
        console.error('Error details:', {
            message: err.message,
            stack: err.stack,
            name: err.name
        });
    }
}
document.addEventListener('DOMContentLoaded', function() {
    loadOtherExpenses();
    const monthFilter = document.getElementById('monthFilter');
    if (monthFilter) {
        monthFilter.addEventListener('change', loadOtherExpenses);
    }
});
// Helper for populating selects with selected value
async function populateSelect(url, selectId, selectedId) {
    try {
        console.log('Populating select:', { url, selectId, selectedId });
        
        const res = await fetch(url);
        
        console.log('Select population response status:', res.status);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Select data received:', data);
        
        const select = document.getElementById(selectId);
        if (!select) {
            console.warn('Select element not found:', selectId);
            return;
        }
        
        select.innerHTML = '<option value="">-- هەلبژێرە --</option>';
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            if (selectedId && String(item.id) === String(selectedId)) opt.selected = true;
            select.appendChild(opt);
        });
        
        console.log('Select populated successfully');
    } catch (err) {
        console.error('Error populating select:', err);
        console.error('Error details:', {
            message: err.message,
            stack: err.stack,
            name: err.name,
            url,
            selectId,
            selectedId
        });
    }
}

// Function to calculate edit form total cost
function calculateEditTotalCost() {
    calculateMaterialTotalCost('edit');
}

// Function to calculate edit form gas total cost
function calculateEditGasTotalCost() {
    calculateGasTotalCost('edit');
}
