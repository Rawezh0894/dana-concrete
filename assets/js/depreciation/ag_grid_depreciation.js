// AG Grid Configuration for Depreciation Schedules Table
let gridApi;
let gridColumnApi;

// Format functions
const formatNumber = window.AGGridFormatters?.formatNumber || function(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const formatUSD = window.AGGridFormatters?.formatUSD || function(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(2)) + ' $';
};

// Column Definitions
const columnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        flex: 0,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            let buttons = '';
            
            if (params.data.is_posted == 0 && window.userPermissions && window.userPermissions.canPost) {
                buttons += `<button class='btn btn-success btn-sm post-depreciation' data-id='${params.data.id}' title='پۆستکردن' style='margin: 2px;'><i class='fa fa-check'></i></button>`;
            }
            
            if (params.data.is_posted == 0 && window.userPermissions && window.userPermissions.canCalculate) {
                buttons += `<button class='btn btn-danger btn-sm delete-depreciation' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`;
            }
            
            return buttons || '-';
        }
    },
    {
        field: 'asset_code',
        headerName: 'کۆدی ئامێر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' }
    },
    {
        field: 'asset_name',
        headerName: 'ناوی ئامێر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'period_start',
        headerName: 'بەرواری دەستپێکردن',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' }
    },
    {
        field: 'period_end',
        headerName: 'بەرواری کۆتایی',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' }
    },
    {
        field: 'depreciation_amount',
        headerName: 'بڕی داخوران',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#ffc107', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'accumulated_depreciation',
        headerName: 'کۆی داخوران',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#ff9800' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'book_value',
        headerName: 'نرخی کتێب',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#28a745', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'is_posted',
        headerName: 'دۆخ',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (params.value == 1) {
                return '<span class="badge bg-success">پۆستکراو</span>';
            } else {
                return '<span class="badge bg-warning">نەپۆستکراو</span>';
            }
        }
    },
    {
        field: 'posted_at',
        headerName: 'بەرواری پۆستکردن',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'posted_by_name',
        headerName: 'پۆستکراو لەلایەن',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'notes',
        headerName: 'تێبینی',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const displayText = params.value.length > 40 ? params.value.substring(0, 40) + '...' : params.value;
            return `<span title="${params.value}">${displayText}</span>`;
        },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    }
];

// Grid Options
const gridOptions = {
    columnDefs: columnDefs,
    defaultColDef: {
        resizable: true,
        sortable: true,
        filter: true,
        floatingFilter: true
    },
    rowData: [],
    pagination: true,
    paginationPageSize: 50,
    paginationPageSizeSelector: [25, 50, 100, 200],
    localeText: {
        page: 'لاپەڕە',
        more: 'زیاتر',
        to: 'بۆ',
        of: 'لە',
        next: 'دواتر',
        last: 'کۆتایی',
        first: 'سەرەتا',
        previous: 'پێشوو',
        loadingOoo: 'بارکردن...',
        noRowsToShow: 'هیچ داتایەک نییە',
        filterOoo: 'فلتەر...',
        applyFilter: 'جێبەجێکردنی فلتەر',
        resetFilter: 'پاککردنەوەی فلتەر',
        clearFilter: 'پاککردنەوە',
        searchOoo: 'گەڕان...',
        selectAll: 'هەموو هەڵبژێرە',
        selectAllSearchResults: 'هەموو ئەنجامەکانی گەڕان هەڵبژێرە',
        blanks: 'بەتاڵ',
        equals: 'یەکسانە',
        notEqual: 'یەکسان نییە',
        lessThan: 'کەمتر لە',
        greaterThan: 'زیاتر لە',
        lessThanOrEqual: 'کەمتر یان یەکسان',
        greaterThanOrEqual: 'زیاتر یان یەکسان',
        inRange: 'لە نێوان',
        contains: 'تێدەگرێت',
        notContains: 'تێناگرێت',
        startsWith: 'دەست پێدەکات بە',
        endsWith: 'کۆتایی دێت بە',
        andCondition: 'و',
        orCondition: 'یان'
    },
    suppressRowClickSelection: true,
    rowSelection: 'multiple',
    animateRows: true
};

// Load depreciation schedules
function loadDepreciationSchedules(filterParams = '') {
    if (!gridApi) {
        console.error('Grid API not initialized');
        return;
    }
    
    const params = new URLSearchParams(filterParams);
    
    // Get filter values
    const assetId = $('#filter_asset').val() || '';
    const isPosted = $('#filter_posted').val() || '';
    
    if (assetId) params.set('asset_id', assetId);
    if (isPosted !== '') params.set('is_posted', isPosted);
    
    // Show loading overlay
    gridApi.showLoadingOverlay();
    
    $.ajax({
        url: '../process/depreciation/select_depreciation_schedules.php?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (Array.isArray(data) && data.length >= 0) {
                gridApi.setGridOption('rowData', data);
                gridApi.hideOverlay();
            } else {
                console.error('Invalid data format:', data);
                gridApi.setGridOption('rowData', []);
                gridApi.showNoRowsOverlay();
            }
        },
        error: function(xhr) {
            console.error('Error loading depreciation schedules:', xhr);
            gridApi.setGridOption('rowData', []);
            gridApi.showNoRowsOverlay();
        }
    });
}

// Reload function for external use
window.reloadDepreciation = function() {
    loadDepreciationSchedules();
};

// Initialize grid when DOM is ready
$(document).ready(function() {
    const gridDiv = document.querySelector('#depreciationGrid');
    if (gridDiv) {
        // Create grid - createGrid returns the API directly in v31+
        gridApi = agGrid.createGrid(gridDiv, gridOptions);
        gridColumnApi = gridApi;
        
        // Load data after grid is created
        if (gridApi) {
            loadDepreciationSchedules();
        }
    }
    
    // Event handlers for post and delete buttons
    $(document).on('click', '.post-depreciation', function() {
        const scheduleId = $(this).data('id');
        if (typeof postDepreciation === 'function') {
            postDepreciation(scheduleId);
        }
    });
    
    $(document).on('click', '.delete-depreciation', function() {
        const scheduleId = $(this).data('id');
        if (typeof deleteDepreciationSchedule === 'function') {
            deleteDepreciationSchedule(scheduleId);
        }
    });
    
    // Filter change handlers
    $('#filter_asset, #filter_posted').on('change', function() {
        loadDepreciationSchedules();
    });
    
    $('#clearFilterBtn').on('click', function() {
        $('#filter_asset').val('');
        $('#filter_posted').val('');
        loadDepreciationSchedules();
    });
});
