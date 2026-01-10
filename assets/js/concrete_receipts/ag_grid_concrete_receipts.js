// AG Grid Configuration for Concrete Receipts Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let concreteReceiptsGridApi;
window.concreteReceiptsGridApi = null; // Will be set after initialization

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const concreteReceiptsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        maxWidth: 200,
        flex: 0,
        pinned: 'right',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            let buttons = '';
            if (window.userPermissions && window.userPermissions.canEdit) {
                buttons += `<button class='btn btn-warning btn-sm edit-receipt' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button> `;
            }
            if (window.userPermissions && window.userPermissions.canDelete) {
                buttons += `<button class='btn btn-danger btn-sm delete-receipt' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button> `;
            }
            if (window.userPermissions && window.userPermissions.canPrint) {
                buttons += `<button class='btn btn-info btn-sm print-receipt' data-id='${params.data.id}' title='پرێنت' style='margin: 2px;'><i class='fa fa-print'></i></button>`;
            }
            return buttons.trim() || '-';
        }
    },
    {
        field: 'mixer_driver_name',
        headerName: 'شۆفێری میکسەر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'mixer_car_name',
        headerName: 'میکسەر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'pump_driver_name',
        headerName: 'شۆفێری پەمپ',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'pump_car_name',
        headerName: 'پەمپ',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'formula_name',
        headerName: 'فۆرمۆلا',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'meter_amount',
        headerName: 'بڕی مەتر سێجا',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) + ' m³' || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'created_at',
        headerName: 'بەروار',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (!params.value) return '-';
            const d = new Date(params.value);
            if (isNaN(d)) return params.value;
            return d.getFullYear() + '-' + 
                   String(d.getMonth()+1).padStart(2,'0') + '-' + 
                   String(d.getDate()).padStart(2,'0') + ' ' + 
                   String(d.getHours()).padStart(2,'0') + ':' + 
                   String(d.getMinutes()).padStart(2,'0');
        }
    },
    {
        field: 'receiver_name',
        headerName: 'وەرگر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'location',
        headerName: 'شوێن',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'customer_name',
        headerName: 'کڕیار',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return params.value || '-';
        }
    },
    {
        field: 'receipt_number',
        headerName: 'ژم.پسووڵە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const isDuplicate = params.data && params.data.is_duplicate;
            const warningIcon = isDuplicate ? '<i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-left: 4px;" title="ژمارەی پسوڵە دووبارەیە"></i>' : '';
            return warningIcon + (params.value || '-');
        }
    }
];

// Grid Options
const concreteReceiptsGridOptions = {
    columnDefs: concreteReceiptsColumnDefs,
    rowData: [],
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
    // Server-side pagination
    paginationAutoPageSize: false,
    suppressPaginationPanel: false
};

// Flag to prevent infinite loop in pagination
let isLoadingData = false;
let loadTimeout = null;

// Debounce function for better performance
function debounce(func, wait) {
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(loadTimeout);
            func(...args);
        };
        clearTimeout(loadTimeout);
        loadTimeout = setTimeout(later, wait);
    };
}

