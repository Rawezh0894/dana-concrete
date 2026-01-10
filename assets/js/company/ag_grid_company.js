// AG Grid Configuration for Company Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let companyGridApi;
let companyGridColumnApi;

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
const companyColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        flex: 0,
        pinned: 'left',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            const editBtn = window.userPermissions && window.userPermissions.canEdit
                ? `<button class='btn btn-warning btn-sm edit-company-btn' data-id='${params.data.id}' data-name='${params.data.name}' data-opening_debt_usd='${params.data.opening_debt_usd}' data-opening_debt_iqd='${params.data.opening_debt_iqd}' data-currency_type='${params.data.currency_type}' data-bs-toggle='modal' data-bs-target='#editCompanyModal' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-company-btn' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            const viewBtn = `<a href='company_profile.php?id=${params.data.id}' class='btn btn-info btn-sm' title='بینین' style='margin: 2px;'><i class='fa fa-eye'></i></a>`;
            return `${editBtn} ${deleteBtn} ${viewBtn}`.trim() || '-';
        }
    },
    {
        field: 'currency_type',
        headerName: 'جۆری مامەڵە لەگەڵ کۆمپانیا',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'دۆلار' ? '#007bff' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'opening_debt_iqd',
        headerName: 'قەرزی سەرەتایی (IQD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return formatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'opening_debt_usd',
        headerName: 'قەرزی سەرەتایی (USD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return formatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'name',
        headerName: 'ناوی کۆمپانیا',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 200,
        flex: 1,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    }
];

// Grid Options - بەکارهێنانی defaults لە فایلی گشتی
// Merge with defaults from base file first
const companyGridOptions = {
    ...(window.AGGridDefaults || {}),
    columnDefs: companyColumnDefs,
    onGridReady: function(params) {
        console.log('Grid ready callback called');
        companyGridApi = params.api;
        companyGridColumnApi = params.columnApi;
        console.log('Grid API set:', companyGridApi);
        // Wait a bit to ensure grid is fully initialized
        setTimeout(() => {
            loadCompaniesData();
        }, 100);
    },
    onFirstDataRendered: function(params) {
        // Auto-size columns based on content
        const allColumnIds = params.columnApi.getColumns().map(col => col.getId());
        params.columnApi.autoSizeColumns(allColumnIds, false);
    }
};

// Load Companies Data
function loadCompaniesData(preservePagination = false) {
    if (!companyGridApi) {
        console.error('Company Grid API not initialized!');
        return;
    }
    
    const url = '../process/company/select_company.php';
    
    // Use base function if available, otherwise use custom implementation
    if (window.loadAGGridData && companyGridApi) {
        const dataTransformer = (data) => {
            return data.map(row => ({
                id: row.id,
                name: row.name || '-',
                opening_debt_usd: parseFloat(row.opening_debt_usd) || 0,
                opening_debt_iqd: parseFloat(row.opening_debt_iqd) || 0,
                currency_type: row.currency_type || '-'
            }));
        };
        window.loadAGGridData(companyGridApi, url, dataTransformer, preservePagination);
    } else {
        // Fallback to custom implementation
        let currentPage = 0;
        let pageSize = 25;
        if (preservePagination && companyGridApi) {
            currentPage = companyGridApi.paginationGetCurrentPage() || 0;
            pageSize = companyGridApi.paginationGetPageSize() || 25;
        }
        
        companyGridApi.showLoadingOverlay();
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Companies data received:', data);
                if (data.success && data.data) {
                    const rowData = data.data.map(row => ({
                        id: row.id,
                        name: row.name || '-',
                        opening_debt_usd: parseFloat(row.opening_debt_usd) || 0,
                        opening_debt_iqd: parseFloat(row.opening_debt_iqd) || 0,
                        currency_type: row.currency_type || '-'
                    }));
                    
                    console.log('Row data prepared:', rowData);
                    companyGridApi.setGridOption('rowData', rowData);
                    companyGridApi.hideOverlay();
                    
                    if (preservePagination && companyGridApi) {
                        setTimeout(() => {
                            companyGridApi.paginationGoToPage(currentPage);
                            companyGridApi.paginationSetPageSize(pageSize);
                        }, 100);
                    }
                } else {
                    console.warn('No data or success=false:', data);
                    companyGridApi.setGridOption('rowData', []);
                    companyGridApi.showNoRowsOverlay();
                }
            })
            .catch(error => {
                console.error('Error loading companies:', error);
                companyGridApi.setGridOption('rowData', []);
                companyGridApi.showNoRowsOverlay();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵە لە وەرگرتنی داتای کۆمپانیاکان: ' + error.message
                    });
                }
            });
    }
}

// Export to Excel
function exportCompanyToExcel() {
    if (!companyGridApi) {
        console.error('Grid API not initialized!');
        return;
    }
    
    const params = {
        fileName: 'companies.xlsx',
        sheetName: 'کۆمپانیاکان'
    };
    
    companyGridApi.exportDataAsExcel(params);
}

// Global function to reload grid
window.reloadCompanies = function() {
    loadCompaniesData(true);
};

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.getElementById('companyGrid');
    if (gridDiv) {
        console.log('Initializing company grid...');
        
        // Use createGrid for AG Grid v31+ (recommended)
        if (agGrid && agGrid.createGrid) {
            companyGridApi = agGrid.createGrid(gridDiv, companyGridOptions);
            console.log('Company grid initialized with createGrid, API:', companyGridApi);
        } else if (window.initAGGrid) {
            // Use initAGGrid helper function
            companyGridApi = window.initAGGrid('companyGrid', companyColumnDefs, {
                onGridReady: function(params) {
                    console.log('Grid ready callback called');
                    companyGridApi = params.api;
                    companyGridColumnApi = params.columnApi;
                    console.log('Grid API set:', companyGridApi);
                    // Wait a bit to ensure grid is fully initialized
                    setTimeout(() => {
                        loadCompaniesData();
                    }, 100);
                },
                onFirstDataRendered: function(params) {
                    // Auto-size columns based on content
                    const allColumnIds = params.columnApi.getColumns().map(col => col.getId());
                    params.columnApi.autoSizeColumns(allColumnIds, false);
                }
            });
            console.log('Company grid initialized with initAGGrid, API:', companyGridApi);
        } else {
            // Fallback to old Grid constructor
            new agGrid.Grid(gridDiv, companyGridOptions);
            console.log('Company grid initialized with Grid constructor');
        }
        
        // Wait for grid to be ready before adding event listeners
        setTimeout(() => {
            // Handle edit and delete buttons - let existing jQuery handlers work
            // The event delegation in update_company.js and delete_company.js will handle these
            console.log('Company grid ready, API:', companyGridApi);
        }, 200);
    } else {
        console.error('Company grid container not found!');
    }
});
