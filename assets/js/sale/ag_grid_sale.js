// AG Grid Configuration for Sales Table
let gridApi;
let gridColumnApi;

// Format functions
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

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const columnDefs = [
    {
        field: 'discount',
        headerName: 'داشکاندن',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 500,
        cellStyle: { textAlign: 'center', direction: 'ltr', color: '#28a745', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'notes',
        headerName: 'تێبینی',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 200,
        minWidth: 100,
        maxWidth: 600,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const displayText = params.value.length > 40 ? params.value.substring(0, 40) + '...' : params.value;
            return `<span title="${params.value}">${displayText}</span>`;
        },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'dolar_rate',
        headerName: 'نرخی ١٠٠ دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 130,
        minWidth: 100,
        maxWidth: 300,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return formatNumber(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'remaining_amount',
        headerName: 'پارەی ماوە',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 300,
        cellStyle: { textAlign: 'center', direction: 'ltr', fontWeight: 'bold', color: '#dc3545', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'amount_paid_usd',
        headerName: 'پارەی دراو بە دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 400,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'amount_paid_iq',
        headerName: 'پارەی دراو بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 400,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            return formatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'total_price',
        headerName: 'کۆی نرخ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 300,
        cellStyle: { textAlign: 'center', direction: 'ltr', fontWeight: 'bold', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'price_per_unit',
        headerName: 'نرخی یەکە',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 300,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'quantity',
        headerName: 'بڕ (م³)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 110,
        minWidth: 100,
        maxWidth: 200,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return `M³ ${formatNumber(params.value)}`;
        },
        type: 'numericColumn'
    },
    {
        field: 'payment_type',
        headerName: 'جۆری پارەدان',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 200,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'نەقد' ? '#28a745' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${params.value}</span>`;
        }
    },
    {
        field: 'order_date',
        headerName: 'بەروار',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 200,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        valueFormatter: function(params) {
            if (!params.value) return '-';
            return params.value;
        }
    },
    {
        field: 'formula_name',
        headerName: 'فۆرمۆلا',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 500,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'invoice_number',
        headerName: 'ژمارەی پسوڵە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 130,
        minWidth: 100,
        maxWidth: 300,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        cellRenderer: function(params) {
            if (params.data && params.data.duplicate_count && params.data.duplicate_count > 1) {
                return `<span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${params.value || '-'}</span>`;
            }
            return params.value || '-';
        },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'location',
        headerName: 'شوێن',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 500,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'recipient',
        headerName: 'وەرگر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 500,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'customer_name',
        headerName: 'کڕیار',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        width: 150,
        minWidth: 100,
        maxWidth: 500,
        cellStyle: { textAlign: 'center', direction: 'ltr', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', wordBreak: 'break-word' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        width: 120,
        minWidth: 100,
        maxWidth: 200,
        cellStyle: { textAlign: 'center', wordBreak: 'break-word' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            const editBtn = window.userPermissions && window.userPermissions.canEdit
                ? `<button class='btn btn-warning btn-sm edit-sale' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-sale' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            return `${editBtn} ${deleteBtn}`.trim() || '-';
        }
    }
];

// Grid Options
const gridOptions = {
    columnDefs: columnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
    },
    rowData: [],
    pagination: true,
    paginationPageSize: 25,
    paginationPageSizeSelector: [10, 25, 50, 100],
    localeText: {
        // Pagination
        page: 'لاپەڕە',
        more: 'زیاتر',
        to: 'بۆ',
        of: 'لە',
        next: 'دواتر',
        last: 'کۆتایی',
        first: 'یەکەم',
        previous: 'پێشوو',
        loadingOoo: 'چاوەڕوان بە...',
        noRowsToShow: 'هیچ ڕیزێک نییە',
        // Filter
        filterOoo: 'فلتەر...',
        equals: 'یەکسان',
        notEqual: 'نا یەکسان',
        lessThan: 'کەمتر لە',
        greaterThan: 'زیاتر لە',
        lessThanOrEqual: 'کەمتر یان یەکسان',
        greaterThanOrEqual: 'زیاتر یان یەکسان',
        inRange: 'لە نێوان',
        contains: 'تێدایە',
        notContains: 'تێدا نییە',
        startsWith: 'دەست پێدەکات بە',
        endsWith: 'کۆتایی دێت بە',
        // Buttons
        applyFilter: 'جێبەجێکردنی فلتەر',
        resetFilter: 'دووبارەکردنەوەی فلتەر',
        clearFilter: 'پاککردنەوەی فلتەر',
        // Columns
        pinColumn: 'جێگیرکردنی ستون',
        valueAggregation: 'کۆکردنەوە',
        autosizeThiscolumn: 'گەورەکردنی ئەم ستونە',
        autosizeAllColumns: 'گەورەکردنی هەموو ستونەکان',
        groupBy: 'گروپکردن بەپێی',
        ungroupBy: 'لادانی گروپ',
        // Export
        export: 'ئیکسپۆرت',
        csvExport: 'ئیکسپۆرتی CSV',
        excelExport: 'ئیکسپۆرتی Excel',
    },
    sideBar: {
        toolPanels: [
            {
                id: 'columns',
                labelDefault: 'ستونەکان',
                labelKey: 'columns',
                iconKey: 'columns',
                toolPanel: 'agColumnsToolPanel',
            },
            {
                id: 'filters',
                labelDefault: 'فلتەرەکان',
                labelKey: 'filters',
                iconKey: 'filter',
                toolPanel: 'agFiltersToolPanel',
            }
        ],
        defaultToolPanel: 'filters',
        hiddenByDefault: false
    },
    rowClassRules: {
        'duplicate-invoice-row': function(params) {
            return params.data && params.data.duplicate_count && params.data.duplicate_count > 1;
        }
    },
    onGridReady: function(params) {
        gridApi = params.api;
        gridColumnApi = params.columnApi;
        loadSalesData();
    },
    onFirstDataRendered: function(params) {
        // Don't auto-size to fit - allow horizontal scroll
        // params.api.sizeColumnsToFit();
    },
    // Enable horizontal scroll
    suppressSizeToFit: true,
    suppressHorizontalScroll: false,
    suppressRowClickSelection: true,
    animateRows: true,
    enableRangeSelection: true,
    enableCharts: true,
    rowSelection: 'multiple',
    suppressCellFocus: false,
    enableCellTextSelection: true,
    ensureDomOrder: true
};

