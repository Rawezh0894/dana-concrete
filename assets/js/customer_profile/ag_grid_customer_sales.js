// AG Grid Configuration for Customer Sales Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let customerSalesGridApi;

// Format functions - بەکارهێنانی لە فایلی گشتی
const agFormatNumber = window.AGGridFormatters?.formatNumber || function(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const agFormatUSD = window.AGGridFormatters?.formatUSD || function(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return agFormatNumber(Number(n).toFixed(2)) + ' $';
};

const agFormatIQD = window.AGGridFormatters?.formatIQD || function(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return agFormatNumber(Number(n).toFixed(0)) + ' د.ع';
};

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const customerSalesColumnDefs = [
    {
        field: 'notes',
        headerName: 'تێبینی',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        field: 'discount',
        headerName: 'داشکاندن',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#28a745' },
        valueFormatter: function(params) {
            return agFormatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'dolar_rate',
        headerName: 'نرخی ١٠٠ دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return agFormatNumber(params.value);
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function(params) {
            return agFormatUSD(params.value);
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return agFormatUSD(params.value);
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return agFormatIQD(params.value);
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return agFormatUSD(params.value);
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return agFormatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'quantity',
        headerName: 'بڕ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return agFormatNumber(params.value) + ' m³';
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'نەقد' ? '#28a745' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'order_date',
        headerName: 'بەروار',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    }
];

// Grid Options
const customerSalesGridOptions = {
    columnDefs: customerSalesColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
        minWidth: 100
    },
    pagination: true,
    paginationPageSize: 25,
    paginationPageSizeSelector: [10, 25, 50, 100],
    animateRows: true,
    rowSelection: 'multiple',
    suppressRowClickSelection: true,
    enableCellTextSelection: true,
    ensureDomOrder: true,
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
        customerSalesGridApi = params.api;
        loadCustomerSalesData();
    },
    onFirstDataRendered: function(params) {
        params.api.sizeColumnsToFit();
    }
};

// Load Customer Sales Data
function loadCustomerSalesData(preservePagination = false) {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID || CUSTOMER_ID <= 0) {
        console.error('Invalid CUSTOMER_ID for loading sales:', CUSTOMER_ID);
        return;
    }
    
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && customerSalesGridApi) {
        currentPage = customerSalesGridApi.paginationGetCurrentPage() || 0;
        pageSize = customerSalesGridApi.paginationGetPageSize() || 25;
    }
    
    // Show loading
    customerSalesGridApi?.showLoadingOverlay();
    
    fetch(`../process/customer_profile/select_sale.php?customer_id=${CUSTOMER_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data for AG Grid
                const rowData = data.data.map(row => ({
                    id: row.id,
                    customer_name: row.customer_name || '-',
                    location: row.location || '-',
                    recipient: row.recipient || '-',
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
                    discount: row.discount || 0,
                    notes: row.notes || '-'
                }));
                
                customerSalesGridApi.setGridOption('rowData', rowData);
                customerSalesGridApi.hideOverlay();
                
                // Restore pagination state if preserving
                if (preservePagination && customerSalesGridApi) {
                    setTimeout(() => {
                        customerSalesGridApi.paginationGoToPage(currentPage);
                        customerSalesGridApi.paginationSetPageSize(pageSize);
                    }, 100);
                }
            } else {
                customerSalesGridApi.setGridOption('rowData', []);
                customerSalesGridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading customer sales:', error);
            customerSalesGridApi.setGridOption('rowData', []);
            customerSalesGridApi.showNoRowsOverlay();
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        });
}

// Reload function - preserve pagination
window.reloadCustomerSales = function() {
    loadCustomerSalesData(true);
};

// Global function to load customer sales (for use in other scripts)
async function loadCustomerSales(customerId) {
    if (!customerId || customerId <= 0) {
        console.error('Invalid customer ID for loading sales:', customerId);
        return;
    }
    loadCustomerSalesData(true);
}

// Make function globally available
window.loadCustomerSales = loadCustomerSales;

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#customerSalesGrid');
    if (gridDiv) {
        // Use createGrid for AG Grid v31+
        customerSalesGridApi = agGrid.createGrid(gridDiv, customerSalesGridOptions);
    }
});
