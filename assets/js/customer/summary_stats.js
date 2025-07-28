// Load summary statistics for customer cards
function loadSummaryStats() {
    $.ajax({
        url: '../process/customer/get_summary_stats.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.summary) {
                const summary = response.summary;
                
                // Update total debt card
                $('#total_debt').text('$' + summary.total_debt_usd.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                
                // Update total customers card
                $('#total_customers').text(summary.total_customers.toLocaleString('en-US'));
                
                // Update customers with debt card
                $('#customers_with_debt').text(summary.customers_with_debt.toLocaleString('en-US'));
            } else {
                console.error('Error loading summary stats:', response.error);
                // Set default values
                $('#total_debt').text('$0.00');
                $('#total_customers').text('0');
                $('#customers_with_debt').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading summary stats:', error);
            // Set default values on error
            $('#total_debt').text('$0.00');
            $('#total_customers').text('0');
            $('#customers_with_debt').text('0');
        }
    });
}

// Load summary stats when page loads
$(document).ready(function() {
    loadSummaryStats();
});

// Export function to be called from other scripts
window.loadSummaryStats = loadSummaryStats; 