// Load Sales Data
function loadSalesData() {
    const fromDate = document.getElementById('filter_from')?.value || '';
    const toDate = document.getElementById('filter_to')?.value || '';
    const customerId = document.getElementById('filter_customer')?.value || '';
    const minQuantity = document.getElementById('filter_quantity_min')?.value || '';
    const maxQuantity = document.getElementById('filter_quantity_max')?.value || '';
    
    // Build URL with filters
    let url = '../process/sale/select_sale.php?ag_grid=1';
    if (fromDate) url += `&from=${encodeURIComponent(fromDate)}`;
    if (toDate) url += `&to=${encodeURIComponent(toDate)}`;
    if (customerId) url += `&customer_id=${encodeURIComponent(customerId)}`;
    if (minQuantity) url += `&min_quantity=${encodeURIComponent(minQuantity)}`;
    if (maxQuantity) url += `&max_quantity=${encodeURIComponent(maxQuantity)}`;
    
    // Show loading
    gridApi?.showLoadingOverlay();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data for AG Grid
                const rowData = data.data.map(row => ({
                    id: row.id,
                    customer_name: row.customer_name || '-',
                    recipient: row.recipient || '-',
                    location: row.location || '-',
                    invoice_number: row.invoice_number || '-',
                    formula_name: row.formula_name || '-',
                    order_date: row.order_date || '-',
                    payment_type: row.payment_type || '-',
                    quantity: row.quantity || 0,
                    price_per_unit: row.price_per_unit || 0,
                    total_price: row.total_price || 0,
                    amount_paid_iq: row.amount_paid_iq || 0,
                    amount_paid_usd: row.amount_paid_usd || 0,
                    remaining_amount: row.remaining_amount || 0,
                    dolar_rate: row.dolar_rate || 0,
                    notes: row.notes || '-',
                    discount: row.discount || 0,
                    duplicate_count: row.duplicate_count || 0
                }));
                
                gridApi.setGridOption('rowData', rowData);
                gridApi.hideOverlay();
            } else {
                gridApi.setGridOption('rowData', []);
                gridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading sales:', error);
            gridApi.setGridOption('rowData', []);
            gridApi.showNoRowsOverlay();
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
            });
        });
}

// Reload function
window.reloadSales = function() {
    loadSalesData();
};

// Export to Excel
function exportSaleToExcel() {
    const params = {
        fileName: `فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
        sheetName: 'فرۆشتنەکان'
    };
    gridApi.exportDataAsExcel(params);
}

// Export Summary to Excel
function exportSaleSummaryToExcel() {
    const params = {
        fileName: `کورتەی_فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
        sheetName: 'کورتە',
        onlySelected: false
    };
    gridApi.exportDataAsExcel(params);
}

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#salesGrid');
    if (gridDiv) {
        new agGrid.Grid(gridDiv, gridOptions);
        
        // Wait for grid to be ready before adding event listeners
        setTimeout(() => {
            // Add event listeners for filters
            const filterInputs = ['filter_from', 'filter_to', 'filter_customer', 'filter_quantity_min', 'filter_quantity_max'];
            filterInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('change', loadSalesData);
                    input.addEventListener('input', function() {
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(loadSalesData, 500);
                    });
            }
        });
        
        // Handle edit and delete buttons using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-sale')) {
                const saleId = e.target.closest('.edit-sale').getAttribute('data-id');
                if (typeof loadSaleForEdit === 'function') {
                    loadSaleForEdit(saleId);
                } else if (window.loadSaleForEdit) {
                    window.loadSaleForEdit(saleId);
                }
            }
            if (e.target.closest('.delete-sale')) {
                const saleId = e.target.closest('.delete-sale').getAttribute('data-id');
                if (typeof deleteSale === 'function') {
                    deleteSale(saleId);
                } else if (window.deleteSale) {
                    window.deleteSale(saleId);
                }
            }
        });
        }, 100);
    }
});
