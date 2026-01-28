// AG Grid Configuration for Service Receipts Table
let serviceReceiptsGridApi;
window.serviceReceiptsGridApi = null;

// Current pagination state
let currentServerPage = 1;
let currentPageSize = 25;
let totalRecords = 0;
let totalPages = 0;
let currentSearchText = '';
let cachedData = [];

// Column Definitions
const serviceReceiptsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        maxWidth: 200,
        flex: 0,
        pinned: 'left',
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function (params) {
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
        field: 'remaining_balance',
        headerName: 'بڕی ماوە',
        sortable: true,
        resizable: true,
        minWidth: 110,
        cellStyle: function (params) {
            const balance = parseFloat(params.value || 0);
            if (balance > 0.01) {
                return { textAlign: 'center', color: '#dc3545', fontWeight: 'bold' }; // Red for balance
            } else if (balance < -0.01) {
                return { textAlign: 'center', color: '#198754', fontWeight: 'bold' }; // Green for overpaid
            }
            return { textAlign: 'center', color: '#6c757d' };
        },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '0.00';
            const val = parseFloat(params.value);
            if (isNaN(val)) return '0.00';
            return '$ ' + window.AGGridFormatters?.formatNumber(val.toFixed(2));
        }
    },
    {
        field: 'payment_type',
        headerName: 'پارەدان',
        sortable: true,
        resizable: true,
        minWidth: 90,
        cellStyle: { textAlign: 'center' },
        cellRenderer: function (params) {
            if (params.value === 'cash') {
                return '<span class="badge bg-success">نەقد</span>';
            } else {
                return '<span class="badge bg-warning text-dark">قەرز</span>';
            }
        }
    },
    {
        field: 'total_price',
        headerName: 'کۆی پارە',
        sortable: true,
        resizable: true,
        minWidth: 110,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            const val = parseFloat(params.value);
            return '$ ' + window.AGGridFormatters?.formatNumber(val.toFixed(2));
        }
    },
    {
        field: 'price_per_meter',
        headerName: 'نرخی مەتر',
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            const val = parseFloat(params.value);
            return '$ ' + window.AGGridFormatters?.formatNumber(val.toFixed(2));
        }
    },
    {
        field: 'meter_amount',
        headerName: 'بڕی مەتر',
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            const val = parseFloat(params.value);
            return window.AGGridFormatters?.formatNumber(val.toFixed(2)) + ' m³';
        },
        type: 'numericColumn'
    },
    {
        field: 'mixer_driver_name',
        headerName: 'شۆفێری میکسەر',
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'mixer_car_name',
        headerName: 'میکسەر',
        sortable: true,
        resizable: true,
        minWidth: 90,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'pump_driver_name',
        headerName: 'شۆفێری پەمپ',
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'pump_car_name',
        headerName: 'پەمپ',
        sortable: true,
        resizable: true,
        minWidth: 90,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'created_at',
        headerName: 'بەروار',
        sortable: true,
        resizable: true,
        minWidth: 140,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) {
            if (!params.value) return '-';
            const d = new Date(params.value);
            if (isNaN(d)) return params.value;
            return d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0') + ' ' +
                String(d.getHours()).padStart(2, '0') + ':' +
                String(d.getMinutes()).padStart(2, '0');
        }
    },
    {
        field: 'receiver_name',
        headerName: 'وەرگر',
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'customer_name',
        headerName: 'کڕیار',
        sortable: true,
        resizable: true,
        minWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function (params) { return params.value || '-'; }
    },
    {
        field: 'receipt_number',
        headerName: 'ژم.پسووڵە',
        sortable: true,
        resizable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const isDuplicate = params.data && params.data.is_duplicate;
            const warningIcon = isDuplicate ? '<i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-left: 4px;" title="ژمارەی پسوڵە دووبارەیە"></i>' : '';
            return warningIcon + (params.value || '-');
        }
    }
];

// Grid Options
const serviceReceiptsGridOptions = {
    columnDefs: serviceReceiptsColumnDefs,
    rowData: [],
    defaultColDef: {
        sortable: true,
        filter: false,
        resizable: true,
        floatingFilter: false,
        minWidth: 100
    },
    pagination: false,
    animateRows: true,
    rowSelection: 'multiple',
    suppressRowClickSelection: true,
    enableCellTextSelection: true,
    ensureDomOrder: true,
    localeText: {
        noRowsToShow: 'هیچ داتایەک نەدۆزرایەوە',
        loadingOoo: 'چاوەڕوان بە...',
        filterOoo: 'فلتەر...',
    }
};

let isLoadingData = false;

// Format number helper
function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '0.00';
    const num = parseFloat(n);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Load data from server with pagination
