// formatNumber / formatUSD / formatIQD are defined in select.js (loaded first)

function updateCashBoxSummary(from, to, search) {
    var q = (search !== undefined && search !== null) ? String(search).trim() : '';

    $.ajax({
        url: '../process/cash_box/summary.php',
        method: 'GET',
        data: { from: from || '', to: to || '', search: q },
        dataType: 'json',
        success: function (res) {
            if (!res.success) {
                console.error('Summary error:', res);
                resetSummaryUI();
                return;
            }
            var d = res.data;

            // ── Hero card ──────────────────────────────────────────────────
            $('#cashBoxTotalBalanceUsd').text(formatUSD(d.total_usd  || 0));
            $('#cashBoxTotalBalanceIqd').text(formatIQD(d.total_iqd  || 0));
            $('#cashBoxTotalBalanceCombined').text(formatUSD(d.total_usd_all || 0));
            $('#dollarRate').text(formatNumber(d.usd_iqd_rate || 0) + ' د.ع');

            var count = d.transaction_count || 0;
            $('#cashBoxTxCount').text(count + ' مامەڵە');

            // ── Inflow ─────────────────────────────────────────────────────
            $('#totalInflowUsd').text(formatUSD(d.inflow_usd || 0));
            $('#totalInflowIqd').text(formatIQD(d.inflow_iqd || 0));

            // ── Outflow ────────────────────────────────────────────────────
            $('#totalOutflowUsd').text(formatUSD(d.outflow_usd || 0));
            $('#totalOutflowIqd').text(formatIQD(d.outflow_iqd || 0));

            // ── Net (= inflow − outflow) ───────────────────────────────────
            var netUsd = (d.total_usd || 0);
            var netIqd = (d.total_iqd || 0);
            var $netUsd = $('#totalNetUsd');
            var $netIqd = $('#totalNetIqd');
            $netUsd.text(formatUSD(netUsd)).removeClass('text-success text-danger text-primary')
                   .addClass(netUsd >= 0 ? 'text-success' : 'text-danger');
            $netIqd.text(formatIQD(netIqd)).removeClass('text-success text-danger text-primary')
                   .addClass(netIqd >= 0 ? 'text-success' : 'text-danger');

            // ── Net card border colour ─────────────────────────────────────
            var $netCard = $('.cashbox-net-card');
            $netCard.removeClass('cashbox-net-positive cashbox-net-negative cashbox-net-zero');
            if (netUsd > 0)       $netCard.addClass('cashbox-net-positive');
            else if (netUsd < 0)  $netCard.addClass('cashbox-net-negative');
            else                  $netCard.addClass('cashbox-net-zero');
        },
        error: function (xhr, status, err) {
            console.error('Summary AJAX error:', status, err);
            resetSummaryUI();
        },
    });
}

function resetSummaryUI() {
    $('#cashBoxTotalBalanceUsd, #totalInflowUsd, #totalOutflowUsd, #totalNetUsd, #cashBoxTotalBalanceCombined')
        .text('$0.00');
    $('#cashBoxTotalBalanceIqd, #totalInflowIqd, #totalOutflowIqd, #totalNetIqd').text('0 د.ع');
    $('#dollarRate').text('0 د.ع');
    $('#cashBoxTxCount').text('0 مامەڵە');
}

$(document).ready(function () {
    function getFilters() {
        return {
            from:   $('#filter_from').val(),
            to:     $('#filter_to').val(),
            search: ($('#cashBoxSearch').val() || '').trim(),
        };
    }
    var f = getFilters();
    updateCashBoxSummary(f.from, f.to, f.search);

    // Expose globally so other modules can call it
    window.updateCashBoxSummary = updateCashBoxSummary;
});
