let submitting = false;
$('#addDebtForm').on('submit', function(e) {
    if (submitting) return false;
    submitting = true;
    e.preventDefault();
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
        submitting = false;
    }, 'json').fail(function() {
        submitting = false;
    });
});
