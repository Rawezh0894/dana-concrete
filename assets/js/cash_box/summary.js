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

function updateCashBoxSummary(from, to, search) {
    const q = (search !== undefined && search !== null) ? String(search).trim() : '';
    $.ajax({
        url: '../process/cash_box/summary.php',
        method: 'GET',
        data: { from: from || '', to: to || '', search: q },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const totalUsdOnly = Number(response.data.total_usd || 0);
                $('#totalCashUsdOnly').text(formatUsd(totalUsdOnly));

                const totalIqdOnly = Number(response.data.total_iqd || 0);
                $('#totalCashIqdOnly').text(formatIqd(totalIqdOnly));

                $('#cashBoxTotalBalanceUsd').text(formatUsd(totalUsdOnly));
                $('#cashBoxTotalBalanceIqd').text(formatIqd(totalIqdOnly));

                const combined = Number(response.data.total_usd_all || 0);
                $('#cashBoxTotalBalanceCombined').text(formatUsd(combined));

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
                $('#cashBoxTotalBalanceUsd').text('$0.00');
                $('#cashBoxTotalBalanceIqd').text('0 د.ع');
                $('#cashBoxTotalBalanceCombined').text('$0.00');
            }
        },
        error: function(xhr, status, error) {
            console.error('Summary AJAX error:', xhr, status, error);
            $('#totalCashUsdOnly').text('$0');
            $('#totalCashIqdOnly').text('0 د.ع');
            $('#dollarRate').text('0 د.ع');
            $('#cashBoxTotalBalanceUsd').text('$0.00');
            $('#cashBoxTotalBalanceIqd').text('0 د.ع');
            $('#cashBoxTotalBalanceCombined').text('$0.00');
        }
    });
}

$(document).ready(function() {
    function getFilterDatesAndSearch() {
        return {
            from: $('#filter_from').val(),
            to: $('#filter_to').val(),
            search: ($('#cashBoxSearch').val() || '').trim()
        };
    }
    var initial = getFilterDatesAndSearch();
    updateCashBoxSummary(initial.from, initial.to, initial.search);
    window.updateCashBoxSummary = updateCashBoxSummary;
});
