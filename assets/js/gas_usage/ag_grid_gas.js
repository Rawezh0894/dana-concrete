const columnDefs = [
    {
        headerName: "#",
        valueGetter: "node.rowIndex + 1",
        width: 70,
        pinned: 'right'
    },
    {
        field: "date",
        headerName: "بەروار",
        sortable: true,
        filter: true,
        width: 130
    },
    {
        field: "car_name",
        headerName: "سەیارە",
        sortable: true,
        filter: true,
        flex: 1
    },
    {
        field: "gas_liters",
        headerName: "بڕ (لیتر)",
        sortable: true,
        filter: 'agNumberColumnFilter',
        width: 120,
        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString() : '0'
    },
    {
        field: "gas_purchase_price_input",
        headerName: "نرخی لیتر (د.ع)",
        sortable: true,
        width: 150,
        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString() : '0'
    },
    {
        field: "gas_total_cost",
        headerName: "کۆی تێچوو (د.ع)",
        sortable: true,
        width: 150,
        cellStyle: { fontWeight: 'bold', color: '#2a5298' },
        valueFormatter: params => params.value ? parseFloat(params.value).toLocaleString() : '0'
    },
    {
        headerName: "کردارەکان",
        width: 100,
        cellRenderer: params => {
            return `<button class="btn btn-sm btn-danger rounded-3" onclick="deleteGasRecord(${params.data.id})">
                        <i class="fas fa-trash"></i>
                    </button>`;
        }
    }
];

const gridOptions = {
    columnDefs: columnDefs,
    pagination: true,
    paginationPageSize: 15,
    enableRtl: true,
    defaultColDef: {
        resizable: true,
        filter: true
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const gridDiv = document.querySelector('#gasUsageGrid');
    new agGrid.Grid(gridDiv, gridOptions);
});
