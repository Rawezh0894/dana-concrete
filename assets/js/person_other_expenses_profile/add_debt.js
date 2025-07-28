// Multiple submission prevention flag
let submitting = false;

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
