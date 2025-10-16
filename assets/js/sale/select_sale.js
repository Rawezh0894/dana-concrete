async function loadSalesTable(page = 1, limit = 50) {
    // Show loading state
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">چاوەڕوان...</span>
                </div>
                <p class="mt-2">چاوەڕوان... ڕیکۆردەکان لۆد دەکرێن</p>
            </div>
        `;
    }
    
    // Get current month and year
    const now = new Date();
    const currentMonth = now.getMonth() + 1; // JavaScript months are 0-indexed
    const currentYear = now.getFullYear();
    
    // Set default filter to current month
    const fromDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
    const toDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate()}`;
    
    // Update filter inputs if they exist
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    if (fromInput && !fromInput.value) fromInput.value = fromDate;
    if (toInput && !toInput.value) toInput.value = toDate;
    
    // Build URL with pagination parameters
    const params = new URLSearchParams({
        page: page,
        limit: limit,
        from: fromInput ? fromInput.value : '',
        to: toInput ? toInput.value : '',
        customer_id: document.getElementById('filter_customer') ? document.getElementById('filter_customer').value : ''
    });
    
    try {
        let res = await fetch(`../process/sale/select_sale.php?${params.toString()}`);
        let text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response from select_sale.php:', text);
            console.error('Server response error. Check console for details.');
            return;
        }
        if (!data.success) {
            TableController.renderWithPagination('#saleTable', [], columns, { pageSize: 10 });
            return;
        }
    
    // Check for duplicate invoice numbers
    const invoiceCounts = {};
    data.data.forEach(row => {
        if (row.invoice_number) {
            invoiceCounts[row.invoice_number] = (invoiceCounts[row.invoice_number] || 0) + 1;
        }
    });
    
    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'notes', 'discount', 'actions'
    ];
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
    const mapped = data.data.map((row, idx) => ({
        '#': idx + 1,
        customer_name: row.customer_name || '-',
        recipient: row.recipient || '-',
        location: row.location || '-',
        invoice_number: row.invoice_number || '-',
        formula_name: row.formula_name || '-',
        order_date: row.order_date || '-',
        payment_type: row.payment_type || '-',
        quantity: 'M³' + (row.quantity !== null && row.quantity !== undefined && row.quantity !== '' ? formatNumber(row.quantity) : '-'),
        price_per_unit: row.price_per_unit !== null && row.price_per_unit !== undefined && row.price_per_unit !== '' ? formatUSD(row.price_per_unit) : '-',
        total_price: row.total_price !== null && row.total_price !== undefined && row.total_price !== '' ? formatUSD(row.total_price) : '-',
        amount_paid_iq: row.amount_paid_iq !== null && row.amount_paid_iq !== undefined && row.amount_paid_iq !== '' ? formatIQD(row.amount_paid_iq) : '-',
        amount_paid_usd: row.amount_paid_usd !== null && row.amount_paid_usd !== undefined && row.amount_paid_usd !== '' ? formatUSD(row.amount_paid_usd) : '-',
        remaining_amount: row.remaining_amount !== null && row.remaining_amount !== undefined && row.remaining_amount !== '' ? formatUSD(row.remaining_amount) : '-',
        dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
        notes: row.notes || '-',
        discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
        actions: `${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-warning btn-sm edit-sale' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''} ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-danger btn-sm delete-sale' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}`,
        // Add row class for duplicate highlighting
        _rowClass: (row.invoice_number && invoiceCounts[row.invoice_number] > 1) ? 'duplicate-invoice-row' : ''
    }));
    
        // Render table with server-side pagination
        TableController.renderWithPagination('#saleTable', mapped, columns, { 
            pageSize: limit,
            currentPage: page,
            totalPages: data.pagination.total_pages,
            totalRecords: data.pagination.total_records,
            rowClass: (row) => row._rowClass || '',
            onPageChange: (newPage) => {
                loadSalesTable(newPage, limit);
            }
        });
    } catch (error) {
        console.error('Error loading sales:', error);
        // Show error message
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="mb-0">هەڵەیەک لە لۆدکردنی ڕیکۆردەکان هەیە. تکایە دواتر هەوڵ بدەوە.</p>
                </div>
            `;
        }
    }
}

// Global function for reloading sales
window.reloadSales = () => loadSalesTable(1, 50);

// Load sales on page load
document.addEventListener('DOMContentLoaded', () => loadSalesTable(1, 50));



async function loadSalesFiltered() {
    // Reset to first page when filtering
    loadSalesTable(1, 50);
}

// Legacy function for backward compatibility
async function loadSales(filterParams = '') {
    const params = new URLSearchParams(filterParams);
    const page = parseInt(params.get('page')) || 1;
    const limit = parseInt(params.get('limit')) || 50;
    loadSalesTable(page, limit);
}

const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', loadSalesFiltered);
    toInput.addEventListener('input', loadSalesFiltered);
}
const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        loadSalesFiltered();
    });
}

