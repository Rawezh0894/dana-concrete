// Summary Cards Update Script
// This script updates the summary cards with real-time data

function updateSummaryCards() {
    // Update total purchases
    $.ajax({
        url: '../process/purchase_materilas/get_summary_stats.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#total-purchases').text(response.data.total_purchases || 0);
                $('#total-purchase-value').text('$' + (response.data.total_value || 0).toLocaleString());
                $('#total-suppliers').text(response.data.total_suppliers || 0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching summary stats:', error);
        }
    });
    
    // Update total materials count from inventory data
    if (typeof window.initialMaterials !== 'undefined') {
        const totalMaterials = window.initialMaterials.length;
        $('#total-materials').text(totalMaterials);
    } else {
        // If materials data is not available, fetch it
        $.ajax({
            url: '../process/purchase_materilas/get_materials_with_inventory.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const totalMaterials = response.data.length;
                    $('#total-materials').text(totalMaterials);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching materials count:', error);
                $('#total-materials').text('0');
            }
        });
    }
}

// Update summary cards on page load
$(document).ready(function() {
    updateSummaryCards();
    
    // Update summary cards every 5 minutes
    setInterval(updateSummaryCards, 5 * 60 * 1000);
    
    // Update summary cards when inventory is refreshed
    $(document).on('inventoryRefreshed', function() {
        updateSummaryCards();
    });
});

// Export function for manual updates
window.updateSummaryCards = updateSummaryCards; 