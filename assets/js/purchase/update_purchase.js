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
    const rateInput = document.getElementById('edit_exchange_rate');
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

// Initialize edit modal with API rate
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('editPurchaseModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            updateDollarRateInEditModal();
        });
    }
});

document.getElementById('editPurchaseForm').onsubmit = async function(e) {
    e.preventDefault();
    
    // Prevent multiple submissions
    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }
    
    // Set updating flag and disable submit button
    isUpdating = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
    }
    
    const form = e.target;
    
    // Validate required fields
    const requiredFields = [
        'id', 'company_id', 'driver_id', 'location_id', 'invoice_number', 
        'material_id', 'date', 'type', 'kg', 'exchange_rate', 'price', 'payment_type'
    ];
    
    const missingFields = [];
    for (const fieldName of requiredFields) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field || field.value.trim() === '') {
            missingFields.push(fieldName);
            if (field) field.classList.add('is-invalid');
        } else {
            if (field) field.classList.remove('is-invalid');
        }
    }
    
    // Validate price_per_kg based on type
    const type = form.querySelector('[name="type"]').value;
    const pricePerKgIqd = parseFloat(form.querySelector('[name="price_per_kg_iqd"]').value) || 0;
    const pricePerKgUsd = parseFloat(form.querySelector('[name="price_per_kg_usd"]').value) || 0;
    
    if (type === 'دینار' && pricePerKgIqd <= 0) {
        missingFields.push('price_per_kg_iqd');
        form.querySelector('[name="price_per_kg_iqd"]').classList.add('is-invalid');
    }
    if (type === 'دۆلار' && pricePerKgUsd <= 0) {
        missingFields.push('price_per_kg_usd');
        form.querySelector('[name="price_per_kg_usd"]').classList.add('is-invalid');
    }
    
    if (missingFields.length > 0) {
        Swal.fire('هەڵە!', `تکایە خانەکانی خوارەوە پڕ بکە: ${missingFields.join(', ')}`, 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    // Validate numeric fields
    let hasNegative = false;
    form.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseFloat(input.value) < 0) {
            hasNegative = true;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (hasNegative) {
        Swal.fire('هەڵە!', 'نابێت هیچ بڕێک منفی بێت!', 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    const paymentType = form.querySelector('[name="payment_type"]').value;
    const remainingUsd = parseFloat(form.querySelector('[name="remaining_usd"]').value) || 0;
    const remainingIqd = parseFloat(form.querySelector('[name="remaining_iqd"]').value) || 0;
    if (paymentType === 'نەقد' && (remainingUsd !== 0 || remainingIqd !== 0)) {
        Swal.fire('هەڵە!', 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!', 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    const formData = new FormData(form);
    try {
        const res = await fetch('../process/purchase/update_purchase.php', {
            method: 'POST',
            body: formData
        });
        let data;
        try {
            data = await res.json();
        } catch (e) {
            const text = await res.text();
            console.error('Raw response:', text);
            Swal.fire('هەڵە!', 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.', 'error');
            return;
        }
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'کڕین نوێکرایەوە', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editPurchaseModal'));
            modal.hide();
            if (typeof loadPurchases === 'function') loadPurchases();
            if (typeof loadPurchaseSummary === 'function') loadPurchaseSummary();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        console.error('Error updating purchase:', err);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    } finally {
        // Reset updating flag and restore submit button
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
};
