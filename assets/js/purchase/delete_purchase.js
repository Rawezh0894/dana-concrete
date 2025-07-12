// Delete purchase
$(document).on('click', '.delete-purchase', function() {
    const id = $(this).data('id');
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
            $.post('../process/purchase/delete_purchase.php', { id }, function(res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', 'کڕینەکە سڕایەوە.', 'success');
                    if (typeof loadPurchases === 'function') loadPurchases();
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json');
        }
    });
});
