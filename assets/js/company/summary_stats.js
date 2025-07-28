// Load summary statistics for company cards
function loadSummaryStats() {
    $.ajax({
        url: '../process/company/get_summary_stats.php',
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
                
                // Update total companies card
                $('#total_companies').text(summary.total_companies.toLocaleString('en-US'));
                
                // Update companies with debt card
                $('#companies_with_debt').text(summary.companies_with_debt.toLocaleString('en-US'));
            } else {
                console.error('Error loading summary stats:', response.error);
                // Set default values
                $('#total_debt').text('$0.00');
                $('#total_companies').text('0');
                $('#companies_with_debt').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading summary stats:', error);
            // Set default values on error
            $('#total_debt').text('$0.00');
            $('#total_companies').text('0');
            $('#companies_with_debt').text('0');
        }
    });
}

// Load summary stats when page loads
$(document).ready(function() {
    loadSummaryStats();
});

// Export function to be called from other scripts
window.loadSummaryStats = loadSummaryStats; 