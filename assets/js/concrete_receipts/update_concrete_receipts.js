$(document).on('click', '.edit-receipt', function() {
    var id = $(this).data('id');
    
    // Fetch the specific receipt data using the new endpoint
    $.get('../process/concrete_receipts/get_single_receipt.php', {id: id}, function(res) {
        if (res.success && res.data) {
            const receipt = res.data;
            
            // Fill modal fields
            $('#edit_receipt_id').val(receipt.id);
            $('#edit_receipt_number').val(receipt.receipt_number);
            $('#edit_customer_id').val(receipt.customer_id).trigger('change');
            $('#edit_location').val(receipt.location);
            $('#edit_receiver_name').val(receipt.receiver_name).trigger('change');
            $('#edit_meter_amount').val(receipt.meter_amount);
            $('#edit_formulas_id').val(receipt.formulas_id);
            $('#edit_pump_car_id').val(receipt.pump_car_id);
            $('#edit_pump_driver_id').val(receipt.pump_driver_id);
            $('#edit_mixer_car_id').val(receipt.mixer_car_id);
            $('#edit_mixer_driver_id').val(receipt.mixer_driver_id);
            
            // Show the modal
            $('#editConcreteReceiptModal').modal('show');
        } else {
            Swal.fire('هەڵە!', res.message || 'داتای پسوڵە نەدۆزرایەوە', 'error');
        }
    }, 'json').fail(function(xhr) {
        Swal.fire('هەڵە!', 'هەڵەیەک لە وەرگرتنی داتاکان هەیە', 'error');
        console.error('Error fetching receipt:', xhr);
    });
});

// Multiple submission prevention flag
let isUpdating = false;

$('#editConcreteReceiptForm').on('submit', function(e) {
    e.preventDefault();
    
    // Prevent multiple submissions
    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }
    
    // Set updating flag and disable submit button
    isUpdating = true;
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
    
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
    }).always(function() {
        // Reset updating flag and restore submit button
        isUpdating = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});
