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
                const summary = response.data || response.summary || {};
                const totalDebtValue = summary.total_debt !== undefined 
                    ? summary.total_debt 
                    : (summary.total_debt_usd !== undefined ? summary.total_debt_usd : 0);
                const totalCustomersValue = summary.total_customers !== undefined ? summary.total_customers : 0;
                const customersWithDebtValue = summary.customers_with_debt !== undefined ? summary.customers_with_debt : 0;

                $('#total_debt').text('$' + Number(totalDebtValue || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#total_customers').text(Number(totalCustomersValue || 0).toLocaleString('en-US'));
                $('#customers_with_debt').text(Number(customersWithDebtValue || 0).toLocaleString('en-US'));
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