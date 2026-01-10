// AG Grid Configuration for Concrete Receipts Table
// Server-Side Pagination - وەک سیستەمەکانی SAP, Odoo, Oracle
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let concreteReceiptsGridApi;
window.concreteReceiptsGridApi = null;

// Current pagination state
let currentServerPage = 1;
let currentPageSize = 25;
let totalRecords = 0;
let totalPages = 0;
let currentSearchText = '';
let cachedData = [];

// Column Definitions - ستونی کردارەکان لە چەپ (pinned: 'left') - بەڵام ستونەکانی تر بە شێوەی کۆن
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
        pinned: 'left', // تەنها ستونی کردارەکان لە چەپ جێگیر دەکرێت
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

// Grid Options - بەبێ pagination ی AG Grid (ئێمە خۆمان پاژینەیشنی سێرڤەر دەکەین)
const concreteReceiptsGridOptions = {
    columnDefs: concreteReceiptsColumnDefs,
    rowData: [],
    defaultColDef: {
        sortable: true,
        filter: false, // فلتەرەکانی ستون ناچالاک - تەنها سێرچی گشتی بەکاردەهێنرێت
        resizable: true,
        floatingFilter: false, // ناچالاک - بۆ ئەوەی بەکارهێنەر تەنها سێرچی گشتی بەکاربهێنێت
        minWidth: 100
    },
    // Disable AG Grid built-in pagination - use custom server-side pagination
    pagination: false,
    animateRows: true,
    rowSelection: 'multiple',
    suppressRowClickSelection: true,
    enableCellTextSelection: true,
    ensureDomOrder: true,
    suppressMenuHide: false,
    // Locale for Kurdish
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

// Loading state
let isLoadingData = false;

// Format number helper
function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '0';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Load data from server with pagination
async function loadConcreteReceiptsGrid(page = 1, pageSize = 25, search = '') {
    if (!concreteReceiptsGridApi || isLoadingData) {
        return;
    }

    isLoadingData = true;
    currentServerPage = page;
    currentPageSize = pageSize;
    currentSearchText = search;

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

    // Server-side pagination parameters
    queryParams.append('page', page);
    queryParams.append('pageSize', pageSize);
    
    // Server-side search
    if (search) {
        queryParams.append('search', search);
    }

    // Show loading overlay
    concreteReceiptsGridApi.showLoadingOverlay();
    updatePaginationInfo('چاوەڕوان بە...');

    const startTime = performance.now();

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        const res = await fetch('../process/concrete_receipts/select_concrete_receipts.php?' + queryParams.toString(), {
            signal: controller.signal,
            cache: 'no-cache'
        });
        
        clearTimeout(timeoutId);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();
        const loadTime = ((performance.now() - startTime) / 1000).toFixed(2);
        console.log(`Data loaded in ${loadTime}s - Page ${page} of ${data.pagination?.totalPages || 1}`);

        if (data.success && data.data) {
            // Cache data for client-side search within current page
            cachedData = data.data;
            
            // Update pagination info
            totalRecords = data.pagination?.total || data.data.length;
            totalPages = data.pagination?.totalPages || 1;
            
            // Set row data
            concreteReceiptsGridApi.setGridOption('rowData', data.data);
            concreteReceiptsGridApi.hideOverlay();

            // Update summary cards
            if (data.summary) {
                $('#summary_total_receipts').text(data.summary.total_receipts || 0);
                $('#summary_total_meter').text(formatNumber(data.summary.total_meter || 0) + ' m³');
                $('#summary_total_customers').text(data.summary.total_customers || 0);
            }

            // Update pagination UI
            updatePaginationUI();
            
            // نیشاندانی پەیامی سێرچ
            showSearchMessage(search, totalRecords);
        } else {
            concreteReceiptsGridApi.setGridOption('rowData', []);
            concreteReceiptsGridApi.showNoRowsOverlay();
            totalRecords = 0;
            totalPages = 0;
            updatePaginationUI();
            
            // نیشاندانی پەیامی سێرچ
            showSearchMessage(search, 0);
        }
    } catch (error) {
        console.error('Error loading data:', error);
        concreteReceiptsGridApi.setGridOption('rowData', []);
        concreteReceiptsGridApi.showNoRowsOverlay();
        
        if (error.name === 'AbortError') {
            updatePaginationInfo('کاتی زیاد بوو - تکایە دووبارە هەوڵ بدەوە');
        } else {
            updatePaginationInfo('هەڵە لە بارکردن - تکایە دووبارە هەوڵ بدەوە');
        }
    } finally {
        isLoadingData = false;
    }
}

