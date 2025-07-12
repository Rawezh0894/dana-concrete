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

// Calculation logic (same as add_sale.js)
$(document).ready(function() {
    $('#edit_total_price').prop('readonly', true);
    $('#edit_remaining_amount').prop('readonly', true);
    function calculateTotalPrice() {
        var quantity = parseFloat($('#edit_quantity').val()) || 0;
        var pricePerUnit = parseFloat($('#edit_price_per_unit').val()) || 0;
        var total = quantity * pricePerUnit;
        $('#edit_total_price').val(total.toFixed(2));
    }
    function calculateRemainingAmount() {
        var total = parseFloat($('#edit_total_price').val()) || 0;
        var paidIQD = parseFloat($('#edit_amount_paid_iq').val()) || 0;
        var paidUSD = parseFloat($('#edit_amount_paid_usd').val()) || 0;
        var dolarRate = parseFloat($('#edit_dolar_rate').val()) || 1;
        var discount = parseFloat($('#edit_discount').val()) || 0;
        var paidIQD_inUSD = paidIQD / (dolarRate / 100);
        var remaining = (total - paidIQD_inUSD - paidUSD) - discount;
        $('#edit_remaining_amount').val(remaining.toFixed(2));
    }
    $('#edit_quantity, #edit_price_per_unit').on('input', function() {
        calculateTotalPrice();
        calculateRemainingAmount();
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
