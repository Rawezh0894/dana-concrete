function loadSummaryCardsData(filterParams = '') {
    const params = new URLSearchParams(filterParams);
    
    // Get filter values
    const categoryId = $('#filter_category').val() || '';
    const status = $('#filter_status').val() || '';
    
    if (categoryId) params.set('category_id', categoryId);
    if (status) params.set('status', status);
    
    $.ajax({
        url: '../process/assets/get_summary_stats.php?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Format numbers
            const formatNumber = (num) => {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(num);
            };
            
            $('#total-assets').text(data.total_assets || 0);
            $('#total-value').text('$' + formatNumber(data.total_value || 0));
            $('#total-depreciation').text('$' + formatNumber(data.total_depreciation || 0));
            $('#total-book-value').text('$' + formatNumber(data.total_book_value || 0));
        },
        error: function(xhr) {
            console.error('Error loading summary stats:', xhr);
        }
    });
}

// Load summary cards on page load
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Reload when filters change
    $('#filter_category, #filter_status').on('change', function() {
        loadSummaryCardsData();
    });
});
