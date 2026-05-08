// Global variable to store current customer debt
let CUSTOMER_CURRENT_DEBT = 0;
let CUSTOMER_OPENING_DEBT_USD = 0;
// Multiple submission prevention flag
let submitting = false;
let isManualChangeBack = false;
let isManualDiscount = false;

// Function to fetch dollar rate from API
async function fetchDollarRateFromAPI() {
    try {
        const response = await fetch('https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk');
        const data = await response.json();
        if (data && data.value && !isNaN(data.value)) {
            return parseFloat(data.value);
        }
    } catch (error) {
        console.error('Error fetching dollar rate from API:', error);
    }
    return null; // No default fallback value
}

// Function to update dollar rate in modal
async function updateDollarRateInModal() {
    const rateInput = document.getElementById('customer_debt_dolar_rate');
    if (rateInput) {
        const apiRate = await fetchDollarRateFromAPI();
        if (apiRate !== null) {
            rateInput.value = apiRate;
            // Trigger calculation after updating rate
            calculateRemainingDebt();
        } else {
            // Show error if API fails
            console.error('Failed to fetch dollar rate from API');
            rateInput.value = '';
        }
    }
}

// Function to fetch customer current debt
async function fetchCustomerDebt(customerId) {
    try {
        const response = await fetch(`../process/customer_profile/get_customer_debt.php?customer_id=${customerId}`);
        const data = await response.json();
        if (data.success) {
            CUSTOMER_CURRENT_DEBT = data.debt_usd;
            calculateRemainingDebt();
        }
    } catch (error) {
        console.error('Error fetching customer debt:', error);
    }
}

// وەرگرتنی opening_debt_usd
async function fetchCustomerOpeningDebt(customerId) {
    try {
        const response = await fetch(`../process/customer/get_opening_debt.php?customer_id=${customerId}`);
        const data = await response.json();
        CUSTOMER_OPENING_DEBT_USD = data.opening_debt_usd || 0;
        calculateRemainingDebt();
    } catch (error) {
        console.error('Error fetching opening debt:', error);
    }
}

// Make functions globally available
window.fetchCustomerDebt = fetchCustomerDebt;
window.fetchCustomerOpeningDebt = fetchCustomerOpeningDebt;
window.updateDollarRateInModal = updateDollarRateInModal;

// Function to calculate and display remaining debt
function calculateRemainingDebt(prefix = 'customer_debt_') {
    const dolarRateInput = document.getElementById(prefix + 'dolar_rate');
    const paidUsdInput = document.getElementById(prefix + 'paid_usd');
    const paidIqdInput = document.getElementById(prefix + 'paid_iqd');
    const changeUsdInput = document.getElementById(prefix + 'change_back_usd');
    const changeIqdInput = document.getElementById(prefix + 'change_back_iqd');
    const discountInput = document.getElementById(prefix + 'discount');
    const remainingElement = document.getElementById(prefix + 'remaining');

    if (!dolarRateInput || !paidUsdInput || !paidIqdInput || !discountInput || !remainingElement) {
        return;
    }

    const dolar_rate = parseFloat(dolarRateInput.value) || 0;
    const paid_usd = parseFloat(paidUsdInput.value) || 0;
    const paid_iqd = parseFloat(paidIqdInput.value) || 0;
    const change_usd = parseFloat(changeUsdInput?.value) || 0;
    const change_iqd = parseFloat(changeIqdInput?.value) || 0;
    const discount = parseFloat(discountInput.value) || 0;
    
    // Calculate IQD to USD conversion (Net)
    const net_paid_usd = paid_usd - change_usd;
    const net_paid_iqd = paid_iqd - change_iqd;
    const net_paid_iqd_usd = dolar_rate > 0 ? net_paid_iqd / (dolar_rate / 100) : 0;
    const total_payment = net_paid_usd + net_paid_iqd_usd + discount;
    
    // Calculate remaining debt
    let total_debt = CUSTOMER_CURRENT_DEBT + CUSTOMER_OPENING_DEBT_USD;
    
    // If in edit mode, we should ideally fetch the specific debt situation of that record's context
    // But for now, we'll use the current global debt as a reference
    const remainingDebt = total_debt - total_payment;
    const adjustedRemainingDebt = Math.abs(remainingDebt) < 0.01 ? 0 : remainingDebt;
    
    // Smart Auto-Balance Logic (Only for Add Modal)
    const autoBalanceToggle = document.getElementById('auto_balance_toggle');
    const paymentType = document.getElementById(prefix + 'payment_type')?.value || 'fifo';
    
    if (prefix === 'customer_debt_' && autoBalanceToggle && autoBalanceToggle.checked) {
        let target_amount = total_debt;
        
        if (paymentType === 'specific_sales') {
            // In specific sales, we want to match the total allocated amount
            let total_allocated = 0;
            document.querySelectorAll('.sale-checkbox:checked').forEach(cb => {
                const amtInput = document.querySelector(`.sale-amount[data-sale-id="${cb.value}"]`);
                if (amtInput) total_allocated += parseFloat(amtInput.value) || 0;
            });
            if (total_allocated > 0) target_amount = total_allocated;
        }

        const current_net_paid_no_discount = (paid_usd - change_usd) + ((paid_iqd - change_iqd) / (dolar_rate / 100));
        const current_gap = target_amount - current_net_paid_no_discount;

        if (current_gap > 0 && current_gap < 10) { 
            // Small Positive Gap -> Auto-fill Discount
            if (!isManualDiscount) {
                discountInput.value = Math.max(0, current_gap).toFixed(4);
            }
        } else if (current_gap <= 0) {
            // No gap or surplus -> Discount should be 0 unless manual
            if (!isManualDiscount) {
                discountInput.value = 0;
            }
        }
        
        // Recalculate final remaining
        const final_discount = parseFloat(discountInput.value) || 0;
        const final_total_p = current_net_paid_no_discount + final_discount;
        const final_rem_d = total_debt - final_total_p;
        const final_adj_rem_d = Math.abs(final_rem_d) < 0.01 ? 0 : final_rem_d;
        
        remainingElement.value = final_adj_rem_d.toFixed(4) + ' USD';
        updateRemainingColor(remainingElement, final_adj_rem_d);
    } else {
        remainingElement.value = adjustedRemainingDebt.toFixed(4) + ' USD';
        updateRemainingColor(remainingElement, adjustedRemainingDebt);
    }
}

