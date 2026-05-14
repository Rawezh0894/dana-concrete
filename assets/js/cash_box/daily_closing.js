// ─── Daily Closing Balance Panel ─────────────────────────────────────────────

function loadDailyClosing(from, to, search) {
    var q = (search !== undefined && search !== null) ? String(search).trim() : '';

    $('#dailyClosingBody').html(
        '<tr><td colspan="8" class="text-center text-muted py-3">' +
        '<i class="fas fa-spinner fa-spin me-2"></i>بارکردن...</td></tr>'
    );

    $.ajax({
        url:      '../process/cash_box/daily_closing.php',
        method:   'GET',
        data:     { from: from || '', to: to || '', search: q },
        dataType: 'json',
        success: function (res) {
            if (!res.success) {
                $('#dailyClosingBody').html(
                    '<tr><td colspan="8" class="text-danger text-center py-3">' + (res.error || 'هەڵەیەک ڕووی دا') + '</td></tr>'
                );
                return;
            }
            renderDailyClosing(res.data);
        },
        error: function () {
            $('#dailyClosingBody').html(
                '<tr><td colspan="8" class="text-danger text-center py-3">هەڵەیەک ڕووی دا لە کۆنێکتکردن</td></tr>'
            );
        },
    });
}

function renderDailyClosing(rows) {
    if (!rows || rows.length === 0) {
        $('#dailyClosingBody').html(
            '<tr><td colspan="8" class="text-center text-muted py-3">هیچ زانیارییەک نەدۆزرایەوە</td></tr>'
        );
        return;
    }

    var html = '';
    rows.forEach(function (row) {
        var closeUsd = parseFloat(row.closing_usd) || 0;
        var closeIqd = parseFloat(row.closing_iqd) || 0;
        var clsUsd   = closeUsd >= 0 ? 'cashbox-bal-pos' : 'cashbox-bal-neg';
        var clsIqd   = closeIqd >= 0 ? 'cashbox-bal-pos' : 'cashbox-bal-neg';

        html += '<tr>';
        html += '<td class="fw-semibold">' + row.date + '</td>';
        html += '<td><span class="badge bg-secondary">' + row.tx_count + '</span></td>';
        html += '<td class="text-success">' + (row.inflow_usd  > 0 ? formatUSD(row.inflow_usd)  : '—') + '</td>';
        html += '<td class="text-danger">'  + (row.outflow_usd > 0 ? formatUSD(row.outflow_usd) : '—') + '</td>';
        html += '<td class="text-success">' + (row.inflow_iqd  > 0 ? formatIQD(row.inflow_iqd)  : '—') + '</td>';
        html += '<td class="text-danger">'  + (row.outflow_iqd > 0 ? formatIQD(row.outflow_iqd) : '—') + '</td>';
        html += '<td><span class="' + clsUsd + '">' + formatUSD(closeUsd) + '</span></td>';
        html += '<td><span class="' + clsIqd + '">' + formatIQD(closeIqd) + '</span></td>';
        html += '</tr>';
    });

    $('#dailyClosingBody').html(html);
}

// Expose globally
window.loadDailyClosing = loadDailyClosing;
