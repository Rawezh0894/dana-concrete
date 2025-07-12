$(function() {
    $(document).on('click', '.delete-adjustment', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت ئەم گۆڕانکارییە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/stock_adjustments/delete.php', { id: id }, function(res) {
                    if (res.success) {
                        swalAlert('سەرکەوتوو', res.message || 'گۆڕانکاری سڕایەوە', 'success');
                        if (window.loadAdjustments) window.loadAdjustments();
                    } else {
                        swalAlert('هەڵە', res.message || 'هەڵەیەک هەیە', 'error');
                    }
                }, 'json').fail(function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                });
            }
        });
    });
});
