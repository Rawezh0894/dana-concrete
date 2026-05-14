$(document).ready(function() {
    // Show edit modal and fill data
    $('#cashBoxTable').on('click', '.btn-edit-cashbox', function() {
        var id = $(this).data('id');
        var rowData = $(this).data('row');
        
        $('#edit_id').val(id);
        $('#edit_date').val(rowData.date || '');
        
        // Set type based on the original value
        var typeValue = '';
        if (rowData.type === 'deposit') typeValue = 'deposit';
        else if (rowData.type === 'withdraw') typeValue = 'withdraw';
        $('#edit_type').val(typeValue);
        
        // Clean amount values (remove formatting)
        var amountIqd = rowData.amount_iqd || 0;
        var amountUsd = rowData.amount_usd || 0;
        $('#edit_amount_iqd').val(amountIqd);
        $('#edit_amount_usd').val(amountUsd);
        
        $('#edit_currency').val(rowData.currency || '');
        $('#edit_note').val(rowData.note || '');
        
        $('#editCashBoxModal').modal('show');
    });

    // Submit update
    $('#editCashBoxForm').on('submit', function(e) {
        e.preventDefault();
        var noteValue = ($('#edit_note').val() || '').trim();
        if (noteValue.length < 10) {
            Swal.fire('ئاگادارکردنەوە', 'تێبینی پێویستە بە کورتەی مانادار بنوسرێت (کەمترین ١٠ پیت).', 'warning');
            return;
        }
        var formData = {
            id: $('#edit_id').val(),
            date: $('#edit_date').val(),
            type: $('#edit_type').val(),
            amount_iqd: $('#edit_amount_iqd').val(),
            amount_usd: $('#edit_amount_usd').val(),
            currency: $('#edit_currency').val(),
            note: noteValue
        };
        $.ajax({
            url: '../process/cash_box/update.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('سەرکەوتوو!', 'مامەڵە نوێکرایەوە', 'success');
                    $('#editCashBoxModal').modal('hide');
                    if (typeof loadCashBoxEntries === 'function') loadCashBoxEntries();
                    if (typeof updateCashBoxSummary === 'function') {
                        var from = $('#filter_from').val();
                        var to = $('#filter_to').val();
                        var search = ($('#cashBoxSearch').val() || '').trim();
                        updateCashBoxSummary(from, to, search);
                    }
                } else {
                    Swal.fire('هەڵە!', response.error || 'ناتوانرێت نوێکرایەوە', 'error');
                }
            },
            error: function() {
                Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
            }
        });
    });
});
