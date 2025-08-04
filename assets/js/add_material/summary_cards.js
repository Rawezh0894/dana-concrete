// Summary Cards Management for Materials Page
function loadSummaryCards() {
    $.ajax({
        url: '../process/add_material/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateSummaryCards(response.data);
            } else {
                console.error('Error loading summary stats:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading summary stats:', error);
        }
    });
}

function updateSummaryCards(data) {
    // Update total materials count
    $('#totalMaterialsCount').text(data.total_materials || 0);
    
    // Update low stock count
    $('#lowStockCount').text(data.low_stock_materials || 0);
    
    // Update most used material
    $('#mostUsedMaterial').text(data.most_used_count || 0);
    $('#mostUsedMaterialName').text(data.most_used_name || 'هیچ');
}

// Auto-refresh summary cards every 30 seconds
function startAutoRefresh() {
    setInterval(function() {
        loadSummaryCards();
    }, 30000); // 30 seconds
}

// Initialize when document is ready
$(document).ready(function() {
    // Load summary cards on page load
    loadSummaryCards();
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Refresh summary cards after successful operations
    $(document).on('materialAdded', function() {
        loadSummaryCards();
    });
    
    $(document).on('materialUpdated', function() {
        loadSummaryCards();
    });
    
    $(document).on('materialDeleted', function() {
        loadSummaryCards();
    });
}); 