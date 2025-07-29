// Summary Cards Data Loading for Person Other Expenses Profile Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when expenses are updated
    $(document).on('expenseAdded expenseUpdated expenseDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    // Get person ID from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const personId = urlParams.get('id');
    
    if (!personId) {
        console.error('Person ID not found in URL');
        return;
    }
    
    $.ajax({
        url: '../process/person_other_expenses_profile/get_summary_stats.php',
        type: 'GET',
        data: { person_id: personId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#summary_total_usd').text('$' + response.data.total_usd.toLocaleString());
                $('#summary_total_iqd').text(response.data.total_iqd.toLocaleString() + ' د.ع');
                $('#summary_count').text(response.data.total_count);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#summary_total_usd').text('$0');
                $('#summary_total_iqd').text('0 د.ع');
                $('#summary_count').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#summary_total_usd').text('$0');
            $('#summary_total_iqd').text('0 د.ع');
            $('#summary_count').text('0');
        }
    });
} 