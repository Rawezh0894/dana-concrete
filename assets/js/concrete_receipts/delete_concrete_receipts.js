// Multiple deletion prevention flag
let isDeleting = false;

$(document).on('click', '.delete-receipt', function() {
    // Prevent multiple delete operations
    if (isDeleting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }
    
    var id = $(this).data('id');
    const deleteBtn = $(this);
    const originalBtnText = deleteBtn.html();
    
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم پسوڵە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Set deleting flag and disable button
            isDeleting = true;
            deleteBtn.prop('disabled', true);
            deleteBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
            $.post('../process/concrete_receipts/delete_concrete_receipts.php', {id: id}, function(res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', res.message || 'پسوڵە سڕایەوە', 'success');
                    if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
                } else {
                    Swal.fire('هەڵە!', res.message || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json').fail(function(xhr) {
                Swal.fire('هەڵە!', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕویدا', 'error');
            }).always(function() {
                // Reset deleting flag and restore button
                isDeleting = false;
                deleteBtn.prop('disabled', false);
                deleteBtn.html(originalBtnText);
            });
        }
    });
});
