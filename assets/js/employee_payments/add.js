$(function() {
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('../process/employee_payments/add.php', formData, function(response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'پارەدان بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addPaymentForm')[0].reset();
                if (window.loadPayments) window.loadPayments();
                $('#addPaymentModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            let msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                msg += '\n' + xhr.responseText;
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });
});
