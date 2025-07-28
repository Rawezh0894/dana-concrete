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

function recalculateAmounts() {
    let amount_usd = parseFloat($('#debt_amount_usd').val()) || 0;
    let amount_iqd = parseFloat($('#debt_amount_iqd').val()) || 0;
    let dollar_rate = parseFloat($('#debt_dollar_rate').val()) || 150000;
    if (companyCurrencyType === 'دۆلار') {
        let effective_usd = amount_usd + (amount_iqd / (dollar_rate / 100));
        let new_remaining = lastTotalRemainingUSD - effective_usd;
        $('#total_remaining_usd').val(new_remaining.toLocaleString('en-US') + ' $');
        $('#total_remaining_iqd').val('');
    } else {
        let effective_iqd = amount_iqd + (amount_usd * (dollar_rate / 100));
        let new_remaining = lastTotalRemainingIQD - effective_iqd;
        $('#total_remaining_iqd').val(new_remaining.toLocaleString('en-US') + ' د.ع');
        $('#total_remaining_usd').val('');
    }
}

$('#debt_amount_usd, #debt_amount_iqd, #debt_dollar_rate').on('input', recalculateAmounts);

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
});

$('#addDebtForm').on('submit', function(e) {
    if (companyCurrencyType === 'دۆلار') {
        let amount_usd = parseFloat($('#debt_amount_usd').val()) || 0;
        let amount_iqd = parseFloat($('#debt_amount_iqd').val()) || 0;
        let dollar_rate = parseFloat($('#debt_dollar_rate').val()) || 150000;
        let effective_usd = amount_usd + (amount_iqd / (dollar_rate / 100));
        $('#debt_amount_usd').val(effective_usd);
        $('#debt_amount_iqd').val(0);
    } else if (companyCurrencyType === 'دینار') {
        let amount_usd = parseFloat($('#debt_amount_usd').val()) || 0;
        let amount_iqd = parseFloat($('#debt_amount_iqd').val()) || 0;
        let dollar_rate = parseFloat($('#debt_dollar_rate').val()) || 150000;
        let effective_iqd = amount_iqd + (amount_usd * (dollar_rate / 100));
        $('#debt_amount_iqd').val(effective_iqd);
        $('#debt_amount_usd').val(0);
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
    const formData = new FormData(form);
    formData.append('company_id', COMPANY_ID);
    const amount_usd = parseFloat(form.amount_usd.value) || 0;
    const amount_iqd = parseFloat(form.amount_iqd.value) || 0;
    
    console.log('Form submission started');
    console.log('Amount USD:', amount_usd);
    console.log('Amount IQD:', amount_iqd);
    console.log('Company ID:', COMPANY_ID);
    
    if (amount_usd <= 0 && amount_iqd <= 0) {
        Swal.fire('هەڵە!', 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار)', 'error');
        submitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return;
    }
    
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
            
            // Also refresh the debt table if it's currently visible
            if ($('#debt').hasClass('active')) {
                loadDebts();
            }
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
