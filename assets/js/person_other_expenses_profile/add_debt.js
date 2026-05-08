// Multiple submission prevention flag
let submitting = false;
let totalDebtUSD = 0;
let totalDebtIQD = 0;

function formatNumber(value) {
    return Number.isFinite(value) ? Number(value).toFixed(2) : '0.00';
}

function updateAddDebtSummaryFields() {
    const amountUSD = parseFloat($('#debt_amount_usd').val()) || 0;
    const discountUSD = parseFloat($('#debt_discount_usd').val()) || 0;
    const amountIQD = parseFloat($('#debt_amount_iqd').val()) || 0;
    const discountIQD = parseFloat($('#debt_discount_iqd').val()) || 0;
    const changeBackUSD = parseFloat($('#debt_change_back_usd').val()) || 0;
    const changeBackIQD = parseFloat($('#debt_change_back_iqd').val()) || 0;

    let rate = parseFloat($('#exchange_rate').val()) || 0;
    if (rate <= 0) rate = window.DEFAULT_USD_RATE || 150000;
    const ratePerUSD = rate / 100;

    // Calculate total net effect in each currency
    // Note: Payment + Discount reduces debt, Change Back increases debt.
    let netUSD = amountUSD + discountUSD - changeBackUSD;
    let netIQD = amountIQD + discountIQD - changeBackIQD;

    let remainingUSD = totalDebtUSD;
    let remainingIQD = totalDebtIQD;

    // Apply USD net effect
    if (netUSD > 0) {
        let reduction = Math.min(netUSD, remainingUSD);
        remainingUSD -= reduction;
        let excess = netUSD - reduction;
        if (excess > 0) {
            remainingIQD -= (excess * ratePerUSD);
        }
    } else if (netUSD < 0) {
        remainingUSD += Math.abs(netUSD);
    }

    // Apply IQD net effect
    if (netIQD > 0) {
        let reduction = Math.min(netIQD, remainingIQD);
        remainingIQD -= reduction;
        let excess = netIQD - reduction;
        if (excess > 0 && ratePerUSD > 0) {
            remainingUSD -= (excess / ratePerUSD);
        }
    } else if (netIQD < 0) {
        remainingIQD += Math.abs(netIQD);
    }

    $('#debt_remaining_usd').val(formatNumber(Math.max(remainingUSD, 0)) + ' $');
    $('#debt_remaining_iqd').val(formatNumber(Math.max(remainingIQD, 0)) + ' د.ع');
}

function fetchDebtTotalsForAddModal() {
    if (typeof PERSON_ID === 'undefined' || !PERSON_ID) {
        totalDebtUSD = 0;
        totalDebtIQD = 0;
        updateAddDebtSummaryFields();
        return;
    }

    $.getJSON('../process/person_other_expenses_profile/get_debt_totals.php', { person_id: PERSON_ID })
        .done(function (response) {
            if (response && response.success && response.data) {
                totalDebtUSD = parseFloat(response.data.total_debt_usd) || 0;
                totalDebtIQD = parseFloat(response.data.total_debt_iqd) || 0;
            } else {
                totalDebtUSD = 0;
                totalDebtIQD = 0;
            }
        })
        .fail(function () {
            totalDebtUSD = 0;
            totalDebtIQD = 0;
        })
        .always(function () {
            updateAddDebtSummaryFields();
        });
}

$('#addDebtForm').on('submit', function (e) {
    e.preventDefault();

    // Prevent multiple submissions
    if (submitting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }

    // Set submitting flag and disable submit button
    submitting = true;
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

    const formData = $(this).serialize() + '&person_id=' + PERSON_ID;
    $.post('../process/person_other_expenses_profile/add_debt.php', formData, function (res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', 'دانەوە زیادکرا.', 'success');
            $('#addDebtModal').modal('hide');
            if (typeof loadDebtTable === 'function') loadDebtTable();
            if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا.', 'error');
        }
    }, 'json').fail(function () {
        Swal.fire('هەڵە!', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە', 'error');
    }).always(function () {
        // Reset submitting flag and restore submit button
        submitting = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});

$('#addDebtModal').on('shown.bs.modal', function () {
    $('#exchange_rate').val(window.DEFAULT_USD_RATE || 150000);
    fetchDebtTotalsForAddModal();
});

$('#addDebtModal').on('hidden.bs.modal', function () {
    totalDebtUSD = 0;
    totalDebtIQD = 0;
    $('#debt_remaining_usd').val('0.00');
    $('#debt_remaining_iqd').val('0.00');
});

async function fetchAndSetDollarRate(inputId) {
    try {
        const res = await fetch('../process/purchase_materilas/get_usd_rate.php');
        const data = await res.json();
        if (data.success && data.rate) {
            document.getElementById(inputId).value = data.rate;
            updateAddDebtSummaryFields();
        }
    } catch (e) {
        console.error('Error fetching rate:', e);
    }
}

$('#debt_amount_usd, #debt_discount_usd, #debt_amount_iqd, #debt_discount_iqd, #exchange_rate, #debt_change_back_usd, #debt_change_back_iqd').on('input', function () {
    updateAddDebtSummaryFields();
});
