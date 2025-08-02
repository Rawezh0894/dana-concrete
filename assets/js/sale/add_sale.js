// Multiple submission prevention flag
let submitting = false;

// Function to populate form from URL parameters
function populateFormFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check if we have parameters from receipt selection
    if (urlParams.has('customer_id') || urlParams.has('receipt_numbers')) {
        // Auto-open the modal
        $('#addSaleModal').modal('show');
        
        // Populate form fields
        if (urlParams.has('customer_id')) {
            const customerId = urlParams.get('customer_id');
            console.log('Setting customer_id:', customerId);
            if (customerId && customerId !== 'null' && customerId !== '') {
                $('#customer_id').val(customerId).trigger('change');
            }
        }
        
        if (urlParams.has('recipient')) {
            const recipient = urlParams.get('recipient');
            console.log('Setting recipient:', recipient);
            $('#recipient').val(recipient);
        }
        
        if (urlParams.has('location')) {
            const location = urlParams.get('location');
            console.log('Setting location:', location);
            $('#location').val(location);
        }
        
        if (urlParams.has('formula_id')) {
            const formulaId = urlParams.get('formula_id');
            console.log('Setting formula_id:', formulaId);
            if (formulaId && formulaId !== 'null' && formulaId !== '') {
                $('#formula_id').val(formulaId).trigger('change');
            }
        }
        
        if (urlParams.has('quantity')) {
            const quantity = urlParams.get('quantity');
            console.log('Setting quantity:', quantity);
            $('#quantity').val(quantity);
        }
        
        if (urlParams.has('price_per_unit')) {
            const pricePerUnit = urlParams.get('price_per_unit');
            console.log('Setting price_per_unit:', pricePerUnit);
            $('#price_per_unit').val(pricePerUnit);
        }
        
        if (urlParams.has('total_price')) {
            const totalPrice = urlParams.get('total_price');
            console.log('Setting total_price:', totalPrice);
            $('#total_price').val(totalPrice);
        }
        
        if (urlParams.has('receipt_numbers')) {
            const receiptNumbers = urlParams.get('receipt_numbers');
            const totalMeterAmount = urlParams.get('total_meter_amount');
            
            console.log('Setting receipt_numbers:', receiptNumbers);
            console.log('Setting total_meter_amount:', totalMeterAmount);
            
            // Add receipt information to notes
            let notes = `پسووڵەکان: ${receiptNumbers}`;
            if (totalMeterAmount) {
                notes += `\nکۆی مەتر سێجا: ${totalMeterAmount} م³`;
            }
            $('#notes').val(notes);
        }
        
        // Set today's date
        const today = new Date().toISOString().split('T')[0];
        $('#order_date').val(today);
        
        // Generate invoice number based on receipt numbers
        if (urlParams.has('receipt_numbers')) {
            const receiptNumbers = urlParams.get('receipt_numbers').split(',');
            if (receiptNumbers.length > 0) {
                // Use the first receipt number as base for invoice
                const baseReceipt = receiptNumbers[0];
                const invoiceNumber = `SALE-${baseReceipt}`;
                $('#invoice_number').val(invoiceNumber);
            }
        }
        
        // Show success message
        Swal.fire({
            icon: 'info',
            title: 'داتا زیادکرا',
            text: 'داتای پسووڵەکان بە سەرکەوتوویی زیادکرا بۆ فۆڕمەکە',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        
        // Clear URL parameters to prevent re-population on refresh
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

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
    // Check for URL parameters and populate form if they exist
    populateFormFromURL();
    
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
        e.preventDefault();
        
        // Prevent multiple submissions
        if (submitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        // Set submitting flag and disable submit button
        submitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
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
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
            return false;
        }
        if (paidIQD_inUSD > total) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'پارەی دراو بە دینار (بە دۆلار) نابێت لە کۆی نرخ زیاتربێت!'
            });
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
            return false;
        }
        if (paidUSD > total) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'پارەی دراو بە دۆلار نابێت لە کۆی نرخ زیاتربێت!'
            });
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
            return false;
        }
        
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
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr, xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕوویدا لە پەیوەندیکردن!'
                });
            },
            complete: function() {
                // Reset submitting flag and restore submit button
                submitting = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
});
