let companyCurrencyType = null;
let lastTotalRemainingUSD = 0;
let lastTotalRemainingIQD = 0;

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

$('#addDebtModal').on('show.bs.modal', function() {
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
    const form = e.target;
    const formData = new FormData(form);
    formData.append('company_id', COMPANY_ID);
    const amount_usd = parseFloat(form.amount_usd.value) || 0;
    const amount_iqd = parseFloat(form.amount_iqd.value) || 0;
    if (amount_usd <= 0 && amount_iqd <= 0) {
        Swal.fire('هەڵە!', 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار)', 'error');
        return;
    }
    const res = await fetch('../process/company_profile/add_debt.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'دانەوەی قەرز تۆمارکرا', 'success');
        form.reset();
        var modal = bootstrap.Modal.getInstance(document.getElementById('addDebtModal'));
        modal.hide();
        if (typeof loadDebts === 'function') loadDebts();
        if (typeof loadPurchases === 'function') loadPurchases();
        if (typeof loadCompanyInfoCards === 'function') loadCompanyInfoCards();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
};
