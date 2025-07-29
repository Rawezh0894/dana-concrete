// Summary Cards Data Loading for Add Customers Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when customers are updated
    $(document).on('customerAdded customerUpdated customerDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/customer/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total_debt').text('$' + response.data.total_debt.toLocaleString());
                $('#total_customers').text(response.data.total_customers);
                $('#customers_with_debt').text(response.data.customers_with_debt);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total_debt').text('$0');
                $('#total_customers').text('0');
                $('#customers_with_debt').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total_debt').text('$0');
            $('#total_customers').text('0');
            $('#customers_with_debt').text('0');
        }
    });
} 