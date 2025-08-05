async function loadConcreteReceiptsTable() {
    // This function is now deprecated in favor of the paginated version in filter.js
    // Redirect to the paginated version
    if (typeof window.loadFilteredReceipts === 'function') {
        window.loadFilteredReceipts(1);
    } else {
        // Fallback to old method if filter.js is not loaded
        const columns = [
            '#', 'receipt_number', 'customer_name', 'location', 'receiver_name', 'created_at', 'meter_amount',
            'formula_name', 'pump_car_name', 'pump_driver_name', 'mixer_car_name', 'mixer_driver_name', 'actions'
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
            receiver_name: row.receiver_name || '-',
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
}

document.addEventListener('DOMContentLoaded', loadConcreteReceiptsTable);
window.reloadConcreteReceipts = loadConcreteReceiptsTable;

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
