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
        cellRenderer: function (params) {
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
        valueFormatter: function (params) {
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
        valueFormatter: function (params) {
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
        valueFormatter: function (params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
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
        valueFormatter: function (params) {
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
        valueFormatter: function (params) {
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
        valueFormatter: function (params) {
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
        valueFormatter: function (params) {
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
        tooltipValueGetter: function (params) {
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
        cellRenderer: function (params) {
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
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const color = params.value === 'نەقد' ? '#28a745' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
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
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const colors = {
                'خەرجی تر': '#6c757d',

                'خواردنگە': '#28a745',
                'ئۆفیس': '#17a2b8'
            };
            const color = colors[params.value] || '#6c757d';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
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
        tooltipValueGetter: function (params) {
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
        tooltipValueGetter: function (params) {
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
        tooltipValueGetter: function (params) {
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
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const displayText = params.value.length > 50 ? params.value.substring(0, 50) + '...' : params.value;
            return `<span title="${params.value}">${displayText}</span>`;
        },
        tooltipValueGetter: function (params) {
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
    onGridReady: function (params) {
        otherExpensesGridApi = params.api;
        loadOtherExpensesData();
    },
    onFirstDataRendered: function (params) {
        // Auto-size all columns except actions column to prevent extra space on the right
        const allColumnIds = params.api.getColumns()?.map(col => col.getColId()) || [];
        const columnsToAutoSize = allColumnIds.filter(colId => colId !== 'actions');
        if (columnsToAutoSize.length > 0) {
            params.api.autoSizeColumns(columnsToAutoSize);
        }
    }
};

// Store current row ID for restoration after update
let currentEditingRowId = null;

// Load Other Expenses Data
function loadOtherExpensesData(preservePagination = false, restoreRowId = null) {
    // Save current pagination state and filters
    let currentPage = 0;
    let pageSize = 25;
    let savedFilters = null;
    if (preservePagination && otherExpensesGridApi) {
        currentPage = otherExpensesGridApi.paginationGetCurrentPage() || 0;
        pageSize = otherExpensesGridApi.paginationGetPageSize() || 25;
        // Save current filter model
        try {
            savedFilters = otherExpensesGridApi.getFilterModel();
        } catch (e) {
            console.warn('Could not get filter model:', e);
        }
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

                // Restore pagination state and filters if preserving
                if (preservePagination && otherExpensesGridApi) {
                    setTimeout(() => {
                        // Restore filters first
                        if (savedFilters && Object.keys(savedFilters).length > 0) {
                            try {
                                otherExpensesGridApi.setFilterModel(savedFilters);
                            } catch (e) {
                                console.warn('Could not restore filter model:', e);
                            }
                        }
                        // Restore pagination
                        otherExpensesGridApi.paginationGoToPage(currentPage);
                        otherExpensesGridApi.setGridOption('paginationPageSize', pageSize);

                        // Restore selected row if provided
                        if (restoreRowId) {
                            setTimeout(() => {
                                // Find row by data id
                                let foundNode = null;
                                otherExpensesGridApi.forEachNode((node) => {
                                    if (node.data && String(node.data.id) === String(restoreRowId)) {
                                        foundNode = node;
                                    }
                                });

                                if (foundNode) {
                                    // Calculate which page the row is on
                                    const rowIndex = foundNode.rowIndex;
                                    const currentPageSize = otherExpensesGridApi.paginationGetPageSize();
                                    const targetPage = Math.floor(rowIndex / currentPageSize);

                                    // Go to the page containing the row
                                    otherExpensesGridApi.paginationGoToPage(targetPage);

                                    // After page change, scroll to the row
                                    setTimeout(() => {
                                        foundNode.setSelected(true);
                                        otherExpensesGridApi.ensureNodeVisible(foundNode, 'middle');
                                    }, 150);
                                }
                            }, 200);
                        }
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
        let totalOtherExpensesIQD = 0, totalOtherExpensesUSD = 0;

        expenses.forEach(row => {
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
        });

        const totalOtherExpensesUSDConverted = totalOtherExpensesIQD / (usdRate / 100) + totalOtherExpensesUSD;
        const totalAllExpensesUSD = totalOtherExpensesUSDConverted;

        // Calculate total IQD and USD expenses
        const totalExpensesIQD = totalOtherExpensesIQD;
        const totalExpensesUSD = totalOtherExpensesUSD;

        function formatNumber(num) {
            return Number(num).toLocaleString('en-US');
        }

        function formatUSD(num) {
            return num ? `$${formatNumber(num)}` : '$0';
        }

        function formatIQD(num) {
            return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
        }

        if (document.getElementById('totalExpensesIQD')) {
            document.getElementById('totalExpensesIQD').innerHTML = `${formatIQD(totalExpensesIQD)}`;
        }
        if (document.getElementById('totalExpensesUSD')) {
            document.getElementById('totalExpensesUSD').innerHTML = `${formatUSD(totalExpensesUSD)}`;
        }
    } catch (error) {
        console.error('Error updating summary cards:', error);
    }
}

// Reload function - preserve pagination and restore row
window.reloadOtherExpenses = function (restoreRowId = null) {
    loadOtherExpensesData(true, restoreRowId);
};

// Export function is handled in export_functions.js with AG Grid priority

// Initialize Grid
document.addEventListener('DOMContentLoaded', function () {
    const gridDiv = document.querySelector('#otherExpensesGrid');
    if (gridDiv) {
        // Use createGrid for AG Grid v31+
        otherExpensesGridApi = agGrid.createGrid(gridDiv, otherExpensesGridOptions);
    }
});

async function populateSelect(url, selectId, selectedId) {
    try {
        // console.log(`[populateSelect] Initializing for #${selectId} using URL: ${url}`);
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const result = await res.json();
        // console.log(`[populateSelect] Received data for #${selectId}:`, result);

        let data = [];
        if (Array.isArray(result)) {
            data = result;
        } else if (result && result.success && Array.isArray(result.data)) {
            data = result.data;
        } else if (result && result.success && Array.isArray(result.materials)) {
            data = result.materials;
        } else if (result && typeof result === 'object' && !Array.isArray(result)) {
            const keys = Object.keys(result);
            if (keys.length > 0 && !isNaN(keys[0])) {
                data = Object.values(result);
            }
        }

        const select = document.getElementById(selectId);
        if (!select) {
            console.error(`[populateSelect] Element #${selectId} NOT FOUND in DOM`);
            return;
        }

        select.innerHTML = '';
        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = '-- هەلبژێرە --';
        select.appendChild(placeholderOpt);

        if (!data || data.length === 0) {
            console.warn(`[populateSelect] No data items found for #${selectId}`);
        } else {
            data.forEach(item => {
                if (item && (item.id !== undefined || item.value !== undefined)) {
                    const opt = document.createElement('option');
                    opt.value = item.id || item.value;
                    opt.textContent = item.name || item.text || item.label;
                    if (selectedId && String(opt.value) === String(selectedId)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                }
            });
        }

        // console.log(`[populateSelect] Successfully added ${data ? data.length : 0} options to #${selectId}`);

        if ($(select).hasClass('select2-hidden-accessible')) {
            // console.log(`[populateSelect] Triggering Select2 update for #${selectId}`);
            $(select).trigger('change');
        }

        // console.log(`[populateSelect] Dispatching native change event for #${selectId}`);
        select.dispatchEvent(new Event('change'));
    } catch (err) {
        // console.error(`[populateSelect] SEVERE ERROR for #${selectId}:`, err);
    }
}

// Open edit modal by ID
window.openEditModalById = async function (id) {
    try {
        // Store the row ID for restoration after update
        currentEditingRowId = id;

        const dataSource = window.otherExpensesData || [];
        const row = dataSource.find(r => String(r.id) === String(id));
        if (!row) {
            // console.error('openEditModalById: row not found', { id });
            return;
        }

        await populateSelect('../process/other_expenses/select_persons.php', 'edit_person_id', row.person_id);
        await populateSelect('../process/other_expenses/select_employees.php', 'edit_employee_id', row.employee_id);
        await populateSelect('../process/other_expenses/select_cars.php', 'edit_car_id', row.car_id);

        document.getElementById('edit_id').value = row.id;
        document.getElementById('edit_purpose').value = row.purpose || '';
        document.getElementById('edit_payment_type').value = row.payment_type || 'نەقد';
        document.getElementById('edit_currency_type').value = row.currency_type || 'دینار';
        document.getElementById('edit_invoice_number').value = row.invoice_number || '';
        document.getElementById('edit_amount_iqd').value = row.amount_iqd || 0;
        document.getElementById('edit_amount_usd').value = row.amount_usd || 0;
        document.getElementById('edit_paid_iqd').value = row.paid_iqd || 0;
        document.getElementById('edit_paid_usd').value = row.paid_usd || 0;
        document.getElementById('edit_exchange_rate').value = row.exchange_rate || 139250;
        document.getElementById('edit_remaining_iqd').value = row.remaining_iqd || 0;
        document.getElementById('edit_remaining_usd').value = row.remaining_usd || 0;
        if (document.getElementById('edit_expense_type')) {
            document.getElementById('edit_expense_type').value = row.expense_type || '';
            const event = new Event('change');
            document.getElementById('edit_expense_type').dispatchEvent(event);
        }

        document.getElementById('edit_date').value = row.date || '';

        if (typeof applyOtherExpenseCurrencyFields === 'function') {
            applyOtherExpenseCurrencyFields('edit');
        }
        if (typeof window.updateEditRemaining === 'function') {
            window.updateEditRemaining();
        }

        const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        modal.show();

        if (typeof setupEditExpenseModal === 'function') setupEditExpenseModal();
    } catch (error) {
        // console.error('openEditModalById failed', error);
    }
};

// Event delegation for edit and delete buttons
$(document).on('click', '.edit-expense', function () {
    const expenseId = $(this).data('id');
    if (typeof openEditModalById === 'function') {
        openEditModalById(expenseId);
    }
});

$(document).on('click', '.delete-expense', function () {
    const expenseId = $(this).data('id');
    if (typeof deleteExpense === 'function') {
        deleteExpense(expenseId);
    }
});
