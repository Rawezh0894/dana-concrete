// Summary Cards Data Loading for Stock Adjustments Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when adjustments are updated
    $(document).on('adjustmentAdded adjustmentUpdated adjustmentDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/stock_adjustments/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-adjustments').text(response.data.total_adjustments);
                $('#total-additions').text(response.data.total_additions);
                $('#total-subtractions').text(response.data.total_subtractions);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-adjustments').text('0');
                $('#total-additions').text('0');
                $('#total-subtractions').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-adjustments').text('0');
            $('#total-additions').text('0');
            $('#total-subtractions').text('0');
        }
    });
} 