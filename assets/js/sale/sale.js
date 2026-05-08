// Calculate total price and set it as readonly
$(document).ready(function() {
    // Set total_price inputs to readonly
    $('#total_price, #edit_total_price').prop('readonly', true);
    $('#remaining_amount, #edit_remaining_amount').prop('readonly', true);

    function calculateTotalPrice(prefix = '') {
        var quantity = parseFloat($('#' + prefix + 'quantity').val()) || 0;
        var pricePerUnit = parseFloat($('#' + prefix + 'price_per_unit').val()) || 0;
        var total = quantity * pricePerUnit;
        $('#' + prefix + 'total_price').val(total.toFixed(4));
    }

    function calculatePricePerUnit(prefix = '') {
        var quantity = parseFloat($('#' + prefix + 'quantity').val()) || 0;
        var paidUSD = parseFloat($('#' + prefix + 'amount_paid_usd').val()) || 0;
        var paidIQD = parseFloat($('#' + prefix + 'amount_paid_iq').val()) || 0;
        var changeUSD = parseFloat($('#' + prefix + 'change_back_usd').val()) || 0;
        var changeIQD = parseFloat($('#' + prefix + 'change_back_iq').val()) || 0;
        var dolarRate = parseFloat($('#' + prefix + 'dolar_rate').val()) || 1;
        
        if (quantity > 0) {
            var netPaidUSD = paidUSD - changeUSD;
            var netPaidIQD = paidIQD - changeIQD;
            var netPaidIQD_inUSD = netPaidIQD / (dolarRate / 100);
            var totalPaid = netPaidUSD + netPaidIQD_inUSD;
            var pricePerUnit = totalPaid / quantity;
            $('#' + prefix + 'price_per_unit').val(pricePerUnit.toFixed(4));
        }
    }

    function calculateRemainingAmount(prefix = '') {
        var total = parseFloat($('#' + prefix + 'total_price').val()) || 0;
        var paidIQD = parseFloat($('#' + prefix + 'amount_paid_iq').val()) || 0;
        var paidUSD = parseFloat($('#' + prefix + 'amount_paid_usd').val()) || 0;
        var changeIQD = parseFloat($('#' + prefix + 'change_back_iq').val()) || 0;
        var changeUSD = parseFloat($('#' + prefix + 'change_back_usd').val()) || 0;
        var dolarRate = parseFloat($('#' + prefix + 'dolar_rate').val()) || 1;
        var discount = parseFloat($('#' + prefix + 'discount').val()) || 0;

        var netPaidUSD = paidUSD - changeUSD;
        var netPaidIQD = paidIQD - changeIQD;
        var netPaidIQD_inUSD = netPaidIQD / (dolarRate / 100);
        var remaining = (total - netPaidIQD_inUSD - netPaidUSD) - discount;
        $('#' + prefix + 'remaining_amount').val(remaining.toFixed(4));
    }

    // Event listeners
    $('#quantity, #price_per_unit, #edit_quantity, #edit_price_per_unit').on('input', function() {
        var prefix = this.id.startsWith('edit_') ? 'edit_' : '';
        calculateTotalPrice(prefix);
        calculateRemainingAmount(prefix);
    });
    
    // When paid amounts, change back, or dollar rate change -> recalculate unit price and remaining
    $('#amount_paid_usd, #amount_paid_iq, #change_back_usd, #change_back_iq, #dolar_rate, #edit_amount_paid_usd, #edit_amount_paid_iq, #edit_change_back_usd, #edit_change_back_iq, #edit_dolar_rate').on('input', function() {
        var prefix = this.id.startsWith('edit_') ? 'edit_' : '';
        calculatePricePerUnit(prefix);
        calculateTotalPrice(prefix);
        calculateRemainingAmount(prefix);
    });

    // When discount changes -> only recalculate remaining amount (NOT unit price)
    $('#discount, #edit_discount').on('input', function() {
        var prefix = this.id.startsWith('edit_') ? 'edit_' : '';
        calculateRemainingAmount(prefix);
    });

    $('#quantity, #edit_quantity').on('input', function() {
        var prefix = this.id.startsWith('edit_') ? 'edit_' : '';
        calculatePricePerUnit(prefix);
    });

    // Initial calculations
    calculateTotalPrice();
    calculateRemainingAmount();
    calculateTotalPrice('edit_');
    calculateRemainingAmount('edit_');

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
    const minQuantity = $('#filter_quantity_min').val() || '';
    const maxQuantity = $('#filter_quantity_max').val() || '';
    
    // Create form data
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    formData.append('min_quantity', minQuantity);
    formData.append('max_quantity', maxQuantity);
    
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
