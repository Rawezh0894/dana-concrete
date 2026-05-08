// Function to fetch dollar rate from API
function fetchDollarRateForEdit() {
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    // Show loading state on the input field
    const $input = $('#edit_dolar_rate');
    const originalValue = $input.val();
    $input.val('جێبەجێکردن...');
    $input.prop('disabled', true);
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.value) {
                $('#edit_dolar_rate').val(response.value);
                $('#edit_dolar_rate').prop('disabled', false);
                console.log('Dollar rate fetched successfully for edit:', response.value);
                // Recalculate remaining amount after updating rate
                calculateRemainingAmount();
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
                $('#edit_dolar_rate').val(originalValue);
                $('#edit_dolar_rate').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching dollar rate:', error);
            console.error('Response:', xhr.responseText);
            // Restore original value and enable input
            $('#edit_dolar_rate').val(originalValue);
            $('#edit_dolar_rate').prop('disabled', false);
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

function setEditRecipientSelect(recipientId, recipientName) {
    const $select = $('#edit_recipient');
    if (!$select.length) return;

    if (recipientId) {
        $select.val(String(recipientId)).trigger('change');
        if ($select.val()) return;
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
        $select.val('').trigger('change');
    } else {
        $select.val('').trigger('change');
    }
}

function loadCustomersAndFormulas(selectedCustomerId, selectedFormulaId) {
    // Load customers
    $.getJSON('../process/sale/select_customers.php', function(customers) {
        var $customerSelect = $('#edit_customer_id');
        $customerSelect.empty();
        $customerSelect.append('<option value="">هەڵبژێرە</option>');
        customers.forEach(function(c) {
            $customerSelect.append(
                `<option value="${c.id}" ${c.id == selectedCustomerId ? 'selected' : ''}>${c.name}</option>`
            );
        });
    });
    // Load formulas
    $.getJSON('../process/sale/select_formulas.php', function(formulas) {
        var $formulaSelect = $('#edit_formula_id');
        $formulaSelect.empty();
        $formulaSelect.append('<option value="">هەڵبژێرە</option>');
        formulas.forEach(function(f) {
            $formulaSelect.append(
                `<option value="${f.id}" ${f.id == selectedFormulaId ? 'selected' : ''}>${f.name}</option>`
            );
        });
    });
}

$(document).on('click', '.edit-sale', function() {
    var saleId = $(this).data('id');
    $.ajax({
        url: '../process/sale/select_sale.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var sale = response.data.find(s => s.id == saleId);
                if (sale) {
                    loadCustomersAndFormulas(sale.customer_id, sale.formula_id);
                    $('#edit_sale_id').val(sale.id);
                    setEditRecipientSelect(sale.recipient_id, sale.recipient);
                    $('#edit_location').val(sale.location);
                    $('#edit_quantity').val(sale.quantity);
                    $('#edit_price_per_unit').val(sale.price_per_unit);
                    $('#edit_total_price').val(sale.total_price);
                    $('#edit_payment_type').val(sale.payment_type);
                    $('#edit_amount_paid_usd').val(sale.amount_paid_usd);
                    $('#edit_amount_paid_iq').val(sale.amount_paid_iq);
                    $('#edit_dolar_rate').val(sale.dolar_rate);
                    $('#edit_remaining_amount').val(sale.remaining_amount);
                    $('#edit_invoice_number').val(sale.invoice_number);
                    $('#edit_order_date').val(sale.order_date);
                    $('#edit_notes').val(sale.notes);
                    $('#edit_discount').val(sale.discount);
                    $('#edit_change_back_iq').val(sale.change_back_iq);
                    $('#edit_change_back_usd').val(sale.change_back_usd);
                    $('#editSaleModal').modal('show');
                }
            }
        }
    });
});

// Fetch dollar rate when edit modal is shown
$(document).on('show.bs.modal', '#editSaleModal', function() {
    fetchDollarRateForEdit();
});

// Handle refresh button click for edit modal
$(document).on('click', '#refreshDollarRateEdit', function() {
    const $btn = $(this);
    const $icon = $btn.find('i');
    
    // Show loading state
    $icon.addClass('fa-spin');
    $btn.prop('disabled', true);
    
    fetchDollarRateForEdit();
    
    // Remove loading state after a short delay
    setTimeout(function() {
        $icon.removeClass('fa-spin');
        $btn.prop('disabled', false);
    }, 1000);
});

$(document).ready(function() {
    if (typeof calculateTotalPrice === 'function') {
        calculateTotalPrice('edit_');
    }
    if (typeof calculateRemainingAmount === 'function') {
        calculateRemainingAmount('edit_');
    }
});

// Real-time invoice number validation for edit form
let editInvoiceValidationTimeout;
$('#edit_invoice_number').on('input', function() {
    const invoiceNumber = $(this).val().trim();
    const currentSaleId = $('#edit_sale_id').val();
    
    // Clear previous timeout
    clearTimeout(editInvoiceValidationTimeout);
    
    // Remove previous validation classes
    $(this).removeClass('is-valid is-invalid');
    
    if (invoiceNumber.length === 0) {
        return;
    }
    
    // Set timeout to avoid too many requests
    editInvoiceValidationTimeout = setTimeout(function() {
        $.ajax({
            url: '../process/sale/check_invoice_number_edit.php',
            type: 'POST',
            data: { 
                invoice_number: invoiceNumber,
                current_id: currentSaleId 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && !response.exists) {
                    $('#edit_invoice_number').addClass('is-valid');
                    // Remove any error message
                    $('#edit_invoice_number').siblings('.invalid-feedback').remove();
                } else {
                    $('#edit_invoice_number').addClass('is-invalid');
                    // Add error message if not already present
                    if ($('#edit_invoice_number').siblings('.invalid-feedback').length === 0) {
                        $('#edit_invoice_number').after('<div class="invalid-feedback">' + (response.error || 'ئەم ژمارەی پسوڵە پێشتر تۆمارکراوە') + '</div>');
                    }
                }
            },
            error: function() {
                // On error, don't show validation state
                console.error('Error checking invoice number for edit');
            }
        });
    }, 500); // Wait 500ms after user stops typing
});

// Clear validation state when edit modal is hidden
$('#editSaleModal').on('hidden.bs.modal', function() {
    $('#edit_invoice_number').removeClass('is-valid is-invalid');
    $('#edit_invoice_number').siblings('.invalid-feedback').remove();
});

// Multiple submission prevention flag
let isUpdating = false;

$('#editSaleForm').on('submit', function(e) {
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
    
    // Check if invoice number is valid (not duplicate)
    const invoiceNumber = $('#edit_invoice_number').val().trim();
    if (invoiceNumber && $('#edit_invoice_number').hasClass('is-invalid')) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە ژمارەی پسوڵەیەکی تر هەڵبژێرە'
        });
        isUpdating = false;
        submitBtn.prop('disabled', false);
        submitBtn.html(originalBtnText);
        return false;
    }
    
    var formData = $(this).serialize();
    $.ajax({
        url: '../process/sale/update_sale.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: response.message || 'فرۆشتن نوێکرایەوە!',
                    timer: 1500,
                    showConfirmButton: false
                });
                $('#editSaleModal').modal('hide');
                if (window.reloadSales) window.reloadSales();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.message || 'هەڵەیەک ڕووی دا لە نوێکردنەوە!'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕووی دا لە پەیوەندیکردن!'
            });
        },
        complete: function() {
            // Reset updating flag and restore submit button
            isUpdating = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        }
    });
});
