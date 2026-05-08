let saleTable = null;
let salesTableInitialized = false;

function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatUSD(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(2)) + ' $';
}

function formatIQD(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

function ensureDefaultDates() {
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    if (fromInput && toInput && !salesTableInitialized) {
        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentYear = now.getFullYear();
        const fromDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
        const toDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate()}`;
        if (!fromInput.value) fromInput.value = fromDate;
        if (!toInput.value) toInput.value = toDate;
    }
}

function loadSalesTable() {
    ensureDefaultDates();
    
    if (saleTable) {
        saleTable.ajax.reload(null, false);
        return;
    }
    
    saleTable = $('#saleTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../process/sale/select_sale.php',
            type: 'GET',
            data: function(d) {
                d.from = document.getElementById('filter_from')?.value || '';
                d.to = document.getElementById('filter_to')?.value || '';
                d.customer_id = document.getElementById('filter_customer')?.value || '';
                d.min_quantity = document.getElementById('filter_quantity_min')?.value || '';
                d.max_quantity = document.getElementById('filter_quantity_max')?.value || '';
                d.amount_min = document.getElementById('filter_amount_min')?.value || '';
                d.amount_max = document.getElementById('filter_amount_max')?.value || '';
            },
            error: function(xhr) {
                console.error('Error loading sales:', xhr?.responseText || xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'customer_name', defaultContent: '-' },
            { data: 'recipient', defaultContent: '-' },
            { data: 'location', defaultContent: '-' },
            { data: 'invoice_number', defaultContent: '-' },
            { data: 'formula_name', defaultContent: '-' },
            { data: 'order_date', defaultContent: '-' },
            { data: 'payment_type', defaultContent: '-' },
            { 
                data: 'quantity', 
                render: function(data) {
                    return data && data !== '' ? `M³ ${formatNumber(data)}` : '-';
                }
            },
            { 
                data: 'price_per_unit', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'total_price', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'amount_paid_iq', 
                render: function(data) {
                    return formatIQD(data);
                }
            },
            { 
                data: 'amount_paid_usd', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'remaining_amount', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'dolar_rate', 
                render: function(data) {
                    return data && data !== '' ? formatNumber(data) : '-';
                }
            },
            { data: 'notes', defaultContent: '-' },
            { 
                data: 'discount', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: null,
                orderable: false,
                searchable: false,
                render: function(data) {
                    const editBtn = window.userPermissions && window.userPermissions.canEdit
                        ? `<button class='btn btn-warning btn-sm edit-sale' data-id='${data.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>`
                        : '';
                    const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                        ? `<button class='btn btn-danger btn-sm delete-sale' data-id='${data.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`
                        : '';
                    return `${editBtn} ${deleteBtn}`.trim() || '-';
                }
            }
        ],
        language: {
            "processing": "چاوەڕوان بە...",
            "search": "گەڕان:",
            "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
            "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
            "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
            "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
            "loadingRecords": "لۆدینگ...",
            "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
            "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
            "paginate": {
                "first": "یەکەم",
                "previous": "پێشوو",
                "next": "دواتر",
                "last": "کۆتایی"
            },
            "aria": {
                "sortAscending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی زیادبوون",
                "sortDescending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی کەمبوون"
            }
        },
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
            { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
            { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
            { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
        ],
        createdRow: function(row, data) {
            if (data.duplicate_count && data.duplicate_count > 1) {
                $(row).addClass('duplicate-invoice-row');
            }
        }
    });
    
    salesTableInitialized = true;
}

document.addEventListener('DOMContentLoaded', function() {
    loadSalesTable();
});

window.reloadSales = function() {
    loadSalesTable();
};

