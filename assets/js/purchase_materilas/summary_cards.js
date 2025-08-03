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
                $('#total-purchases').text(response.summary.total_purchases.toLocaleString());
                $('#total-purchase-value').text('$' + response.summary.total_purchase_value_usd.toLocaleString());
                $('#total-suppliers').text(response.summary.total_suppliers.toLocaleString());
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

// Function to refresh summary cards (can be called from other scripts)
function refreshSummaryCards() {
    loadSummaryCardsData();
} 