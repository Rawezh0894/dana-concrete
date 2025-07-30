function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return Number(n).toLocaleString();
}
function formatUSD(n) {
    if (!n || isNaN(n)) return '';
    return '$' + formatNumber(Number(n).toFixed(2));
}
function formatIQD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

function mapCashBoxRow(row, idx) {
    return {
        '#': idx + 1,
        date: row.date || '',
        type: row.type === 'deposit' ? 'زیادکردن' : (row.type === 'withdraw' ? 'کەمکردنەوە' : ''),
        amount_iqd: formatIQD(row.amount_iqd),
        amount_usd: formatUSD(row.amount_usd),
        currency: row.currency || '',
        note: row.note || '',
        created_by_username: row.created_by_username || '',
        created_at: row.created_at || '',
        actions: `<button class='btn btn-primary btn-sm btn-edit-cashbox' data-id='${row.id}' data-row='${JSON.stringify(row)}'><i class='fa fa-edit'></i></button> <button class='btn btn-danger btn-sm btn-delete-cashbox' data-id='${row.id}'><i class='fa fa-trash'></i></button>`
    };
}

function loadCashBoxEntriesFiltered() {
    var from = $('#filter_from').val();
    var to = $('#filter_to').val();
    var url = '../process/cash_box/select.php';
    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (params.length) url += '?' + params.join('&');
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var data = response.data || [];
                var mapped = data.map(mapCashBoxRow);
                var columns = ['#', 'date', 'type', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions'];
                TableController.renderWithPagination('#cashBoxTable', mapped, columns, { pageSize: 10 });
            } else {
                TableController.renderWithPagination('#cashBoxTable', [], ['#', 'date', 'type', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions'], { pageSize: 10 });
                Swal.fire('هەڵە!', response.error || 'ناتوانرێت زانیاری بخوێنرێتەوە', 'error');
            }
        },
        error: function() {
            TableController.renderWithPagination('#cashBoxTable', [], ['#', 'date', 'type', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions'], { pageSize: 10 });
            Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
        }
    });
}

window.loadCashBoxEntries = loadCashBoxEntriesFiltered;

$(document).ready(function() {
    loadCashBoxEntriesFiltered();
    $('#filter_from, #filter_to').on('input change', function() {
        loadCashBoxEntriesFiltered();
    });
    $('#clearFilterBtn').on('click', function() {
        $('#filter_from').val('');
        $('#filter_to').val('');
        loadCashBoxEntriesFiltered();
    });
});
