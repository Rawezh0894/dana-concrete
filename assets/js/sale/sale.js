// Calculate total price and set it as readonly
$(document).ready(function() {
    // Set total_price input to readonly
    $('#total_price').prop('readonly', true);

    function calculateTotalPrice() {
        var quantity = parseFloat($('#quantity').val()) || 0;
        var pricePerUnit = parseFloat($('#price_per_unit').val()) || 0;
        var total = quantity * pricePerUnit;
        $('#total_price').val(total.toFixed(2));
    }

    $('#quantity, #price_per_unit').on('input', calculateTotalPrice);

    // Initial calculation in case values are pre-filled
    calculateTotalPrice();

    // Set remaining_amount input to readonly
    $('#remaining_amount').prop('readonly', true);

    function calculateRemainingAmount() {
        var total = parseFloat($('#total_price').val()) || 0;
        var paidIQD = parseFloat($('#amount_paid_iq').val()) || 0;
        var paidUSD = parseFloat($('#amount_paid_usd').val()) || 0;
        var dolarRate = parseFloat($('#dolar_rate').val()) || 1;
        var discount = parseFloat($('#discount').val()) || 0;

        // Convert IQD to USD based on rate for 100 USD
        var paidIQD_inUSD = paidIQD / (dolarRate / 100);
        var remaining = (total - paidIQD_inUSD - paidUSD) - discount;
        $('#remaining_amount').val(remaining.toFixed(2));
    }

    // Update remaining amount when any relevant field changes
    $('#total_price, #amount_paid_iq, #amount_paid_usd, #dolar_rate, #discount').on('input', calculateRemainingAmount);

    // Also recalculate remaining amount after total price changes
    $('#quantity, #price_per_unit').on('input', function() {
        calculateTotalPrice();
        calculateRemainingAmount();
    });

    // Initial calculation
    calculateRemainingAmount();

    // Set default order_date to yesterday when modal opens
    $('#addSaleModal').on('show.bs.modal', function() {
        var today = new Date();
        var yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        var yyyy = yesterday.getFullYear();
        var mm = String(yesterday.getMonth() + 1).padStart(2, '0');
        var dd = String(yesterday.getDate()).padStart(2, '0');
        var formatted = yyyy + '-' + mm + '-' + dd;
        $('#order_date').val(formatted);
    });
});
