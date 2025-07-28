$(document).on('click', '.delete-customer-btn', function() {
    const btn = $(this);
    const id = btn.data('id');
    
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'ئایا دەتەوێت ئەم کڕیارە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/customer/delete_customer.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire('سڕایەوە!', data.message || 'کڕیار سڕایەوە', 'success');
                        if (typeof loadCustomers === 'function') loadCustomers();
                        // Refresh summary stats
                        if (typeof loadSummaryStats === 'function') loadSummaryStats();
                    } else {
                        Swal.fire('هەڵە!', data.message || 'هەڵەیەک ڕووی دا', 'error');
                    }
                },
                error: function() {
                    Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا', 'error');
                }
            });
        }
    });
});
