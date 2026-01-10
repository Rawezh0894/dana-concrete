// AG Grid configuration for concrete receipts
let concreteReceiptsGridApi = null;

// Column definitions - ڕیزبەندی پێچەوانە بۆ RTL (جگە لە ستوونی کردارەکان)
const concreteReceiptsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        width: 150,
        maxWidth: 200,
        pinned: 'left',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            let buttons = '';
            if (window.userPermissions && window.userPermissions.canEdit) {
                buttons += `<button class='btn btn-warning btn-sm edit-receipt' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`;
            }
            if (window.userPermissions && window.userPermissions.canDelete) {
                buttons += `<button class='btn btn-danger btn-sm delete-receipt' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`;
            }
            if (window.userPermissions && window.userPermissions.canPrint) {
                buttons += `<button class='btn btn-info btn-sm print-receipt' data-id='${params.data.id}' title='پرێنت' style='margin: 2px;'><i class='fa fa-print'></i></button>`;
            }
            return buttons || '-';
        }
    },
    {
        field: 'mixer_driver_name',
        headerName: 'شۆفێری میکسەر',
        flex: 1,
        minWidth: 110
    },
    {
        field: 'mixer_car_name',
        headerName: 'میکسەر',
        flex: 1,
        minWidth: 80
    },
    {
        field: 'pump_driver_name',
        headerName: 'شۆفێری پەمپ',
        flex: 1,
        minWidth: 110
    },
    {
        field: 'pump_car_name',
        headerName: 'پەمپ',
        flex: 1,
        minWidth: 80
    },
    {
        field: 'formula_name',
        headerName: 'فۆرمۆلا',
        flex: 1,
        minWidth: 100
    },
    {
        field: 'meter_amount',
        headerName: 'بڕی مەتر',
        flex: 1,
        minWidth: 100,
        cellStyle: { textAlign: 'left', direction: 'ltr' },
        cellRenderer: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters.formatNumber(params.value) + ' m³';
        }
    },
    {
        field: 'created_at',
        headerName: 'بەروار',
        flex: 1,
        minWidth: 140,
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const d = new Date(params.value);
            if (isNaN(d)) return params.value;
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day} ${hours}:${minutes}`;
        }
    },
    {
        field: 'receiver_name',
        headerName: 'وەرگر',
        flex: 1,
        minWidth: 100
    },
    {
        field: 'location',
        headerName: 'شوێن',
        flex: 1,
        minWidth: 100
    },
    {
        field: 'customer_name',
        headerName: 'کڕیار',
        flex: 1,
        minWidth: 120
    },
    {
        field: 'receipt_number',
        headerName: 'ژم.پسووڵە',
        flex: 1,
        minWidth: 100,
        cellRenderer: function(params) {
            if (!params.data) return '-';
            const warning = params.data.is_duplicate 
                ? '<i class="fas fa-exclamation-triangle duplicate-warning" title="ژمارەی پسوڵە دووبارەیە"></i>' 
                : '';
            return warning + (params.value || '-');
        }
    },
    {
        field: '#',
        headerName: '#',
        width: 70,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        valueGetter: function(params) {
            if (!params.node) return '';
            // Calculate row number based on server pagination
            const startRow = window.currentConcreteReceiptsStartRow || 0;
            return startRow + params.node.rowIndex + 1;
        },
        sortable: false,
        filter: false
    }
];

// Grid options - disable AG Grid's built-in pagination (using server-side)
const concreteReceiptsGridOptions = {
    columnDefs: concreteReceiptsColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: false, // Disable client-side filtering (using server-side)
        resizable: true,
        flex: 1
    },
    pagination: false, // Disable AG Grid pagination - using server-side
    rowSelection: 'single',
    animateRows: true,
    suppressPaginationPanel: true, // Hide AG Grid's pagination panel
    localeText: {
        page: 'پەڕە',
        of: 'لە',
        to: 'بۆ',
        next: 'دواتر',
        previous: 'پێشوو',
        loadingOoo: 'چاوەڕوان بە...',
        noRowsToShow: 'هیچ داتایەک نییە',
        filterOoo: 'فلتەر...',
        equals: 'یەکسانە',
        notEqual: 'یەکسان نییە',
        lessThan: 'کەمتر لە',
        greaterThan: 'زیاتر لە',
        lessThanOrEqual: 'کەمتر یان یەکسان',
        greaterThanOrEqual: 'زیاتر یان یەکسان',
        inRange: 'لە نێوان',
        contains: 'لەخۆ دەگرێت',
        notContains: 'لەخۆ ناگرێت',
        startsWith: 'دەست پێ دەکات بە',
        endsWith: 'کۆتایی دێت بە',
        blank: 'بەتاڵ',
        notBlank: 'بەتاڵ نییە',
        andCondition: 'و',
        orCondition: 'یان',
        applyFilter: 'جێبەجێکردن',
        resetFilter: 'ڕیسێت',
        clearFilter: 'پاککردنەوە',
        columns: 'ستونەکان',
        filters: 'فلتەرەکان',
        pinColumn: 'چەسپاندنی ستون',
        valueAggregation: 'کۆکردنەوەی نرخ',
        autosizeThiscolumn: 'قەبارەی ئۆتۆماتیکی ئەم ستونە',
        autosizeAllColumns: 'قەبارەی ئۆتۆماتیکی هەموو ستونەکان',
        groupBy: 'گروپ بکە بەپێی',
        ungroupBy: 'گروپ لابدە لە',
        resetColumns: 'ڕیسێتی ستونەکان',
        expandAll: 'کردنەوەی هەموو',
        collapseAll: 'داخستنی هەموو',
        copy: 'کۆپی',
        ctrlC: 'Ctrl+C',
        paste: 'پەیست',
        ctrlV: 'Ctrl+V',
        export: 'ئیکسپۆرت',
        csvExport: 'ئیکسپۆرتی CSV',
        excelExport: 'ئیکسپۆرتی Excel'
    },
    onGridReady: function(params) {
        concreteReceiptsGridApi = params.api;
        loadConcreteReceiptsData(1, 25);
    },
    onFirstDataRendered: function(params) {
        const allColumnIds = params.api.getColumns()?.map(col => col.getColId()) || [];
        const columnsToAutoSize = allColumnIds.filter(colId => colId !== 'actions' && colId !== '#');
        if (columnsToAutoSize.length > 0) {
            params.api.autoSizeColumns(columnsToAutoSize);
        }
    }
};

// Merge with defaults from base file
Object.assign(concreteReceiptsGridOptions, window.AGGridDefaults || {});

// Store current pagination state
let currentConcreteReceiptsPage = 1;
let currentConcreteReceiptsPageSize = 25;
window.currentConcreteReceiptsStartRow = 0;

// Load Concrete Receipts Data with server-side pagination
function loadConcreteReceiptsData(page = 1, pageSize = 25, restoreRowId = null) {
    // Store current pagination
    currentConcreteReceiptsPage = page;
    currentConcreteReceiptsPageSize = pageSize;
    window.currentConcreteReceiptsStartRow = (page - 1) * pageSize;
    
    // Show loading
    if (concreteReceiptsGridApi) {
        concreteReceiptsGridApi.showLoadingOverlay();
    }
    
    // Build URL with filters from form
    const filters = {
        search: $('#filter_search').val(),
        customer_id: $('#filter_customer_id').val(),
        location: $('#filter_location').val(),
        formulas_id: $('#filter_formulas_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    };
    
    const queryParams = new URLSearchParams();
    queryParams.append('page', page);
    queryParams.append('pageSize', pageSize);
    
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            queryParams.append(key, filters[key]);
        }
    });
    
    const url = '../process/concrete_receipts/select_concrete_receipts.php?' + queryParams.toString();
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                concreteReceiptsGridApi.setGridOption('rowData', []);
                concreteReceiptsGridApi.showNoRowsOverlay();
                updateSummaryCards({ total_receipts: 0, total_meter: 0, total_customers: 0 });
                return;
            }
            
            let receipts = data.data || [];
            let summary = data.summary || {};
            let pagination = data.pagination || {};
            
            // Store data globally
            window.concreteReceiptsData = receipts;
            
            // Update summary cards
            updateSummaryCards(summary);
            
            // Set row data
            concreteReceiptsGridApi.setGridOption('rowData', receipts);
            concreteReceiptsGridApi.hideOverlay();
            
            // Render server-side pagination controls
            renderServerPagination(pagination, pageSize);
            
            // Restore selected row if provided
            if (restoreRowId) {
                setTimeout(() => {
                    let foundNode = null;
                    concreteReceiptsGridApi.forEachNode((node) => {
                        if (node.data && String(node.data.id) === String(restoreRowId)) {
                            foundNode = node;
                        }
                    });
                    
                    if (foundNode) {
                        foundNode.setSelected(true);
                        concreteReceiptsGridApi.ensureNodeVisible(foundNode, 'middle');
                    }
                }, 200);
            }
        })
        .catch(error => {
            console.error('Error loading concrete receipts:', error);
            if (concreteReceiptsGridApi) {
                concreteReceiptsGridApi.setGridOption('rowData', []);
                concreteReceiptsGridApi.showNoRowsOverlay();
            }
        });
}

// Render server-side pagination controls
function renderServerPagination(pagination, pageSize) {
    const gridContainer = document.querySelector('#concreteReceiptsGrid');
    if (!gridContainer) return;
    
    // Remove existing pagination
    const existingPagination = gridContainer.parentElement.querySelector('.server-pagination');
    if (existingPagination) {
        existingPagination.remove();
    }
    
    const totalPages = pagination.totalPages || 1;
    const currentPage = pagination.page || 1;
    const total = pagination.total || 0;
    
    // Create pagination container
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'server-pagination d-flex justify-content-between align-items-center mt-2 p-2';
    paginationDiv.style.background = '#f8f9fa';
    paginationDiv.style.borderRadius = '5px';
    
    // Page size selector
    const sizeSelect = document.createElement('select');
    sizeSelect.className = 'form-select form-select-sm';
    sizeSelect.style.width = 'auto';
    [10, 25, 50, 100].forEach(size => {
        const opt = document.createElement('option');
        opt.value = size;
        opt.textContent = size + ' / پەڕ';
        if (size === pageSize) opt.selected = true;
        sizeSelect.appendChild(opt);
    });
    sizeSelect.onchange = function() {
        loadConcreteReceiptsData(1, parseInt(this.value));
    };
    
    // Info text
    const infoSpan = document.createElement('span');
    infoSpan.className = 'mx-2';
    const startRow = (currentPage - 1) * pageSize + 1;
    const endRow = Math.min(currentPage * pageSize, total);
    infoSpan.textContent = `پیشاندانی ${startRow} - ${endRow} لە ${total}`;
    
    // Pagination buttons container
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'd-flex gap-1';
    
    // First page button
    const firstBtn = document.createElement('button');
    firstBtn.className = 'btn btn-sm btn-outline-secondary';
    firstBtn.innerHTML = '<i class="fas fa-angle-double-right"></i>';
    firstBtn.disabled = currentPage === 1;
    firstBtn.onclick = () => loadConcreteReceiptsData(1, pageSize);
    buttonsDiv.appendChild(firstBtn);
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'btn btn-sm btn-outline-secondary';
    prevBtn.innerHTML = '<i class="fas fa-angle-right"></i>';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => loadConcreteReceiptsData(currentPage - 1, pageSize);
    buttonsDiv.appendChild(prevBtn);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        const dots = document.createElement('span');
        dots.textContent = '...';
        dots.className = 'mx-1';
        buttonsDiv.appendChild(dots);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'btn btn-sm ' + (i === currentPage ? 'btn-success' : 'btn-outline-secondary');
        pageBtn.textContent = i;
        pageBtn.onclick = () => loadConcreteReceiptsData(i, pageSize);
        buttonsDiv.appendChild(pageBtn);
    }
    
    if (endPage < totalPages) {
        const dots = document.createElement('span');
        dots.textContent = '...';
        dots.className = 'mx-1';
        buttonsDiv.appendChild(dots);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn btn-sm btn-outline-secondary';
    nextBtn.innerHTML = '<i class="fas fa-angle-left"></i>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => loadConcreteReceiptsData(currentPage + 1, pageSize);
    buttonsDiv.appendChild(nextBtn);
    
    // Last page button
    const lastBtn = document.createElement('button');
    lastBtn.className = 'btn btn-sm btn-outline-secondary';
    lastBtn.innerHTML = '<i class="fas fa-angle-double-left"></i>';
    lastBtn.disabled = currentPage === totalPages;
    lastBtn.onclick = () => loadConcreteReceiptsData(totalPages, pageSize);
    buttonsDiv.appendChild(lastBtn);
    
    // Assemble pagination
    paginationDiv.appendChild(sizeSelect);
    paginationDiv.appendChild(infoSpan);
    paginationDiv.appendChild(buttonsDiv);
    
    gridContainer.parentElement.appendChild(paginationDiv);
}

// Update Summary Cards
function updateSummaryCards(summary) {
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '0';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    const totalReceipts = summary && summary.total_receipts !== undefined ? summary.total_receipts : 0;
    $('#summary_total_receipts').text(totalReceipts);
    
    const totalMeter = summary && summary.total_meter !== undefined ? summary.total_meter : 0;
    $('#summary_total_meter').text(formatNumber(totalMeter) + ' m³');
    
    const totalCustomers = summary && summary.total_customers !== undefined ? summary.total_customers : 0;
    $('#summary_total_customers').text(totalCustomers);
}

// Reload function - preserve pagination and restore row
window.reloadConcreteReceipts = function(restoreRowId = null) {
    loadConcreteReceiptsData(currentConcreteReceiptsPage, currentConcreteReceiptsPageSize, restoreRowId);
};

// Function to reload only the summary cards
window.reloadConcreteReceiptsSummary = function() {
    loadConcreteReceiptsData(currentConcreteReceiptsPage, currentConcreteReceiptsPageSize);
};

// Function to reload from first page (for filters)
window.reloadConcreteReceiptsFromStart = function() {
    loadConcreteReceiptsData(1, currentConcreteReceiptsPageSize);
};

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#concreteReceiptsGrid');
    if (gridDiv) {
        concreteReceiptsGridApi = agGrid.createGrid(gridDiv, concreteReceiptsGridOptions);
    }
});
