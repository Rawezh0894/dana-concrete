$(function() {
    if (typeof COMPANY_ID === 'undefined') return;
    $.get('../process/company_receipts/select_purchse.php', { company_id: COMPANY_ID }, function(res) {
        if (!res.success || !res.data) return;
        const tbody = $('#receipt-table-body');
        tbody.empty();
        res.data.forEach(row => {
            const tr = $('<tr>');
            tr.append($('<td>').text(row.material_name || '-'));
            tr.append($('<td>').text(row.kg || '-'));
            tr.append($('<td>').text(row.price_per_kg_usd || '-'));
            tr.append($('<td>').text(row.amount_iqd || '-'));
            tr.append($('<td>').text(row.paid_usd || '-'));
            tr.append($('<td>').text(row.paid_iqd || '-'));
            tr.append($('<td>').text(row.remaining_usd || '-'));
            tr.append($('<td>').text(row.invoice_number || '-'));
            tr.append($('<td>').text(row.date || '-'));
            tbody.append(tr);
        });
    }, 'json');
}); 