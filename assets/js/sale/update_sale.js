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
                    $('#edit_recipient').val(sale.recipient);
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

// Calculation logic (same as add_sale.js)
$(document).ready(function() {
    $('#edit_total_price').prop('readonly', true);
    $('#edit_remaining_amount').prop('readonly', true);
    function calculateTotalPrice() {
        var quantity = parseFloat($('#edit_quantity').val()) || 0;
        var pricePerUnit = parseFloat($('#edit_price_per_unit').val()) || 0;
        var total = quantity * pricePerUnit;
        $('#edit_total_price').val(total.toFixed(4));
    }

    function calculatePricePerUnit() {
        var quantity = parseFloat($('#edit_quantity').val()) || 0;
        var paidUSD = parseFloat($('#edit_amount_paid_usd').val()) || 0;
        var paidIQD = parseFloat($('#edit_amount_paid_iq').val()) || 0;
        var dolarRate = parseFloat($('#edit_dolar_rate').val()) || 1;
        
        if (quantity > 0) {
            // فۆرمۆلە: (پارەی دراو بە دۆلار + (پارەی دراو بە دینار / (نرخی100 دۆلار بە دینار/100))) / بڕ م3
            var paidIQD_inUSD = paidIQD / (dolarRate / 100);
            var totalPaid = paidUSD + paidIQD_inUSD;
            var pricePerUnit = totalPaid / quantity;
            $('#edit_price_per_unit').val(pricePerUnit.toFixed(4));
        }
    }
    function calculateRemainingAmount() {
        var total = parseFloat($('#edit_total_price').val()) || 0;
        var paidIQD = parseFloat($('#edit_amount_paid_iq').val()) || 0;
        var paidUSD = parseFloat($('#edit_amount_paid_usd').val()) || 0;
        var dolarRate = parseFloat($('#edit_dolar_rate').val()) || 1;
        var discount = parseFloat($('#edit_discount').val()) || 0;
        var paidIQD_inUSD = paidIQD / (dolarRate / 100);
        var remaining = (total - paidIQD_inUSD - paidUSD) - discount;
        $('#edit_remaining_amount').val(remaining.toFixed(4));
    }
    $('#edit_quantity, #edit_price_per_unit').on('input', function() {
        calculateTotalPrice();
        calculateRemainingAmount();
    });
    
    // ژماردنی نرخی یەکە کاتێک پارەی دراو یان نرخی دۆلار دەگۆڕدرێت
    $('#edit_amount_paid_usd, #edit_amount_paid_iq, #edit_dolar_rate').on('input', function() {
        calculatePricePerUnit();
        calculateTotalPrice();
        calculateRemainingAmount();
    });
    
    // ژماردنی نرخی یەکە کاتێک بڕ دەگۆڕدرێت
    $('#edit_quantity').on('input', function() {
        calculatePricePerUnit();
    });
    $('#edit_total_price, #edit_amount_paid_iq, #edit_amount_paid_usd, #edit_dolar_rate, #edit_discount').on('input', calculateRemainingAmount);
    calculateTotalPrice();
    calculateRemainingAmount();
});

$('#editSaleForm').on('submit', function(e) {
    e.preventDefault();
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
        }
    });
});
