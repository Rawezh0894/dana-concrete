// AG Grid Configuration for Purchase Table
// بەکارهێنانی فایلی گشتی
// <script src="../assets/js/comon/ag_grid_base.js"></script> پێویستە لە HTML دا زیاد بکرێت

let purchaseGridApi;
let purchaseGridColumnApi;

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
const purchaseColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 120,
        maxWidth: 150,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function (params) {
            if (!params.data) return '-';
            const editBtn = window.userPermissions && window.userPermissions.canEdit
                ? `<button class='btn btn-warning btn-sm edit-purchase' data-id='${params.data.id}' title='نوێکردنەوە' style='margin: 2px;'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                ? `<button class='btn btn-danger btn-sm delete-purchase' data-id='${params.data.id}' title='سڕینەوە' style='margin: 2px;'><i class='fa fa-trash'></i></button>`
                : '';
            return `${editBtn} ${deleteBtn}`.trim() || '-';
        }
    },
    {
        field: 'bin_name',
        headerName: 'چاو/سایلۆ',
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
        field: 'remaining_iqd',
        headerName: 'پارەی ماوە بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function (params) {
            return agFormatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'remaining_usd',
        headerName: 'پارەی ماوە بە دۆلار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#dc3545' },
        valueFormatter: function (params) {
            return agFormatUSD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'paid_iqd',
        headerName: 'پارەی دراو بە دینار',
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
        field: 'paid_usd',
        headerName: 'پارەی دراو بە دۆلار',
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
        field: 'exchange_rate',
        headerName: 'نرخی 100 دۆلار بە دینار',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return agFormatNumber(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'amount_iqd',
        headerName: 'بڕی پارە بە دینار',
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
        field: 'material_cost_iqd',
        headerName: 'تێچووی کڕین',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#17a2b8' },
        valueFormatter: function (params) {
            return agFormatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'total_freight_cost_iqd',
        headerName: 'تێچووی نقڵ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold', color: '#fd7e14' },
        valueFormatter: function (params) {
            return agFormatIQD(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'price',
        headerName: 'نرخ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function (params) {
            if (!params.data) return '-';
            const type = params.data.type;
            if (type === 'دینار') {
                return agFormatIQD(params.value);
            } else if (type === 'دۆلار') {
                return agFormatUSD(params.value);
            }
            return agFormatNumber(params.value);
        },
        type: 'numericColumn'
    },
    {
        field: 'price_per_kg_iqd',
        headerName: 'نرخی یەک کیلۆ بە دینار',
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
        field: 'price_per_kg_usd',
        headerName: 'نرخی یەک کیلۆ بە دۆلار',
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
        field: 'kg',
        headerName: 'کیلۆگرام',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            if (params.value === null || params.value === undefined || params.value === '') return '-';
            return agFormatNumber(params.value) + ' کگم';
        },
        type: 'numericColumn'
    },
    {
        field: 'type',
        headerName: 'جۆری دراو',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const color = params.value === 'دۆلار' ? '#007bff' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
        }
    },
    {
        field: 'payment_type',
        headerName: 'جۆری پارەدان',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        cellRenderer: function (params) {
            if (!params.value) return '-';
            const color = params.value === 'نەقد' ? '#28a745' : '#ffc107';
            return `<span style="background: ${color}; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold;">${params.value}</span>`;
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
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            if (!params.value) return '-';
            return params.value;
        }
    },
    {
        field: 'material_name',
        headerName: 'مەواد',
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
        field: 'invoice_number',
        headerName: 'ژمارەی پسوڵە',
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
        field: 'driver_name',
        headerName: 'شۆفێر',
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
        field: 'factory_truck_name',
        headerName: 'تڕێلەی کارگە',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 110,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function (params) {
            if (!params.value || params.value === '-') return '—';
            return params.value;
        },
        tooltipValueGetter: function (params) {
            return params.value && params.value !== '-' ? params.value : '';
        }
    },
    {
        field: 'location_name',
        headerName: 'شوێن',
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
        field: 'company_name',
        headerName: 'کۆمپانیا',
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
const purchaseGridOptions = {
    columnDefs: purchaseColumnDefs,
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
        purchaseGridApi = params.api;
        loadPurchaseData();
    },
    onFirstDataRendered: function (params) {
        // Don't auto-size columns to fit - let them maintain their minWidth
        // This prevents columns from overlapping when there are many columns
        params.api.autoSizeAllColumns(false);
    }
};

// Load Purchase Data - with pagination state preservation
function loadPurchaseData(preservePagination = false) {
    const fromDate = document.getElementById('filter_from')?.value || '';
    const toDate = document.getElementById('filter_to')?.value || '';
    const companyId = document.getElementById('filter_company')?.value || '';
    let locationId = $('#filter_location').val() || '';
    if (Array.isArray(locationId)) locationId = locationId.join(',');
    let driverId = $('#filter_driver').val() || '';
    if (Array.isArray(driverId)) driverId = driverId.join(',');
    const materialId = document.getElementById('filter_material')?.value || '';
    const searchTerm = document.getElementById('purchase_global_search')?.value || '';

    // Save current pagination state
    let currentPage = 0;
    let pageSize = 25;
    if (preservePagination && purchaseGridApi) {
        currentPage = purchaseGridApi.paginationGetCurrentPage() || 0;
        pageSize = purchaseGridApi.paginationGetPageSize() || 25;
    }

    // Build request data
    const requestData = new FormData();
    if (fromDate) requestData.append('from', fromDate);
    if (toDate) requestData.append('to', toDate);
    if (companyId) requestData.append('company_id', companyId);
    if (locationId) requestData.append('location_id', locationId);
    if (driverId) requestData.append('driver_id', driverId);
    if (materialId) requestData.append('material_id', materialId);
    if (searchTerm) requestData.append('search', searchTerm);
    requestData.append('limit', '500'); // Get more records for AG Grid

    // Show loading
    purchaseGridApi?.showLoadingOverlay();

    fetch('../process/purchase/select_purchase.php', {
        method: 'POST',
        body: requestData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Transform data for AG Grid
                const rowData = data.data.map(row => ({
                    id: row.id,
                    company_name: row.company_name || '-',
                    location_name: row.location_name || '-',
                    driver_name: row.driver_name || '-',
                    factory_truck_name: row.factory_truck_name || '',
                    invoice_number: row.invoice_number || '-',
                    material_name: row.material_name || '-',
                    date: row.date || '-',
                    payment_type: row.payment_type || '-',
                    type: row.type || '-',
                    kg: row.kg || 0,
                    price_per_kg_usd: row.price_per_kg_usd || 0,
                    price_per_kg_iqd: row.price_per_kg_iqd || 0,
                    price: row.price || 0,
                    amount_iqd: row.amount_iqd || 0,
                    material_cost_iqd: row.material_cost_iqd || 0,
                    total_freight_cost_iqd: row.total_freight_cost_iqd || 0,
                    exchange_rate: row.exchange_rate || 0,
                    paid_usd: row.paid_usd || 0,
                    paid_iqd: row.paid_iqd || 0,
                    remaining_usd: row.remaining_usd || 0,
                    remaining_iqd: row.remaining_iqd || 0,
                    bin_name: row.bin_name || '-'
                }));

                purchaseGridApi.setGridOption('rowData', rowData);
                purchaseGridApi.hideOverlay();

                // Restore pagination state if preserving
                if (preservePagination && purchaseGridApi) {
                    setTimeout(() => {
                        purchaseGridApi.paginationGoToPage(currentPage);
                        purchaseGridApi.paginationSetPageSize(pageSize);
                    }, 100);
                }
            } else {
                purchaseGridApi.setGridOption('rowData', []);
                purchaseGridApi.showNoRowsOverlay();
            }
        })
        .catch(error => {
            console.error('Error loading purchases:', error);
            purchaseGridApi.setGridOption('rowData', []);
            purchaseGridApi.showNoRowsOverlay();
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
window.reloadPurchases = function () {
    loadPurchaseData(true); // Preserve pagination state
};

// Export functions: see purchase.js (server-side .xls / .csv aligned with grid columns)
// Function to set default date range to current month
function setDefaultDateFilter() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    // Format dates as YYYY-MM-DD
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const filterFrom = document.getElementById('filter_from');
    const filterTo = document.getElementById('filter_to');

    if (filterFrom && filterTo) {
        filterFrom.value = formatDate(firstDay);
        filterTo.value = formatDate(lastDay);
    }
}

// Initialize Grid
document.addEventListener('DOMContentLoaded', function () {
    const gridDiv = document.querySelector('#purchaseGrid');
    if (gridDiv) {
        // Set default date filter to current month
        setDefaultDateFilter();

        // Use createGrid for AG Grid v31+
        purchaseGridApi = agGrid.createGrid(gridDiv, purchaseGridOptions);

        // Wait for grid to be ready before adding event listeners
        setTimeout(() => {
            // Add event listeners for filters
            const filterInputs = ['filter_from', 'filter_to', 'filter_company', 'filter_location', 'filter_driver', 'filter_material', 'purchase_global_search'];
            filterInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('change', function () { loadPurchaseData(); });
                    input.addEventListener('input', function () {
                        clearTimeout(this.searchTimeout);
                        this.searchTimeout = setTimeout(function () { loadPurchaseData(); }, 500);
                    });
                }
            });

            // Clear filters button
            const clearFilterBtn = document.getElementById('clearFilterBtn');
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function () {
                    $('#filter_company').val('').trigger('change');
                    $('#filter_location').val(null).trigger('change');
                    $('#filter_driver').val(null).trigger('change');
                    $('#filter_material').val('').trigger('change');
                    document.getElementById('filter_from').value = '';
                    document.getElementById('filter_to').value = '';
                    document.getElementById('purchase_global_search').value = '';
                    loadPurchaseData();
                });
            }

            // Clear column filters button
            const clearColumnFiltersBtn = document.getElementById('clearColumnFiltersBtn');
            if (clearColumnFiltersBtn) {
                clearColumnFiltersBtn.addEventListener('click', function () {
                    if (purchaseGridApi) {
                        purchaseGridApi.setFilterModel(null);
                        loadPurchaseData();
                    }
                });
            }
        }, 100);
    }
});

// Event delegation for edit button in AG Grid
// تێبینی: delete handler لە delete_purchase.js هەیە
$(document).on('click', '.edit-purchase', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id = $(this).data('id');
    if (!id) return;

    // Fetch purchase data and populate edit modal
    $.ajax({
        url: `../process/purchase/select_purchase.php?id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data && data.id) {
                // Populate edit form
                $('#edit_id').val(data.id);
                $('#edit_company_id').val(data.company_id).trigger('change');
                $('#edit_driver_id').val(data.driver_id).trigger('change');
                $('#edit_factory_truck_id').val(data.factory_truck_id ? String(data.factory_truck_id) : '').trigger('change');
                $('#edit_location_id').val(data.location_id).trigger('change');
                $('#edit_invoice_number').val(data.invoice_number);
                $('#edit_material_id').val(data.material_id).trigger('change');
                $('#edit_bin_id').val(data.bin_id).trigger('change');
                $('#edit_date').val(data.date);
                $('#edit_type').val(data.type).trigger('change');
                $('#edit_kg').val(data.kg);
                
                // Calculate and set material and freight totals
                const kgTons = (parseFloat(data.kg) || 0) / 1000;
                
                const materialCostIqd = kgTons * (parseFloat(data.price_per_kg_iqd) || 0);
                const materialCostUsd = kgTons * (parseFloat(data.price_per_kg_usd) || 0);
                const freightCostIqd = kgTons * (parseFloat(data.freight_price_per_kg_iqd) || 0);
                const freightCostUsd = kgTons * (parseFloat(data.freight_price_per_kg_usd) || 0);
                
                $('#edit_material_cost_iqd').val(materialCostIqd.toFixed(0));
                const pricePerTonUsd = parseFloat(data.price_per_kg_usd) || 0;
                $('#edit_price_per_ton_usd').val(pricePerTonUsd.toFixed(2));
                $('#edit_freight_cost_iqd').val(freightCostIqd.toFixed(0));
                $('#edit_freight_cost_usd').val(freightCostUsd.toFixed(2));
                
                // Hidden fields for backward compatibility
                $('#edit_price_per_kg_iqd').val(data.price_per_kg_iqd);
                $('#edit_price_per_kg_usd').val(data.price_per_kg_usd);
                $('#edit_freight_price_per_kg_iqd').val(data.freight_price_per_kg_iqd);
                $('#edit_freight_price_per_kg_usd').val(data.freight_price_per_kg_usd);
                $('#edit_total_freight_cost_iqd').val(data.total_freight_cost_iqd);
                $('#edit_total_freight_cost_usd').val(data.total_freight_cost_usd);
                
                $('#edit_exchange_rate').val(data.exchange_rate);
                $('#edit_payment_type').val(data.payment_type);
                $('#edit_price').val(data.price);
                $('#edit_amount_iqd').val(data.amount_iqd);
                $('#edit_paid_usd').val(data.paid_usd);
                $('#edit_paid_iqd').val(data.paid_iqd);
                $('#edit_remaining_usd').val(data.remaining_usd);
                $('#edit_remaining_iqd').val(data.remaining_iqd);

                // Inline panel (add_purchase) — no Bootstrap modal
                const editPanel = document.getElementById('editPurchasePanel');
                if (editPanel) {
                    editPanel.classList.remove('d-none');
                    editPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (typeof $ !== 'undefined') {
                        $('#editPurchasePanel').trigger('editPurchasePanel:opened');
                    }
                } else {
                    const editModalEl = document.getElementById('editPurchaseModal');
                    if (editModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(editModalEl).show();
                    } else if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                        $('#editPurchaseModal').modal('show');
                    }
                }
            } else {
                Swal.fire('هەڵە!', 'نەتوانرا داتاکان بخوێندرێنەوە', 'error');
            }
        },
        error: function () {
            Swal.fire('هەڵە!', 'هەڵەیەک لە پەیوەندیکردن', 'error');
        }
    });
});
