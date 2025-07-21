async function loadConcreteReceiptsTable() {
    const columns = [
        '#', 'receipt_number', 'customer_name', 'location', 'created_at', 'meter_amount', 'formula_name',
        'pump_car_name', 'pump_driver_name', 'mixer_car_name', 'mixer_driver_name', 'actions'
    ];
    let res = await fetch('../process/concrete_receipts/select_concrete_receipts.php');
    let text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_concrete_receipts.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
    }
    if (!data.success) {
        TableController.renderWithPagination('#concreteReceiptsTable', [], columns, { pageSize: 10, currentPage: 1 });
        return;
    }
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    const mapped = data.data.map((row, idx) => ({
        '#': idx + 1,
        receipt_number: row.receipt_number || '-',
        customer_name: row.customer_name || '-',
        location: row.location || '-',
        created_at: (function(dt) {
            if (!dt) return '-';
            const d = new Date(dt);
            if (isNaN(d)) return dt;
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
        })(row.created_at),
        meter_amount: row.meter_amount !== null && row.meter_amount !== undefined && row.meter_amount !== '' ? formatNumber(row.meter_amount) + ' m³' : '-',
        formula_name: row.formula_name || '-',
        pump_car_name: row.pump_car_name || '-',
        pump_driver_name: row.pump_driver_name || '-',
        mixer_car_name: row.mixer_car_name || '-',
        mixer_driver_name: row.mixer_driver_name || '-',
        actions: (function() {
            let buttons = '';
            if (window.userPermissions && window.userPermissions.canEdit) {
                buttons += `<button class='btn btn-warning btn-sm edit-receipt' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button> `;
            }
            if (window.userPermissions && window.userPermissions.canDelete) {
                buttons += `<button class='btn btn-danger btn-sm delete-receipt' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button> `;
            }
            if (window.userPermissions && window.userPermissions.canPrint) {
                buttons += `<button class='btn btn-info btn-sm print-receipt' data-id='${row.id}' title='پرێنت'><i class='fa fa-print'></i></button>`;
            }
            return buttons || '-';
        })()
    }));
    TableController.renderWithPagination('#concreteReceiptsTable', mapped, columns, { pageSize: 10, currentPage: 1 });
}
document.addEventListener('DOMContentLoaded', loadConcreteReceiptsTable);
window.reloadConcreteReceipts = loadConcreteReceiptsTable;

$(document).ready(function() {
    function loadConcreteReceipts() {
        $.ajax({
            url: '../process/concrete_receipts/select_concrete_receipts.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.length === 0) {
                        $('#concreteReceiptsTable tbody').html('<tr><td colspan="8">هیچ پسوڵەیەک نیە</td></tr>');
                        return;
                    }
                    let rows = '';
                    response.data.forEach(function(receipt, idx) {
                        function formatNumber(n) {
                            if (n === null || n === undefined || n === '') return '';
                            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                        rows += `<tr>
                            <td>${idx + 1}</td>
                            <td>${receipt.receipt_number || '-'}</td>
                            <td>${receipt.customer_name || '-'}</td>
                            <td>${receipt.location || '-'}</td>
                            <td>${(function(dt) {
                                if (!dt) return '-';
                                const d = new Date(dt);
                                if (isNaN(d)) return dt;
                                return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
                            })(receipt.created_at)}</td>
                            <td>${receipt.meter_amount !== null && receipt.meter_amount !== undefined && receipt.meter_amount !== '' ? formatNumber(receipt.meter_amount) + ' m³' : '-'}</td>
                            <td>${receipt.formula_name || '-'}</td>
                            <td>${receipt.pump_car_name || '-'}</td>
                            <td>${receipt.pump_driver_name || '-'}</td>
                            <td>${receipt.mixer_car_name || '-'}</td>
                            <td>${receipt.mixer_driver_name || '-'}</td>
                            <td>
                                ${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-sm btn-warning edit-receipt' data-id='${receipt.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''}
                                ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-sm btn-danger delete-receipt' data-id='${receipt.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}
                                ${window.userPermissions && window.userPermissions.canPrint ? `<button class='btn btn-sm btn-info print-receipt' data-id='${receipt.id}' title='پرێنت'><i class='fa fa-print'></i></button>` : ''}
                            </td>
                        </tr>`;
                    });
                    $('#concreteReceiptsTable tbody').html(rows);
                } else {
                    $('#concreteReceiptsTable tbody').html('<tr><td colspan="12">هەڵەیەک روویدا</td></tr>');
                }
            },
            error: function() {
                $('#concreteReceiptsTable tbody').html('<tr><td colspan="12">هەڵەیەک روویدا</td></tr>');
            }
        });
    }
    loadConcreteReceipts();
    window.reloadConcreteReceipts = loadConcreteReceipts;
});

$(document).on('click', '.print-receipt', function() {
    var id = $(this).data('id');
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'question',
            title: 'چاپکردن',
            text: 'دەتەوێت پسوڵە چاپ بکەیت؟',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر',
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('../pages/central_receipts.php?id=' + id, '_self');
            }
        });
    } else {
        if (window.confirm('دەتەوێت پسوڵە چاپ بکەیت؟')) {
            window.open('../pages/central_receipts.php?id=' + id, '_self');
        }
    }
});