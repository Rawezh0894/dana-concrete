$(function() {
    $('#addAdjustmentForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('../process/stock_adjustments/add.php', formData, function(res) {
            if (res.success) {
                $('#addAdjustmentModal').modal('hide');
                swalAlert('سەرکەوتوو', res.message || 'گۆڕانکاری زیادکرا', 'success');
                if (window.loadAdjustments) window.loadAdjustments();
                $('#addAdjustmentForm')[0].reset();
            } else {
                swalAlert('هەڵە', res.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
        });
    });
    // Select2 removed
});
