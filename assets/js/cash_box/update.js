$(document).ready(function() {
    // Show edit modal and fill data
    $('#cashBoxTable').on('click', '.btn-edit-cashbox', function() {
        var row = $(this).closest('tr');
        var id = $(this).data('id');
        $('#edit_id').val(id);
        $('#edit_date').val(row.find('td:eq(1)').text().trim());
        $('#edit_type').val(row.find('td:eq(2)').data('type') || '');
        $('#edit_amount_iqd').val(row.find('td:eq(3)').text().trim());
        $('#edit_amount_usd').val(row.find('td:eq(4)').text().trim());
        $('#edit_currency').val(row.find('td:eq(5)').text().trim());
        $('#edit_note').val(row.find('td:eq(6)').text().trim());
        $('#editCashBoxModal').modal('show');
    });

    // Submit update
    $('#editCashBoxForm').on('submit', function(e) {
        e.preventDefault();
        var formData = {
            id: $('#edit_id').val(),
            date: $('#edit_date').val(),
            type: $('#edit_type').val(),
            amount_iqd: $('#edit_amount_iqd').val(),
            amount_usd: $('#edit_amount_usd').val(),
            currency: $('#edit_currency').val(),
            note: $('#edit_note').val()
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
                        updateCashBoxSummary(from, to);
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
