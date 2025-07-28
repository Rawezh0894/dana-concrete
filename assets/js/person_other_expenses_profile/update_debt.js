// Multiple submission prevention flag
let isUpdating = false;

$('#editDebtForm').on('submit', function(e) {
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
    $.post('../process/person_other_expenses_profile/update_debt.php', formData, function(res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', 'نوێکردنەوەی قەرزەکە کرا.', 'success');
            $('#editDebtModal').modal('hide');
            if (typeof loadDebtTable === 'function') loadDebtTable();
            if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
        }
    }, 'json').always(function() {
        // Reset updating flag and restore submit button
        isUpdating = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});
