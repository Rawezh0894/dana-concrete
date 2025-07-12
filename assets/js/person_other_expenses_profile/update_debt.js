$('#editDebtForm').on('submit', function(e) {
    e.preventDefault();
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
    }, 'json');
});
