// AG Grid Configuration for Other Expenses Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let otherExpensesGridApi;

// Format functions - بەکارهێنانی لە فایلی گشتی (بەبێ duplicate declaration)
// بەکارهێنانی window.AGGridFormatters بەبێ const declaration

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const otherExpensesColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        maxWidth: 200,
        flex: 0,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            const editBtn = `<button class='btn btn-warning btn-sm edit-expense' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`;
            const deleteBtn = `<button class='btn btn-danger btn-sm delete-expense' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`;
            return `${editBtn} ${deleteBtn}`.trim() || '-';
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
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (!params.value) return '-';
            return params.value;
        }
    },
    {
        field: 'remaining_usd',
        headerName: 'ماوە دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'remaining_iqd',
        headerName: 'ماوە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'exchange_rate',
        headerName: 'نرخی ١٠٠ دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) || params.value;
        },
        type: 'numericColumn'
    },
    {
        field: 'paid_usd',
        headerName: 'پارەی دراو دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'paid_iqd',
        headerName: 'پارەی دراو دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'amount_usd',
        headerName: 'بڕی دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'amount_iqd',
        headerName: 'بڕی دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'invoice_number',
        headerName: 'ژمارەی وەسڵ',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'currency_type',
        headerName: 'جۆری پارە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'دۆلار' ? '#28a745' : params.value === 'دینار' ? '#ffc107' : '#17a2b8';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'payment_type',
        headerName: 'جۆری مامەڵە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const color = params.value === 'نەقد' ? '#28a745' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'gas_total_cost',
        headerName: 'کۆی نرخی گازی بەکارهاتوو',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) || params.value;
        },
        type: 'numericColumn'
    },
    {
        field: 'gas_purchase_price_input',
        headerName: 'ئینپوتی نرخی کڕینی گاز',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'material_total_cost',
        headerName: 'کۆی نرخی کاڵای بەکارهاتوو',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) || params.value;
        },
        type: 'numericColumn'
    },
    {
        field: 'material_purchase_price_usd',
        headerName: 'نرخی کڕینی کاڵا بە دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'material_purchase_price_iqd',
        headerName: 'نرخی کڕینی کاڵا بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'material_quantity',
        headerName: 'بڕی عەدەدی کاڵا',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) || params.value;
        },
        type: 'numericColumn'
    },
    {
        field: 'material_name',
        headerName: 'کاڵا لە کۆگا',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'expense_type',
        headerName: 'جۆری خەرجی',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const colors = {
                'خەرجی تر': '#6c757d',
                'بەکارهێنانی کاڵای کۆگا': '#007bff',
                'بەکارهێنانی گاز': '#ffc107',
                'خواردنگە': '#28a745',
                'ئۆفیس': '#17a2b8'
            };
            const color = colors[params.value] || '#6c757d';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'gas_liters',
        headerName: 'بڕی گاز (لیتر)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        valueFormatter: function(params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return window.AGGridFormatters?.formatNumber(params.value) + ' L' || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'car_name',
        headerName: 'سەیارە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'employee_name',
        headerName: 'کارمەند',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'person_name',
        headerName: 'کەس',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    },
    {
        field: 'purpose',
        headerName: 'مەبەست',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function(params) {
            if (!params.value) return '-';
            const displayText = params.value.length > 50 ? params.value.substring(0, 50) + '...' : params.value;
            return `<span title="${params.value}">${displayText}</span>`;
        },
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    }
];

// Grid Options
const otherExpensesGridOptions = {
    columnDefs: otherExpensesColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
        minWidth: 100,
        flex: 1
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
        otherExpensesGridApi = params.api;
        loadOtherExpensesData();
    },
    onFirstDataRendered: function(params) {
        // Auto-size all columns except actions column to prevent extra space on the right
        const allColumnIds = params.api.getColumns()?.map(col => col.getColId()) || [];
        const columnsToAutoSize = allColumnIds.filter(colId => colId !== 'actions');
        if (columnsToAutoSize.length > 0) {
            params.api.autoSizeColumns(columnsToAutoSize);
        }
    }
};

// Load Other Expenses Data
function loadOtherExpensesData(preservePagination = false) {
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && otherExpensesGridApi) {
        currentPage = otherExpensesGridApi.paginationGetCurrentPage() || 0;
        pageSize = otherExpensesGridApi.paginationGetPageSize() || 25;
    }
    
    // Show loading
    otherExpensesGridApi?.showLoadingOverlay();
    
    // Build URL with filters
    let url = '../process/other_expenses/select_expenses.php?ag_grid=1';
    if (window.currentFilters && window.currentFilters.length > 0) {
        url += '&' + window.currentFilters;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Handle both array format and object format
            let expenses = [];
            if (Array.isArray(data)) {
                expenses = data;
            } else if (data.success && Array.isArray(data.expenses)) {
                expenses = data.expenses;
            } else if (data.success && Array.isArray(data.data)) {
                expenses = data.data;
            }
            
            if (expenses && expenses.length > 0) {
                // Store data globally for calculations
                window.otherExpensesData = expenses;
                
                // Update summary cards
                updateSummaryCards(expenses);
                
                // Set row data
                otherExpensesGridApi.setGridOption('rowData', expenses);
                otherExpensesGridApi.hideOverlay();
                
                // Restore pagination state if preserving
                if (preservePagination && otherExpensesGridApi) {
                    setTimeout(() => {
                        otherExpensesGridApi.paginationGoToPage(currentPage);
                        otherExpensesGridApi.paginationSetPageSize(pageSize);
                    }, 100);
                }
            } else {
                otherExpensesGridApi.setGridOption('rowData', []);
                otherExpensesGridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading other expenses:', error);
            otherExpensesGridApi.setGridOption('rowData', []);
            otherExpensesGridApi.showNoRowsOverlay();
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        });
}

