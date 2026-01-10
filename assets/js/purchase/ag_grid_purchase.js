// AG Grid Configuration for Purchase Table
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
                ? `<button class='btn btn-warning btn-sm edit-purchase' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-purchase' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            return `${editBtn} ${deleteBtn}`.trim() || '-';
        }
    },
    {
        field: 'bin_name',
        headerName: 'چاو/سایلۆ',
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
        field: 'remaining_iqd',
        headerName: 'پارەی ماوە بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function(params) {
            return formatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'remaining_usd',
        headerName: 'پارەی ماوە بە دۆلار',
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
        field: 'paid_iqd',
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
        field: 'paid_usd',
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
        field: 'exchange_rate',
        headerName: 'نرخی 100 دۆلار بە دینار',
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
        field: 'amount_iqd',
        headerName: 'بڕی پارە بە دینار',
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
        field: 'price',
        headerName: 'نرخ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            if (!params.data) return '-';
            const type = params.data.type;
            if (type === 'دینار') {
                return formatIQD(params.value);
            } else if (type === 'دۆلار') {
                return formatUSD(params.value);
            }
            return formatNumber(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'price_per_kg_iqd',
        headerName: 'نرخی یەک کیلۆ بە دینار',
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
        field: 'price_per_kg_usd',
        headerName: 'نرخی یەک کیلۆ بە دۆلار',
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
        field: 'kg',
        headerName: 'کیلۆگرام',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return formatNumber(params.value) + ' کگم';
        },
        type: 'numericColumn'
    },
    {
        field: 'type',
        headerName: 'جۆری دراو',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'دۆلار' ? '#007bff' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
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
        field: 'date',
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
        field: 'material_name',
        headerName: 'مەواد',
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
        field: 'driver_name',
        headerName: 'شۆفێر',
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
        field: 'location_name',
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
        field: 'company_name',
        headerName: 'کۆمپانیا',
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
    onGridReady: function(params) {
        gridApi = params.api;
        gridColumnApi = params.columnApi;
        loadPurchaseData();
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

// Load Purchase Data - with pagination state preservation
function loadPurchaseData(preservePagination = false) {
    const fromDate = document.getElementById('filter_from')?.value || '';
    const toDate = document.getElementById('filter_to')?.value || '';
    const companyId = document.getElementById('filter_company')?.value || '';
    const locationId = document.getElementById('filter_location')?.value || '';
    const driverId = document.getElementById('filter_driver')?.value || '';
    const materialId = document.getElementById('filter_material')?.value || '';
    const searchTerm = document.getElementById('purchase_global_search')?.value || '';
    
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && gridApi) {
        currentPage = gridApi.paginationGetCurrentPage() || 0;
        pageSize = gridApi.paginationGetPageSize() || 25;
    }
    
    // Build request data
    const requestData = new FormData();
    if (fromDate) requestData.append('from', fromDate);
    if (toDate) requestData.append('to', toDate);
    if (companyId) requestData.append('company_id', companyId);
    if (locationId) requestData.append('location_id', locationId);
    if (driverId) requestData.append('driver_id', driverId);
    if (materialId) requestData.append('material_id', materialId);
    if (searchTerm) requestData.append('search', searchTerm);
    requestData.append('ag_grid', '1');
    
    // Show loading
    gridApi?.showLoadingOverlay();
    
    fetch('../process/purchase/select_purchase.php', {
        method: 'POST',
        body: requestData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data for AG Grid
                const rowData = data.data.map(row => ({
                    id: row.id,
                    company_name: row.company_name || '-',
                    location_name: row.location_name || '-',
                    driver_name: row.driver_name || '-',
                    invoice_number: row.invoice_number || '-',
                    material_name: row.material_name || '-',
                    date: row.date || '-',
                    payment_type: row.payment_type || '-',
                    type: row.type || '-',
                    kg: row.kg || 0,
                    price_per_kg_usd: row.price_per_kg_usd || 0,
                    price_per_kg_iqd: row.price_per_kg_iqd || 0,
                    price: row.price || 0,
                    amount_iqd: row.amount_iqd || 0,
                    exchange_rate: row.exchange_rate || 0,
                    paid_usd: row.paid_usd || 0,
                    paid_iqd: row.paid_iqd || 0,
                    remaining_usd: row.remaining_usd || 0,
                    remaining_iqd: row.remaining_iqd || 0,
                    bin_name: row.bin_name || '-'
                }));
                
                gridApi.setGridOption('rowData', rowData);
                gridApi.hideOverlay();
                
                // Restore pagination state if preserving
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
            console.error('Error loading purchases:', error);
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

// Reload function - preserve pagination
window.reloadPurchases = function() {
    loadPurchaseData(true); // Preserve pagination state
};

// Export to Excel
function exportPurchaseToExcel() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, `کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`, 'کڕینەکان');
    } else if (gridApi) {
        const params = {
            fileName: `کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
            sheetName: 'کڕینەکان'
        };
        gridApi.exportDataAsExcel(params);
    }
}

// Export to CSV
function exportPurchaseToCSV() {
    if (gridApi) {
        const params = {
            fileName: `کڕینەکان_${new Date().toISOString().split('T')[0]}.csv`
        };
        gridApi.exportDataAsCsv(params);
    }
}

// Export Summary to Excel
function exportPurchaseSummaryToExcel() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, `کورتەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`, 'کورتە');
    } else if (gridApi) {
        const params = {
            fileName: `کورتەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
            sheetName: 'کورتە',
            onlySelected: false
        };
        gridApi.exportDataAsExcel(params);
    }
}

// Export Monthly Report
function exportPurchaseMonthlyReport() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, `ڕاپۆرتی_مانگانەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`, 'ڕاپۆرت');
    } else if (gridApi) {
        const params = {
            fileName: `ڕاپۆرتی_مانگانەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.xlsx`,
            sheetName: 'ڕاپۆرت'
        };
        gridApi.exportDataAsExcel(params);
    }
}

// Export Monthly Report to CSV
function exportPurchaseMonthlyReportToCSV() {
    if (gridApi) {
        const params = {
            fileName: `ڕاپۆرتی_مانگانەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.csv`
        };
        gridApi.exportDataAsCsv(params);
    }
}

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#purchaseGrid');
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
            const filterInputs = ['filter_from', 'filter_to', 'filter_company', 'filter_location', 'filter_driver', 'filter_material', 'purchase_global_search'];
            filterInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('change', loadPurchaseData);
                    input.addEventListener('input', function() {
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(loadPurchaseData, 500);
                    });
                }
            });
            
            // Clear filters button
            const clearFilterBtn = document.getElementById('clearFilterBtn');
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    document.getElementById('filter_company').value = '';
                    document.getElementById('filter_location').value = '';
                    document.getElementById('filter_driver').value = '';
                    document.getElementById('filter_material').value = '';
                    document.getElementById('filter_from').value = '';
                    document.getElementById('filter_to').value = '';
                    document.getElementById('purchase_global_search').value = '';
                    loadPurchaseData();
                });
            }
            
            // Clear column filters button
            const clearColumnFiltersBtn = document.getElementById('clearColumnFiltersBtn');
            if (clearColumnFiltersBtn) {
                clearColumnFiltersBtn.addEventListener('click', function() {
                    if (gridApi) {
                        gridApi.setFilterModel(null);
                        loadPurchaseData();
                    }
                });
            }
            
            // Handle edit and delete buttons - let existing jQuery handlers work
            // The event delegation in select_purchase.js and delete_purchase.js will handle these
            // We just need to ensure the buttons are properly rendered in the grid
        }, 100);
    }
});
