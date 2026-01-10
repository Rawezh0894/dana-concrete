// Multiple submission prevention flag
let submitting = false;

// API call removed - exchange_rate will be manually entered by user with default value of 0

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
    
    // Validate exchange_rate (must be greater than 0)
    const exchangeRate = parseFloat(form.querySelector('[name="exchange_rate"]').value) || 0;
    if (exchangeRate <= 0) {
        Swal.fire('هەڵە!', 'تکایە نرخی 100 دۆلار بە دینار داخڵ بکە!', 'error');
        form.querySelector('[name="exchange_rate"]').classList.add('is-invalid');
        form.querySelector('[name="exchange_rate"]').focus();
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    } else {
        form.querySelector('[name="exchange_rate"]').classList.remove('is-invalid');
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
            if (typeof reloadPurchases === 'function') {
                reloadPurchases();
            } else if (typeof refreshPurchaseTable === 'function') {
                refreshPurchaseTable();
            } else if (typeof loadPurchaseData === 'function') {
                loadPurchaseData(true);
            }
            if (typeof loadPurchaseSummary === 'function') {
                loadPurchaseSummary(typeof currentFilterParams === 'string' ? currentFilterParams : '');
            }
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
