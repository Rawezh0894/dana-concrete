$(function() {
    if (typeof COMPANY_ID === 'undefined') return;
    $.get('../process/company_receipts/select_return_debt.php', { company_id: COMPANY_ID }, function(res) {
        if (!res.success || !res.data) return;
        const tbody = $('#paid-table-body');
        tbody.empty();
        res.data.forEach(row => {
            const tr = $('<tr>');
            tr.append($('<td>').text(row.amount_usd || '-'));
            tr.append($('<td>').text(row.amount_iqd || '-'));
            tr.append($('<td>').text(row.date || '-'));
            tr.append($('<td>').text(row.note || '-'));
            tbody.append(tr);
        });
    }, 'json');
});
