// Multiple deletion prevention flag
let isDeleting = false;

// Delete purchase
$(document).on('click', '.delete-purchase', function() {
    // Prevent multiple delete operations
    if (isDeleting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }
    
    const id = $(this).data('id');
    const deleteBtn = $(this);
    const originalBtnText = deleteBtn.html();
    
    console.log('Deleting purchase id:', id); // Debug line
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'ئەم کڕینە سڕدرێتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سڕەوە!',
        cancelButtonText: 'داخستن'
    }).then((result) => {
        if (result.isConfirmed) {
            // Set deleting flag and disable button
            isDeleting = true;
            deleteBtn.prop('disabled', true);
            deleteBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
            $.post('../process/purchase/delete_purchase.php', { id }, function(res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', 'کڕینەکە سڕایەوە.', 'success');
                    if (typeof refreshPurchaseTable === 'function') {
                        refreshPurchaseTable();
                    } else if (typeof loadPurchases === 'function') {
                        loadPurchases(currentFilterParams || '', currentPurchasePage || 1, currentSearchTerm || '');
                    }
                    if (typeof loadPurchaseSummary === 'function') {
                        loadPurchaseSummary(typeof currentFilterParams === 'string' ? currentFilterParams : '');
                    }
                } else {
                    console.error('Delete error:', res);
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX fail:', status, error, xhr.responseText);
                Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا (AJAX)', 'error');
            }).always(function() {
                // Reset deleting flag and restore button
                isDeleting = false;
                deleteBtn.prop('disabled', false);
                deleteBtn.html(originalBtnText);
            });
        }
    });
});
