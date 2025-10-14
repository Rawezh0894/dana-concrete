let currentSalePage = 1;
let currentFilterParams = '';
let currentSearchTerm = '';
let saleSearchTimeout = null;
let originalSaleData = [];
let activeColumnFilters = {};

async function loadSales(filterParams = '', page = 1, searchTerm = '') {
    currentSalePage = page;
    currentFilterParams = filterParams;
    currentSearchTerm = searchTerm;
    
    // Build URL with filters and pagination
    let url = '../process/sale/select_sale.php';
    const params = new URLSearchParams(filterParams);
    params.set('page', page);
    params.set('limit', 10);
    if (searchTerm) {
        params.set('search', searchTerm);
    }
    // Add column filters
    if (Object.keys(activeColumnFilters).length > 0) {
        params.set('column_filters', JSON.stringify(activeColumnFilters));
    }
    url += '?' + params.toString();
    
    let res = await fetch(url);
    let text = await res.text();
    let result;
    try {
        result = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_sale.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
    }
    
    // Handle both old and new response formats
    let data;
    if (result.success && Array.isArray(result.data)) {
        data = result.data;
    } else if (Array.isArray(result)) {
        data = result;
        result = { data: result }; // Wrap for consistency
    } else {
        console.error('Unexpected response format:', result);
        data = [];
    }
    
    // Check for duplicate invoice numbers
    const invoiceCounts = {};
    data.forEach(row => {
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
    
    const mapped = data.map((row, idx) => ({
        '#': (page - 1) * 10 + idx + 1,
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
    
    // Store original data for filter dropdowns
    originalSaleData = mapped;
    
    // Render table without client-side pagination
    TableController.render('#saleTable', mapped, columns, {
        rowClass: (row) => row._rowClass || ''
    });
    
    // Add Excel-style column filters
    addExcelStyleFilters('#saleTable', mapped, columns);
    
    // Render server-side pagination if available
    if (result.pagination) {
        renderSalePagination(result.pagination, data.length);
    }
}

// Render server-side pagination controls
function renderSalePagination(pagination, currentPageItemCount) {
    const table = document.querySelector('#saleTable');
    if (!table) return;
    
    // Remove existing pagination if any
    let existingPagination = table.parentElement.querySelector('.server-pagination');
    if (existingPagination) {
        existingPagination.remove();
    }
    
    // Create pagination container
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'server-pagination d-flex justify-content-between align-items-center mt-3';
    paginationDiv.style.cssText = 'padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;';
    
    // Info section
    const infoDiv = document.createElement('div');
    infoDiv.className = 'pagination-info';
    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(start + currentPageItemCount - 1, pagination.total_records);
    infoDiv.innerHTML = `
        <span style="font-weight: 600; color: #495057;">
            <i class="fas fa-info-circle me-2"></i>
            نیشاندانی ${start} بۆ ${end} لە ${pagination.total_records} ڕیز
        </span>
    `;
    
    // Controls section
    const controlsDiv = document.createElement('div');
    controlsDiv.className = 'd-flex gap-2 align-items-center';
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'btn btn-sm';
    prevBtn.style.cssText = 'background: var(--seafoam-green); color: white; font-weight: bold;';
    prevBtn.innerHTML = '<i class="fas fa-chevron-right me-1"></i>پێشوو';
    prevBtn.disabled = !pagination.has_prev;
    prevBtn.onclick = () => loadSales(currentFilterParams, pagination.current_page - 1, currentSearchTerm);
    if (prevBtn.disabled) {
        prevBtn.style.opacity = '0.5';
        prevBtn.style.cursor = 'not-allowed';
    }
    
    // Page info
    const pageInfo = document.createElement('span');
    pageInfo.style.cssText = 'font-weight: 600; color: #495057; padding: 0 1rem;';
    pageInfo.textContent = `لاپەڕەی ${pagination.current_page} لە ${pagination.total_pages}`;
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn btn-sm';
    nextBtn.style.cssText = 'background: var(--seafoam-green); color: white; font-weight: bold;';
    nextBtn.innerHTML = 'دواتر<i class="fas fa-chevron-left ms-1"></i>';
    nextBtn.disabled = !pagination.has_next;
    nextBtn.onclick = () => loadSales(currentFilterParams, pagination.current_page + 1, currentSearchTerm);
    if (nextBtn.disabled) {
        nextBtn.style.opacity = '0.5';
        nextBtn.style.cursor = 'not-allowed';
    }
    
    controlsDiv.appendChild(prevBtn);
    controlsDiv.appendChild(pageInfo);
    controlsDiv.appendChild(nextBtn);
    
    paginationDiv.appendChild(infoDiv);
    paginationDiv.appendChild(controlsDiv);
    
    // Insert after table's parent container
    table.parentElement.appendChild(paginationDiv);
}

// Add Excel-style column filters
function addExcelStyleFilters(tableSelector, data, columns) {
    const table = document.querySelector(tableSelector);
    if (!table) return;
    
    const thead = table.querySelector('thead tr');
    if (!thead) return;
    
    // Get unique values for each filterable column
    const filterableColumns = ['customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date', 'payment_type'];
    
    filterableColumns.forEach(columnName => {
        const columnIndex = columns.indexOf(columnName);
        if (columnIndex === -1) return;
        
        const th = thead.children[columnIndex];
        if (!th) return;
        
        // Get unique values for this column
        const uniqueValues = [...new Set(data.map(row => row[columnName]).filter(v => v && v !== '-'))].sort();
        
        if (uniqueValues.length === 0) return;
        
        // Add filter icon
        const filterIcon = document.createElement('i');
        filterIcon.className = 'fas fa-filter ms-2';
        filterIcon.style.cssText = 'cursor: pointer; font-size: 0.8rem; color: #6c757d;';
        filterIcon.title = 'فلتەر';
        
        // Create dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'filter-dropdown';
        dropdown.style.cssText = 'position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 0.5rem; z-index: 1000; max-height: 300px; overflow-y: auto; display: none; min-width: 200px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        
        // Add "Select All" option
        const selectAllDiv = document.createElement('div');
        selectAllDiv.innerHTML = `
            <label style="display: flex; align-items: center; padding: 0.25rem; cursor: pointer; font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 0.5rem;">
                <input type="checkbox" class="me-2 filter-select-all" checked> هەموو
            </label>
        `;
        dropdown.appendChild(selectAllDiv);
        
        // Add filter options
        uniqueValues.forEach(value => {
            const optionDiv = document.createElement('div');
            optionDiv.innerHTML = `
                <label style="display: flex; align-items: center; padding: 0.25rem; cursor: pointer; white-space: nowrap;">
                    <input type="checkbox" class="me-2 filter-option" value="${value}" checked> ${value}
                </label>
            `;
            dropdown.appendChild(optionDiv);
        });
        
        // Add apply button
        const applyBtn = document.createElement('button');
        applyBtn.className = 'btn btn-sm w-100 mt-2';
        applyBtn.style.cssText = 'background: var(--seafoam-green); color: white; font-weight: bold;';
        applyBtn.textContent = 'جێبەجێکردن';
        applyBtn.onclick = () => {
            const selectedValues = Array.from(dropdown.querySelectorAll('.filter-option:checked')).map(cb => cb.value);
            if (selectedValues.length > 0 && selectedValues.length < uniqueValues.length) {
                activeColumnFilters[columnName] = selectedValues;
                filterIcon.style.color = 'var(--seafoam-green)';
            } else {
                delete activeColumnFilters[columnName];
                filterIcon.style.color = '#6c757d';
            }
            dropdown.style.display = 'none';
            loadSales(currentFilterParams, 1, currentSearchTerm);
        };
        dropdown.appendChild(applyBtn);
        
        // Handle select all
        const selectAllCheckbox = dropdown.querySelector('.filter-select-all');
        selectAllCheckbox.addEventListener('change', function() {
            dropdown.querySelectorAll('.filter-option').forEach(cb => {
                cb.checked = this.checked;
            });
        });
        
        // Handle individual checkboxes
        dropdown.querySelectorAll('.filter-option').forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(dropdown.querySelectorAll('.filter-option')).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            });
        });
        
        // Toggle dropdown
        filterIcon.onclick = (e) => {
            e.stopPropagation();
            // Close other dropdowns
            document.querySelectorAll('.filter-dropdown').forEach(d => {
                if (d !== dropdown) d.style.display = 'none';
            });
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            
            // Position dropdown
            const rect = th.getBoundingClientRect();
            dropdown.style.top = rect.bottom + 'px';
            dropdown.style.left = rect.left + 'px';
        };
        
        th.style.position = 'relative';
        th.appendChild(filterIcon);
        document.body.appendChild(dropdown);
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.filter-dropdown').forEach(d => d.style.display = 'none');
    });
}

// Clear all column filters
function clearAllColumnFilters() {
    activeColumnFilters = {};
    document.querySelectorAll('.fas.fa-filter').forEach(icon => {
        icon.style.color = '#6c757d';
    });
    document.querySelectorAll('.filter-dropdown .filter-option, .filter-dropdown .filter-select-all').forEach(cb => {
        cb.checked = true;
    });
    loadSales(currentFilterParams, 1, currentSearchTerm);
}

// Make function globally available
window.clearAllColumnFilters = clearAllColumnFilters;

document.addEventListener('DOMContentLoaded', loadSales);
window.reloadSales = loadSales;
