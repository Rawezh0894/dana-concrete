$(document).on('click', '.delete-sale', function() {
    var saleId = $(this).data('id');
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم فرۆشتنە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە!',
        cancelButtonText: 'داخستن'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/sale/delete_sale.php',
                type: 'POST',
                data: { id: saleId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سڕایەوە',
                            text: response.message || 'فرۆشتن بەسەرکەوتوویی سڕایەوە!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        if (window.reloadSales) window.reloadSales();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕووی دا لە سڕینەوە!'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕووی دا لە پەیوەندیکردن!'
                    });
                }
            });
        }
    });
});
