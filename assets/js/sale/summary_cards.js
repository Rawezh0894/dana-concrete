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
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-customer-debt').text('$0');
                $('#customers-with-debt').text('0');
                $('#total-sales').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-customer-debt').text('$0');
            $('#customers-with-debt').text('0');
            $('#total-sales').text('0');
        }
    });
} 