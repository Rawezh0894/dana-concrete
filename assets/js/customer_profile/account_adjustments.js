let customerAdjustmentsGridApi;

const customerAdjustmentsColumnDefs = [
    {
        field: 'date',
        headerName: 'بەروار',
        filter: 'agDateColumnFilter',
        floatingFilter: true,
        sortable: true,
        minWidth: 110,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'adjustment_type',
        headerName: 'جۆر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'center', direction: 'rtl' },
        cellRenderer: function (params) {
            if (params.value === 'increase') {
                return '<span class="badge bg-danger">زیادکردنی قەرز</span>';
            }
            if (params.value === 'decrease') {
                return '<span class="badge bg-success">کەمکردنی قەرز</span>';
            }
            return '-';
        }
    },
    {
        field: 'amount_usd',
        headerName: 'بڕ (USD)',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl', fontWeight: 'bold' },
        valueFormatter: function (params) {
            return window.AGGridFormatters?.formatUSD(params.value) || '-';
        }
    },
    {
        field: 'reason',
        headerName: 'هۆکار/وەسف',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        minWidth: 240,
        flex: 1,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        tooltipValueGetter: function (params) {
            return params.value || '';
        }
    },
    {
        field: 'created_by_name',
        headerName: 'تۆمارکەر',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        minWidth: 120,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    }
];

const customerAdjustmentsGridOptions = {
    columnDefs: customerAdjustmentsColumnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
        minWidth: 100
    },
    pagination: true,
    paginationPageSize: 10,
    paginationPageSizeSelector: [10, 25, 50],
    animateRows: true,
    localeText: {
        noRowsToShow: 'هیچ داتایەک نییە',
        loadingOoo: 'چاوەڕوان بە...'
    }
};

function loadCustomerAdjustments() {
    if (!customerAdjustmentsGridApi || typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) return;
    customerAdjustmentsGridApi.showLoadingOverlay();
    fetch(`../process/customer_profile/select_account_adjustments.php?customer_id=${CUSTOMER_ID}`)
        .then(r => r.json())
        .then(data => {
            const rows = data && data.success && Array.isArray(data.data) ? data.data : [];
            customerAdjustmentsGridApi.setGridOption('rowData', rows);
            if (rows.length === 0) {
                customerAdjustmentsGridApi.showNoRowsOverlay();
            } else {
                customerAdjustmentsGridApi.hideOverlay();
            }
        })
        .catch(() => {
            customerAdjustmentsGridApi.setGridOption('rowData', []);
            customerAdjustmentsGridApi.showNoRowsOverlay();
        });
}

window.loadCustomerAdjustments = loadCustomerAdjustments;

document.addEventListener('DOMContentLoaded', function () {
    const gridDiv = document.getElementById('customerAdjustmentsGrid');
    if (gridDiv) {
        customerAdjustmentsGridApi = agGrid.createGrid(gridDiv, customerAdjustmentsGridOptions);
        loadCustomerAdjustments();
    }

    const form = document.getElementById('addCustomerAdjustmentForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('customer_id', CUSTOMER_ID);

        try {
            const res = await fetch('../process/customer_profile/add_account_adjustment.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو', data.msg, 'success');
                form.reset();
                const dateInput = document.getElementById('adjustment_date');
                if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerAdjustmentModal'));
                if (modal) modal.hide();
                if (typeof refreshCustomerData === 'function') refreshCustomerData();
                loadCustomerAdjustments();
            } else {
                Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕوویدا', 'error');
            }
        } catch (err) {
            Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا', 'error');
        }
    });
});

