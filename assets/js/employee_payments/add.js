$(function () {
    $('#hrTransactionForm').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('../process/hr/add_transaction.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', response.msg || 'کردارەکە بە سەرکەوتوویی ئەنجامدرا!', 'success');
                $('#hrTransactionForm')[0].reset();
                $('#transaction_id').val('');
                if (window.loadPayments) window.loadPayments();
                $('#hrTransactionModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.msg || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
        });
    });

    // Reset form when modal is hidden
    $('#hrTransactionModal').on('hidden.bs.modal', function () {
        $('#hrTransactionForm')[0].reset();
        $('#transaction_id').val('');
        $('#hrTransactionModalLabel').text('مامەڵەی نوێ (HR)');
    });
});
