let customerAdjustmentsGridApi;

const customerAdjustmentsColumnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        minWidth: 130,
        maxWidth: 160,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function (params) {
            if (!params.data) return '-';
            return `
                <button class="btn btn-warning btn-sm edit-adjustment" data-id="${params.data.id}" title="نوێکردنەوە" style="margin:2px;">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm delete-adjustment" data-id="${params.data.id}" title="سڕینەوە" style="margin:2px;">
                    <i class="fa fa-trash"></i>
                </button>
            `;
        }
    },
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
            if (data && data.success === false && data.msg && window.Swal) {
                Swal.fire('هەڵە', data.msg, 'error');
            }
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

    const editForm = document.getElementById('editCustomerAdjustmentForm');
    if (editForm) {
        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            try {
                const res = await fetch('../process/customer_profile/update_account_adjustment.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سەرکەوتوو', data.msg, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerAdjustmentModal'));
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
    }
});

$(document).on('click', '.edit-adjustment', async function () {
    const id = $(this).data('id');
    if (!id) return;
    try {
        const res = await fetch(`../process/customer_profile/select_account_adjustments.php?adjustment_id=${id}`);
        const data = await res.json();
        if (!data || !data.id) {
            Swal.fire('هەڵە', 'ڕیکۆرد نەدۆزرایەوە', 'error');
            return;
        }
        $('#edit_adjustment_id').val(data.id);
        $('#edit_adjustment_date').val(data.date || '');
        $('#edit_adjustment_type').val(data.adjustment_type || 'increase');
        $('#edit_adjustment_amount_usd').val(data.amount_usd || 0);
        $('#edit_adjustment_reason').val(data.reason || '');
        const modalEl = document.getElementById('editCustomerAdjustmentModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    } catch (e) {
        Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا', 'error');
    }
});

$(document).on('click', '.delete-adjustment', async function () {
    const id = $(this).data('id');
    if (!id) return;
    const result = await Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم ڕیکۆردە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'داخستن',
        confirmButtonColor: '#d33'
    });
    if (!result.isConfirmed) return;

    const formData = new FormData();
    formData.append('id', id);
    try {
        const res = await fetch('../process/customer_profile/delete_account_adjustment.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو', data.msg, 'success');
            if (typeof refreshCustomerData === 'function') refreshCustomerData();
            loadCustomerAdjustments();
        } else {
            Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕوویدا', 'error');
        }
    } catch (e) {
        Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا', 'error');
    }
});

