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
            } else {
                $('#totalCashUsdAll').text('$0');
            }
        },
        error: function() {
            $('#totalCashUsdAll').text('$0');
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