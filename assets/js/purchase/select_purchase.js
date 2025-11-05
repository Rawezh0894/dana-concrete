let purchaseTable = null;

function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatUSD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(2)) + ' $';
}

function formatIQD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

async function loadPurchasesTable(filterParams = '', searchTerm = '') {
    // Destroy existing table if it exists
    if (purchaseTable) {
        purchaseTable.destroy();
        purchaseTable = null;
    }
    
    // Clear and prepare table structure
    $('#purchaseTable').empty();
    
    try {
        // Build request data
        const requestData = new FormData();
        
        // Add basic filters from URL params
        const params = new URLSearchParams(filterParams);
        for (const [key, value] of params) {
            if (value) {
                requestData.append(key, value);
            }
        }
        
        // Add search term
        if (searchTerm) {
            requestData.append('search', searchTerm);
        }
        
        // Use POST method to avoid URL length issues
        const response = await fetch('../process/purchase/select_purchase.php', {
            method: 'POST',
            body: requestData
        });
        
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Raw response from select_purchase.php:', text);
            console.error('Server response error. Check console for details.');
            return;
        }
        
        // Handle both old and new response formats
        let data;
        if (result.success && Array.isArray(result.data)) {
            data = result.data;
        } else if (Array.isArray(result)) {
            data = result;
        } else {
            console.error('Unexpected response format:', result);
            $('#purchaseTable').html(`<tr><td colspan="21" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
            return;
        }
        
        if (!data || data.length === 0) {
            $('#purchaseTable').html(`<tr><td colspan="21" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
            return;
        }
        
        // Prepare data for DataTables
        const tableData = data.map((row) => [
            row.company_name || '-',
            row.location_name || row.location || '-',
            row.driver_name || row.driver || '-',
            row.invoice_number || '-',
            row.material_name || '-',
            row.date || '-',
            row.payment_type || '-',
            row.type || '-',
            row.kg !== null && row.kg !== undefined && row.kg !== '' ? formatNumber(row.kg) : '-',
            row.price_per_kg_usd !== null && row.price_per_kg_usd !== undefined && row.price_per_kg_usd !== '' ? formatUSD(row.price_per_kg_usd) : '-',
            row.price_per_kg_iqd !== null && row.price_per_kg_iqd !== undefined && row.price_per_kg_iqd !== '' ? formatIQD(row.price_per_kg_iqd) : '-',
            row.type === 'دینار' ? (row.price !== null && row.price !== undefined && row.price !== '' ? formatIQD(row.price) : '-') : (row.type === 'دۆلار' ? (row.price !== null && row.price !== undefined && row.price !== '' ? formatUSD(row.price) : '-') : (row.price !== null && row.price !== undefined && row.price !== '' ? formatNumber(row.price) : '-')),
            row.amount_iqd !== null && row.amount_iqd !== undefined && row.amount_iqd !== '' ? formatIQD(row.amount_iqd) : '-',
            row.exchange_rate !== null && row.exchange_rate !== undefined && row.exchange_rate !== '' ? formatNumber(row.exchange_rate) : '-',
            row.paid_usd !== null && row.paid_usd !== undefined && row.paid_usd !== '' ? formatUSD(row.paid_usd) : '-',
            row.paid_iqd !== null && row.paid_iqd !== undefined && row.paid_iqd !== '' ? formatIQD(row.paid_iqd) : '-',
            row.remaining_usd !== null && row.remaining_usd !== undefined && row.remaining_usd !== '' ? formatUSD(row.remaining_usd) : '-',
            row.remaining_iqd !== null && row.remaining_iqd !== undefined && row.remaining_iqd !== '' ? formatIQD(row.remaining_iqd) : '-',
            row.bin_name || '-',
            `${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-warning btn-sm edit-purchase' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''} ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-danger btn-sm delete-purchase' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}`
        ]);
        
        // Initialize DataTable
        purchaseTable = new DataTable('#purchaseTable', {
            data: tableData,
            columns: [
                { title: 'کۆمپانیا' },
                { title: 'شوێن' },
                { title: 'شۆفێر' },
                { title: 'ژمارەی پسوڵە' },
                { title: 'مەواد' },
                { title: 'بەروار' },
                { title: 'جۆری پارەدان' },
                { title: 'جۆری دراو' },
                { title: 'کیلۆگرام' },
                { title: 'نرخی یەک کیلۆ بە دۆلار' },
                { title: 'نرخی یەک کیلۆ بە دینار' },
                { title: 'نرخ' },
                { title: 'بڕی پارە بە دینار' },
                { title: 'نرخی 100 دۆلار بە دینار' },
                { title: 'پارەی دراو بە دۆلار' },
                { title: 'پارەی دراو بە دینار' },
                { title: 'پارەی ماوە بە دۆلار' },
                { title: 'پارەی ماوە بە دینار' },
                { title: 'چاو/سایلۆ' },
                { title: 'کردارەکان' }
            ],
            language: {
                "processing": "چاوەڕوان بە...",
                "search": "گەڕان:",
                "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                "loadingRecords": "لۆدینگ...",
                "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                "paginate": {
                    "first": "یەکەم",
                    "previous": "پێشوو",
                    "next": "دواتر",
                    "last": "کۆتایی"
                },
                "aria": {
                    "sortAscending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی زیادبوون",
                    "sortDescending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی کەمبوون"
                }
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[5, 'desc']], // Sort by date descending
            orderMulti: true, // Enable multi-column sorting
            dom: 'Bfrtip', // Buttons, filter, table, info, pagination
            buttons: [
                {
                    extend: 'copy',
                    text: 'لەبەرگرتنەوە',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-sm btn-outline-success'
                },
                {
                    extend: 'print',
                    text: 'پرینت',
                    className: 'btn btn-sm btn-outline-primary'
                }
            ],
            initComplete: function() {
                // Add individual column search inputs
                this.api().columns().every(function() {
                    const column = this;
                    const header = $(column.header());
                    
                    // Skip adding search to actions column
                    if (header.text().includes('کردارەکان')) {
                        return;
                    }
                    
                    // Create search input
                    const searchInput = $('<input>')
                        .attr('type', 'text')
                        .attr('placeholder', 'فلتەر...')
                        .addClass('form-control form-control-sm mt-1 column-filter')
                        .css({
                            'width': '100%',
                            'padding': '0.25rem 0.5rem',
                            'border': '1px solid #ced4da',
                            'border-radius': '0.25rem'
                        });
                    
                    // Add search input to header
                    header.append(searchInput);
                    
                    // Apply search on keyup (Excel-like contains filter)
                    searchInput.on('keyup change', function() {
                        column.search(this.value).draw();
                    });
                });
            }
        });
    } catch (error) {
        console.error('Error loading purchases:', error);
        $('#purchaseTable').html(`<tr><td colspan="21" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadPurchasesTable();
});

// Make it globally accessible for reload
window.loadPurchases = function(filterParams, page, searchTerm) {
    loadPurchasesTable(filterParams, searchTerm);
};

// Alias for compatibility
window.reloadPurchases = function(filterParams) {
    loadPurchasesTable(filterParams);
};

// Filter event listeners
$(document).ready(function() {
    // Global search with debounce
    $('#purchase_global_search').on('input', function() {
        clearTimeout(window.purchaseSearchTimeout);
        window.purchaseSearchTimeout = setTimeout(function() {
            const searchTerm = $('#purchase_global_search').val();
            const filterParams = buildFilterParams();
            loadPurchasesTable(filterParams, searchTerm);
        }, 500);
    });
    
    // Add event listeners for all filters
    $('#filter_company, #filter_location, #filter_driver, #filter_material, #filter_from, #filter_to').on('change', function() {
        const filterParams = buildFilterParams();
        const searchTerm = $('#purchase_global_search').val();
        loadPurchasesTable(filterParams, searchTerm);
    });
    
    // Clear all filters
    $('#clearFilterBtn').on('click', function() {
        $('#filter_company').val('');
        $('#filter_location').val('');
        $('#filter_driver').val('');
        $('#filter_material').val('');
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#purchase_global_search').val('');
        loadPurchasesTable();
    });
});

function buildFilterParams() {
    const params = new URLSearchParams();
    const companyId = $('#filter_company').val();
    const locationId = $('#filter_location').val();
    const driverId = $('#filter_driver').val();
    const materialId = $('#filter_material').val();
    const fromDate = $('#filter_from').val();
    const toDate = $('#filter_to').val();
    
    if (companyId) params.append('company_id', companyId);
    if (locationId) params.append('location_id', locationId);
    if (driverId) params.append('driver_id', driverId);
    if (materialId) params.append('material_id', materialId);
    if (fromDate) params.append('from', fromDate);
    if (toDate) params.append('to', toDate);
    
    return params.toString();
}