function updateRemainingColor(element, debt) {
    if (debt < 0) {
        element.style.color = 'red';
        element.style.fontWeight = 'bold';
    } else if (debt === 0) {
        element.style.color = 'green';
        element.style.fontWeight = 'bold';
    } else {
        element.style.color = 'black';
        element.style.fontWeight = 'normal';
    }
}

// Make function globally available
window.calculateRemainingDebt = calculateRemainingDebt;

// Add event listeners for real-time calculation
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['customer_debt_dolar_rate', 'customer_debt_paid_usd', 'customer_debt_paid_iqd', 'customer_debt_change_back_usd', 'customer_debt_change_back_iqd', 'customer_debt_discount'];
    inputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', (e) => {
                if (id === 'customer_debt_change_back_usd' || id === 'customer_debt_change_back_iqd') isManualChangeBack = true;
                if (id === 'customer_debt_discount') isManualDiscount = true;
                calculateRemainingDebt('customer_debt_');
            });
        }
    });

    // Delegate listeners for dynamically loaded sale amounts and checkboxes
    $(document).on('input', '.sale-amount', function() {
        calculateRemainingDebt('customer_debt_');
    });
    $(document).on('change', '.sale-checkbox', function() {
        calculateRemainingDebt('customer_debt_');
    });
    $(document).on('change', '#customer_debt_payment_type', function() {
        calculateRemainingDebt('customer_debt_');
    });

    const editInputs = ['edit_customer_debt_dolar_rate', 'edit_customer_debt_paid_usd', 'edit_customer_debt_paid_iqd', 'edit_customer_debt_change_back_usd', 'edit_customer_debt_change_back_iqd', 'edit_customer_debt_discount'];
    editInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', (e) => {
                calculateRemainingDebt('edit_customer_debt_');
            });
        }
    });
    
    $(document).on('input', '.edit-sale-amount', function() {
        calculateRemainingDebt('edit_customer_debt_');
    });
    $(document).on('change', '.edit-sale-checkbox', function() {
        calculateRemainingDebt('edit_customer_debt_');
    });
    $(document).on('change', '#edit_customer_debt_payment_type', function() {
        calculateRemainingDebt('edit_customer_debt_');
    });

    // Handle Balance Buttons
    document.querySelectorAll('.balance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const dolar_rate = parseFloat(document.getElementById('customer_debt_dolar_rate')?.value || document.getElementById('edit_customer_debt_dolar_rate')?.value) || 0;
            const total_debt = CUSTOMER_CURRENT_DEBT + CUSTOMER_OPENING_DEBT_USD;
            
            // Get current paid values based on which modal is open
            const isEdit = targetId.startsWith('edit_');
            const prefix = isEdit ? 'edit_customer_debt_' : 'customer_debt_';
            
            const paid_usd = parseFloat(document.getElementById(prefix + 'paid_usd')?.value) || 0;
            const paid_iqd = parseFloat(document.getElementById(prefix + 'paid_iqd')?.value) || 0;
            const discount = parseFloat(document.getElementById(prefix + 'discount')?.value) || 0;
            const change_usd = parseFloat(document.getElementById(prefix + 'change_back_usd')?.value) || 0;
            const change_iqd = parseFloat(document.getElementById(prefix + 'change_back_iqd')?.value) || 0;

            const current_net_paid_no_target = 
                (targetId.includes('change_back_usd') ? paid_usd : (paid_usd - change_usd)) + 
                (targetId.includes('change_back_iq') ? (paid_iqd / (dolar_rate / 100)) : ((paid_iqd - change_iqd) / (dolar_rate / 100))) + 
                (targetId.includes('discount') ? 0 : discount);
            
            const diff_to_zero = total_debt - current_net_paid_no_target;

            const input = document.getElementById(targetId);
            if (!input) return;

            if (targetId.includes('change_back_usd')) {
                input.value = Math.max(0, -diff_to_zero).toFixed(4);
                if (!isEdit) isManualChangeBack = true;
            } else if (targetId.includes('change_back_iq')) {
                const diffIqd = (-diff_to_zero) * (dolar_rate / 100);
                input.value = Math.max(0, Math.round(diffIqd));
                if (!isEdit) isManualChangeBack = true;
            } else if (targetId.includes('discount')) {
                input.value = Math.max(0, diff_to_zero).toFixed(4);
                if (!isEdit) isManualDiscount = true;
            }
            
            if (typeof calculateRemainingDebt === 'function') calculateRemainingDebt();
        });
    });
    
    // Fetch initial customer debt and opening debt
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
        fetchCustomerDebt(CUSTOMER_ID);
        fetchCustomerOpeningDebt(CUSTOMER_ID);
    }
    
    // Recalculate remaining debt when modal is shown
    const modal = document.getElementById('addCustomerDebtModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            isManualChangeBack = false;
            isManualDiscount = false;
            if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
                fetchCustomerDebt(CUSTOMER_ID);
                fetchCustomerOpeningDebt(CUSTOMER_ID);
            }
            // Update dollar rate from API when modal opens
            updateDollarRateInModal();
        });
    }
});

