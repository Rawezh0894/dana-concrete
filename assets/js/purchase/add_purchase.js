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
    const rateInput = document.getElementById('exchange_rate');
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

// Initialize modal with API rate
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addPurchaseModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            updateDollarRateInModal();
        });
    }
});

document.getElementById('addPurchaseForm').onsubmit = async function(e) {
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
    
    const form = e.target;
    let hasNegative = false;
    form.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseFloat(input.value) < 0) {
            hasNegative = true;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    const type = form.querySelector('[name="type"]').value;
    const pricePerKgIqd = parseFloat(form.querySelector('[name="price_per_kg_iqd"]').value) || 0;
    const pricePerKgUsd = parseFloat(form.querySelector('[name="price_per_kg_usd"]').value) || 0;
    
    if (type === 'دینار' && pricePerKgIqd < 0) {
        Swal.fire('هەڵە!', 'بڕی price_per_kg_iqd نابێت منفی بێت!', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    if (type === 'دۆلار' && pricePerKgUsd < 0) {
        Swal.fire('هەڵە!', 'بڕی price_per_kg_usd نابێت منفی بێت!', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    if (hasNegative) {
        Swal.fire('هەڵە!', 'نابێت هیچ بڕێک منفی بێت!', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    const paymentType = form.querySelector('[name="payment_type"]').value;
    const remainingUsd = parseFloat(form.querySelector('[name="remaining_usd"]').value) || 0;
    const remainingIqd = parseFloat(form.querySelector('[name="remaining_iqd"]').value) || 0;
    if (paymentType === 'نەقد' && (remainingUsd !== 0 || remainingIqd !== 0)) {
        Swal.fire('هەڵە!', 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    
    const formData = new FormData(form);
    try {
        const res = await fetch('../process/purchase/add_purchase.php', {
            method: 'POST',
            body: formData
        });
        let text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response:', text);
            Swal.fire('هەڵە!', text || 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.', 'error');
            return;
        }
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'کڕین زیادکرا', 'success');
            form.reset();
            var modal = bootstrap.Modal.getInstance(document.getElementById('addPurchaseModal'));
            modal.hide();
            if (typeof loadPurchases === 'function') loadPurchases();
            if (typeof loadPurchaseSummary === 'function') loadPurchaseSummary();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    } finally {
        // Reset submitting flag and restore submit button
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
};
