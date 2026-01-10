// AG Grid Configuration for Company Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let companyGridApi;
window.companyGridApi = null;

// Column Definitions - ستونی کردارەکان لە چەپ (pinned: 'left')
const companyColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 200,
        maxWidth: 250,
        flex: 0,
        pinned: 'left',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            let buttons = '';
            buttons += `<button class='btn btn-primary btn-sm edit-company-btn' 
                            data-id='${params.data.id}' 
                            data-name='${escapeHtml(params.data.name)}'
                            data-opening_debt_usd='${params.data.opening_debt_usd}'
                            data-opening_debt_iqd='${params.data.opening_debt_iqd}'
                            data-currency_type='${params.data.currency_type}'
                            data-bs-toggle='modal' 
                            data-bs-target='#editCompanyModal'
                            title='نوێکردنەوە' 
                            style='margin: 2px;'>
                        <i class='fa fa-edit'></i>
                    </button> `;
            buttons += `<button class='btn btn-danger btn-sm delete-company-btn' 
                            data-id='${params.data.id}' 
                            title='سڕینەوە' 
                            style='margin: 2px;'>
                        <i class='fa fa-trash'></i>
                    </button> `;
            buttons += `<a href='company_profile.php?id=${params.data.id}' 
                            class='btn btn-info btn-sm' 
                            title='بینین' 
                            style='margin: 2px;'>
                        <i class='fa fa-eye'></i>
                    </a>`;
            return buttons.trim() || '-';
        }
    },
    {
        field: 'name',
        headerName: 'ناوی کۆمپانیا',
        sortable: true,
        resizable: true,
        minWidth: 200,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'opening_debt_usd',
        headerName: 'قەرزی سەرەتایی (USD)',
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'opening_debt_iqd',
        headerName: 'قەرزی سەرەتایی (IQD)',
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'currency_type',
        headerName: 'جۆری مامەڵە لەگەڵ کۆمپانیا',
        sortable: true,
        resizable: true,
        minWidth: 180,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    }
];

// Grid Options
const companyGridOptions = {
    columnDefs: companyColumnDefs,
    rowData: [],
    defaultColDef: {
        sortable: true,
        filter: false,
        resizable: true,
        floatingFilter: false,
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
    suppressMenuHide: false,
    localeText: {
        noRowsToShow: 'هیچ داتایەک نەدۆزرایەوە',
        loadingOoo: 'چاوەڕوان بە...',
        filterOoo: 'فلتەر...',
        equals: 'یەکسانە',
        notEqual: 'یەکسان نییە',
        contains: 'تێیدایە',
        notContains: 'تێیدا نییە',
        startsWith: 'دەست پێدەکات بە',
        endsWith: 'کۆتایی دێت بە'
    }
};

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Load data from server
async function loadCompanyGrid() {
    if (!companyGridApi) {
        return;
    }

    companyGridApi.showLoadingOverlay();

    try {
        const res = await fetch('../process/company/select_company.php', {
            cache: 'no-cache'
        });

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (data.success && data.data) {
            companyGridApi.setGridOption('rowData', data.data);
            companyGridApi.hideOverlay();
        } else {
            companyGridApi.setGridOption('rowData', []);
            companyGridApi.showNoRowsOverlay();
        }
    } catch (error) {
        console.error('Error loading data:', error);
        companyGridApi.setGridOption('rowData', []);
        companyGridApi.showNoRowsOverlay();
    }
}

// Server-side search
function serverSearch(searchText) {
    if (!companyGridApi) return;
    
    const search = searchText.trim();
    if (search) {
        companyGridApi.setGridOption('quickFilterText', search);
    } else {
        companyGridApi.setGridOption('quickFilterText', '');
    }
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced search
const debouncedServerSearch = debounce(serverSearch, 500);

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.getElementById('companyGrid');
    if (!gridDiv) {
        console.error('Grid container not found!');
        return;
    }

    // Initialize grid
    companyGridApi = initAGGrid('companyGrid', companyColumnDefs, companyGridOptions);
    window.companyGridApi = companyGridApi;

    if (!companyGridApi) {
        console.error('Failed to initialize grid!');
        return;
    }

    // Load initial data
    loadCompanyGrid();

    // Quick search functionality
    const quickSearchInput = document.getElementById('quickSearchInput');
    const clearQuickSearchBtn = document.getElementById('clearQuickSearch');
    
    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function() {
            debouncedServerSearch(this.value);
        });

        if (clearQuickSearchBtn) {
            clearQuickSearchBtn.addEventListener('click', function() {
                quickSearchInput.value = '';
                serverSearch('');
            });
        }

        quickSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                serverSearch(this.value);
            }
        });
    }
});

// Global function to reload grid
window.reloadCompanyGrid = function() {
    loadCompanyGrid();
};

// Make functions globally accessible
window.loadCompanyGrid = loadCompanyGrid;

// Handle edit button clicks (delegated event)
$(document).on('click', '.edit-company-btn', function() {
    const btn = $(this);
    $('#editCompanyId').val(btn.data('id'));
    $('#editName').val(btn.data('name'));
    $('#editOpeningDebtUsd').val(btn.data('opening_debt_usd'));
    $('#editOpeningDebtIqd').val(btn.data('opening_debt_iqd'));
    $('#editCurrencyType').val(btn.data('currency_type'));
    
    // Trigger currency type change to enable/disable fields
    setTimeout(() => {
        if (typeof handleEditCurrencyTypeChange === 'function') {
            handleEditCurrencyTypeChange();
        }
    }, 100);
});

// Handle delete button clicks (delegated event)
$(document).on('click', '.delete-company-btn', function() {
    const companyId = $(this).data('id');
    if (typeof deleteCompany === 'function') {
        deleteCompany(companyId);
    }
});

// Refresh grid when company is added/updated/deleted
$(document).on('companyAdded companyUpdated companyDeleted', function() {
    loadCompanyGrid();
    if (typeof loadSummaryCardsData === 'function') {
        loadSummaryCardsData();
    }
});
