// Summary Cards Data Loading for Purchase Materials Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when purchases are updated
    $(document).on('purchaseAdded purchaseUpdated purchaseDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/purchase_materilas/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-purchases').text(response.data.total_purchases);
                $('#total-purchase-value').text('$' + response.data.total_purchase_value.toLocaleString());
                $('#total-suppliers').text(response.data.total_suppliers);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-purchases').text('0');
                $('#total-purchase-value').text('$0');
                $('#total-suppliers').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-purchases').text('0');
            $('#total-purchase-value').text('$0');
            $('#total-suppliers').text('0');
        }
    });
} 