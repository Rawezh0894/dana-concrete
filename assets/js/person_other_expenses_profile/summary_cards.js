// Summary Cards Management for Person Other Expenses Profile
function loadSummaryCards() {
    $.ajax({
        url: '../process/person_other_expenses_profile/get_summary_stats.php',
        type: 'GET',
        data: { person_id: PERSON_ID },
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
    // Update total expenses
    $('#summary_total_usd').text(Number(data.total_expense_usd || 0).toLocaleString('en-US') + ' $');
    $('#summary_total_iqd').text(Number(data.total_expense_iqd || 0).toLocaleString('en-US') + ' د.ع');
    $('#summary_count').text(data.expense_count || 0);
    
    // Update our debt USD
    $('#summary_our_debt_usd').text(Number(data.our_debt_usd || 0).toLocaleString('en-US') + ' $');
    
    // Update our debt IQD
    $('#summary_our_debt_iqd').text(Number(data.our_debt_iqd || 0).toLocaleString('en-US') + ' د.ع');
    
    // Add tooltip with breakdown for USD debt
    $('#summary_our_debt_usd').attr('title', 
        'قەرزی سەرەتایی: ' + Number(data.opening_debt_usd || 0).toLocaleString('en-US') + ' $' +
        '\nماوەی خەرجیەکان: ' + Number(data.remaining_expenses_usd || 0).toLocaleString('en-US') + ' $' +
        '\nماوەی کڕینەکان: ' + Number(data.remaining_purchase_usd || 0).toLocaleString('en-US') + ' $'
    );
    
    // Add tooltip with breakdown for IQD debt
    $('#summary_our_debt_iqd').attr('title', 
        'قەرزی سەرەتایی: ' + Number(data.opening_debt_iqd || 0).toLocaleString('en-US') + ' د.ع' +
        '\nماوەی خەرجیەکان: ' + Number(data.remaining_expenses_iqd || 0).toLocaleString('en-US') + ' د.ع' +
        '\nماوەی کڕینەکان: ' + Number(data.remaining_purchase_iqd || 0).toLocaleString('en-US') + ' د.ع'
    );
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
    $(document).on('debtAdded', function() {
        loadSummaryCards();
    });
    
    $(document).on('debtUpdated', function() {
        loadSummaryCards();
    });
    
    $(document).on('debtDeleted', function() {
        loadSummaryCards();
    });
}); 