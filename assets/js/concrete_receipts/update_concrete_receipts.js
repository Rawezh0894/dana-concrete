$(document).on('click', '.edit-receipt', function() {
    var id = $(this).data('id');
    $.get('../process/concrete_receipts/select_concrete_receipts.php', function(res) {
        if (res.success) {
            var receipt = res.data.find(r => r.id == id);
            if (!receipt) return Swal.fire('هەڵە!', 'داتای پسوڵە نەدۆزرایەوە', 'error');
            // Fill modal fields
            $('#edit_receipt_id').val(receipt.id);
            $('#edit_receipt_number').val(receipt.receipt_number);
            $('#edit_customer_id').val(receipt.customer_id).trigger('change');
            $('#edit_location').val(receipt.location);
            $('#edit_meter_amount').val(receipt.meter_amount);
            $('#edit_formulas_id').val(receipt.formulas_id);
            $('#edit_pump_car_id').val(receipt.pump_car_id);
            $('#edit_pump_driver_id').val(receipt.pump_driver_id);
            $('#edit_mixer_car_id').val(receipt.mixer_car_id);
            $('#edit_mixer_driver_id').val(receipt.mixer_driver_id);
            $('#editConcreteReceiptModal').modal('show');
        } else {
            Swal.fire('هەڵە!', res.message || 'هەڵەیەک ڕویدا', 'error');
        }
    }, 'json');
});

$('#editConcreteReceiptForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.post('../process/concrete_receipts/update_concrete_receipts.php', formData, function(res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو', res.message || 'پسوڵە نوێکرایەوە', 'success');
            $('#editConcreteReceiptModal').modal('hide');
            if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
        } else {
            Swal.fire('هەڵە!', res.message || 'هەڵەیەک ڕویدا', 'error');
        }
    }, 'json').fail(function(xhr) {
        Swal.fire('هەڵە!', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕویدا', 'error');
    });
});
