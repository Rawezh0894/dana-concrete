// Summary Cards Data Loading for Sales Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when sales are updated
    $(document).on('saleAdded saleUpdated saleDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/sale/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-customer-debt').text('$' + response.data.total_customer_debt.toLocaleString());
                $('#customers-with-debt').text(response.data.customers_with_debt);
                $('#total-sales').text(response.data.total_sales);
                $('#total-cubic-meters').text(response.data.total_cubic_meters.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-customer-debt').text('$0');
                $('#customers-with-debt').text('0');
                $('#total-sales').text('0');
                $('#total-cubic-meters').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-customer-debt').text('$0');
            $('#customers-with-debt').text('0');
            $('#total-sales').text('0');
            $('#total-cubic-meters').text('0');
        }
    });
} 

// Function to export sale summary to Excel
function exportSaleSummaryToExcel() {
    // Get current filter values
    const customerId = $('#filter_customer') ? $('#filter_customer').val() : '';
    const fromDate = $('#filter_from').val() || '';
    const toDate = $('#filter_to').val() || '';
    
    // Create form data
    const formData = new FormData();
    formData.append('customer_id', customerId);
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    formData.append('export_type', 'summary');
    
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'خەملێنراوە بۆ ئیکسپۆرتی کورتەی فرۆشتنەکان',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Make AJAX request to export summary
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
        a.download = `کورتەی_فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو!',
            text: 'کورتەی فرۆشتنەکان بە سەرکەوتوویی ئیکسپۆرت کرا',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        console.error('Summary export error:', error);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'هەڵەیەک لە ئیکسپۆرتی کورتەکە هەیە. تکایە دواتر هەوڵ بدەوە'
        });
    });
}

// Make function globally available
window.exportSaleSummaryToExcel = exportSaleSummaryToExcel; 