// Multiple deletion prevention flag
let isDeleting = false;

function deleteDebt(id) {
    // Prevent multiple delete operations
    if (isDeleting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }
    
    Swal.fire({
        title: 'دڵنیی؟',
        text: 'دەتەوێت ئەم دانەوەی قەرز بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Set deleting flag and disable button
            isDeleting = true;
            const deleteBtn = $(`.delete-debt[data-id="${id}"]`);
            const originalBtnText = deleteBtn.html();
            deleteBtn.prop('disabled', true);
            deleteBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
            $.post('../process/person_other_expenses_profile/delete_debt.php', {id: id}, function(res) {
                if (res.success) {
                    Swal.fire('سەرکەوتوو!', 'دانەوە سڕایەوە.', 'success');
                    if (typeof loadDebtTable === 'function') loadDebtTable();
                    if (typeof loadPersonSummaryCards === 'function') loadPersonSummaryCards();
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
                }
            }, 'json').always(function() {
                // Reset deleting flag and restore button
                isDeleting = false;
                deleteBtn.prop('disabled', false);
                deleteBtn.html(originalBtnText);
            });
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
