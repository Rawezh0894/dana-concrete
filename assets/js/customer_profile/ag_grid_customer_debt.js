// AG Grid Configuration for Customer Debt Payments Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let customerDebtGridApi;

// Format functions - بەکارهێنانی لە فایلی گشتی (بەبێ duplicate declaration)
// بەکارهێنانی window.AGGridFormatters بەبێ const declaration

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const customerDebtColumnDefs = [
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
            const editBtn = `<button class='btn btn-warning btn-sm edit-return-debt' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`;
            const deleteBtn = `<button class='btn btn-danger btn-sm delete-return-debt' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`;
            const printBtn = `<button class='btn btn-success btn-sm print-debt-receipt' data-id='${params.data.id}' title='پرێنت' style='margin: 2px;'><i class='fa fa-print'></i></button>`;
            return `${editBtn} ${deleteBtn} ${printBtn}`.trim() || '-';
        }
    },
    {
        field: 'note',
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
        field: 'discount',
        headerName: 'داشکاندن',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#28a745' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'paid_iqd',
        headerName: 'بڕی داوە (IQD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'change_back_usd',
        headerName: 'باقی بە دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#dc3545' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'change_back_iq',
        headerName: 'باقی بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', color: '#dc3545' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatIQD(params.value) || '-';
        },
        type: 'numericColumn'
    },
    {
        field: 'paid_usd',
        headerName: 'بڕی داوە (USD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function(params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        },
        type: 'numericColumn'
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
            return window.AGGridFormatters?.formatNumber(params.value) || params.value;
        },
        type: 'numericColumn'
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
    }
];

// Grid Options
const customerDebtGridOptions = {
    columnDefs: customerDebtColumnDefs,
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
        customerDebtGridApi = params.api;
        loadCustomerDebtData();
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

// Load Customer Debt Payments Data
function loadCustomerDebtData(preservePagination = false) {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID || CUSTOMER_ID <= 0) {
        console.error('Invalid CUSTOMER_ID for loading debt payments:', CUSTOMER_ID);
        return;
    }
    
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && customerDebtGridApi) {
        currentPage = customerDebtGridApi.paginationGetCurrentPage() || 0;
        pageSize = customerDebtGridApi.paginationGetPageSize() || 25;
    }
    
    // Show loading
    customerDebtGridApi?.showLoadingOverlay();
    
    fetch(`../process/customer_profile/select_return_debt.php?customer_id=${CUSTOMER_ID}`)
        .then(response => response.json())
        .then(data => {
            // Handle both array format and object format
            let debtPayments = [];
            if (Array.isArray(data)) {
                debtPayments = data;
            } else if (data && Array.isArray(data.data)) {
                debtPayments = data.data;
            } else if (data && data.success && Array.isArray(data.data)) {
                debtPayments = data.data;
            }
            
            if (debtPayments.length >= 0) {
                // Transform data for AG Grid
                const rowData = debtPayments.map(row => ({
                    id: row.id,
                    date: row.date || '-',
                    dolar_rate: row.dolar_rate || 0,
                    paid_usd: row.paid_usd || 0,
                    paid_iqd: row.paid_iqd || 0,
                    discount: row.discount || 0,
                    change_back_usd: row.change_back_usd || 0,
                    change_back_iq: row.change_back_iq || 0,
                    note: row.note || '-',
                    payment_type: row.payment_type || '-'
                }));
                
                customerDebtGridApi.setGridOption('rowData', rowData);
                customerDebtGridApi.hideOverlay();
                
                // Restore pagination state if preserving
                if (preservePagination && customerDebtGridApi) {
                    setTimeout(() => {
                        customerDebtGridApi.paginationGoToPage(currentPage);
                        customerDebtGridApi.paginationSetPageSize(pageSize);
                    }, 100);
                }
            } else {
                customerDebtGridApi.setGridOption('rowData', []);
                customerDebtGridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading customer debt payments:', error);
            customerDebtGridApi.setGridOption('rowData', []);
            customerDebtGridApi.showNoRowsOverlay();
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
window.reloadCustomerDebts = function() {
    loadCustomerDebtData(true);
};

// Global function to load customer debt payments (for use in other scripts)
async function loadCustomerReturnDebts(customerId) {
    if (!customerId || customerId <= 0) {
        console.error('Invalid customer ID for loading return debts:', customerId);
        return;
    }
    loadCustomerDebtData(true);
}

// Make function globally available
window.loadCustomerReturnDebts = loadCustomerReturnDebts;

// Initialize Grid
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#customerDebtGrid');
    if (gridDiv) {
        // Use createGrid for AG Grid v31+
        customerDebtGridApi = agGrid.createGrid(gridDiv, customerDebtGridOptions);
    }
});

// Event delegation for edit, delete, and print buttons
// تێبینی: delete handler لە delete_return_debt.js هەیە
$(document).on('click', '.edit-return-debt', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const id = $(this).data('id');
    if (!id) return;
    
    // Show loading state
    const btn = $(this);
    const originalHTML = btn.html();
    btn.prop('disabled', true);
    btn.html('<i class="fa fa-spinner fa-spin"></i>');
    
    // Fetch debt payment data
    fetch(`../process/customer_profile/select_return_debt.php?debt_id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.id) {
                // Populate edit form
                $('#edit_customer_debt_id').val(data.id);
                $('#edit_customer_debt_date').val(data.date || '');
                $('#edit_customer_debt_dolar_rate').val(data.dolar_rate || '');
                $('#edit_customer_debt_paid_usd').val(data.paid_usd || '');
                $('#edit_customer_debt_paid_iqd').val(data.paid_iqd || '');
                $('#edit_customer_debt_discount').val(data.discount || '');
                $('#edit_customer_debt_change_back_usd').val(data.change_back_usd || 0);
                $('#edit_customer_debt_change_back_iqd').val(data.change_back_iq || 0);
                $('#edit_customer_debt_note').val(data.note || '');
                
                const paymentTypeField = document.getElementById('edit_customer_debt_payment_type');
                if (paymentTypeField) {
                    paymentTypeField.value = data.payment_type || 'fifo';
                }
                
                if (typeof window.editPaymentAllocations !== 'undefined') {
                    window.editPaymentAllocations = data.allocations || [];
                }
                
                if (typeof handleEditPaymentTypeChange === 'function') {
                    handleEditPaymentTypeChange();
                }
                
                // Show modal
                $('#editCustomerDebtModal').modal('show');
            } else {
                Swal.fire('هەڵە!', 'نەتوانرا داتاکان بخوێندرێنەوە', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading debt data:', error);
            Swal.fire('هەڵە!', 'هەڵەیەک لە پەیوەندیکردن', 'error');
        })
        .finally(() => {
            btn.prop('disabled', false);
            btn.html(originalHTML);
        });
});

$(document).on('click', '.print-debt-receipt', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const id = $(this).data('id');
    if (!id) return;
    
    // Open debt payment receipt in new window
    window.open(`../pages/debt_payment_receipt.php?id=${id}&auto_print=1`, '_blank');
});
