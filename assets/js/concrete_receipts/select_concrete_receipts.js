async function loadConcreteReceiptsTable(page = 1, pageSize = 10) {
    const columns = [
        '#', 'receipt_number', 'customer_name', 'location', 'receiver_name', 'created_at', 'meter_amount',
        'formula_name', 'pump_car_name', 'pump_driver_name', 'mixer_car_name', 'mixer_driver_name', 'actions'
    ];

    // Get current filters
    const filters = {
        customer_id: $('#filter_customer_id').val(),
        location: $('#filter_location').val(),
        formulas_id: $('#filter_formulas_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        page: page,
        pageSize: pageSize
    };

    // Build query string
    const queryParams = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            queryParams.append(key, filters[key]);
        }
    });

    let res = await fetch('../process/concrete_receipts/select_concrete_receipts.php?' + queryParams.toString());
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
        TableController.renderWithPagination('#concreteReceiptsTable', [], columns, { pageSize: pageSize, currentPage: page });
        return;
    }

    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    const mapped = data.data.map((row, idx) => ({
        '#': ((page - 1) * pageSize) + idx + 1,
        receipt_number: (row.is_duplicate ? '<i class="fas fa-exclamation-triangle duplicate-warning" title="ژمارەی پسوڵە دووبارەیە"></i>' : '') + (row.receipt_number || '-'),
        customer_name: row.customer_name || '-',
        location: row.location || '-',
        receiver_name: row.receiver_name || '-', // ✅ نوێ
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
        })(),
        // Add duplicate flag for styling
        is_duplicate: row.is_duplicate || false
    }));

    // Update summary cards
    if (data.summary) {
        $('#summary_total_receipts').text(data.summary.total_receipts || 0);
        $('#summary_total_meter').text(formatNumber(data.summary.total_meter || 0) + ' m³');
        $('#summary_total_customers').text(data.summary.total_customers || 0);
    }

    // Use custom pagination with server-side data
    renderServerSidePagination('#concreteReceiptsTable', mapped, columns, data.pagination, pageSize);
}

// Custom function for server-side pagination
function renderServerSidePagination(tableSelector, data, columns, pagination, pageSize) {
    const table = document.querySelector(tableSelector);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    // Clear existing pagination controls
    const existingPagination = table.parentElement.querySelector('.table-pagination');
    if (existingPagination) {
        existingPagination.remove();
    }

    // Clear existing page size selector
    const existingSizeSelect = table.parentElement.querySelector('.table-page-size');
    if (existingSizeSelect) {
        existingSizeSelect.remove();
    }

    // Render table data
    TableController.render(tableSelector, data, columns, { rowOffset: (pagination.page - 1) * pageSize });

    // Note: Event handlers are now attached using $(document).on() in the individual files,
    // which means they work for dynamically created elements without needing re-attachment

    // Add page size selector
    const sizeSelect = document.createElement('select');
    sizeSelect.className = 'table-page-size';
    sizeSelect.style.float = 'right';
    sizeSelect.style.marginBottom = '8px';
    [5, 10, 20, 50, 100].forEach(size => {
        const opt = document.createElement('option');
        opt.value = size;
        opt.textContent = size + ' / پەڕ';
        if (size === pageSize) opt.selected = true;
        sizeSelect.appendChild(opt);
    });
    sizeSelect.onchange = function() {
        loadConcreteReceiptsTable(1, parseInt(this.value));
    };
    table.parentElement.insertBefore(sizeSelect, table);

    // Add pagination controls
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'table-pagination';
    table.parentElement.appendChild(paginationDiv);

    // Prev button
    const prev = document.createElement('button');
    prev.className = 'btn btn-sm btn-outline-secondary mx-1';
    prev.setAttribute('aria-label', 'پەڕەی پێشوو');
    prev.innerHTML = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 15L8 10L13 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    prev.disabled = pagination.page === 1;
    prev.onclick = () => loadConcreteReceiptsTable(pagination.page - 1, pageSize);
    paginationDiv.appendChild(prev);

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        if (i === 1 || i === pagination.totalPages || Math.abs(i - pagination.page) <= 2) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm' + (i === pagination.page ? ' btn-success active' : ' btn-outline-secondary');
            btn.textContent = i;
            btn.setAttribute('aria-label', 'پەڕەی ' + i);
            if (i === pagination.page) {
                btn.style.transition = 'transform 0.18s';
                btn.style.transform = 'scale(1.08)';
            }
            btn.onclick = () => loadConcreteReceiptsTable(i, pageSize);
            paginationDiv.appendChild(btn);
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            paginationDiv.appendChild(span);
        }
    }

    // Next button
    const next = document.createElement('button');
    next.className = 'btn btn-sm btn-outline-secondary mx-1';
    next.setAttribute('aria-label', 'پەڕەی دواتر');
    next.innerHTML = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 5L12 10L7 15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    next.disabled = pagination.page === pagination.totalPages;
    next.onclick = () => loadConcreteReceiptsTable(pagination.page + 1, pageSize);
    paginationDiv.appendChild(next);
}

document.addEventListener('DOMContentLoaded', () => {
    loadConcreteReceiptsTable(1, 10);
    // Note: Event handlers are now attached using $(document).on() in the individual files,
    // which means they work for dynamically created elements without needing re-attachment
});
window.reloadConcreteReceipts = () => loadConcreteReceiptsTable(1, 10);

// Print receipt event handler is now handled in filter.js
