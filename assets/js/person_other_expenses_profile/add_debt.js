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

    const remainingUSD = Math.max(totalDebtUSD - amountUSD - discountUSD, 0);
    const remainingIQD = Math.max(totalDebtIQD - amountIQD - discountIQD, 0);

    $('#debt_remaining_usd').val(formatNumber(remainingUSD));
    $('#debt_remaining_iqd').val(formatNumber(remainingIQD));
}

function fetchDebtTotalsForAddModal() {
    if (typeof PERSON_ID === 'undefined' || !PERSON_ID) {
        totalDebtUSD = 0;
        totalDebtIQD = 0;
        updateAddDebtSummaryFields();
        return;
    }

    $.getJSON('../process/person_other_expenses_profile/get_debt_totals.php', { person_id: PERSON_ID })
        .done(function(response) {
            if (response && response.success && response.data) {
                totalDebtUSD = parseFloat(response.data.total_debt_usd) || 0;
                totalDebtIQD = parseFloat(response.data.total_debt_iqd) || 0;
            } else {
                totalDebtUSD = 0;
                totalDebtIQD = 0;
            }
        })
        .fail(function() {
            totalDebtUSD = 0;
            totalDebtIQD = 0;
        })
        .always(function() {
            updateAddDebtSummaryFields();
        });
}

$('#addDebtForm').on('submit', function(e) {
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
    $.post('../process/person_other_expenses_profile/add_debt.php', formData, function(res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', 'دانەوە زیادکرا.', 'success');
            $('#addDebtModal').modal('hide');
            if (typeof loadDebtTable === 'function') loadDebtTable();
            if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا.', 'error');
        }
    }, 'json').fail(function() {
        Swal.fire('هەڵە!', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە', 'error');
    }).always(function() {
        // Reset submitting flag and restore submit button
        submitting = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});

$('#addDebtModal').on('shown.bs.modal', function() {
    fetchDebtTotalsForAddModal();
});

$('#addDebtModal').on('hidden.bs.modal', function() {
    totalDebtUSD = 0;
    totalDebtIQD = 0;
    $('#debt_remaining_usd').val('0.00');
    $('#debt_remaining_iqd').val('0.00');
});

$('#debt_amount_usd, #debt_discount_usd, #debt_amount_iqd, #debt_discount_iqd').on('input', function() {
    updateAddDebtSummaryFields();
});
