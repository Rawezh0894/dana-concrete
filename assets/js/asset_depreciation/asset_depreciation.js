var gridOptions = null;

document.addEventListener('DOMContentLoaded', function () {
    initGrid();
});

function initGrid() {
    const gridDiv = document.querySelector('#depreciationGrid');
    if (!gridDiv) return;

    const columnDefs = [
        { headerName: "#", field: "id", width: 80, sortable: true, filter: true },
        { headerName: "بەروار", field: "depreciation_date", width: 250, sortable: true, filter: "agDateColumnFilter" },
        {
            headerName: "بڕ بە دینار",
            field: "amount_iqd",
            width: 200,
            sortable: true,
            filter: "agNumberColumnFilter",
            valueFormatter: params => formatCurrency(params.value, 'IQD')
        },
        {
            headerName: "بڕ بە دۆلار",
            field: "amount_usd",
            width: 200,
            sortable: true,
            filter: "agNumberColumnFilter",
            valueFormatter: params => formatCurrency(params.value, 'USD')
        },
        { headerName: "تێبینی", field: "note", flex: 2, minWidth: 300, filter: true, wrapText: true, autoHeight: true },
        {
            headerName: "کردارەکان",
            field: "id",
            width: 180,
            sortable: false,
            filter: false,
            cellRenderer: params => {
                return `
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" onclick="openEditDepreciationModal(${JSON.stringify(params.data).replace(/"/g, '&quot;')})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteDepreciation(${params.data.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }
        }
    ];

    gridOptions = {
        columnDefs: columnDefs,
        defaultColDef: {
            resizable: true,
            sortable: true,
            filter: true
        },
        pagination: true,
        paginationPageSize: 20,
        enableRtl: true,
        rowData: [],
        onGridReady: (params) => {
            params.api.sizeColumnsToFit();
        }
    };

    new agGrid.Grid(gridDiv, gridOptions);
    refreshDepreciationGrid();
}

function refreshDepreciationGrid() {
    if (!gridOptions) return;

    gridOptions.api.showLoadingOverlay();

    fetch('../process/asset_depreciation/get_depreciation.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                gridOptions.api.setRowData(data.data);
                calculateTotals(data.data);
            } else {
                console.error('Error:', data.message);
            }
            gridOptions.api.hideOverlay();
            setTimeout(() => {
                gridOptions.api.sizeColumnsToFit();
            }, 100);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            gridOptions.api.showNoRowsOverlay();
        });
}

function calculateTotals(data) {
    let totalIQD = 0;
    let totalUSD = 0;

    if (data && Array.isArray(data)) {
        data.forEach(row => {
            totalIQD += parseFloat(row.amount_iqd || 0);
            totalUSD += parseFloat(row.amount_usd || 0);
        });
    }

    const totalElIQD = document.getElementById('totalDepreciationIQD');
    const totalElUSD = document.getElementById('totalDepreciationUSD');

    if (totalElIQD) totalElIQD.innerText = formatCurrency(totalIQD, 'IQD');
    if (totalElUSD) totalElUSD.innerText = formatCurrency(totalUSD, 'USD');
}

function formatCurrency(value, type) {
    if (value === undefined || value === null) return '';
    let num = parseFloat(value);
    if (type === 'USD') {
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' د.ع';
    }
}

function openEditDepreciationModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_date').value = data.depreciation_date;
    document.getElementById('edit_amount_iqd').value = data.amount_iqd;
    document.getElementById('edit_amount_usd').value = data.amount_usd;
    document.getElementById('edit_note').value = data.note;

    new bootstrap.Modal(document.getElementById('editDepreciationModal')).show();
}

// Global scope
window.refreshDepreciationGrid = refreshDepreciationGrid;
window.openEditDepreciationModal = openEditDepreciationModal;
