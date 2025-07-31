// Summary Cards Data Loading for Sales Page
$(document).ready(function() {
    loadSummaryCardsData();
    loadUsdRate();
    
    // Refresh summary cards when sales are updated
    $(document).on('saleAdded saleUpdated saleDeleted', function() {
        loadSummaryCardsData();
    });
    
    // Refresh USD rate button click
    $('#refresh-usd-rate').on('click', function() {
        loadUsdRate();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/sale/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-customer-debt').text('$' + response.data.total_customer_debt.toLocaleString());
                $('#customers-with-debt').text(response.data.customers_with_debt);
                $('#total-sales').text(response.data.total_sales);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-customer-debt').text('$0');
                $('#customers-with-debt').text('0');
                $('#total-sales').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-customer-debt').text('$0');
            $('#customers-with-debt').text('0');
            $('#total-sales').text('0');
        }
    });
}

function loadUsdRate() {
    // Show loading state
    const rateElement = $('#usd-rate');
    const refreshBtn = $('#refresh-usd-rate');
    const originalText = rateElement.text();
    
    rateElement.html('<i class="fas fa-spinner fa-spin"></i>');
    refreshBtn.prop('disabled', true);
    
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Format the rate with commas
                const formattedRate = response.rate.toLocaleString();
                rateElement.text(formattedRate);
                
                // Show success indicator briefly
                rateElement.addClass('text-success');
                setTimeout(() => {
                    rateElement.removeClass('text-success');
                }, 1000);
            } else {
                console.error('Error loading USD rate:', response.error);
                rateElement.text('0');
                
                // Show error indicator
                rateElement.addClass('text-danger');
                setTimeout(() => {
                    rateElement.removeClass('text-danger');
                }, 2000);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error loading USD rate:', error);
            rateElement.text('0');
            
            // Show error indicator
            rateElement.addClass('text-danger');
            setTimeout(() => {
                rateElement.removeClass('text-danger');
            }, 2000);
        },
        complete: function() {
            refreshBtn.prop('disabled', false);
        }
    });
} 