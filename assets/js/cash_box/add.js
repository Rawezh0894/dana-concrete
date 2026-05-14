var submitting = false;

$(document).ready(function () {
    // Reset balance warning when modal opens
    $('#addCashBoxModal').on('show.bs.modal', function () {
        $('#addBalanceWarning').addClass('d-none');
        $('#addBalanceWarningText').text('');
    });

    $('#addCashBoxForm').on('submit', function (e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;

        var noteValue = ($('#note').val() || '').trim();
        if (noteValue.length < 10) {
            Swal.fire('ئاگادارکردنەوە', 'تێبینی پێویستە بە کورتەی مانادار بنوسرێت (کەمترین ١٠ پیت).', 'warning');
            submitting = false;
            return;
        }

        $('#addBalanceWarning').addClass('d-none');

        var formData = {
            date:       $('#date').val(),
            type:       $('#type').val(),
            amount_iqd: $('#amount_iqd').val(),
            amount_usd: $('#amount_usd').val(),
            currency:   $('#currency').val(),
            note:       noteValue,
        };

        $.ajax({
            url:      '../process/cash_box/add.php',
            method:   'POST',
            data:     formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'سەرکەوتوو!', text: 'مامەڵە زیادکرا', timer: 1500, showConfirmButton: false });
                    $('#addCashBoxModal').modal('hide');
                    $('#addCashBoxForm')[0].reset();
                    if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                    if (typeof updateCashBoxSummary === 'function') {
                        updateCashBoxSummary($('#filter_from').val(), $('#filter_to').val(), ($('#cashBoxSearch').val() || '').trim());
                    }
                    if (typeof loadDailyClosing === 'function' && $('#dailyClosingPanel').hasClass('show')) {
                        loadDailyClosing($('#filter_from').val(), $('#filter_to').val(), ($('#cashBoxSearch').val() || '').trim());
                    }
                } else if (response.insufficient_balance) {
                    // Show inline warning in the modal
                    $('#addBalanceWarningText').text(response.error);
                    $('#addBalanceWarning').removeClass('d-none');
                    // Scroll warning into view
                    document.getElementById('addBalanceWarning').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    Swal.fire('هەڵە!', response.error || 'ناتوانرێت مامەڵە زیاد بکرێت', 'error');
                }
                submitting = false;
            },
            error: function () {
                Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
                submitting = false;
            },
        });
    });
});
