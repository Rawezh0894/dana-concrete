// Global variable to store current customer debt
let CUSTOMER_CURRENT_DEBT = 0;
let CUSTOMER_OPENING_DEBT_USD = 0;
// Multiple submission prevention flag
let submitting = false;

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
function calculateRemainingDebt() {
    const dolar_rate = parseFloat(document.getElementById('customer_debt_dolar_rate').value) || 0;
    const paid_usd = parseFloat(document.getElementById('customer_debt_paid_usd').value) || 0;
    const paid_iqd = parseFloat(document.getElementById('customer_debt_paid_iqd').value) || 0;
    const discount = parseFloat(document.getElementById('customer_debt_discount').value) || 0;
    
    // Calculate IQD to USD conversion
    const paid_iqd_usd = dolar_rate > 0 ? paid_iqd / (dolar_rate / 100) : 0;
    const total_payment = paid_usd + paid_iqd_usd + discount;
    
    // Calculate remaining debt (داخیلکردنی قەرزی پێشوو)
    const remainingDebt = (CUSTOMER_CURRENT_DEBT + CUSTOMER_OPENING_DEBT_USD) - total_payment;
    
    // Format and display remaining debt
    const remainingElement = document.getElementById('customer_debt_remaining');
    if (remainingElement) {
        remainingElement.value = remainingDebt.toFixed(2) + ' USD';
        
        // Change color based on remaining debt
        if (remainingDebt < 0) {
            remainingElement.style.color = 'red';
            remainingElement.style.fontWeight = 'bold';
        } else if (remainingDebt === 0) {
            remainingElement.style.color = 'green';
            remainingElement.style.fontWeight = 'bold';
        } else {
            remainingElement.style.color = 'black';
            remainingElement.style.fontWeight = 'normal';
        }
    }
}

// Make function globally available
window.calculateRemainingDebt = calculateRemainingDebt;

// Add event listeners for real-time calculation
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['customer_debt_dolar_rate', 'customer_debt_paid_usd', 'customer_debt_paid_iqd', 'customer_debt_discount'];
    inputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', calculateRemainingDebt);
        }
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
            if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
                fetchCustomerDebt(CUSTOMER_ID);
                fetchCustomerOpeningDebt(CUSTOMER_ID);
            }
            // Update dollar rate from API when modal opens
            updateDollarRateInModal();
        });
    }
});

document.getElementById('addCustomerDebtForm').addEventListener('submit', async function(e) {
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

    const formData = new FormData();
    formData.append('customer_id', customer_id);
    formData.append('date', date);
    formData.append('dolar_rate', dolar_rate);
    formData.append('paid_usd', paid_usd);
    formData.append('paid_iqd', paid_iqd);
    formData.append('discount', discount);
    formData.append('note', note);

    try {
        const res = await fetch('../process/customer_profile/add_return_debt.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو', data.msg, 'success');
            document.getElementById('addCustomerDebtForm').reset();
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