// Update pagination info text
function updatePaginationInfo(text) {
    const infoEl = document.getElementById('pagination-info');
    if (infoEl) {
        infoEl.textContent = text;
    }
}

// Update pagination UI
function updatePaginationUI() {
    const startRecord = ((currentServerPage - 1) * currentPageSize) + 1;
    const endRecord = Math.min(currentServerPage * currentPageSize, totalRecords);
    
    // Update info
    const infoText = totalRecords > 0 
        ? `نیشاندانی ${formatNumber(startRecord)} تا ${formatNumber(endRecord)} لە ${formatNumber(totalRecords)} ڕیکۆرد`
        : 'هیچ ڕیکۆردێک نەدۆزرایەوە';
    updatePaginationInfo(infoText);

    // Update buttons
    const prevBtn = document.getElementById('pagination-prev');
    const nextBtn = document.getElementById('pagination-next');
    const firstBtn = document.getElementById('pagination-first');
    const lastBtn = document.getElementById('pagination-last');
    const pageInput = document.getElementById('pagination-page-input');
    const totalPagesSpan = document.getElementById('pagination-total-pages');

    if (prevBtn) prevBtn.disabled = currentServerPage <= 1;
    if (nextBtn) nextBtn.disabled = currentServerPage >= totalPages;
    if (firstBtn) firstBtn.disabled = currentServerPage <= 1;
    if (lastBtn) lastBtn.disabled = currentServerPage >= totalPages;
    if (pageInput) pageInput.value = currentServerPage;
    if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
}

// Pagination navigation functions
function goToFirstPage() {
    if (currentServerPage > 1) {
        loadConcreteReceiptsGrid(1, currentPageSize, currentSearchText);
    }
}

function goToPrevPage() {
    if (currentServerPage > 1) {
        loadConcreteReceiptsGrid(currentServerPage - 1, currentPageSize, currentSearchText);
    }
}

function goToNextPage() {
    if (currentServerPage < totalPages) {
        loadConcreteReceiptsGrid(currentServerPage + 1, currentPageSize, currentSearchText);
    }
}

function goToLastPage() {
    if (currentServerPage < totalPages) {
        loadConcreteReceiptsGrid(totalPages, currentPageSize, currentSearchText);
    }
}

function goToPage(page) {
    const pageNum = parseInt(page);
    if (pageNum >= 1 && pageNum <= totalPages && pageNum !== currentServerPage) {
        loadConcreteReceiptsGrid(pageNum, currentPageSize, currentSearchText);
    }
}

function changePageSize(size) {
    const newSize = parseInt(size);
    if (newSize !== currentPageSize) {
        currentPageSize = newSize;
        loadConcreteReceiptsGrid(1, newSize, currentSearchText);
    }
}

// Server-side search - سێرچ لە هەموو داتابەیسەکەدا
function serverSearch(searchText) {
    currentSearchText = searchText.trim();
    // هەمیشە لە لاپەڕەی یەکەوە دەست پێدەکات بۆ ئەوەی هەموو ئەنجامەکان ببینێت
    loadConcreteReceiptsGrid(1, currentPageSize, currentSearchText);
}

