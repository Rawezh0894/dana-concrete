$(document).on('click', '.delete-receipt', function() {
    var id = $(this).data('id');
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
            $.post('../process/concrete_receipts/delete_concrete_receipts.php', {id: id}, function(res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', res.message || 'پسوڵە سڕایەوە', 'success');
                    if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
                } else {
                    Swal.fire('هەڵە!', res.message || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json').fail(function(xhr) {
                Swal.fire('هەڵە!', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕویدا', 'error');
            });
        }
    });
});
