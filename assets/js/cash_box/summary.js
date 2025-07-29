function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function updateCashBoxSummary(from, to) {
    $.ajax({
        url: '../process/cash_box/summary.php',
        method: 'GET',
        data: { from: from, to: to },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.data.total_usd_all !== undefined) {
                    $('#totalCashUsdAll').text('$' + Number(response.data.total_usd_all).toLocaleString());
                } else {
                    $('#totalCashUsdAll').text('$0');
                }
                
                // Update dollar rate card
                if (response.data.usd_iqd_rate !== undefined) {
                    $('#dollarRate').text(formatNumber(response.data.usd_iqd_rate) + ' د.ع');
                } else {
                    $('#dollarRate').text('0 د.ع');
                }
            } else {
                $('#totalCashUsdAll').text('$0');
                $('#dollarRate').text('0 د.ع');
            }
        },
        error: function() {
            $('#totalCashUsdAll').text('$0');
            $('#dollarRate').text('0 د.ع');
        }
    });
}

$(document).ready(function() {
    function getFilterDates() {
        return {
            from: $('#filter_from').val(),
            to: $('#filter_to').val()
        };
    }
    // Initial summary
    var dates = getFilterDates();
    updateCashBoxSummary(dates.from, dates.to);

    // Update summary on filter change
    $('#filter_from, #filter_to').on('change', function() {
        var dates = getFilterDates();
        updateCashBoxSummary(dates.from, dates.to);
    });
    $('#clearFilterBtn').on('click', function() {
        setTimeout(function() {
            var dates = getFilterDates();
            updateCashBoxSummary(dates.from, dates.to);
        }, 100);
    });
    // Optionally, update after add/edit/delete
    window.updateCashBoxSummary = updateCashBoxSummary;
}); 