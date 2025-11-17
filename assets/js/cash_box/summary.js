function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatUsd(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        amount = 0;
    }
    return '$' + Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatIqd(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        amount = 0;
    }
    return Number(amount).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + ' د.ع';
}

function updateCashBoxSummary(from, to) {
    $.ajax({
        url: '../process/cash_box/summary.php',
        method: 'GET',
        data: { from: from || '', to: to || '' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const totalUsdOnly = Number(response.data.total_usd || 0);
                $('#totalCashUsdOnly').text(formatUsd(totalUsdOnly));
                
                const totalIqdOnly = Number(response.data.total_iqd || 0);
                $('#totalCashIqdOnly').text(formatIqd(totalIqdOnly));
                
                if (response.data.usd_iqd_rate !== undefined) {
                    $('#dollarRate').text(formatNumber(response.data.usd_iqd_rate) + ' د.ع');
                } else {
                    $('#dollarRate').text('0 د.ع');
                }
            } else {
                console.error('Summary error:', response);
                $('#totalCashUsdOnly').text('$0');
                $('#totalCashIqdOnly').text('0 د.ع');
                $('#dollarRate').text('0 د.ع');
            }
        },
        error: function(xhr, status, error) {
            console.error('Summary AJAX error:', xhr, status, error);
            $('#totalCashUsdOnly').text('$0');
            $('#totalCashIqdOnly').text('0 د.ع');
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