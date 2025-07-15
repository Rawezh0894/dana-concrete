let submitting = false;
$(document).ready(function() {
    $('#addCashBoxForm').on('submit', function(e) {
        if (submitting) return false;
        submitting = true;
        e.preventDefault();
        var formData = {
            date: $('#date').val(),
            type: $('#type').val(),
            amount_iqd: $('#amount_iqd').val(),
            amount_usd: $('#amount_usd').val(),
            currency: $('#currency').val(),
            note: $('#note').val()
        };
        $.ajax({
            url: '../process/cash_box/add.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('سەرکەوتوو!', 'مامەڵە زیادکرا', 'success');
                    $('#addCashBoxModal').modal('hide');
                    $('#addCashBoxForm')[0].reset();
                    if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                } else {
                    Swal.fire('هەڵە!', response.error || 'ناتوانرێت مامەڵە زیاد بکرێت', 'error');
                }
                submitting = false;
            },
            error: function() {
                Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
                submitting = false;
            }
        });
    });
});
