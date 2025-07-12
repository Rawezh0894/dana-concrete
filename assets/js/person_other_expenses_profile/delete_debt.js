function deleteDebt(id) {
    Swal.fire({
        title: 'دڵنیی؟',
        text: 'دەتەوێت ئەم دانەوەی قەرز بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../process/person_other_expenses_profile/delete_debt.php', {id: id}, function(res) {
                if (res.success) {
                    Swal.fire('سەرکەوتوو!', 'دانەوە سڕایەوە.', 'success');
                    if (typeof loadDebtTable === 'function') loadDebtTable();
                    if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
                }
            }, 'json');
        }
    });
}

function attachDeleteDebtEvents() {
    $(document).off('click', '.delete-debt');
    $(document).on('click', '.delete-debt', function() {
        const id = $(this).data('id');
        if (id) deleteDebt(id);
    });
}
