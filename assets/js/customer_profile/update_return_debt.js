// Multiple submission prevention flag
let isUpdating = false;

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

// Function to update dollar rate in edit modal
async function updateDollarRateInEditModal() {
    const rateInput = document.getElementById('edit_customer_debt_dolar_rate');
    if (rateInput) {
        const apiRate = await fetchDollarRateFromAPI();
        if (apiRate !== null) {
            rateInput.value = apiRate;
        } else {
            // Show error if API fails
            console.error('Failed to fetch dollar rate from API');
            rateInput.value = '';
        }
    }
}

document.getElementById('editCustomerDebtForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Prevent multiple submissions
    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }
    
    // Set updating flag and disable submit button
    isUpdating = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
    submitBtn.disabled = true;
    
    try {
        const id = document.getElementById('edit_customer_debt_id').value;
        const customer_id = typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : null;
        const date = document.getElementById('edit_customer_debt_date').value;
        const dolar_rate = parseFloat(document.getElementById('edit_customer_debt_dolar_rate').value) || 0;
        const paid_usd = parseFloat(document.getElementById('edit_customer_debt_paid_usd').value) || 0;
        const paid_iqd = parseFloat(document.getElementById('edit_customer_debt_paid_iqd').value) || 0;
        const discount = parseFloat(document.getElementById('edit_customer_debt_discount').value) || 0;
        const note = document.getElementById('edit_customer_debt_note').value;

        if (!id || !customer_id || !date || (paid_usd <= 0 && paid_iqd <= 0 && discount <= 0)) {
            Swal.fire('هەڵە', 'هەموو خانەکان پڕ بکە!', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('customer_id', customer_id);
        formData.append('date', date);
        formData.append('dolar_rate', dolar_rate);
        formData.append('paid_usd', paid_usd);
        formData.append('paid_iqd', paid_iqd);
        formData.append('discount', discount);
        formData.append('note', note);

        const res = await fetch('../process/customer_profile/update_return_debt.php', {
            method: 'POST',
            body: formData
        });
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو', data.msg, 'success');
            // Close modal and reload debts
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerDebtModal'));
            if (modal) modal.hide();
            
            // Automatically refresh all customer data
            if (typeof refreshCustomerData === 'function') {
                refreshCustomerData();
            } else {
                // Fallback to individual refresh functions
                if (typeof loadCustomerReturnDebts === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                    loadCustomerReturnDebts(CUSTOMER_ID);
                }
                if (typeof loadCustomerSales === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                    loadCustomerSales(CUSTOMER_ID);
                }
                if (typeof loadCustomerSummaryCards === 'function') {
                    loadCustomerSummaryCards();
                }
            }
        } else {
            Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕووی دا', 'error');
        }
    } catch (error) {
        console.error('Error updating debt:', error);
        Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە پەیوەندی بە سێرڤەرەوە', 'error');
    } finally {
        // Reset updating flag and restore button state
        isUpdating = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

// Add modal cleanup function
function cleanupEditModal() {
    const modal = document.getElementById('editCustomerDebtModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            // Clear form when modal is closed
            document.getElementById('editCustomerDebtForm').reset();
            document.getElementById('edit_customer_debt_id').value = '';
        });
        
        // Update dollar rate when modal is shown
        modal.addEventListener('shown.bs.modal', function() {
            updateDollarRateInEditModal();
        });
    }
}

// Initialize modal cleanup when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    cleanupEditModal();
});
