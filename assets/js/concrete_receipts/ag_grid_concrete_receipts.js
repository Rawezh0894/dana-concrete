// AG Grid configuration for concrete receipts
let concreteReceiptsGridApi = null;

// Column definitions - reversed for RTL display
const concreteReceiptsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        width: 150,
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
        minWidth: 100
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
        minWidth: 100
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
        minWidth: 90
    },
    {
        field: 'meter_amount',
        headerName: 'بڕی مەتر سێجا',
        flex: 1,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters.formatNumber(params.value) + ' m³';
        }
    },
    {
        field: 'created_at',
        headerName: 'بەروار',
        flex: 1,
        minWidth: 130,
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
        minWidth: 90
    },
    {
        field: 'location',
        headerName: 'شوێن',
        flex: 1,
        minWidth: 90
    },
    {
        field: 'customer_name',
        headerName: 'کڕیار',
        flex: 1,
        minWidth: 110
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
        pinned: 'right',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        valueGetter: function(params) {
            if (!params.node) return '';
            const page = concreteReceiptsGridApi ? concreteReceiptsGridApi.paginationGetCurrentPage() : 0;
            const pageSize = concreteReceiptsGridApi ? concreteReceiptsGridApi.paginationGetPageSize() : 25;
            return (page * pageSize) + params.node.rowIndex + 1;
        },
        sortable: false,
        filter: false
    }
];

// Grid options
const concreteReceiptsGridOptions = {
    columnDefs: concreteReceiptsColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        flex: 1
    },
    pagination: true,
    paginationPageSize: 25,
    paginationPageSizeSelector: [10, 25, 50, 100],
    rowSelection: 'single',
    animateRows: true,
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
        loadConcreteReceiptsData();
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

// Store current row ID for restoration after update
let currentEditingRowId = null;

// Load Concrete Receipts Data
function loadConcreteReceiptsData(preservePagination = false, restoreRowId = null) {
    // Save current pagination state and filters
    let currentPage = 0;
    let pageSize = 25;
    let savedFilters = null;
    if (preservePagination && concreteReceiptsGridApi) {
        currentPage = concreteReceiptsGridApi.paginationGetCurrentPage() || 0;
        pageSize = concreteReceiptsGridApi.paginationGetPageSize() || 25;
        try {
            savedFilters = concreteReceiptsGridApi.getFilterModel();
        } catch (e) {
            console.warn('Could not get filter model:', e);
        }
    }
    
    // Show loading
    concreteReceiptsGridApi?.showLoadingOverlay();
    
    // Build URL with filters from form
    let url = '../process/concrete_receipts/select_concrete_receipts.php?ag_grid=1';
    const filters = {
        customer_id: $('#filter_customer_id').val(),
        location: $('#filter_location').val(),
        formulas_id: $('#filter_formulas_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    };
    
    const queryParams = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            queryParams.append(key, filters[key]);
        }
    });
    
    if (queryParams.toString()) {
        url += '&' + queryParams.toString();
    }
    
    // Single fetch with summary included
    fetch(url)
        .then(r => r.json())
        .catch(() => ({ success: false, data: [] }))
        .then(data => {
            let receipts = [];
            if (Array.isArray(data)) {
                receipts = data;
            } else if (data.success && Array.isArray(data.data)) {
                receipts = data.data;
            }
            
            if (receipts && receipts.length > 0) {
                // Store data globally
                window.concreteReceiptsData = receipts;
                
                // Set row data
                concreteReceiptsGridApi.setGridOption('rowData', receipts);
                concreteReceiptsGridApi.hideOverlay();
                
                // Restore pagination state and filters if preserving
                if (preservePagination && concreteReceiptsGridApi) {
                    setTimeout(() => {
                        // Restore filters first
                        if (savedFilters && Object.keys(savedFilters).length > 0) {
                            try {
                                concreteReceiptsGridApi.setFilterModel(savedFilters);
                            } catch (e) {
                                console.warn('Could not restore filter model:', e);
                            }
                        }
                        // Restore pagination
                        concreteReceiptsGridApi.paginationGoToPage(currentPage);
                        concreteReceiptsGridApi.paginationSetPageSize(pageSize);
                        
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
                                    const rowIndex = foundNode.rowIndex;
                                    const currentPageSize = concreteReceiptsGridApi.paginationGetPageSize();
                                    const targetPage = Math.floor(rowIndex / currentPageSize);
                                    
                                    concreteReceiptsGridApi.paginationGoToPage(targetPage);
                                    
                                    setTimeout(() => {
                                        foundNode.setSelected(true);
                                        concreteReceiptsGridApi.ensureNodeVisible(foundNode, 'middle');
                                    }, 150);
                                }
                            }, 200);
                        }
                    }, 100);
                }
            } else {
                concreteReceiptsGridApi.setGridOption('rowData', []);
                concreteReceiptsGridApi.showNoRowsOverlay();
            }
            
            // Fetch summary separately (faster, smaller request)
            fetchAndUpdateSummary(filters);
        })
        .catch(error => {
            console.error('Error loading concrete receipts:', error);
            concreteReceiptsGridApi.setGridOption('rowData', []);
            concreteReceiptsGridApi.showNoRowsOverlay();
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        });
}

// Fetch and update summary cards
function fetchAndUpdateSummary(filters) {
    let summaryUrl = '../process/concrete_receipts/select_concrete_receipts.php?summary_only=1';
    const summaryParams = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            summaryParams.append(key, filters[key]);
        }
    });
    if (summaryParams.toString()) {
        summaryUrl += '&' + summaryParams.toString();
    }
    
    fetch(summaryUrl)
        .then(r => r.json())
        .then(summaryData => {
            if (summaryData && summaryData.success && summaryData.summary) {
                updateSummaryCards(summaryData.summary);
            }
        })
        .catch(err => console.warn('Could not fetch summary:', err));
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
    loadConcreteReceiptsData(true, restoreRowId);
};

// Function to reload only the summary cards
window.reloadConcreteReceiptsSummary = function() {
    loadConcreteReceiptsData(true);
};

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#concreteReceiptsGrid');
    if (gridDiv) {
        concreteReceiptsGridApi = agGrid.createGrid(gridDiv, concreteReceiptsGridOptions);
    }
});