// Update Summary Cards
function updateSummaryCards(expenses) {
    try {
        // Get USD rate
        let usdRate = 139250;
        const exchangeRateInput = document.getElementById('exchange_rate');
        if (exchangeRateInput && exchangeRateInput.value) {
            usdRate = parseFloat(exchangeRateInput.value);
        }
        
        function iqdToUsd(iqd) {
            return usdRate && iqd ? (parseFloat(iqd) / (usdRate / 100)) : 0;
        }
        
        // Calculate totals
        let totalCarMaterialCostIQD = 0, totalCarMaterialCostUSD = 0, totalCarGasCost = 0;
        let totalOtherExpensesIQD = 0, totalOtherExpensesUSD = 0;
        
        expenses.forEach(row => {
            if (row.car_id && row.expense_type === 'بەکارهێنانی کاڵای کۆگا') {
                totalCarMaterialCostIQD += parseFloat(row.material_purchase_price_iqd || 0) * parseFloat(row.material_quantity || 0);
                totalCarMaterialCostUSD += parseFloat(row.material_purchase_price_usd || 0) * parseFloat(row.material_quantity || 0);
            }
            
            if (row.car_id && row.expense_type === 'بەکارهێنانی گاز') {
                totalCarGasCost += parseFloat(row.gas_total_cost || 0);
            }
            
            if (!row.car_id || (row.expense_type !== 'بەکارهێنانی کاڵای کۆگا' && row.expense_type !== 'بەکارهێنانی گاز')) {
                if (row.currency_type === 'دۆلار') {
                    totalOtherExpensesUSD += parseFloat(row.amount_usd || 0);
                } else if (row.currency_type === 'دینار') {
                    totalOtherExpensesIQD += parseFloat(row.amount_iqd || 0);
                } else if (row.currency_type === 'تێکەڵ') {
                    totalOtherExpensesUSD += parseFloat(row.amount_usd || 0);
                    totalOtherExpensesIQD += parseFloat(row.amount_iqd || 0);
                } else {
                    totalOtherExpensesUSD += parseFloat(row.amount_usd || 0);
                    totalOtherExpensesIQD += parseFloat(row.amount_iqd || 0);
                }
            }
        });
        
        const totalCarMaterialCostUSDConverted = totalCarMaterialCostIQD / (usdRate / 100) + totalCarMaterialCostUSD;
        const totalCarGasCostUSD = totalCarGasCost / (usdRate / 100);
        const totalOtherExpensesUSDConverted = totalOtherExpensesIQD / (usdRate / 100) + totalOtherExpensesUSD;
        const totalCarExpensesUSD = totalCarMaterialCostUSDConverted + totalCarGasCostUSD;
        const totalAllExpensesUSD = totalOtherExpensesUSDConverted + totalCarExpensesUSD;
        
        // Calculate total IQD and USD expenses
        const totalExpensesIQD = totalCarMaterialCostIQD + totalCarGasCost + totalOtherExpensesIQD;
        const totalExpensesUSD = totalCarMaterialCostUSD + totalOtherExpensesUSD;
        
        function formatNumber(num) {
            return Number(num).toLocaleString('en-US');
        }
        
        function formatUSD(num) {
            return num ? `$${formatNumber(num)}` : '$0';
        }
        
        function formatIQD(num) {
            return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
        }
        
        if (document.getElementById('totalCarMaterialCost')) {
            document.getElementById('totalCarMaterialCost').innerHTML = `${formatUSD(totalCarMaterialCostUSDConverted)}`;
        }
        if (document.getElementById('totalCarGasCost')) {
            document.getElementById('totalCarGasCost').innerHTML = `${formatUSD(totalCarGasCostUSD)}`;
        }
        if (document.getElementById('totalOtherExpenses')) {
            document.getElementById('totalOtherExpenses').innerHTML = `${formatUSD(totalOtherExpensesUSDConverted)}`;
        }
        if (document.getElementById('totalCarExpenses')) {
            document.getElementById('totalCarExpenses').innerHTML = `${formatUSD(totalAllExpensesUSD)}`;
        }
        if (document.getElementById('totalExpensesIQD')) {
            document.getElementById('totalExpensesIQD').innerHTML = `${formatIQD(totalExpensesIQD)}`;
        }
        if (document.getElementById('totalExpensesUSD')) {
            document.getElementById('totalExpensesUSD').innerHTML = `${formatUSD(totalExpensesUSD)}`;
        }
        if (document.getElementById('usdExchangeRate')) {
            document.getElementById('usdExchangeRate').innerHTML = `${formatNumber(usdRate)} د.ع`;
        }
    } catch (error) {
        console.error('Error updating summary cards:', error);
    }
}

// Reload function - preserve pagination
window.reloadOtherExpenses = function() {
    loadOtherExpensesData(true);
};

// Export function is handled in export_functions.js with AG Grid priority

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#otherExpensesGrid');
    if (gridDiv) {
        // Use createGrid for AG Grid v31+
        otherExpensesGridApi = agGrid.createGrid(gridDiv, otherExpensesGridOptions);
    }
});

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-expense', function() {
    const expenseId = $(this).data('id');
    if (typeof openEditModalById === 'function') {
        openEditModalById(expenseId);
    }
});

$(document).on('click', '.delete-expense', function() {
    const expenseId = $(this).data('id');
    if (typeof deleteExpense === 'function') {
        deleteExpense(expenseId);
    }
});
