// Summary Cards Data Loading for Add Customers Page
$(document).ready(function () {
    loadSummaryCardsData();

    // Refresh summary cards when customers are updated
    $(document).on('customerAdded customerUpdated customerDeleted', function () {
        loadSummaryCardsData();
    });

    // Filter button click
    $('#apply_filters').on('click', function () {
        loadSummaryCardsData();
    });

    // Clear filters button click
    $('#clear_filters').on('click', function () {
        $('#filter_year').val('');
        $('#filter_month').val('');
        $('#filter_from_date').val('');
        $('#filter_to_date').val('');
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    const filters = {
        year: $('#filter_year').val(),
        month: $('#filter_month').val(),
        from_date: $('#filter_from_date').val(),
        to_date: $('#filter_to_date').val()
    };

    $.ajax({
        url: '../process/customer/get_summary_stats.php',
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                const summary = response.data || response.summary || {};
                const totalDebtValue = summary.total_debt !== undefined
                    ? summary.total_debt
                    : (summary.total_debt_usd !== undefined ? summary.total_debt_usd : 0);
                const totalCustomersValue = summary.total_customers !== undefined ? summary.total_customers : 0;
                const customersWithDebtValue = summary.customers_with_debt !== undefined ? summary.customers_with_debt : 0;

                // Update total debt card only if it exists (user has permission)
                const totalDebtCard = $('#total_debt');
                if (totalDebtCard.length > 0) {
                    totalDebtCard.text('$' + Number(totalDebtValue || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                }

                $('#total_customers').text(Number(totalCustomersValue || 0).toLocaleString('en-US'));
                $('#customers_with_debt').text(Number(customersWithDebtValue || 0).toLocaleString('en-US'));
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values only if cards exist
                if ($('#total_debt').length > 0) {
                    $('#total_debt').text('$0');
                }
                $('#total_customers').text('0');
                $('#customers_with_debt').text('0');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error only if cards exist
            if ($('#total_debt').length > 0) {
                $('#total_debt').text('$0');
            }
            $('#total_customers').text('0');
            $('#customers_with_debt').text('0');
        }
    });
} 