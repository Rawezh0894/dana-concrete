// Multiple submission prevention flag
let isUpdating = false;
let editDebtBaseUSD = 0;
let editDebtBaseIQD = 0;

function formatNumber(value) {
    return Number.isFinite(value) ? Number(value).toFixed(2) : '0.00';
}

function updateEditDebtSummaryFields() {
    const amountUSD = parseFloat($('#edit_debt_amount_usd').val()) || 0;
    const discountUSD = parseFloat($('#edit_debt_discount_usd').val()) || 0;
    const amountIQD = parseFloat($('#edit_debt_amount_iqd').val()) || 0;
    const discountIQD = parseFloat($('#edit_debt_discount_iqd').val()) || 0;

    // Get exchange rate (Price of $100 in IQD)
    let rate = parseFloat($('#edit_exchange_rate').val()) || 0;
    if (rate <= 0) rate = window.DEFAULT_USD_RATE || 150000;

    // Rate per 1 USD
    const ratePerUSD = rate / 100;

    let remainingUSD = editDebtBaseUSD - amountUSD - discountUSD;
    let remainingIQD = editDebtBaseIQD - amountIQD - discountIQD;

    // Cross-currency settlement logic
    if (remainingUSD < 0) {
        const excessUSD = Math.abs(remainingUSD);
        const equivalentIQD = excessUSD * ratePerUSD;
        remainingIQD -= equivalentIQD;
        remainingUSD = 0;
    } else if (remainingIQD < 0) {
        const excessIQD = Math.abs(remainingIQD);
        const equivalentUSD = excessIQD / ratePerUSD;
        remainingUSD -= equivalentUSD;
        remainingIQD = 0;
    }

    $('#edit_debt_remaining_usd').val(formatNumber(Math.max(remainingUSD, 0)));
    $('#edit_debt_remaining_iqd').val(formatNumber(Math.max(remainingIQD, 0)));
}

function fetchDebtTotalsForEditModal(oldValues) {
    if (typeof PERSON_ID === 'undefined' || !PERSON_ID) {
        editDebtBaseUSD = (oldValues.amount_usd || 0) + (oldValues.discount_usd || 0);
        editDebtBaseIQD = (oldValues.amount_iqd || 0) + (oldValues.discount_iqd || 0);
        updateEditDebtSummaryFields();
        return;
    }

    $.getJSON('../process/person_other_expenses_profile/get_debt_totals.php', { person_id: PERSON_ID })
        .done(function (response) {
            if (response && response.success && response.data) {
                const currentUSD = parseFloat(response.data.total_debt_usd) || 0;
                const currentIQD = parseFloat(response.data.total_debt_iqd) || 0;
                editDebtBaseUSD = currentUSD + (oldValues.amount_usd || 0) + (oldValues.discount_usd || 0);
                editDebtBaseIQD = currentIQD + (oldValues.amount_iqd || 0) + (oldValues.discount_iqd || 0);
            } else {
                editDebtBaseUSD = (oldValues.amount_usd || 0) + (oldValues.discount_usd || 0);
                editDebtBaseIQD = (oldValues.amount_iqd || 0) + (oldValues.discount_iqd || 0);
            }
        })
        .fail(function () {
            editDebtBaseUSD = (oldValues.amount_usd || 0) + (oldValues.discount_usd || 0);
            editDebtBaseIQD = (oldValues.amount_iqd || 0) + (oldValues.discount_iqd || 0);
        })
        .always(function () {
            updateEditDebtSummaryFields();
        });
}

window.setupEditDebtModal = function (oldValues) {
    // Set default rate if available
    $('#edit_exchange_rate').val(window.DEFAULT_USD_RATE || 150000);
    fetchDebtTotalsForEditModal(oldValues || {});
};

$('#editDebtForm').on('submit', function (e) {
    e.preventDefault();

    // Prevent multiple submissions
    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }

    // Set updating flag and disable submit button
    isUpdating = true;
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

    const formData = $(this).serialize() + '&person_id=' + PERSON_ID + '&debt_id=' + $('#edit_debt_id').val();
    $.post('../process/person_other_expenses_profile/update_debt.php', formData, function (res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', 'نوێکردنەوەی قەرزەکە کرا.', 'success');
            $('#editDebtModal').modal('hide');
            if (typeof loadDebtTable === 'function') loadDebtTable();
            if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
        }
    }, 'json').always(function () {
        // Reset updating flag and restore submit button
        isUpdating = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});

$('#editDebtModal').on('hidden.bs.modal', function () {
    editDebtBaseUSD = 0;
    editDebtBaseIQD = 0;
    $('#edit_debt_remaining_usd').val('0.00');
    $('#edit_debt_remaining_iqd').val('0.00');
});

$('#edit_debt_amount_usd, #edit_debt_discount_usd, #edit_debt_amount_iqd, #edit_debt_discount_iqd, #edit_exchange_rate').on('input', function () {
    updateEditDebtSummaryFields();
});
