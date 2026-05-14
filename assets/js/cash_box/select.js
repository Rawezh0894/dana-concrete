// Row data store — avoids HTML-attribute encoding issues with apostrophes/quotes
var cashBoxRowStore = {};

// ─── Formatting helpers ───────────────────────────────────────────────────
function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return Number(n).toLocaleString('en-US');
}
function formatUSD(n) {
    if (n === null || n === undefined || isNaN(n)) return '$0.00';
    return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatIQD(n) {
    if (n === null || n === undefined || isNaN(n)) return '0 د.ع';
    return Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' د.ع';
}

// Running balance cell: show the relevant currency balance for this row
function formatRunningBalance(row) {
    var usd = parseFloat(row.running_bal_usd);
    var iqd = parseFloat(row.running_bal_iqd);

    if (row.running_bal_usd === null && row.running_bal_iqd === null) {
        return '<span class="text-muted small">—</span>';
    }

    var html = '';

    if (!isNaN(usd) && row.currency === 'دۆلار') {
        var cls = usd >= 0 ? 'cashbox-bal-pos' : 'cashbox-bal-neg';
        html += '<span class="' + cls + '">' + formatUSD(usd) + '</span>';
    }
    if (!isNaN(iqd) && row.currency === 'دینار') {
        var cls2 = iqd >= 0 ? 'cashbox-bal-pos' : 'cashbox-bal-neg';
        html += '<span class="' + cls2 + '">' + formatIQD(iqd) + '</span>';
    }
    return html || '<span class="text-muted small">—</span>';
}

function mapCashBoxRow(row, idx) {
    // Store raw row data by ID for edit modal access
    cashBoxRowStore[row.id] = row;

    var actions =
        '<button class="btn btn-primary btn-sm btn-edit-cashbox me-1" data-id="' + row.id + '"><i class="fa fa-edit"></i></button>' +
        '<button class="btn btn-danger btn-sm btn-delete-cashbox me-1" data-id="' + row.id + '"><i class="fa fa-trash"></i></button>' +
        '<button class="btn btn-outline-secondary btn-sm btn-history-cashbox" data-id="' + row.id + '" title="مێژووی گۆڕانکاری"><i class="fa fa-history"></i></button>';

    return {
        '#': idx + 1,
        date: row.date || '',
        type: row.type === 'deposit'
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle">زیادکردن</span>'
            : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">کەمکردنەوە</span>',
        in_out: '<span class="in-out-cell ' + (row.type === 'deposit' ? 'in-out-incoming' : 'in-out-outgoing') + '">' +
                (row.type === 'deposit' ? 'هاتوو' : 'ڕۆشتوو') + '</span>',
        amount_iqd: row.amount_iqd > 0 ? formatIQD(row.amount_iqd) : '<span class="text-muted">—</span>',
        amount_usd: row.amount_usd > 0 ? formatUSD(row.amount_usd) : '<span class="text-muted">—</span>',
        currency: row.currency || '',
        running_balance: formatRunningBalance(row),
        note: row.note
            ? '<div style="max-width:350px;white-space:pre-wrap;word-wrap:break-word;text-align:right;">' + row.note + '</div>'
            : '',
        created_by_username: row.created_by_username || '',
        created_at: row.created_at || '',
        actions: actions,
    };
}

var currentCashBoxPage = 1;
var cashBoxSearchTimer  = null;

function getCashBoxSearchValue() {
    return ($('#cashBoxSearch').val() || '').trim();
}

async function loadCashBoxEntriesFiltered(page) {
    page = page || 1;
    currentCashBoxPage = page;

    var from   = $('#filter_from').val();
    var to     = $('#filter_to').val();
    var search = getCashBoxSearchValue();

    var fd = new FormData();
    if (from)   fd.append('from',   from);
    if (to)     fd.append('to',     to);
    if (search) fd.append('search', search);
    fd.append('page',  page);
    fd.append('limit', 10);

    var cols = ['#', 'date', 'type', 'in_out', 'amount_iqd', 'amount_usd', 'currency', 'running_balance',
                'note', 'created_by_username', 'created_at', 'actions'];

    try {
        var resp   = await fetch('../process/cash_box/select.php', { method: 'POST', body: fd });
        var result = await resp.json();

        if (result.success) {
            var data   = result.data || [];
            var mapped = data.map(function (row, idx) {
                return mapCashBoxRow(row, (page - 1) * 10 + idx);
            });
            TableController.render('#cashBoxTable', mapped, cols);
            if (result.pagination) {
                renderCashBoxPagination(result.pagination, data.length);
            }
        } else {
            TableController.render('#cashBoxTable', [], cols);
            Swal.fire('هەڵە!', result.error || 'ناتوانرێت زانیاری بخوێنرێتەوە', 'error');
        }
    } catch (err) {
        console.error('Error loading cash box entries:', err);
        TableController.render('#cashBoxTable', [], cols);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
    }
}

function renderCashBoxPagination(pagination, currentCount) {
    var html = '<nav class="mt-3"><ul class="pagination justify-content-center">';

    html += pagination.has_prev
        ? '<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="' + (pagination.current_page - 1) + '">پێشوو</a></li>'
        : '<li class="page-item disabled"><span class="page-link">پێشوو</span></li>';

    var start = Math.max(1, pagination.current_page - 2);
    var end   = Math.min(pagination.total_pages, pagination.current_page + 2);

    if (start > 1) {
        html += '<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="1">1</a></li>';
        if (start > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for (var i = start; i <= end; i++) {
        html += i === pagination.current_page
            ? '<li class="page-item active"><span class="page-link">' + i + '</span></li>'
            : '<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
    }
    if (end < pagination.total_pages) {
        if (end < pagination.total_pages - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        html += '<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="' + pagination.total_pages + '">' + pagination.total_pages + '</a></li>';
    }

    html += pagination.has_next
        ? '<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="' + (pagination.current_page + 1) + '">دواتر</a></li>'
        : '<li class="page-item disabled"><span class="page-link">دواتر</span></li>';

    html += '</ul><p class="text-center text-muted mt-2 small">پیشاندانی ' + currentCount + ' لە ' + pagination.total_records + ' — پەڕە ' + pagination.current_page + ' لە ' + pagination.total_pages + '</p></nav>';

    $('#cashBoxTable').closest('.table-responsive').next('nav').remove();
    $('#cashBoxTable').closest('.table-responsive').after(html);
}

$(document).on('click', '.cashbox-page-link', function (e) {
    e.preventDefault();
    var pg = parseInt($(this).data('page'), 10);
    if (pg) {
        loadCashBoxEntriesFiltered(pg);
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});

window.loadCashBoxEntries = loadCashBoxEntriesFiltered;

function scheduleCashBoxReload() {
    if (cashBoxSearchTimer) clearTimeout(cashBoxSearchTimer);
    cashBoxSearchTimer = setTimeout(function () {
        cashBoxSearchTimer = null;
        loadCashBoxEntriesFiltered(1);
        if (typeof updateCashBoxSummary === 'function') {
            updateCashBoxSummary($('#filter_from').val(), $('#filter_to').val(), getCashBoxSearchValue());
        }
        if (typeof loadDailyClosing === 'function' && $('#dailyClosingPanel').hasClass('show')) {
            loadDailyClosing($('#filter_from').val(), $('#filter_to').val(), getCashBoxSearchValue());
        }
    }, 400);
}

$(document).ready(function () {
    loadCashBoxEntriesFiltered(1);

    $('#filter_from, #filter_to').on('input change', function () {
        loadCashBoxEntriesFiltered(1);
        if (typeof updateCashBoxSummary === 'function') {
            updateCashBoxSummary($('#filter_from').val(), $('#filter_to').val(), getCashBoxSearchValue());
        }
        if (typeof loadDailyClosing === 'function' && $('#dailyClosingPanel').hasClass('show')) {
            loadDailyClosing($('#filter_from').val(), $('#filter_to').val(), getCashBoxSearchValue());
        }
    });

    $('#cashBoxSearch').on('input', scheduleCashBoxReload);

    $('#clearFilterBtn').on('click', function () {
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#cashBoxSearch').val('');
        $('.quick-filter-btn').removeClass('active');
        if (cashBoxSearchTimer) { clearTimeout(cashBoxSearchTimer); cashBoxSearchTimer = null; }
        loadCashBoxEntriesFiltered(1);
        if (typeof updateCashBoxSummary === 'function') updateCashBoxSummary('', '', '');
        if (typeof loadDailyClosing === 'function' && $('#dailyClosingPanel').hasClass('show')) {
            loadDailyClosing('', '', '');
        }
    });

    $('#exportExcelBtn').on('click', exportToExcel);
    $('#printReportBtn').on('click', openPrintReport);
});

function exportToExcel() {
    var from   = $('#filter_from').val();
    var to     = $('#filter_to').val();
    var search = getCashBoxSearchValue();
    var url    = '../process/cash_box/export_excel.php';
    var parts  = [];
    if (from)   parts.push('from='   + encodeURIComponent(from));
    if (to)     parts.push('to='     + encodeURIComponent(to));
    if (search) parts.push('search=' + encodeURIComponent(search));
    if (parts.length) url += '?' + parts.join('&');

    var $btn  = $('#exportExcelBtn');
    var orig  = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>چاوەڕوان...').prop('disabled', true);

    var a = document.createElement('a');
    a.href     = url;
    a.download = 'cash_box_' + new Date().toISOString().split('T')[0] + '.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(function () {
        $btn.html(orig).prop('disabled', false);
        Swal.fire({ icon: 'success', title: 'سەرکەوتوو!', text: 'فایلەکە داونلۆد کرا', timer: 1800, showConfirmButton: false });
    }, 1500);
}

function openPrintReport() {
    var from   = $('#filter_from').val();
    var to     = $('#filter_to').val();
    var search = getCashBoxSearchValue();
    var url    = '../process/cash_box/print_report.php';
    var parts  = [];
    if (from)   parts.push('from='   + encodeURIComponent(from));
    if (to)     parts.push('to='     + encodeURIComponent(to));
    if (search) parts.push('search=' + encodeURIComponent(search));
    if (parts.length) url += '?' + parts.join('&');
    window.open(url, '_blank', 'width=1100,height=800,scrollbars=yes');
}