// Load data function with optimized performance
async function loadConcreteReceiptsGrid() {
    if (!concreteReceiptsGridApi || isLoadingData) {
        return;
    }

    isLoadingData = true;

    // Get current filters
    const filters = {
        customer_id: $('#filter_customer_id').val() || '',
        location: $('#filter_location').val() || '',
        formulas_id: $('#filter_formulas_id').val() || '',
        date_from: $('#filter_date_from').val() || '',
        date_to: $('#filter_date_to').val() || ''
    };

    // Build query string
    const queryParams = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            queryParams.append(key, filters[key]);
        }
    });

    // Use reasonable page size for better performance (500 records at a time)
    // If more data is needed, we can implement server-side pagination later
    queryParams.append('page', 1);
    queryParams.append('pageSize', 500);

    // Show loading with better visual feedback
    concreteReceiptsGridApi.showLoadingOverlay();
    
    // Add custom loading message
    const loadingMessage = document.createElement('div');
    loadingMessage.id = 'concrete-receipts-loading-msg';
    loadingMessage.style.cssText = 'text-align: center; padding: 20px; color: var(--seafoam-green); font-weight: bold;';
    loadingMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> چاوەڕوان بە...';
    const gridContainer = document.getElementById('concreteReceiptsGrid');
    if (gridContainer && !document.getElementById('concrete-receipts-loading-msg')) {
        gridContainer.appendChild(loadingMessage);
    }

    const startTime = performance.now();

    try {
        // Use AbortController for request cancellation if needed
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

        const res = await fetch('../process/concrete_receipts/select_concrete_receipts.php?' + queryParams.toString(), {
            signal: controller.signal,
            cache: 'no-cache'
        });
        
        clearTimeout(timeoutId);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const text = await res.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response from select_concrete_receipts.php:', text);
            concreteReceiptsGridApi.hideOverlay();
            const loadingMsg = document.getElementById('concrete-receipts-loading-msg');
            if (loadingMsg) loadingMsg.remove();
            isLoadingData = false;
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.'
                });
            }
            return;
        }

        const loadTime = ((performance.now() - startTime) / 1000).toFixed(2);
        console.log(`Data loaded in ${loadTime} seconds`);

        if (data.success && data.data) {
            // Set row data efficiently
            concreteReceiptsGridApi.setGridOption('rowData', data.data);
            concreteReceiptsGridApi.hideOverlay();
            
            // Remove loading message
            const loadingMsg = document.getElementById('concrete-receipts-loading-msg');
            if (loadingMsg) loadingMsg.remove();

            // Update summary cards
            if (data.summary) {
                function formatNumber(n) {
                    if (n === null || n === undefined || n === '') return '0';
                    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                
                $('#summary_total_receipts').text(data.summary.total_receipts || 0);
                $('#summary_total_meter').text(formatNumber(data.summary.total_meter || 0) + ' m³');
                $('#summary_total_customers').text(data.summary.total_customers || 0);
            }
        } else {
            concreteReceiptsGridApi.setGridOption('rowData', []);
            concreteReceiptsGridApi.showNoRowsOverlay();
            const loadingMsg = document.getElementById('concrete-receipts-loading-msg');
            if (loadingMsg) loadingMsg.remove();
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            console.error('Request timeout');
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'کاتی زیاد',
                    text: 'کاتی زیاد بوو. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        } else {
            console.error('Error loading data:', error);
        }
        concreteReceiptsGridApi.setGridOption('rowData', []);
        concreteReceiptsGridApi.showNoRowsOverlay();
        const loadingMsg = document.getElementById('concrete-receipts-loading-msg');
        if (loadingMsg) loadingMsg.remove();
        if (window.Swal && error.name !== 'AbortError') {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
            });
        }
    } finally {
        isLoadingData = false;
    }
}

// Debounced version for filter changes
const debouncedLoadConcreteReceiptsGrid = debounce(loadConcreteReceiptsGrid, 300);

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.getElementById('concreteReceiptsGrid');
    if (!gridDiv) {
        console.error('Grid container not found!');
        return;
    }

    // Initialize grid
    concreteReceiptsGridApi = initAGGrid('concreteReceiptsGrid', concreteReceiptsColumnDefs, concreteReceiptsGridOptions);
    window.concreteReceiptsGridApi = concreteReceiptsGridApi; // Make it globally accessible

    if (!concreteReceiptsGridApi) {
        console.error('Failed to initialize grid!');
        return;
    }

    // Load initial data
    loadConcreteReceiptsGrid();
});

// Global function to reload grid (overrides the old one from select_concrete_receipts.js)
window.reloadConcreteReceipts = function() {
    if (concreteReceiptsGridApi) {
        concreteReceiptsGridApi.paginationGoToPage(0);
        loadConcreteReceiptsGrid();
    } else if (window.concreteReceiptsGridApi) {
        window.concreteReceiptsGridApi.paginationGoToPage(0);
        if (typeof loadConcreteReceiptsGrid === 'function') {
            loadConcreteReceiptsGrid();
        }
    } else {
        // Fallback: wait a bit for grid to initialize
        setTimeout(function() {
            if (window.concreteReceiptsGridApi) {
                window.concreteReceiptsGridApi.paginationGoToPage(0);
                if (typeof loadConcreteReceiptsGrid === 'function') {
                    loadConcreteReceiptsGrid();
                }
            }
        }, 500);
    }
};

// Make loadConcreteReceiptsGrid globally accessible for filter.js
window.loadConcreteReceiptsGrid = loadConcreteReceiptsGrid;
window.debouncedLoadConcreteReceiptsGrid = debouncedLoadConcreteReceiptsGrid;

// Export grid data to CSV
window.exportConcreteReceiptsToCSV = function() {
    if (!concreteReceiptsGridApi) {
        console.error('Grid API not initialized!');
        return;
    }
    exportAGGridToCSV(concreteReceiptsGridApi, 'concrete_receipts.csv');
};
