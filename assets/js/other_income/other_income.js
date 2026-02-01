var gridOptions = null;

document.addEventListener('DOMContentLoaded', function () {
    initGrid();
});

function initGrid() {
    const gridDiv = document.querySelector('#otherIncomeGrid');

    const columnDefs = [
        { headerName: "#", field: "id", width: 70, maxWidth: 100, sortable: true, filter: true },
        { headerName: "بەروار", field: "date", width: 120, maxWidth: 150, sortable: true, filter: "agDateColumnFilter" },
        { headerName: "وەسف", field: "description", width: 300, maxWidth: 450, filter: true, wrapText: true, autoHeight: true },
        {
            headerName: "بڕ (دینار)",
            field: "amount_iqd",
            width: 150,
            maxWidth: 150,
            sortable: true,
            filter: "agNumberColumnFilter",
            valueFormatter: params => formatCurrency(params.value, 'IQD')
        },
        {
            headerName: "بڕ (دۆلار)",
            field: "amount_usd",
            width: 150,
            maxWidth: 150,
            sortable: true,
            filter: "agNumberColumnFilter",
            valueFormatter: params => formatCurrency(params.value, 'USD')
        },
        { headerName: "جۆری دراو", field: "currency", width: 100, maxWidth: 120, filter: true },
        { headerName: "دیبەیت", field: "created_at", width: 180, sortable: true, hide: true }
    ];

    gridOptions = {
        columnDefs: columnDefs,
        defaultColDef: {
            resizable: true,
            sortable: true,
            filter: true,
            maxWidth: 150
        },
        pagination: true,
        paginationPageSize: 20,
        enableRtl: true,
        rowData: []
    };

    new agGrid.Grid(gridDiv, gridOptions);
    refreshIncomeGrid();
}

function refreshIncomeGrid() {
    if (!gridOptions) return;

    gridOptions.api.showLoadingOverlay();

    fetch('../process/other_income/get_incomes.php')
        .then(response => response.json())
        .then(data => {
            gridOptions.api.setRowData(data);
            calculateTotals(data);
            gridOptions.api.hideOverlay();
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

    const totalElIQD = document.getElementById('totalIncomeIQD');
    const totalElUSD = document.getElementById('totalIncomeUSD');

    if (totalElIQD) totalElIQD.innerText = formatCurrency(totalIQD, 'IQD');
    if (totalElUSD) totalElUSD.innerText = formatCurrency(totalUSD, 'USD');
}

function formatCurrency(params, type) {
    let value = params;
    if (typeof params === 'object' && params !== null && params.value !== undefined) {
        value = params.value;
    }

    if (value === undefined || value === null) return '';

    let num = parseFloat(value);
    if (type === 'USD') {
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' د.ع';
    }
}

// Global scope
window.refreshIncomeGrid = refreshIncomeGrid;
