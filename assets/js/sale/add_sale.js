let submitting = false;
$(document).ready(function() {
    $('#addSaleForm').on('submit', function(e) {
        if (submitting) return false;
        submitting = true;
        var paymentType = $('#payment_type').val();
        var remaining = parseFloat($('#remaining_amount').val()) || 0;
        var total = parseFloat($('#total_price').val()) || 0;
        var paidIQD = parseFloat($('#amount_paid_iq').val()) || 0;
        var paidUSD = parseFloat($('#amount_paid_usd').val()) || 0;
        var dolarRate = parseFloat($('#dolar_rate').val()) || 1;
        var paidIQD_inUSD = paidIQD / (dolarRate / 100);
        if (paymentType === 'نەقد' && remaining > 0) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'کاتێک جۆری پارەدان نەقدە، نابێت پارەی ماوە بێت!'
            });
            e.preventDefault();
            submitting = false;
            return false;
        }
        if (paidIQD_inUSD > total) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'پارەی دراو بە دینار (بە دۆلار) نابێت لە کۆی نرخ زیاتربێت!'
            });
            e.preventDefault();
            submitting = false;
            return false;
        }
        if (paidUSD > total) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'پارەی دراو بە دۆلار نابێت لە کۆی نرخ زیاتربێت!'
            });
            e.preventDefault();
            submitting = false;
            return false;
        }
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: '../process/sale/add_sale.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: response.message || 'فرۆشتن بەسەرکەوتوویی زیادکرا!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#addSaleForm')[0].reset();
                    $('#customer_id').val('').trigger('change');
                    $('#formula_id').val('').trigger('change');
                    $('#addSaleModal').modal('hide');
                    if (window.reloadSales) window.reloadSales();
                } else {
                    console.error('Server error:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە زیادکردنی فرۆشتن!'
                    });
                }
                submitting = false;
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr, xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕوویدا لە پەیوەندیکردن!'
                });
                submitting = false;
            }
        });
    });
});
