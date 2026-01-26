// AG Grid Configuration for Customer Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let customerGridApi;

// Format functions - بەکارهێنانی لە فایلی گشتی (ناوی جیاواز بۆ دوورکەوتنەوە لە کێشەی duplicate)
const agFormatNumber = window.AGGridFormatters?.formatNumber || function (n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const agFormatUSD = window.AGGridFormatters?.formatUSD || function (n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return agFormatNumber(Number(n).toFixed(2)) + ' $';
};

const agFormatIQD = window.AGGridFormatters?.formatIQD || function (n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return agFormatNumber(Number(n).toFixed(0)) + ' د.ع';
};

// Column Definitions - ترتیب ستونەکان بە شێوەی دروست (لە چەپ بۆ ڕاست - LTR)
const customerColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 150,
        maxWidth: 200,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function (params) {
            if (!params.data) return '-';
            const editBtn = window.userPermissions && window.userPermissions.canEdit
                ? `<button class='btn btn-warning btn-sm edit-customer-btn' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-customer-btn' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            const viewBtn = `<a href='customer_profile.php?id=${params.data.id}' class='btn btn-info btn-sm' title='بینین' style='margin: 2px;'><i class='fa fa-eye'></i></a>`;
            return `${editBtn} ${deleteBtn} ${viewBtn}`.trim() || '-';
        }
    },
    {
        field: 'is_recipient',
        headerName: 'وەرگر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function (params) {
            if (!params.value) return '<span class="badge bg-secondary"><i class="fas fa-times"></i> نەخێر</span>';
            const isRecipient = parseInt(params.value) === 1;
            return isRecipient
                ? '<span class="badge bg-success"><i class="fas fa-check"></i> بەڵێ</span>'
                : '<span class="badge bg-secondary"><i class="fas fa-times"></i> نەخێر</span>';
        }
    },
    {
        field: 'opening_debt_iqd',
        headerName: 'بڕی قەرزی سەرەتایی (IQD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            return agFormatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'opening_debt_usd',
        headerName: 'بڕی قەرزی سەرەتایی (USD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            return agFormatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'mobile2',
        headerName: 'ژمارە مۆبایلی دووەم',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function (params) {
            return params.value || '';
        }
    },
    {
        field: 'mobile1',
        headerName: 'ژمارە مۆبایلی یەکەم',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function (params) {
            return params.value || '';
        }
    },
    {
        field: 'name',
        headerName: 'ناو',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function (params) {
            return params.value || '';
        }
    }
];

// Grid Options - تەنها بۆ community version
const customerGridOptions = {
    columnDefs: customerColumnDefs,
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
        customerGridApi = params.api;
        loadCustomerData();
    },
    onFirstDataRendered: function (params) {
        // Auto-size columns
        params.api.sizeColumnsToFit();
    }
};

// Load Customer Data - with pagination state preservation
function loadCustomerData(preservePagination = false) {
    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && customerGridApi) {
        currentPage = customerGridApi.paginationGetCurrentPage() || 0;
        pageSize = customerGridApi.paginationGetPageSize() || 25;
    }

    // Show loading
    customerGridApi?.showLoadingOverlay();

    fetch('../process/customer/select_customer.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data for AG Grid
                const rowData = data.data.map(row => ({
                    id: row.id,
                    name: row.name || '-',
                    mobile1: row.mobile1 || '-',
                    mobile2: row.mobile2 || '-',
                    opening_debt_usd: row.opening_debt_usd || 0,
                    opening_debt_iqd: row.opening_debt_iqd || 0,
                    is_recipient: row.is_recipient || 0
                }));

                customerGridApi.setGridOption('rowData', rowData);
                customerGridApi.hideOverlay();

                // Restore pagination state if preserving
                if (preservePagination && customerGridApi) {
                    setTimeout(() => {
                        customerGridApi.paginationGoToPage(currentPage);
                        customerGridApi.paginationSetPageSize(pageSize);
                    }, 100);
                }
            } else {
                customerGridApi.setGridOption('rowData', []);
                customerGridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading customers:', error);
            customerGridApi.setGridOption('rowData', []);
            customerGridApi.showNoRowsOverlay();
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
window.reloadCustomers = function () {
    loadCustomerData(true); // Preserve pagination state
};

// Export to CSV
function exportCustomersToExcel() {
    if (customerGridApi) {
        const params = {
            fileName: `کڕیارەکان_${new Date().toISOString().split('T')[0]}.csv`
        };
        customerGridApi.exportDataAsCsv(params);
    }
}

// Export function for button
window.exportCustomersToExcel = exportCustomersToExcel;

// Initialize Grid
document.addEventListener('DOMContentLoaded', function () {
    const gridDiv = document.querySelector('#customerGrid');
    if (gridDiv) {
        // Use createGrid for AG Grid v31+
        customerGridApi = agGrid.createGrid(gridDiv, customerGridOptions);

        // Wait for grid to be ready before adding event listeners
        setTimeout(() => {
            // Add event listeners for filters if they exist
            const filterInputs = ['filter_year', 'filter_month', 'filter_from_date', 'filter_to_date'];
            filterInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('change', function () { loadCustomerData(); });
                }
            });

            // Apply filters button
            const applyFiltersBtn = document.getElementById('apply_filters');
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function () {
                    loadCustomerData();
                });
            }

            // Clear filters button
            const clearFiltersBtn = document.getElementById('clear_filters');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function () {
                    document.getElementById('filter_year').value = '';
                    document.getElementById('filter_month').value = '';
                    document.getElementById('filter_from_date').value = '';
                    document.getElementById('filter_to_date').value = '';
                    loadCustomerData();
                });
            }
        }, 100);
    }
});

// Event delegation for edit button in AG Grid
// تێبینی: delete handler لە delete_customer.js هەیە
$(document).on('click', '.edit-customer-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id = $(this).data('id');
    if (!id) return;

    // Fetch customer data and populate edit modal
    $.ajax({
        url: `../process/customer/get_customer.php?id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data) {
                const data = response.data;
                // Populate edit form
                $('#editCustomerId').val(data.id);
                $('#editCustomerName').val(data.name || '');
                $('#editCustomerMobile1').val(data.mobile1 || '');
                $('#editCustomerMobile2').val(data.mobile2 || '');

                // Handle numeric values properly
                const usdValue = parseFloat(data.opening_debt_usd || 0);
                const iqdValue = parseFloat(data.opening_debt_iqd || 0);

                $('#editCustomerOpeningDebtUsd').val(usdValue > 0 ? usdValue : '');
                $('#editCustomerOpeningDebtIqd').val(iqdValue > 0 ? iqdValue : '');

                // Set is_recipient checkbox
                const isRecipient = parseInt(data.is_recipient || 0);
                $('#editCustomerIsRecipient').prop('checked', isRecipient === 1);

                // Enable/disable fields based on values
                if (usdValue > 0) {
                    $('#editCustomerOpeningDebtIqd').prop('disabled', true);
                } else if (iqdValue > 0) {
                    $('#editCustomerOpeningDebtUsd').prop('disabled', true);
                } else {
                    $('#editCustomerOpeningDebtUsd, #editCustomerOpeningDebtIqd').prop('disabled', false);
                }

                // Show modal
                $('#editCustomerModal').modal('show');
            } else {
                Swal.fire('هەڵە!', response.error || 'نەتوانرا داتاکان بخوێندرێنەوە', 'error');
            }
        },
        error: function () {
            Swal.fire('هەڵە!', 'هەڵەیەک لە پەیوەندیکردن', 'error');
        }
    });
});
