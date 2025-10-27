let saleTable = null;

function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatUSD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(2)) + ' $';
}

function formatIQD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

async function loadSalesTable(filterParams = '') {
    // Destroy existing table if it exists
    if (saleTable) {
        saleTable.destroy();
        saleTable = null;
        $('#saleTable').empty();
    }
    
    // Get current month and year
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();
    
    // Set default filter to current month
    const fromDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    const toDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate()}`;
    
    // Update filter inputs if they exist
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    if (fromInput && !fromInput.value) fromInput.value = fromDate;
    if (toInput && !toInput.value) toInput.value = toDate;
    
    try {
        let url = '../process/sale/select_sale.php';
        if (filterParams) url += '?' + filterParams;
        
        let res = await fetch(url);
        let text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response from select_sale.php:', text);
            console.error('Server response error. Check console for details.');
            return;
        }
        
        if (!data.success || !data.data) {
            $('#saleTable').html(`<tr><td colspan="18" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
            return;
        }
        
        // Check for duplicate invoice numbers
        const invoiceCounts = {};
        data.data.forEach(row => {
            if (row.invoice_number) {
                invoiceCounts[row.invoice_number] = (invoiceCounts[row.invoice_number] || 0) + 1;
            }
        });
        
        // Prepare data for DataTables
        const tableData = data.data.map((row) => [
            row.customer_name || '-',
            row.recipient || '-',
            row.location || '-',
            row.invoice_number || '-',
            row.formula_name || '-',
            row.order_date || '-',
            row.payment_type || '-',
            'M³ ' + (row.quantity !== null && row.quantity !== undefined && row.quantity !== '' ? formatNumber(row.quantity) : '-'),
            row.price_per_unit !== null && row.price_per_unit !== undefined && row.price_per_unit !== '' ? formatUSD(row.price_per_unit) : '-',
            row.total_price !== null && row.total_price !== undefined && row.total_price !== '' ? formatUSD(row.total_price) : '-',
            row.amount_paid_iq !== null && row.amount_paid_iq !== undefined && row.amount_paid_iq !== '' ? formatIQD(row.amount_paid_iq) : '-',
            row.amount_paid_usd !== null && row.amount_paid_usd !== undefined && row.amount_paid_usd !== '' ? formatUSD(row.amount_paid_usd) : '-',
            row.remaining_amount !== null && row.remaining_amount !== undefined && row.remaining_amount !== '' ? formatUSD(row.remaining_amount) : '-',
            row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
            row.notes || '-',
            row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
            `${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-warning btn-sm edit-sale' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''} ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-danger btn-sm delete-sale' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}`,
            (row.invoice_number && invoiceCounts[row.invoice_number] > 1) ? 'duplicate-invoice-row' : '' // store row class
        ]);
        
        // Initialize DataTable
        saleTable = new DataTable('#saleTable', {
            data: tableData,
            columns: [
                { title: 'کڕیار' },
                { title: 'وەرگر' },
                { title: 'شوێن' },
                { title: 'ژمارەی پسوڵە' },
                { title: 'فۆرمۆلا' },
                { title: 'بەروار' },
                { title: 'جۆری پارەدان' },
                { title: 'بڕ' },
                { title: 'نرخی یەکە' },
                { title: 'کۆی نرخ' },
                { title: 'پارەی دراو بە دینار' },
                { title: 'پارەی دراو بە دۆلار' },
                { title: 'پارەی ماوە' },
                { title: 'نرخی ١٠٠ دۆلار' },
                { title: 'تێبینی' },
                { title: 'داشکاندن' },
                { title: 'کردارەکان' }
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
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[5, 'desc']], // Sort by date descending
            orderMulti: true, // Enable multi-column sorting
            dom: 'Bfrtip', // Buttons, filter, table, info, pagination
            buttons: [
                {
                    extend: 'copy',
                    text: 'لەبەرگرتنەوە',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-sm btn-outline-success'
                },
                {
                    extend: 'print',
                    text: 'پرینت',
                    className: 'btn btn-sm btn-outline-primary'
                }
            ],
            rowCallback: function(row, data) {
                // Apply duplicate row class
                const rowClass = data[18]; // Last column contains the row class
                if (rowClass === 'duplicate-invoice-row') {
                    $(row).addClass('duplicate-invoice-row');
                }
            },
            initComplete: function() {
                // Add individual column search inputs
                this.api().columns().every(function() {
                    const column = this;
                    const header = $(column.header());
                    
                    // Skip adding search to actions column
                    if (header.text().includes('کردارەکان')) {
                        return;
                    }
                    
                    // Create search input
                    const searchInput = $('<input>')
                        .attr('type', 'text')
                        .attr('placeholder', 'فلتەر...')
                        .addClass('form-control form-control-sm mt-1 column-filter')
                        .css({
                            'width': '100%',
                            'padding': '0.25rem 0.5rem',
                            'border': '1px solid #ced4da',
                            'border-radius': '0.25rem'
                        });
                    
                    // Add search input to header
                    header.append(searchInput);
                    
                    // Apply search on keyup (Excel-like contains filter)
                    searchInput.on('keyup change', function() {
                        column.search(this.value).draw();
                    });
                });
            }
        });
    } catch (error) {
        console.error('Error loading sales:', error);
        $('#saleTable').html(`<tr><td colspan="18" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadSalesTable();
});

// Make it globally accessible for reload
window.reloadSales = function(filterParams) {
    loadSalesTable(filterParams);
};

// Filter event listeners
const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', function() {
        const from = fromInput.value;
        const to = toInput.value;
        const params = [];
        if (from) params.push('from=' + encodeURIComponent(from));
        if (to) params.push('to=' + encodeURIComponent(to));
        loadSalesTable(params.join('&'));
    });
    
    toInput.addEventListener('input', function() {
        const from = fromInput.value;
        const to = toInput.value;
        const params = [];
        if (from) params.push('from=' + encodeURIComponent(from));
        if (to) params.push('to=' + encodeURIComponent(to));
        loadSalesTable(params.join('&'));
    });
}

const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        loadSalesTable();
    });
}
