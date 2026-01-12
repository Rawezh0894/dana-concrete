// AG Grid Configuration for Assets Table
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
        minWidth: 120,
        flex: 0,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            const editBtn = window.userPermissions && window.userPermissions.canEdit
                ? `<button class='btn btn-warning btn-sm edit-asset' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-asset' data-id='${params.data.id}' data-name='${params.data.name}' data-code='${params.data.asset_code}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            return `${editBtn} ${deleteBtn}`.trim() || '-';
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
        field: 'name',
        headerName: 'ناوی ئامێر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'category_name',
        headerName: 'جۆر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'serial_number',
        headerName: 'ژمارەی سیریاڵ',
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
        field: 'purchase_date',
        headerName: 'بەرواری کڕین',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' }
    },
    {
        field: 'purchase_cost',
        headerName: 'نرخی کڕین',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'salvage_value',
        headerName: 'نرخی کۆتایی',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'useful_life_years',
        headerName: 'ماوەی بەکارهێنان (ساڵ)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        type: 'numericColumn'
    },
    {
        field: 'depreciation_method_name',
        headerName: 'شێوازی داخوران',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'current_value',
        headerName: 'نرخی ئێستا',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#007bff', fontWeight: 'bold' },
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
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#ffc107' },
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
        field: 'status_name',
        headerName: 'دۆخ',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const status = params.data.status;
            let badgeClass = 'badge bg-secondary';
            if (status === 'active') badgeClass = 'badge bg-success';
            else if (status === 'inactive') badgeClass = 'badge bg-secondary';
            else if (status === 'disposed') badgeClass = 'badge bg-danger';
            else if (status === 'under_maintenance') badgeClass = 'badge bg-warning';
            return `<span class="${badgeClass}">${params.value}</span>`;
        }
    },
    {
        field: 'location',
        headerName: 'شوێن',
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
        field: 'supplier',
        headerName: 'دابینکەر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function(params) {
            return params.value || '-';
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
        // Kurdish translations
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
        searchOoo: 'گەڕان...',
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
    sideBar: {
        toolPanels: [
            {
                id: 'columns',
                labelDefault: 'ستونەکان',
                labelKey: 'columns',
                iconKey: 'columns',
                toolPanel: 'agColumnsToolPanel',
                toolPanelParams: {
                    suppressRowGroups: true,
                    suppressValues: true,
                    suppressPivots: true,
                    suppressPivotMode: true
                }
            },
            {
                id: 'filters',
                labelDefault: 'فلتەرەکان',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel'
            }
        ],
        defaultToolPanel: 'filters',
        hiddenByDefault: false
    },
    enableRangeSelection: true,
    suppressRowClickSelection: true,
    rowSelection: 'multiple',
    animateRows: true,
    onGridReady: function(params) {
        gridApi = params.api;
        gridColumnApi = params.columnApi;
        loadAssets();
    }
};

// Load assets data
function loadAssets(filterParams = '') {
    const params = new URLSearchParams(filterParams);
    
    // Get filter values
    const categoryId = $('#filter_category').val() || '';
    const status = $('#filter_status').val() || '';
    
    if (categoryId) params.set('category_id', categoryId);
    if (status) params.set('status', status);
    
    $.ajax({
        url: '../process/assets/select_assets.php?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            gridApi.setRowData(data);
        },
        error: function(xhr) {
            console.error('Error loading assets:', xhr);
            gridApi.setRowData([]);
        }
    });
}

// Reload function for external use
window.reloadAssets = function() {
    loadAssets();
};

// Initialize grid when DOM is ready
$(document).ready(function() {
    const gridDiv = document.querySelector('#assetsGrid');
    if (gridDiv) {
        new agGrid.Grid(gridDiv, gridOptions);
    }
    
    // Event handlers for edit and delete buttons
    $(document).on('click', '.edit-asset', function() {
        const assetId = $(this).data('id');
        if (typeof loadAssetForEdit === 'function') {
            loadAssetForEdit(assetId);
        }
    });
    
    $(document).on('click', '.delete-asset', function() {
        const assetId = $(this).data('id');
        const assetName = $(this).data('name');
        const assetCode = $(this).data('code');
        if (typeof deleteAsset === 'function') {
            deleteAsset(assetId, assetName, assetCode);
        }
    });
    
    // Filter change handlers
    $('#filter_category, #filter_status').on('change', function() {
        loadAssets();
        if (typeof loadSummaryCardsData === 'function') {
            loadSummaryCardsData();
        }
    });
    
    $('#clearFilterBtn').on('click', function() {
        $('#filter_category').val('');
        $('#filter_status').val('');
        loadAssets();
        if (typeof loadSummaryCardsData === 'function') {
            loadSummaryCardsData();
        }
    });
});
