# بەکارهێنانی AG Grid لە پڕۆژەکەدا

## فایلە گشتیەکان

### CSS
- `assets/css/comon/ag_grid.css` - ستایلی گشتی بۆ هەموو تەیبڵەکان

### JavaScript
- `assets/js/comon/ag_grid_base.js` - فەنکشنی گشتی و defaults

## نموونەی بەکارهێنان

### 1. لە HTML دا

```html
<!-- لینکی CSS -->
<link href="../assets/css/comon/ag_grid.css" rel="stylesheet">
<!-- یان لە فایلی تایبەتدا: -->
<link href="../assets/css/your_module/ag_grid_custom.css" rel="stylesheet">

<!-- AG Grid CDN -->
<link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css" rel="stylesheet">

<!-- Container بۆ Grid -->
<div id="myGrid" class="ag-grid-container ag-theme-alpine"></div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
<script src="../assets/js/comon/ag_grid_base.js"></script>
<script src="../assets/js/your_module/ag_grid_custom.js"></script>
```

### 2. لە JavaScript دا

#### نموونەی سادە:

```javascript
// Column Definitions
const columnDefs = [
    {
        field: 'id',
        headerName: 'ID',
        sortable: true,
        filter: true,
        resizable: true,
        minWidth: 100
    },
    {
        field: 'name',
        headerName: 'ناو',
        filter: 'agTextColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' }
    },
    {
        field: 'price',
        headerName: 'نرخ',
        filter: 'agNumberColumnFilter',
        floatingFilter: true,
        sortable: true,
        resizable: true,
        minWidth: 100,
        cellStyle: { textAlign: 'right', direction: 'rtl' },
        valueFormatter: function(params) {
            return window.AGGridFormatters.formatUSD(params.value);
        },
        type: 'numericColumn'
    }
];

// Initialize Grid
let gridApi;
const grid = initAGGrid('myGrid', columnDefs, {
    onGridReady: function(params) {
        gridApi = params.api;
        loadData();
    }
});

// Load Data
function loadData() {
    if (window.loadAGGridData && gridApi) {
        const url = '../process/your_module/select.php?ag_grid=1';
        window.loadAGGridData(gridApi, url, null, false);
    }
}

// Export
function exportToExcel() {
    if (window.exportAGGridToExcel && gridApi) {
        window.exportAGGridToExcel(gridApi, 'فایل.xlsx', 'وەرەق');
    }
}
```

#### نموونەی پێشکەوتوو:

```javascript
// Column Definitions with custom renderers
const columnDefs = [
    {
        field: 'actions',
        headerName: 'کردارەکان',
        sortable: false,
        filter: false,
        resizable: true,
        minWidth: 100,
        flex: 0,
        cellStyle: { textAlign: 'center', direction: 'ltr' },
        cellRenderer: function(params) {
            if (!params.data) return '-';
            return `
                <button class='btn btn-warning btn-sm edit-btn' data-id='${params.data.id}'>
                    <i class='fa fa-edit'></i>
                </button>
                <button class='btn btn-danger btn-sm delete-btn' data-id='${params.data.id}'>
                    <i class='fa fa-trash'></i>
                </button>
            `;
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
        tooltipValueGetter: function(params) {
            return params.value || '';
        }
    }
];

// Custom Options
const customOptions = {
    rowClassRules: {
        'highlight-row': function(params) {
            return params.data && params.data.status === 'active';
        }
    },
    onGridReady: function(params) {
        gridApi = params.api;
        loadData();
    },
    onFirstDataRendered: function(params) {
        // Custom auto-size logic
        const allColumnIds = params.columnApi.getColumns().map(col => col.getId());
        params.columnApi.autoSizeColumns(allColumnIds, false);
    }
};

// Initialize
const grid = initAGGrid('myGrid', columnDefs, customOptions);

// Load Data with transformer
function loadData() {
    if (window.loadAGGridData && gridApi) {
        const url = '../process/your_module/select.php?ag_grid=1';
        const dataTransformer = (data) => {
            return data.map(row => ({
                id: row.id,
                name: row.name || '-',
                price: row.price || 0,
                // ... transform other fields
            }));
        };
        window.loadAGGridData(gridApi, url, dataTransformer, false);
    }
}

// Reload with pagination preservation
function reloadData() {
    if (window.loadAGGridData && gridApi) {
        const url = '../process/your_module/select.php?ag_grid=1';
        window.loadAGGridData(gridApi, url, null, true); // preservePagination = true
    }
}
```

## Format Functions

فەنکشنەکانی format لە `window.AGGridFormatters` بەردەستن:

```javascript
// Format Number
window.AGGridFormatters.formatNumber(1234567); // "1,234,567"

// Format USD
window.AGGridFormatters.formatUSD(1234.56); // "1,234.56 $"

// Format IQD
window.AGGridFormatters.formatIQD(1234567); // "1,234,567 د.ع"

// Format Date
window.AGGridFormatters.formatDate('2024-01-01'); // "2024-01-01"

// Truncate Text
window.AGGridFormatters.truncateText('Long text here', 10); // "Long text..."
```

## تایبەتمەندیەکان

### Default Options
- Pagination: 25 rows per page
- Page sizes: [10, 25, 50, 100]
- Sidebar: Columns & Filters panels
- RTL support for cell content
- LTR for grid structure
- Auto-size columns based on content
- Export to Excel

### Customization
دەتوانیت هەموو option ێک override بکەیت:

```javascript
const customOptions = {
    pagination: true,
    paginationPageSize: 50,
    paginationPageSizeSelector: [25, 50, 100, 200],
    // ... other options
};
```

## CSS Customization

دەتوانیت لە فایلی CSS ی تایبەتدا override بکەیت:

```css
/* Custom styles for specific grid */
#myGrid {
    height: 800px; /* Override default height */
}

/* Custom row styles */
.ag-theme-alpine .ag-row.my-custom-row {
    background-color: #f0f0f0;
}
```

## نموونەی تەواو

سەیری فایلەکانی خوارەوە بکە:
- `assets/js/sale/ag_grid_sale.js` - نموونەی تەواو بۆ Sales
- `assets/css/sale/ag_grid_sale.css` - نموونەی CSS تایبەت
