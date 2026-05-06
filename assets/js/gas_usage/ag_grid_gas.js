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
        headerName: "کردارەکان (ئەپدەیٹ)",
        width: 180,
        cellRenderer: params => {
            if (!params.data) return '-';
            return `<div class="d-flex gap-2">
                        <button class="btn btn-sm btn-warning rounded-3 edit-gas" data-id="${params.data.id}" title="نوێکردنەوە">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger rounded-3 delete-gas" data-id="${params.data.id}" title="سڕینەوە">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
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

let gasGridApi;

document.addEventListener('DOMContentLoaded', () => {
    const gridDiv = document.querySelector('#gasUsageGrid');
    if (gridDiv) {
        gasGridApi = agGrid.createGrid(gridDiv, gridOptions);
    }
});
