// ─── Audit Log Modal ─────────────────────────────────────────────────────────

$(document).on('click', '.btn-history-cashbox', function () {
    var id = $(this).data('id');
    $('#auditLogTxId').text('#' + id);
    $('#auditLogContent').html(
        '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>بارکردن...</div>'
    );
    var modal = new bootstrap.Modal(document.getElementById('auditLogModal'));
    modal.show();

    $.ajax({
        url:      '../process/cash_box/audit_log.php',
        method:   'GET',
        data:     { id: id },
        dataType: 'json',
        success: function (res) {
            if (!res.success) {
                $('#auditLogContent').html('<div class="alert alert-danger">' + (res.error || 'هەڵەیەک ڕووی دا') + '</div>');
                return;
            }
            renderAuditLog(res.data, id);
        },
        error: function () {
            $('#auditLogContent').html('<div class="alert alert-danger">هەڵەیەک ڕووی دا لە بارکردن.</div>');
        },
    });
});

function renderAuditLog(entries, txId) {
    if (!entries || entries.length === 0) {
        $('#auditLogContent').html(
            '<div class="text-center text-muted py-4">' +
            '<i class="fas fa-history fa-3x mb-3 d-block opacity-25"></i>' +
            'هیچ تۆمار نەدۆزرایەوە بۆ ئەم مامەڵەیە' +
            '</div>'
        );
        return;
    }

    var actionLabels = {
        created: '<span class="badge bg-success"><i class="fas fa-plus me-1"></i>دروستکرا</span>',
        updated: '<span class="badge bg-warning text-dark"><i class="fas fa-edit me-1"></i>نوێکرایەوە</span>',
        deleted: '<span class="badge bg-danger"><i class="fas fa-trash me-1"></i>سڕایەوە</span>',
    };

    var fieldLabels = {
        date:       'بەروار',
        type:       'جۆر',
        amount_iqd: 'بڕ (دینار)',
        amount_usd: 'بڕ (دۆلار)',
        currency:   'دراو',
        note:       'تێبینی',
    };

    var html = '<div class="cashbox-audit-timeline">';

    entries.forEach(function (entry, idx) {
        var action = entry.action || 'updated';
        var badge  = actionLabels[action] || ('<span class="badge bg-secondary">' + action + '</span>');
        var user   = entry.changed_by_username || 'نەزانراو';
        var time   = entry.changed_at || '';

        html += '<div class="cashbox-audit-entry ' + (idx % 2 === 0 ? 'cashbox-audit-even' : 'cashbox-audit-odd') + '">';
        html += '<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">';
        html += '<div>' + badge + ' <span class="fw-semibold ms-2">' + escHtml(user) + '</span></div>';
        html += '<small class="text-muted"><i class="fas fa-clock me-1"></i>' + escHtml(time) + '</small>';
        html += '</div>';

        if (action === 'created' && entry.new_data) {
            html += '<div class="small text-muted mb-1">زانیاری دروستکراو:</div>';
            html += renderDataBadges(entry.new_data, fieldLabels);
        } else if (action === 'deleted' && entry.old_data) {
            html += '<div class="small text-muted mb-1">زانیاری سڕایەوە:</div>';
            html += renderDataBadges(entry.old_data, fieldLabels, 'deleted');
        } else if (action === 'updated' && entry.old_data && entry.new_data) {
            html += renderDiff(entry.old_data, entry.new_data, fieldLabels);
        }

        html += '</div>';
    });

    html += '</div>';
    $('#auditLogContent').html(html);
}

function renderDataBadges(data, labels) {
    var html = '<div class="d-flex flex-wrap gap-2 mt-1">';
    Object.keys(labels).forEach(function (k) {
        if (data[k] !== undefined && data[k] !== null && data[k] !== '') {
            html += '<span class="badge bg-light text-dark border">' +
                    labels[k] + ': <strong>' + escHtml(String(data[k])) + '</strong></span>';
        }
    });
    html += '</div>';
    return html;
}

function renderDiff(oldData, newData, labels) {
    var changed = false;
    var html    = '<table class="table table-sm table-bordered mb-0 small">';
    html += '<thead class="table-light"><tr><th>خانە</th><th class="text-danger">پێشتر</th><th class="text-success">ئێستا</th></tr></thead><tbody>';

    Object.keys(labels).forEach(function (k) {
        var o = String(oldData[k] !== undefined ? oldData[k] : '');
        var n = String(newData[k] !== undefined ? newData[k] : '');
        if (o !== n) {
            changed = true;
            html += '<tr>';
            html += '<td class="fw-semibold">' + labels[k] + '</td>';
            html += '<td class="text-danger text-decoration-line-through">' + escHtml(o) + '</td>';
            html += '<td class="text-success fw-bold">'  + escHtml(n) + '</td>';
            html += '</tr>';
        }
    });

    html += '</tbody></table>';

    if (!changed) {
        return '<div class="text-muted small">هیچ گۆڕانکارییەک نەدۆزرایەوە</div>';
    }
    return html;
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}