async function loadServiceReceiptsGrid(page = 1, pageSize = 25, search = '') {
    if (!serviceReceiptsGridApi || isLoadingData) {
        return;
    }

    isLoadingData = true;
    currentServerPage = page;
    currentPageSize = pageSize;
    currentSearchText = search;

    const filters = {
        customer_id: $('#filter_customer_id').val() || '',
        location: $('#filter_location').val() || '',
        date_from: $('#filter_date_from').val() || '',
        date_to: $('#filter_date_to').val() || ''
    };

    const queryParams = new URLSearchParams();
    Object.keys(filters).forEach(key => {
        if (filters[key]) {
            queryParams.append(key, filters[key]);
        }
    });

    queryParams.append('page', page);
    queryParams.append('pageSize', pageSize);

    if (search) {
        queryParams.append('search', search);
    }

    serviceReceiptsGridApi.showLoadingOverlay();
    updatePaginationInfo('چاوەڕوان بە...');

    try {
        const res = await fetch('../process/service_receipts/select_service_receipts.php?' + queryParams.toString(), {
            cache: 'no-cache'
        });

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (data.success && data.data) {
            cachedData = data.data;
            totalRecords = data.pagination?.total || data.data.length;
            totalPages = data.pagination?.totalPages || 1;

            serviceReceiptsGridApi.setGridOption('rowData', data.data);
            serviceReceiptsGridApi.hideOverlay();

            // Update summary cards
            if (data.summary) {
                $('#summary_total_receipts').text(data.summary.total_receipts || 0);
                $('#summary_total_meter').text(formatNumber(data.summary.total_meter || 0) + ' m³');
                $('#summary_total_price').text('$' + formatNumber(data.summary.total_price || 0));
            }

            updatePaginationUI();
            showSearchMessage(search, totalRecords);
        } else {
            serviceReceiptsGridApi.setGridOption('rowData', []);
            serviceReceiptsGridApi.showNoRowsOverlay();
            totalRecords = 0;
            totalPages = 0;
            updatePaginationUI();
            showSearchMessage(search, 0);
        }
    } catch (error) {
        console.error('Error loading data:', error);
        serviceReceiptsGridApi.setGridOption('rowData', []);
        serviceReceiptsGridApi.showNoRowsOverlay();
        updatePaginationInfo('هەڵە لە بارکردن - تکایە دووبارە هەوڵ بدەوە');
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

    const infoText = totalRecords > 0
        ? `نیشاندانی ${formatNumber(startRecord)} تا ${formatNumber(endRecord)} لە ${formatNumber(totalRecords)} ڕیکۆرد`
        : 'هیچ ڕیکۆردێک نەدۆزرایەوە';
    updatePaginationInfo(infoText);

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
        loadServiceReceiptsGrid(1, currentPageSize, currentSearchText);
    }
}

function goToPrevPage() {
    if (currentServerPage > 1) {
        loadServiceReceiptsGrid(currentServerPage - 1, currentPageSize, currentSearchText);
    }
}

function goToNextPage() {
    if (currentServerPage < totalPages) {
        loadServiceReceiptsGrid(currentServerPage + 1, currentPageSize, currentSearchText);
    }
}

function goToLastPage() {
    if (currentServerPage < totalPages) {
        loadServiceReceiptsGrid(totalPages, currentPageSize, currentSearchText);
    }
}

function goToPage(page) {
    const pageNum = parseInt(page);
    if (pageNum >= 1 && pageNum <= totalPages && pageNum !== currentServerPage) {
        loadServiceReceiptsGrid(pageNum, currentPageSize, currentSearchText);
    }
}

function changePageSize(size) {
    const newSize = parseInt(size);
    if (newSize !== currentPageSize) {
        currentPageSize = newSize;
        loadServiceReceiptsGrid(1, newSize, currentSearchText);
    }
}

// Server-side search
function serverSearch(searchText) {
    currentSearchText = searchText.trim();
    loadServiceReceiptsGrid(1, currentPageSize, currentSearchText);
}

// Show search result message
function showSearchMessage(searchText, totalFound) {
    let messageContainer = document.getElementById('search-result-message');
    if (!messageContainer) {
        messageContainer = document.createElement('div');
        messageContainer.id = 'search-result-message';
        messageContainer.style.cssText = 'margin-bottom: 10px; padding: 10px 15px; border-radius: 6px; font-weight: 500;';
        const gridDiv = document.getElementById('serviceReceiptsGrid');
        if (gridDiv) {
            gridDiv.parentNode.insertBefore(messageContainer, gridDiv);
        }
    }

    if (searchText && searchText.trim()) {
        if (totalFound > 0) {
            messageContainer.innerHTML = `<i class="fas fa-check-circle text-success"></i> <strong>${totalFound}</strong> ڕیکۆرد dۆزرایەوە بۆ "<strong>${searchText}</strong>" لە هەموو داتابەیسەکەدا`;
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

const debouncedServerSearch = debounce(serverSearch, 500);

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
document.addEventListener('DOMContentLoaded', function () {
    const gridDiv = document.getElementById('serviceReceiptsGrid');
    if (!gridDiv) {
        console.error('Grid container not found!');
        return;
    }

    // Initialize grid
    serviceReceiptsGridApi = initAGGrid('serviceReceiptsGrid', serviceReceiptsColumnDefs, serviceReceiptsGridOptions);
    window.serviceReceiptsGridApi = serviceReceiptsGridApi;

    if (!serviceReceiptsGridApi) {
        console.error('Failed to initialize grid!');
        return;
    }

    // Add custom pagination UI
    const paginationContainer = document.createElement('div');
    paginationContainer.id = 'custom-pagination-container';
    paginationContainer.innerHTML = createPaginationHTML();
    gridDiv.parentNode.insertBefore(paginationContainer, gridDiv.nextSibling);

    // Load initial data
    loadServiceReceiptsGrid(1, 25, '');

    // Quick search
    const quickSearchInput = document.getElementById('quickSearchInput');
    const clearQuickSearchBtn = document.getElementById('clearQuickSearch');

    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function () {
            debouncedServerSearch(this.value);
        });

        if (clearQuickSearchBtn) {
            clearQuickSearchBtn.addEventListener('click', function () {
                quickSearchInput.value = '';
                serverSearch('');
            });
        }

        quickSearchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                serverSearch(this.value);
            }
        });
    }
});

window.reloadServiceReceipts = function () {
    loadServiceReceiptsGrid(1, currentPageSize, currentSearchText);
};
