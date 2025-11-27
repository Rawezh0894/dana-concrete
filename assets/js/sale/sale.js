// Calculate total price and set it as readonly
$(document).ready(function() {
    // Set total_price input to readonly
    $('#total_price').prop('readonly', true);

    function calculateTotalPrice() {
        var quantity = parseFloat($('#quantity').val()) || 0;
        var pricePerUnit = parseFloat($('#price_per_unit').val()) || 0;
        var total = quantity * pricePerUnit;
        $('#total_price').val(total.toFixed(4));
    }

    function calculatePricePerUnit() {
        var quantity = parseFloat($('#quantity').val()) || 0;
        var paidUSD = parseFloat($('#amount_paid_usd').val()) || 0;
        var paidIQD = parseFloat($('#amount_paid_iq').val()) || 0;
        var dolarRate = parseFloat($('#dolar_rate').val()) || 1;
        
        if (quantity > 0) {
            // فۆرمۆلە: (پارەی دراو بە دۆلار + (پارەی دراو بە دینار / (نرخی100 دۆلار بە دینار/100))) / بڕ م3
            var paidIQD_inUSD = paidIQD / (dolarRate / 100);
            var totalPaid = paidUSD + paidIQD_inUSD;
            var pricePerUnit = totalPaid / quantity;
            $('#price_per_unit').val(pricePerUnit.toFixed(4));
        }
    }

    $('#quantity, #price_per_unit').on('input', calculateTotalPrice);
    
    // ژماردنی نرخی یەکە کاتێک پارەی دراو یان نرخی دۆلار دەگۆڕدرێت
    $('#amount_paid_usd, #amount_paid_iq, #dolar_rate').on('input', function() {
        calculatePricePerUnit();
        calculateTotalPrice();
    });

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
        $('#remaining_amount').val(remaining.toFixed(4));
    }

    // Update remaining amount when any relevant field changes
    $('#total_price, #amount_paid_iq, #amount_paid_usd, #dolar_rate, #discount').on('input', calculateRemainingAmount);

    // Also recalculate remaining amount after total price changes
    $('#quantity, #price_per_unit').on('input', function() {
        calculateTotalPrice();
        calculateRemainingAmount();
    });
    
    // ژماردنی نرخی یەکە کاتێک بڕ دەگۆڕدرێت
    $('#quantity').on('input', function() {
        calculatePricePerUnit();
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

// Excel Export Function
function exportSaleToExcel() {
    // Get current filter values
    const customerId = $('#filter_customer') ? $('#filter_customer').val() : '';
    const fromDate = $('#filter_from').val() || '';
    const toDate = $('#filter_to').val() || '';
    const quantityRange = $('#filter_quantity').val() || '';
    const quantityRange = $('#filter_quantity').val() || '';
    
    // Create form data
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    formData.append('quantity_range', quantityRange);
    formData.append('quantity_range', quantityRange);
    
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'خەملێنراوە بۆ ئیکسپۆرتکردن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Make AJAX request to export
    fetch('../process/sale/export_excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Network response was not ok');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو!',
            text: 'فایلەکە بە سەرکەوتوویی ئیکسپۆرت کرا',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        console.error('Export error:', error);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'هەڵەیەک لە ئیکسپۆرتکردن هەیە. تکایە دواتر هەوڵ بدەوە'
        });
    });
}
