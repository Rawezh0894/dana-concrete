// Multiple deletion prevention flag
let isDeleting = false;

$(document).on('click', '.delete-sale', function() {
    // Prevent multiple delete operations
    if (isDeleting) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }
    
    var saleId = $(this).data('id');
    var deleteBtn = $(this);
    var originalBtnText = deleteBtn.html();
    
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
            // Set deleting flag and disable button
            isDeleting = true;
            deleteBtn.prop('disabled', true);
            deleteBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
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
                },
                complete: function() {
                    // Reset deleting flag and restore button
                    isDeleting = false;
                    deleteBtn.prop('disabled', false);
                    deleteBtn.html(originalBtnText);
                }
            });
        }
    });
});
