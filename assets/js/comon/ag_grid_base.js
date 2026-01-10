/**
 * AG Grid Base Configuration - بۆ بەکارهێنانی لە هەموو پەیجەکاندا
 * 
 * بەکارهێنان:
 * 1. لە HTML دا: <div id="myGrid" class="ag-grid-container ag-theme-alpine"></div>
 * 2. لە JS دا: const grid = initAGGrid('myGrid', columnDefs, options);
 */

// Format functions - بۆ بەکارهێنانی لە هەموو پەیجەکاندا
window.AGGridFormatters = {
    formatNumber: function(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    
    formatUSD: function(n) {
        if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
        return window.AGGridFormatters.formatNumber(Number(n).toFixed(2)) + ' $';
    },
    
    formatIQD: function(n) {
        if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
        return window.AGGridFormatters.formatNumber(Number(n).toFixed(0)) + ' د.ع';
    },
    
    formatDate: function(dateStr) {
        if (!dateStr) return '-';
        return dateStr;
    },
    
    truncateText: function(text, maxLength = 40) {
        if (!text) return '-';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
};

// Default Grid Options - دەتوانرێت override بکرێت
window.AGGridDefaults = {
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
        autoHeight: true,
        wrapText: true,
        flex: 1,
        minWidth: 100
    },
    autoSizeStrategy: {
        type: 'fitCellContents'
    },
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
    // sideBar removed - requires Enterprise version
    // suppressSizeToFit removed - not valid in AG Grid v31
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

/**
 * Initialize AG Grid
 * @param {string} gridId - ID ی div ی grid
 * @param {Array} columnDefs - لیستی ستونەکان
 * @param {Object} customOptions - گزینه‌های سفارشی (اختیاری)
 * @returns {Object} - gridApi و gridColumnApi
 */
function initAGGrid(gridId, columnDefs, customOptions = {}) {
    const gridDiv = document.getElementById(gridId);
    if (!gridDiv) {
        console.error(`Grid container with ID "${gridId}" not found!`);
        return null;
    }
    
    // Merge default options with custom options
    const gridOptions = {
        ...window.AGGridDefaults,
        columnDefs: columnDefs,
        rowData: [],
        ...customOptions,
        // Merge nested objects
        defaultColDef: {
            ...window.AGGridDefaults.defaultColDef,
            ...(customOptions.defaultColDef || {})
        },
        localeText: {
            ...window.AGGridDefaults.localeText,
            ...(customOptions.localeText || {})
        }
    };
    
    // Add default onGridReady if not provided
    if (!gridOptions.onGridReady) {
        gridOptions.onGridReady = function(params) {
            if (customOptions.onGridReady) {
                customOptions.onGridReady(params);
            }
        };
    }
    
    // Add default onFirstDataRendered if not provided
    if (!gridOptions.onFirstDataRendered) {
        gridOptions.onFirstDataRendered = function(params) {
            // Auto-size columns based on content
            const allColumnIds = params.columnApi.getColumns().map(col => col.getId());
            params.columnApi.autoSizeColumns(allColumnIds, false);
            
            if (customOptions.onFirstDataRendered) {
                customOptions.onFirstDataRendered(params);
            }
        };
    }
    
    // Initialize grid
    const grid = new agGrid.Grid(gridDiv, gridOptions);
    
    return {
        grid: grid,
        api: gridOptions.api,
        columnApi: gridOptions.columnApi,
        gridOptions: gridOptions
    };
}

/**
 * Load data into grid with pagination preservation
 * @param {Object} gridApi - AG Grid API
 * @param {string} url - URL ی API
 * @param {Function} dataTransformer - فەنکشنی گۆڕینی داتا (optional)
 * @param {boolean} preservePagination - پاراستنی pagination state
 */
function loadAGGridData(gridApi, url, dataTransformer = null, preservePagination = false) {
    if (!gridApi) {
        console.error('Grid API not provided!');
        return;
    }
    
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination) {
        currentPage = gridApi.paginationGetCurrentPage() || 0;
        pageSize = gridApi.paginationGetPageSize() || 25;
    }
    
    // Show loading
    gridApi.showLoadingOverlay();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data if transformer function provided
                let rowData = dataTransformer ? dataTransformer(data.data) : data.data;
                
                gridApi.setGridOption('rowData', rowData);
                gridApi.hideOverlay();
                
                // Restore pagination state if preserving
                if (preservePagination) {
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
            console.error('Error loading data:', error);
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

/**
 * Export grid data to Excel
 * @param {Object} gridApi - AG Grid API
 * @param {string} fileName - ناوی فایل
 * @param {string} sheetName - ناوی وەرەق
 */
function exportAGGridToExcel(gridApi, fileName, sheetName = 'Sheet1') {
    if (!gridApi) {
        console.error('Grid API not provided!');
        return;
    }
    
    const params = {
        fileName: fileName,
        sheetName: sheetName
    };
    
    gridApi.exportDataAsExcel(params);
}
