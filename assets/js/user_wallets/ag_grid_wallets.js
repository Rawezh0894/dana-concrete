// AG Grid Configuration for Wallets Table
let walletGridApi;

const walletsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        width: 120,
        cellStyle: { textAlign: 'center' },
        cellRenderer: function (params) {
            if (!params.data) return '-';
            const isExchange = params.data.trans_type === 'EXCHANGE';
            const editBtn = !isExchange 
                ? `<button class='btn btn-outline-info btn-sm border-0 edit-wallet' data-id='${params.node.id}' title='دەستکاری'><i class='fa fa-edit'></i></button>`
                : '';
            const deleteBtn = `<button class='btn btn-outline-danger btn-sm border-0 delete-wallet' data-id='${params.data.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`;
            return `<div class="d-flex justify-content-center gap-1">${editBtn} ${deleteBtn}</div>`;
        }
    },
    {
        field: 'description',
        headerName: 'تێبینی',
        filter: 'agTextColumnFilter',
        sortable: true,
        resizable: true,
        flex: 1,
        minWidth: 200,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'iqd_amount',
        headerName: 'بڕی IQD',
        filter: 'agNumberColumnFilter',
        sortable: true,
        resizable: true,
        width: 150,
        cellStyle: { textAlign: 'right', direction: 'ltr', fontWeight: 'bold' },
        cellClassRules: {
            'text-success': params => params.value > 0,
            'text-danger': params => params.value < 0,
            'text-muted': params => !params.value
        },
        valueFormatter: params => params.value ? Math.abs(params.value).toLocaleString() + ' IQD' : '-'
    },
    {
        field: 'usd_amount',
        headerName: 'بڕی USD',
        filter: 'agNumberColumnFilter',
        sortable: true,
        resizable: true,
        width: 150,
        cellStyle: { textAlign: 'right', direction: 'ltr', fontWeight: 'bold' },
        cellClassRules: {
            'text-success': params => params.value > 0,
            'text-danger': params => params.value < 0,
            'text-muted': params => !params.value
        },
        valueFormatter: params => params.value ? Math.abs(params.value).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' $' : '-'
    },
    {
        field: 'category_name',
        headerName: 'هۆکار/پۆلێن',
        filter: 'agTextColumnFilter',
        sortable: true,
        resizable: true,
        width: 160,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueGetter: params => params.data.trans_type === 'EXCHANGE' ? 'ئاڵوگۆڕ' : (params.data.category_name || 'بێ جۆر')
    },
    {
        field: 'trans_type',
        headerName: 'جۆر',
        filter: 'agTextColumnFilter',
        sortable: true,
        resizable: true,
        width: 130,
        cellRenderer: function(params) {
            const isExchange = params.value === 'EXCHANGE';
            const isInflow = params.data.usd_amount > 0 || params.data.iqd_amount > 0;
            if (isExchange) return '<span class="badge bg-warning text-dark w-100">ئاڵوگۆڕ 💱</span>';
            if (isInflow) return '<span class="badge bg-success w-100">هاتن 📥</span>';
            return '<span class="badge bg-danger w-100">دەرچوون 📤</span>';
        }
    },
    {
        field: 'created_at',
        headerName: 'بەروار و کات',
        filter: 'agDateColumnFilter',
        sortable: true,
        resizable: true,
        width: 180,
        cellStyle: { textAlign: 'center', direction: 'ltr' }
    }
];

const walletGridOptions = {
    columnDefs: walletsColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: false // We will use custom filters
    },
    pagination: true,
    paginationPageSize: 20,
    paginationPageSizeSelector: [10, 20, 50, 100],
    animateRows: true,
    rowHeight: 50,
    localeText: {
        page: 'پەڕە',
        of: 'لە',
        to: 'بۆ',
        next: 'دواتر',
        previous: 'پێشوو',
        noRowsToShow: 'هیچ داتایەک نەدۆزرایەوە',
        loadingOoo: 'چاوەڕوان بە...'
    },
    onGridReady: function(params) {
        walletGridApi = params.api;
        loadWalletData();
    }
};

function loadWalletData() {
    const fromDate = $('#filter_from').val();
    const toDate = $('#filter_to').val();
    const type = $('#filter_type').val();
    const categoryId = $('#filter_category').val();
    const minAmount = $('#filter_min_amount').val();
    const maxAmount = $('#filter_max_amount').val();
    const search = $('#filter_search').val();

    walletGridApi?.showLoadingOverlay();

    $.ajax({
        url: '../process/user_wallets/select_wallets.php',
        type: 'POST',
        data: {
            from: fromDate,
            to: toDate,
            type: type,
            category_id: categoryId,
            min_amount: minAmount,
            max_amount: maxAmount,
            search: search
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                walletGridApi.setGridOption('rowData', res.data);
            } else {
                walletGridApi.setGridOption('rowData', []);
            }
            walletGridApi.hideOverlay();
        },
        error: function() {
            walletGridApi.setGridOption('rowData', []);
            walletGridApi.hideOverlay();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#walletsGrid');
    if (gridDiv) {
        walletGridApi = agGrid.createGrid(gridDiv, walletGridOptions);

        // Auto-filter on inputs
        const filters = [
            '#filter_from', '#filter_to', '#filter_type', 
            '#filter_category', '#filter_min_amount', 
            '#filter_max_amount', '#filter_search'
        ];

        $(filters.join(',')).on('change input', function() {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(loadWalletData, 400);
        });
    }
});

// Event handlers for AG Grid rows
$(document).on('click', '.edit-wallet', function() {
    const rowIndex = $(this).data('id');
    const rowNode = walletGridApi.getRowNode(rowIndex);
    if (rowNode) {
        const tx = rowNode.data;
        // Transform for existing prepareEdit function
        const dataForEdit = {
            id: tx.id,
            category_id: tx.category_id,
            usd_amount: tx.usd_amount,
            iqd_amount: tx.iqd_amount,
            description: tx.description
        };
        window.prepareEdit(dataForEdit);
    }
});

$(document).on('click', '.delete-wallet', function() {
    const id = $(this).data('id');
    window.deleteTransaction(id);
});