const addCustomerDebtForm = document.getElementById('addCustomerDebtForm');
if (addCustomerDebtForm) {
addCustomerDebtForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Prevent multiple submissions
    if (submitting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }
    
    // Set submitting flag and disable submit button
    submitting = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
    }
    
    const customer_id = typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : null;
    const date = document.getElementById('customer_debt_date').value;
    const dolar_rate = parseFloat(document.getElementById('customer_debt_dolar_rate').value) || 0;
    const paid_usd = parseFloat(document.getElementById('customer_debt_paid_usd').value) || 0;
    const paid_iqd = parseFloat(document.getElementById('customer_debt_paid_iqd').value) || 0;
    const discount = parseFloat(document.getElementById('customer_debt_discount').value) || 0;
    const note = document.getElementById('customer_debt_note').value;
    const payment_type = document.getElementById('customer_debt_payment_type').value;
    const change_back_usd = parseFloat(document.getElementById('customer_debt_change_back_usd').value) || 0;
    const change_back_iq = parseFloat(document.getElementById('customer_debt_change_back_iqd').value) || 0;
    
    // Collect specific sales data if payment type is specific_sales
    let specific_sales = {};
    if (payment_type === 'specific_sales') {
        const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
        checkboxes.forEach(checkbox => {
            const amountInput = document.querySelector(`.sale-amount[data-sale-id="${checkbox.value}"]`);
            const amount = parseFloat(amountInput.value) || 0;
            if (amount > 0) {
                specific_sales[checkbox.value] = amount;
            }
        });
        
    }

    // هەژمارکردنی بڕی پارەی داوە بە دۆلار
    let paid_iqd_usd = dolar_rate > 0 ? paid_iqd / (dolar_rate / 100) : 0;
    let total_paid_usd = paid_usd + paid_iqd_usd + discount;

    if (!customer_id || !date || (paid_usd <= 0 && paid_iqd <= 0 && discount <= 0)) {
        Swal.fire('هەڵە', 'هەموو خانەکان پڕ بکە!', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }

    // Validate payment before proceeding
    if (typeof validatePayment === 'function' && !validatePayment()) {
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }

    // Additional validation is now handled by the comprehensive validatePayment function

    const formData = new FormData();
    formData.append('customer_id', customer_id);
    formData.append('date', date);
    formData.append('dolar_rate', dolar_rate);
    formData.append('paid_usd', paid_usd);
    formData.append('paid_iqd', paid_iqd);
    formData.append('discount', discount);
    formData.append('note', note);
    formData.append('payment_type', payment_type);
    formData.append('change_back_usd', change_back_usd);
    formData.append('change_back_iq', change_back_iq);
    
    // Add specific sales data if applicable
    if (payment_type === 'specific_sales' && Object.keys(specific_sales).length > 0) {
        formData.append('specific_sales', JSON.stringify(specific_sales));
    }

    try {
        const res = await fetch('../process/customer_profile/add_return_debt.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو', data.msg, 'success');
            addCustomerDebtForm.reset();
            // Recalculate remaining debt after form reset
            setTimeout(() => {
                if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
                    fetchCustomerDebt(CUSTOMER_ID);
                }
            }, 100);
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerDebtModal'));
            if (modal) modal.hide();
            
            // Automatically refresh all customer data
            if (typeof refreshCustomerData === 'function') {
                refreshCustomerData();
            } else {
                // Fallback to individual refresh functions
                if (typeof loadCustomerReturnDebts === 'function') loadCustomerReturnDebts(customer_id);
                if (typeof loadCustomerSales === 'function') loadCustomerSales(customer_id);
                if (typeof loadCustomerSummaryCards === 'function') loadCustomerSummaryCards();
            }
        } else {
            Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕووی دا', 'error');
        }
    } catch (err) {
        Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا', 'error');
    } finally {
        // Reset submitting flag and restore submit button
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
});
}
