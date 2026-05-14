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
                                var search = ($('#cashBoxSearch').val() || '').trim();
                                updateCashBoxSummary(from, to, search);
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

    const $deleteAllBtn = $('#deleteAllCashBoxBtn');
    if ($deleteAllBtn.length) {
        $deleteAllBtn.on('click', function() {
            Swal.fire({
                title: 'دڵنیایت بۆ سڕینەوەی هەموو مامەڵەکان؟',
                html: 'ئەم کارە ناگەڕێتەوە. بۆ دڵنیابوون، وشەی <b>DELETE</b> بنووسە.',
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'DELETE',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، هەمووی بسڕەوە',
                cancelButtonText: 'داخستن',
                preConfirm: (value) => {
                    if ((value || '').trim().toUpperCase() !== 'DELETE') {
                        Swal.showValidationMessage('تکایە وشەی DELETE بنووسە بۆ بەردەوامبوون');
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = $deleteAllBtn.html();
                    $deleteAllBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>چاوەڕوان بە...');
                    $.ajax({
                        url: '../process/cash_box/delete_all.php',
                        method: 'POST',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                const deletedCount = response.deleted_count !== undefined ? response.deleted_count : '';
                                const successMsg = deletedCount ? `هەموو ${deletedCount} مامەڵە سڕایەوە.` : 'هەموو مامەڵەکان سڕایەوە.';
                                Swal.fire('سڕایەوە!', successMsg, 'success');
                                if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                                if (typeof updateCashBoxSummary === 'function') {
                                    var from = $('#filter_from').val();
                                    var to = $('#filter_to').val();
                                    var search = ($('#cashBoxSearch').val() || '').trim();
                                    updateCashBoxSummary(from, to, search);
                                }
                            } else {
                                Swal.fire('هەڵە!', response.error || 'ناتوانرێت مامەڵەکان بسڕدرێتەوە', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
                        },
                        complete: function() {
                            $deleteAllBtn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        });
    }
});
