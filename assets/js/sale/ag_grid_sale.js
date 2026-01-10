// AG Grid Configuration for Sales Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let gridApi;
let gridColumnApi;

// Format functions - بەکارهێنانی لە فایلی گشتی
const formatNumber = window.AGGridFormatters?.formatNumber || function(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const formatUSD = window.AGGridFormatters?.formatUSD || function(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(2)) + ' $';
};

const formatIQD = window.AGGridFormatters?.formatIQD || function(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
};

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const columnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 100,
        flex: 0,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
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
        cellRenderer: function(params) {
            if (params.data && params.data.duplicate_count && params.data.duplicate_count > 1) {
                return `<span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px;">${params.value || '-'}</span>`;
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

// Grid Options - بەکارهێنانی defaults لە فایلی گشتی
const gridOptions = {
    columnDefs: columnDefs,
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
        // Auto-size columns based on content
        const allColumnIds = params.columnApi.getColumns().map(col => col.getId());
        params.columnApi.autoSizeColumns(allColumnIds, false);
    }
};

// Merge with defaults from base file (excluding sideBar and suppressSizeToFit)
const defaults = { ...window.AGGridDefaults };
delete defaults.sideBar;
delete defaults.suppressSizeToFit;
Object.assign(gridOptions, defaults);

// Load Sales Data - with pagination state preservation
function loadSalesData(preservePagination = false) {
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
    
    // Use base function if available, otherwise use custom implementation
    if (window.loadAGGridData && gridApi) {
        const dataTransformer = (data) => {
            return data.map(row => ({
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
        };
        window.loadAGGridData(gridApi, url, dataTransformer, preservePagination);
    } else {
        // Fallback to custom implementation
        let currentPage = 0;
        let pageSize = 25;
        if (preservePagination && gridApi) {
            currentPage = gridApi.paginationGetCurrentPage() || 0;
            pageSize = gridApi.paginationGetPageSize() || 25;
        }
        
        gridApi?.showLoadingOverlay();
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
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
                    
                    if (preservePagination && gridApi) {
                        setTimeout(() => {
                            gridApi.paginationGoToPage(currentPage);
                            gridApi.paginationSetPageSize(pageSize);
                        }, 100);
                    }
                } else {
                    gridApi.setGridOption('rowData', []);
                    gridApi.showNoRowsOverlay();
                }
            })
            .catch(error => {
                console.error('Error loading sales:', error);
                gridApi.setGridOption('rowData', []);
                gridApi.showNoRowsOverlay();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                    });
                }
            });
    }
}

// Reload function - preserve pagination
window.reloadSales = function() {
    loadSalesData(true); // Preserve pagination state
};

// Export to Excel
function exportSaleToExcel() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, `فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`, 'فرۆشتنەکان');
    } else if (gridApi) {
        const params = {
            fileName: `فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
            sheetName: 'فرۆشتنەکان'
        };
        gridApi.exportDataAsExcel(params);
    }
}

// Export Summary to Excel
function exportSaleSummaryToExcel() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, `کورتەی_فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`, 'کورتە');
    } else if (gridApi) {
        const params = {
            fileName: `کورتەی_فرۆشتنەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
            sheetName: 'کورتە',
            onlySelected: false
        };
        gridApi.exportDataAsExcel(params);
    }
}

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#salesGrid');
    if (gridDiv) {
        // Use createGrid instead of new Grid (AG Grid v31+)
        if (typeof agGrid.createGrid === 'function') {
            gridApi = agGrid.createGrid(gridDiv, gridOptions).api;
        } else {
            // Fallback for older versions
            new agGrid.Grid(gridDiv, gridOptions);
        }
        
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
            
            // Handle edit and delete buttons - let existing jQuery handlers work
            // The event delegation in update_sale.js and delete_sale.js will handle these
            // We just need to ensure the buttons are properly rendered in the grid
        }, 100);
    }
});
