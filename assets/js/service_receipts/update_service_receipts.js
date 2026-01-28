$(document).on('click', '.edit-receipt', function () {
    var id = $(this).data('id');

    $.get('../process/service_receipts/get_single_receipt.php', { id: id }, function (res) {
        if (res.success && res.data) {
            const receipt = res.data;

            $('#edit_receipt_id').val(receipt.id);
            $('#edit_receipt_number').val(receipt.receipt_number);

            if (receipt.created_at) {
                const d = new Date(receipt.created_at);
                const isoStr = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                $('#edit_created_at').val(isoStr);
            }

            $('#edit_customer_id').val(receipt.customer_id).trigger('change');
            $('#edit_location').val(receipt.location);
            $('#edit_receiver_name').val(receipt.receiver_name).trigger('change');
            $('#edit_meter_amount').val(receipt.meter_amount);
            $('#edit_price_per_meter').val(receipt.price_per_meter);
            $('#edit_payment_type').val(receipt.payment_type).trigger('change');
            $('#edit_paid_usd').val(receipt.paid_usd);
            $('#edit_paid_iqd').val(receipt.paid_iqd);
            $('#edit_exchange_rate').val(receipt.exchange_rate);
            $('#edit_pump_car_id').val(receipt.pump_car_id).trigger('change');
            $('#edit_pump_driver_id').val(receipt.pump_driver_id).trigger('change');
            $('#edit_mixer_car_id').val(receipt.mixer_car_id).trigger('change');
            $('#edit_mixer_driver_id').val(receipt.mixer_driver_id).trigger('change');
            $('#edit_notes').val(receipt.notes);

            calculateEditTotals();
            $('#editServiceReceiptModal').modal('show');
        } else {
            Swal.fire('هەڵە!', res.message || 'داتای پسوڵە نەدۆزرایەوە', 'error');
        }
    }, 'json').fail(function (xhr) {
        Swal.fire('هەڵە!', 'هەڵەیەک لە وەرگرتنی داتاکان هەیە', 'error');
    });
});

// Toggle Edit Payment Fields
$('#edit_payment_type').on('change', function () {
    if ($(this).val() === 'cash') {
        $('.edit-cash-fields').removeClass('d-none');
    } else {
        $('.edit-cash-fields').addClass('d-none');
    }
    calculateEditTotals();
});

// Live Calculation Logic for Edit Modal
function calculateEditTotals() {
    const meter = parseFloat($('#edit_meter_amount').val()) || 0;
    const price = parseFloat($('#edit_price_per_meter').val()) || 0;
    const paidUsd = parseFloat($('#edit_paid_usd').val()) || 0;
    const paidIqd = parseFloat($('#edit_paid_iqd').val()) || 0;
    const rate = parseFloat($('#edit_exchange_rate').val()) || 1;

    const totalUsd = meter * price;
    const paidFromIqd = rate > 0 ? (paidIqd / rate) : 0;
    const totalPaid = paidUsd + paidFromIqd;
    const balance = totalUsd - totalPaid;

    $('#edit_display_total_price').val(totalUsd.toFixed(2));

    const $balanceEl = $('#edit_display_remaining_balance');
    $balanceEl.text(balance.toFixed(2) + ' $');

    if (balance > 0.01) {
        $balanceEl.removeClass('text-success text-muted').addClass('text-danger');
    } else if (balance < -0.01) {
        $balanceEl.removeClass('text-danger text-muted').addClass('text-success');
    } else {
        $balanceEl.removeClass('text-danger text-success').addClass('text-muted');
    }
}

$(document).on('input', '.edit-calc-input', calculateEditTotals);

let isUpdating = false;

$('#editServiceReceiptForm').on('submit', function (e) {
    e.preventDefault();

    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }

    isUpdating = true;
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

    var formData = $(this).serialize();
    $.post('../process/service_receipts/update_service_receipts.php', formData, function (res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو', res.message || 'پسوڵە نوێکرایەوە', 'success');
            $('#editServiceReceiptModal').modal('hide');
            if (window.reloadServiceReceipts) window.reloadServiceReceipts();
        } else {
            Swal.fire('هەڵە!', res.message || 'هەڵەیەک ڕویدا', 'error');
        }
    }, 'json').fail(function (xhr) {
        Swal.fire('هەڵە!', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕویدا', 'error');
    }).always(function () {
        isUpdating = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
    });
});
