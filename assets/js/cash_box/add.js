let submitting = false;
$(document).ready(function() {
    $('#addCashBoxForm').on('submit', function(e) {
        if (submitting) return false;
        submitting = true;
        e.preventDefault();
        var noteValue = ($('#note').val() || '').trim();
        if (noteValue.length < 10) {
            Swal.fire('ئاگادارکردنەوە', 'تێبینی پێویستە بە کورتەی مانادار بنوسرێت (کەمترین ١٠ پیت).', 'warning');
            submitting = false;
            return;
        }
        var formData = {
            date: $('#date').val(),
            type: $('#type').val(),
            amount_iqd: $('#amount_iqd').val(),
            amount_usd: $('#amount_usd').val(),
            currency: $('#currency').val(),
            note: noteValue
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
                    if (typeof updateCashBoxSummary === 'function') {
                        var from = $('#filter_from').val();
                        var to = $('#filter_to').val();
                        var search = ($('#cashBoxSearch').val() || '').trim();
                        updateCashBoxSummary(from, to, search);
                    }
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
