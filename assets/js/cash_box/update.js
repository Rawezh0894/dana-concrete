$(document).ready(function () {
    // Open edit modal and populate fields
    $('#cashBoxTable').on('click', '.btn-edit-cashbox', function () {
        var id      = $(this).data('id');
        var rowData = (typeof cashBoxRowStore !== 'undefined' && cashBoxRowStore[id]) ? cashBoxRowStore[id] : {};

        $('#edit_id').val(id);
        $('#edit_date').val(rowData.date || '');

        var typeVal = (rowData.type === 'deposit' || rowData.type === 'withdraw') ? rowData.type : '';
        $('#edit_type').val(typeVal);

        $('#edit_amount_iqd').val(rowData.amount_iqd || 0);
        $('#edit_amount_usd').val(rowData.amount_usd || 0);
        $('#edit_currency').val(rowData.currency || '');
        $('#edit_note').val(rowData.note || '');

        $('#editCashBoxModal').modal('show');
    });

    // Submit update
    $('#editCashBoxForm').on('submit', function (e) {
        e.preventDefault();
        var noteValue = ($('#edit_note').val() || '').trim();
        if (noteValue.length < 10) {
            Swal.fire('ئاگادارکردنەوە', 'تێبینی پێویستە بە کورتەی مانادار بنوسرێت (کەمترین ١٠ پیت).', 'warning');
            return;
        }
        var formData = {
            id:         $('#edit_id').val(),
            date:       $('#edit_date').val(),
            type:       $('#edit_type').val(),
            amount_iqd: $('#edit_amount_iqd').val(),
            amount_usd: $('#edit_amount_usd').val(),
            currency:   $('#edit_currency').val(),
            note:       noteValue,
        };
        $.ajax({
            url:      '../process/cash_box/update.php',
            method:   'POST',
            data:     formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'سەرکەوتوو!', text: 'مامەڵە نوێکرایەوە', timer: 1500, showConfirmButton: false });
                    $('#editCashBoxModal').modal('hide');
                    if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                    if (typeof updateCashBoxSummary === 'function') {
                        var from   = $('#filter_from').val();
                        var to     = $('#filter_to').val();
                        var search = ($('#cashBoxSearch').val() || '').trim();
                        updateCashBoxSummary(from, to, search);
                    }
                    if (typeof loadDailyClosing === 'function' && $('#dailyClosingPanel').hasClass('show')) {
                        loadDailyClosing($('#filter_from').val(), $('#filter_to').val(), ($('#cashBoxSearch').val() || '').trim());
                    }
                } else {
                    Swal.fire('هەڵە!', response.error || 'ناتوانرێت نوێکرایەوە', 'error');
                }
            },
            error: function () {
                Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
            },
        });
    });
});
