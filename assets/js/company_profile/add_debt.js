let companyCurrencyType = null;
let lastTotalRemainingUSD = 0;
let lastTotalRemainingIQD = 0;
// Multiple submission prevention flag
let submitting = false;

function fetchCompanyCurrencyType() {
    return fetch(`../process/company_profile/select_debt.php?company_id=${COMPANY_ID}&company_info=1`)
        .then(res => res.json())
        .then(data => {
            companyCurrencyType = data.currency_type;
        });
}

// Prefetch currency type so it's ready before opening the modal
fetchCompanyCurrencyType();

function recalculateAmounts() {
    let amount_usd = parseFloat($('#debt_amount_usd').val()) || 0;
    let amount_iqd = parseFloat($('#debt_amount_iqd').val()) || 0;
    let discount_usd = parseFloat($('#debt_discount_usd').val()) || 0;
    let discount_iqd = parseFloat($('#debt_discount_iqd').val()) || 0;
    let dollar_rate = parseFloat($('#debt_dollar_rate').val()) || 150000;
    const rateFactor = dollar_rate > 0 ? (dollar_rate / 100) : 0;
    if (companyCurrencyType === 'دۆلار') {
        // Convert IQD payment and discount to USD
        let effective_usd_pay = amount_usd + (rateFactor > 0 ? (amount_iqd / rateFactor) : 0);
        let effective_discount_usd = discount_usd + (rateFactor > 0 ? (discount_iqd / rateFactor) : 0);
        let new_remaining = lastTotalRemainingUSD - (effective_usd_pay + effective_discount_usd);
        $('#total_remaining_usd').val(new_remaining.toLocaleString('en-US') + ' $');
        $('#total_remaining_iqd').val('');
    } else {
        // Convert USD payment and discount to IQD
        let usd_to_iqd = amount_usd * rateFactor;
        let discount_to_iqd = discount_usd * rateFactor;
        let total_iqd_effect = amount_iqd + discount_iqd + usd_to_iqd + discount_to_iqd;
        let new_remaining = lastTotalRemainingIQD - total_iqd_effect;
        $('#total_remaining_iqd').val(new_remaining.toLocaleString('en-US') + ' د.ع');
        $('#total_remaining_usd').val('');
    }
}

$('#debt_amount_usd, #debt_amount_iqd, #debt_dollar_rate, #debt_discount_usd, #debt_discount_iqd').on('input', recalculateAmounts);

async function fetchAndSetDollarRate(inputId) {
    try {
        const res = await fetch('../process/purchase_materilas/get_usd_rate.php');
        const data = await res.json();
        if (data.success && data.rate) {
            document.getElementById(inputId).value = data.rate;
        } else if (data.default_rate) {
            document.getElementById(inputId).value = data.default_rate;
        } else {
            document.getElementById(inputId).value = 139250;
        }
    } catch (e) {
        document.getElementById(inputId).value = 139250;
    }
}

$('#addDebtModal').on('show.bs.modal', function() {
    fetchAndSetDollarRate('debt_dollar_rate');
    fetchCompanyCurrencyType().then(() => {
        fetch(`../process/company_profile/select_debt.php?company_id=${COMPANY_ID}&total_remaining=1`)
            .then(res => res.json())
            .then(data => {
                lastTotalRemainingUSD = parseFloat(data.total_remaining_usd || 0);
                lastTotalRemainingIQD = parseFloat(data.total_remaining_iqd || 0);
                if (companyCurrencyType === 'دۆلار') {
                    $('#total_remaining_usd').val(lastTotalRemainingUSD.toLocaleString('en-US') + ' $');
                    $('#total_remaining_iqd').val('');
                } else {
                    $('#total_remaining_iqd').val(lastTotalRemainingIQD.toLocaleString('en-US') + ' د.ع');
                    $('#total_remaining_usd').val('');
                }
                recalculateAmounts();
            });
    });
    // Ensure discount field exists (if added in HTML later)
    if (!document.getElementById('debt_discount_usd')) {
        // no-op; UI may be updated separately
    }
});

document.getElementById('addDebtForm').onsubmit = async function(e) {
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
    // Read raw values before conversion
    const raw_amount_usd = parseFloat(form.amount_usd.value) || 0;
    const raw_amount_iqd = parseFloat(form.amount_iqd.value) || 0;
    const raw_discount_usd = parseFloat(form.discount_usd?.value || 0);
    const raw_discount_iqd = parseFloat(form.discount_iqd?.value || 0);
    const dollar_rate = parseFloat(form.dollar_rate?.value || 0);
    
    console.log('Form submission started');
    console.log('Amount USD:', raw_amount_usd);
    console.log('Amount IQD:', raw_amount_iqd);
    console.log('Discount USD:', raw_discount_usd);
    console.log('Discount IQD:', raw_discount_iqd);
    console.log('Company ID:', COMPANY_ID);
    
    if (raw_amount_usd <= 0 && raw_amount_iqd <= 0 && raw_discount_usd <= 0 && raw_discount_iqd <= 0) {
        Swal.fire('هەڵە!', 'بە لایەنی کەم یەک بڕ پڕبکە (پارە یان داشکاندن)', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    
    const rateFactor = dollar_rate > 0 ? (dollar_rate / 100) : 0;
    if (companyCurrencyType === 'دۆلار') {
        const effectiveUsd = raw_amount_usd + (rateFactor > 0 ? (raw_amount_iqd / rateFactor) : 0);
        const effectiveDiscountUsd = raw_discount_usd + (rateFactor > 0 ? (raw_discount_iqd / rateFactor) : 0);
        form.amount_usd.value = effectiveUsd;
        form.amount_iqd.value = 0;
        form.discount_usd.value = effectiveDiscountUsd;
        if (form.discount_iqd) form.discount_iqd.value = 0;
    } else if (companyCurrencyType === 'دینار') {
        const effectiveIqd = raw_amount_iqd + (rateFactor > 0 ? (raw_amount_usd * rateFactor) : 0);
        const effectiveDiscountIqd = raw_discount_iqd + (rateFactor > 0 ? (raw_discount_usd * rateFactor) : 0);
        form.amount_iqd.value = effectiveIqd;
        form.amount_usd.value = 0;
        if (form.discount_iqd) form.discount_iqd.value = effectiveDiscountIqd;
        form.discount_usd.value = 0;
    }
    
    const formData = new FormData(form);
    
    try {
        console.log('Sending request to add_debt.php');
        const res = await fetch('../process/company_profile/add_debt.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', res.status);
        const responseText = await res.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            Swal.fire('هەڵە!', 'هەڵە لە وەرگرتنی وەڵام لە سێرڤەر', 'error');
            return;
        }
        
        console.log('Parsed response:', data);
        
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'دانەوەی قەرز تۆمارکرا', 'success');
            form.reset();
            var modal = bootstrap.Modal.getInstance(document.getElementById('addDebtModal'));
            modal.hide();
            
            // Refresh all data without page reload
            if (typeof loadDebts === 'function') loadDebts();
            if (typeof loadPurchases === 'function') loadPurchases();
            if (typeof loadCompanyInfoCards === 'function') loadCompanyInfoCards();
        } else {
            console.error('Server error:', data.msg);
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        console.error('Network error:', err);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا: ' + err.message, 'error');
    } finally {
        // Reset submitting flag and restore submit button
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
};
