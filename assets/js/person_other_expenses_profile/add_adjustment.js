$('#addAdjustmentForm').on('submit', function (e) {
    e.preventDefault();

    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

    const formData = $(this).serialize() + '&person_id=' + PERSON_ID;

    $.post('../process/person_other_expenses_profile/add_adjustment.php', formData, function (res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', res.msg, 'success');
            $('#addAdjustmentModal').modal('hide');
            $('#addAdjustmentForm')[0].reset();
            if (typeof loadAdjustmentTable === 'function') loadAdjustmentTable();
            if (typeof loadSummaryCards === 'function') loadSummaryCards();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
        }
    }, 'json').always(function () {
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});
