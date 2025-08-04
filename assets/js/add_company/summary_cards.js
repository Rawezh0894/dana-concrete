// Summary Cards Data Loading for Add Company Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when companies are updated
    $(document).on('companyAdded companyUpdated companyDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/company/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total_debt').text('$' + response.summary.total_debt_usd.toLocaleString());
                $('#total_companies').text(response.summary.total_companies);
                $('#companies_with_debt').text(response.summary.companies_with_debt);
                console.log('Summary cards updated successfully:', response.summary);
            } else {
                console.error('Error loading summary data:', response.error);
                // Set default values
                $('#total_debt').text('$0');
                $('#total_companies').text('0');
                $('#companies_with_debt').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            // Set default values on error
            $('#total_debt').text('$0');
            $('#total_companies').text('0');
            $('#companies_with_debt').text('0');
        }
    });
} 