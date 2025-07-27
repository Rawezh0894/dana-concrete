let submitting = false;

// Function to fetch dollar rate from API
function fetchDollarRate() {
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    // Show loading state on the input field
    const $input = $('#dolar_rate');
    const originalValue = $input.val();
    $input.val('جێبەجێکردن...');
    $input.prop('disabled', true);
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.value) {
                $('#dolar_rate').val(response.value);
                $('#dolar_rate').prop('disabled', false);
                console.log('Dollar rate fetched successfully:', response.value);
                // Show success notification
                Swal.fire({
                    icon: 'success',
                    title: 'نرخی دۆلار نوێکرایەوە',
                    text: `نرخی ١٠٠ دۆلار: ${response.value} دینار`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                console.warn('No value data in API response:', response);
                // Restore original value if API doesn't return value
                $('#dolar_rate').val(originalValue);
                $('#dolar_rate').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching dollar rate:', error);
            console.error('Response:', xhr.responseText);
            // Restore original value and enable input
            $('#dolar_rate').val(originalValue);
            $('#dolar_rate').prop('disabled', false);
            // Show error notification
            Swal.fire({
                icon: 'error',
                title: 'هەڵە لە وەرگرتنی نرخی دۆلار',
                text: 'نەتوانرا نرخی دۆلار وەربگیرێت، نرخەکەی پێشوو بەکاردەهێنرێت',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    });
}

$(document).ready(function() {
    // Fetch dollar rate when add sale modal is shown
    $('#addSaleModal').on('show.bs.modal', function() {
        fetchDollarRate();
    });

    // Handle refresh button click for add modal
    $('#refreshDollarRate').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        // Show loading state
        $icon.addClass('fa-spin');
        $btn.prop('disabled', true);
        
        fetchDollarRate();
        
        // Remove loading state after a short delay
        setTimeout(function() {
            $icon.removeClass('fa-spin');
            $btn.prop('disabled', false);
        }, 1000);
    });

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