// نیشاندانی پەیامی سێرچ
function showSearchMessage(searchText, totalFound) {
    let messageContainer = document.getElementById('search-result-message');
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'search-result-message';
        messageContainer.style.cssText = 'margin-bottom: 10px; padding: 10px 15px; border-radius: 6px; font-weight: 500;';
        const gridDiv = document.getElementById('concreteReceiptsGrid');
        if (gridDiv) {
            gridDiv.parentNode.insertBefore(messageContainer, gridDiv);
        }
    }
    
    if (searchText && searchText.trim()) {
        if (totalFound > 0) {
            messageContainer.innerHTML = `<i class="fas fa-check-circle text-success"></i> <strong>${formatNumber(totalFound)}</strong> ڕیکۆرد دۆزرایەوە بۆ "<strong>${searchText}</strong>" لە هەموو داتابەیسەکەدا`;
            messageContainer.style.background = '#d4edda';
            messageContainer.style.color = '#155724';
            messageContainer.style.border = '1px solid #c3e6cb';
        } else {
            messageContainer.innerHTML = `<i class="fas fa-exclamation-circle"></i> هیچ ڕیکۆردێک نەدۆزرایەوە بۆ "<strong>${searchText}</strong>" لە داتابەیسەکەدا`;
            messageContainer.style.background = '#fff3cd';
            messageContainer.style.color = '#856404';
            messageContainer.style.border = '1px solid #ffeeba';
        }
        messageContainer.style.display = 'block';
    } else {
        messageContainer.style.display = 'none';
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

// Create custom pagination HTML
function createPaginationHTML() {
    return `
    <div class="server-pagination-container" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 12px 16px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; margin-top: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="pagination-info-section" style="display: flex; align-items: center; gap: 12px;">
            <span id="pagination-info" style="font-weight: 600; color: #495057;">چاوەڕوان بە...</span>
        </div>
        
        <div class="pagination-controls" style="display: flex; align-items: center; gap: 8px;">
            <div class="page-size-selector" style="display: flex; align-items: center; gap: 6px;">
                <label style="font-weight: 500; color: #495057;">ژمارەی ڕیز:</label>
                <select id="pagination-page-size" class="form-select form-select-sm" style="width: auto; min-width: 80px;" onchange="changePageSize(this.value)">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
            
            <div class="pagination-buttons" style="display: flex; align-items: center; gap: 4px; margin-right: 12px;">
                <button id="pagination-first" class="btn btn-sm btn-outline-secondary" onclick="goToFirstPage()" title="یەکەم لاپەڕە" style="padding: 6px 10px;">
                    <i class="fas fa-angle-double-right"></i>
                </button>
                <button id="pagination-prev" class="btn btn-sm btn-outline-secondary" onclick="goToPrevPage()" title="لاپەڕەی پێشوو" style="padding: 6px 10px;">
                    <i class="fas fa-angle-right"></i>
                </button>
                
                <div class="page-input-group" style="display: flex; align-items: center; gap: 6px; margin: 0 8px;">
                    <input type="number" id="pagination-page-input" class="form-control form-control-sm" 
                           style="width: 60px; text-align: center;" 
                           value="1" min="1"
                           onchange="goToPage(this.value)"
                           onkeypress="if(event.key === 'Enter') goToPage(this.value)">
                    <span style="color: #6c757d;">لە</span>
                    <span id="pagination-total-pages" style="font-weight: 600; color: #495057;">1</span>
                </div>
                
                <button id="pagination-next" class="btn btn-sm btn-outline-secondary" onclick="goToNextPage()" title="لاپەڕەی دواتر" style="padding: 6px 10px;">
                    <i class="fas fa-angle-left"></i>
                </button>
                <button id="pagination-last" class="btn btn-sm btn-outline-secondary" onclick="goToLastPage()" title="کۆتا لاپەڕە" style="padding: 6px 10px;">
                    <i class="fas fa-angle-double-left"></i>
                </button>
            </div>
        </div>
    </div>
    `;
}

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.getElementById('concreteReceiptsGrid');
    if (!gridDiv) {
        console.error('Grid container not found!');
        return;
    }

    // Initialize grid
    concreteReceiptsGridApi = initAGGrid('concreteReceiptsGrid', concreteReceiptsColumnDefs, concreteReceiptsGridOptions);
    window.concreteReceiptsGridApi = concreteReceiptsGridApi;

    if (!concreteReceiptsGridApi) {
        console.error('Failed to initialize grid!');
        return;
    }

    // Add custom pagination UI after grid
    const paginationContainer = document.createElement('div');
    paginationContainer.id = 'custom-pagination-container';
    paginationContainer.innerHTML = createPaginationHTML();
    gridDiv.parentNode.insertBefore(paginationContainer, gridDiv.nextSibling);

    // Load initial data
    loadConcreteReceiptsGrid(1, 25, '');

    // Quick search functionality - Server-side search
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
window.reloadConcreteReceipts = function() {
    loadConcreteReceiptsGrid(1, currentPageSize, currentSearchText);
};

// Make functions globally accessible
window.loadConcreteReceiptsGrid = loadConcreteReceiptsGrid;
window.debouncedLoadConcreteReceiptsGrid = debounce(() => loadConcreteReceiptsGrid(currentServerPage, currentPageSize, currentSearchText), 300);
window.goToFirstPage = goToFirstPage;
window.goToPrevPage = goToPrevPage;
window.goToNextPage = goToNextPage;
window.goToLastPage = goToLastPage;
window.goToPage = goToPage;
window.changePageSize = changePageSize;

// Export grid data to CSV
window.exportConcreteReceiptsToCSV = function() {
    if (!concreteReceiptsGridApi) {
        console.error('Grid API not initialized!');
        return;
    }
    exportAGGridToCSV(concreteReceiptsGridApi, 'concrete_receipts.csv');
};
