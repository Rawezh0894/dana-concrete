$(document).ready(function() {
    // Delegate for dynamic rows
    $('#cashBoxTable').on('click', '.btn-delete-cashbox', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'ئەم مامەڵەی قاسە بسڕێتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بسڕەوە!',
            cancelButtonText: 'داخستن'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../process/cash_box/delete.php',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('سڕایەوە!', 'مامەڵەکە سڕایەوە.', 'success');
                            if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                            if (typeof updateCashBoxSummary === 'function') {
                                var from = $('#filter_from').val();
                                var to = $('#filter_to').val();
                                updateCashBoxSummary(from, to);
                            }
                        } else {
                            Swal.fire('هەڵە!', response.error || 'ناتوانرێت بسڕدرێتەوە', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
                    }
                });
            }
        });
    });
});
