// Function to format currency
function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

// Function to format number
function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Function to load purchase summary data
function loadPurchaseSummary() {
    fetch('../process/purchase/get_summary.php')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.error('Error loading summary:', result.error);
                return;
            }
            
            const data = result.data;
            
            // Update total debt card
            $('#total-debt').text('$' + formatCurrency(data.total_debt));
            
            // Update total companies card
            $('#total-companies').text(formatNumber(data.total_companies));
            
            // Update indebted companies card
            $('#indebted-companies').text(formatNumber(data.indebted_companies));
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Initialize summary cards when page loads
$(document).ready(function() {
    // Load initial summary data
    loadPurchaseSummary();
    
    // Update summary when filters change
    $('#filter_from, #filter_to').on('change', function() {
        // Note: Currently summary is not filtered by date, but this can be extended
        // loadPurchaseSummary();
    });
    
    // Update summary when clear filter is clicked
    $('#clearFilterBtn').on('click', function() {
        // Note: Currently summary is not filtered by date, but this can be extended
        // loadPurchaseSummary();
    });
});

// Make function globally available for refresh after add/edit/delete
window.loadPurchaseSummary = loadPurchaseSummary; 