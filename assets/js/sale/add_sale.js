// Multiple submission prevention flag
let submitting = false;

function setRecipientSelectValue(selector, recipientId, recipientName) {
    const $select = $(selector);
    if (!$select.length) return;

    if (recipientId) {
        $select.val(String(recipientId)).trigger('change');
        if ($select.val()) {
            return;
        }
    }

    if (recipientName) {
        const normalizedName = recipientName.trim();
        if (!normalizedName) return;
        const options = $select[0].options;
        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            const optionName = option.dataset && option.dataset.name ? option.dataset.name.trim() : option.textContent.trim();
            if (optionName === normalizedName) {
                $select.val(option.value).trigger('change');
                return;
            }
        }
    }
}

// Function to populate form from localStorage (from receipt selection)
function populateFormFromLocalStorage() {
    const saleData = localStorage.getItem('saleFromReceipts');
    if (saleData) {
        try {
            const data = JSON.parse(saleData);
            
            // Auto-open the modal
            $('#addSaleModal').modal('show');
            
            // Populate form fields
            if (data.customer_id) {
                $('#customer_id').val(data.customer_id).trigger('change');
            } else if (data.customer_name) {
                // Try to find customer by name if ID is not provided
                const customerSelect = $('#customer_id');
                const options = customerSelect.find('option');
                let foundCustomerId = null;
                
                options.each(function() {
                    if ($(this).text().trim() === data.customer_name.trim()) {
                        foundCustomerId = $(this).val();
                        return false; // break the loop
                    }
                });
                
                if (foundCustomerId) {
                    $('#customer_id').val(foundCustomerId).trigger('change');
                }
            }
            
            if (data.recipient_id) {
                setRecipientSelectValue('#recipient', data.recipient_id, data.recipient || data.recipient_name);
            } else if (data.recipient || data.recipient_name) {
                setRecipientSelectValue('#recipient', null, data.recipient || data.recipient_name);
            }
            
            if (data.location) {
                $('#location').val(data.location);
            }
            
            if (data.invoice_number) {
                $('#invoice_number').val(data.invoice_number);
            }
            
            if (data.formula_name) {
                // Try to find formula by name
                const formulaSelect = $('#formula_id');
                const options = formulaSelect.find('option');
                let foundFormulaId = null;
                
                // Split formula names if multiple formulas are provided
                const formulaNames = data.formula_name.split(',').map(name => name.trim());
                
                // Try to find the first matching formula
                for (let formulaName of formulaNames) {
                    options.each(function() {
                        if ($(this).text().trim() === formulaName) {
                            foundFormulaId = $(this).val();
                            return false; // break the loop
                        }
                    });
                    if (foundFormulaId) break; // break outer loop if found
                }
                
                if (foundFormulaId) {
                    $('#formula_id').val(foundFormulaId).trigger('change');
                }
            }
            
            if (data.order_date) {
                $('#order_date').val(data.order_date);
            } else {
                // Set today's date if no order_date provided
                const today = new Date().toISOString().split('T')[0];
                $('#order_date').val(today);
            }
            
            if (data.quantity) {
                $('#quantity').val(data.quantity);
            }
            
            if (data.price_per_unit) {
                $('#price_per_unit').val(data.price_per_unit);
            }
            
            // Calculate total price if quantity and price are available
            if (data.quantity && data.price_per_unit) {
                const totalPrice = parseFloat(data.quantity) * parseFloat(data.price_per_unit);
                $('#total_price').val(totalPrice.toFixed(2));
            }
            
            if (data.total_price && !data.quantity) {
                // Only set total_price if quantity is not provided (to avoid overriding calculated value)
                $('#total_price').val(data.total_price);
            }
            
            // Add receipt information to notes only if there are notes from receipts
            let notes = '';
            if (data.notes && data.notes.trim() !== '') {
                notes = data.notes;
            }
            $('#notes').val(notes);
            
            // Clear localStorage after using the data
            localStorage.removeItem('saleFromReceipts');
            
            // Data added successfully - no message needed
            
        } catch (error) {
            console.error('Error parsing sale data from localStorage:', error);
            localStorage.removeItem('saleFromReceipts');
        }
    }
}

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
        
        const recipientParam = urlParams.get('recipient') || urlParams.get('receiver_name') || '';
        const recipientIdParam = urlParams.get('recipient_id');
        if (recipientIdParam || recipientParam) {
            console.log('Setting recipient (id/name):', recipientIdParam, recipientParam);
            setRecipientSelectValue('#recipient', recipientIdParam, recipientParam);
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
        
        if (urlParams.has('order_date')) {
            const orderDate = urlParams.get('order_date');
            console.log('Setting order_date:', orderDate);
            $('#order_date').val(orderDate);
        } else {
            // Set today's date if no order_date provided
            const today = new Date().toISOString().split('T')[0];
            $('#order_date').val(today);
        }
        
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
    // Check for localStorage data and populate form if it exists
    populateFormFromLocalStorage();
    
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
        
        // Check if invoice number is valid (not duplicate)
        const invoiceNumber = $('#invoice_number').val().trim();
        if (invoiceNumber && $('#invoice_number').hasClass('is-invalid')) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'تکایە ژمارەی پسوڵەیەکی تر هەڵبژێرە'
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
                    $('#recipient').val('').trigger('change');
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

    // Real-time invoice number validation
    let invoiceValidationTimeout;
    $('#invoice_number').on('input', function() {
        const invoiceNumber = $(this).val().trim();
        
        // Clear previous timeout
        clearTimeout(invoiceValidationTimeout);
        
        // Remove previous validation classes
        $(this).removeClass('is-valid is-invalid');
        
        if (invoiceNumber.length === 0) {
            return;
        }
        
        // Set timeout to avoid too many requests
        invoiceValidationTimeout = setTimeout(function() {
            $.ajax({
                url: '../process/sale/check_invoice_number.php',
                type: 'POST',
                data: { invoice_number: invoiceNumber },
                dataType: 'json',
                success: function(response) {
                    if (response.success && !response.exists) {
                        $('#invoice_number').addClass('is-valid');
                        // Remove any error message
                        $('#invoice_number').siblings('.invalid-feedback').remove();
                    } else {
                        $('#invoice_number').addClass('is-invalid');
                        // Add error message if not already present
                        if ($('#invoice_number').siblings('.invalid-feedback').length === 0) {
                            $('#invoice_number').after('<div class="invalid-feedback">' + (response.error || 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە') + '</div>');
                        }
                    }
                },
                error: function() {
                    // On error, don't show validation state
                    console.error('Error checking invoice number');
                }
            });
        }, 500); // Wait 500ms after user stops typing
    });

    // Clear validation state when modal is hidden
    $('#addSaleModal').on('hidden.bs.modal', function() {
        $('#invoice_number').removeClass('is-valid is-invalid');
        $('#invoice_number').siblings('.invalid-feedback').remove();
    });
});
