let gridApi = null;

// Action buttons cell renderer
function actionCellRenderer(params) {
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '5px';
    div.style.justifyContent = 'center';
    div.style.alignItems = 'center';
    
    const editBtn = document.createElement('button');
    editBtn.className = 'btn btn-sm btn-warning edit-person';
    editBtn.setAttribute('data-id', params.data.id);
    editBtn.setAttribute('data-name', params.data.name);
    editBtn.setAttribute('data-opening_debt_usd', params.data.opening_debt_usd || 0);
    editBtn.setAttribute('data-opening_debt_iqd', params.data.opening_debt_iqd || 0);
    editBtn.innerHTML = '<i class="fa fa-edit"></i>';
    editBtn.onclick = function(e) {
        e.stopPropagation();
        openEditModal(this);
    };
    
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-sm btn-danger delete-person';
    deleteBtn.setAttribute('data-id', params.data.id);
    deleteBtn.innerHTML = '<i class="fa fa-trash"></i>';
    deleteBtn.onclick = function(e) {
        e.stopPropagation();
        if (typeof deletePerson === 'function') {
            deletePerson(params.data.id);
        }
    };
    
    const detailsBtn = document.createElement('button');
    detailsBtn.className = 'btn btn-sm btn-info person-details';
    detailsBtn.setAttribute('data-id', params.data.id);
    detailsBtn.innerHTML = '<i class="fa fa-user"></i>';
    detailsBtn.onclick = function(e) {
        e.stopPropagation();
        window.location.href = `person_other_expenses_profile.php?id=${params.data.id}`;
    };
    
    div.appendChild(editBtn);
    div.appendChild(deleteBtn);
    div.appendChild(detailsBtn);
    
    return div;
}

function openEditModal(btn) {
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const opening_debt_usd = btn.dataset.opening_debt_usd || 0;
    const opening_debt_iqd = btn.dataset.opening_debt_iqd || 0;
    
    document.getElementById('edit_person_id').value = id;
    document.getElementById('edit_person_name').value = name;
    document.getElementById('edit_person_expense_usd').value = 0;
    document.getElementById('edit_person_expense_iqd').value = 0;
    document.getElementById('edit_opening_debt_usd').value = opening_debt_usd;
    document.getElementById('edit_opening_debt_iqd').value = opening_debt_iqd;
    const modal = new bootstrap.Modal(document.getElementById('editPersonModal'));
    modal.show();
}

// Format USD currency
function formatUSD(params) {
    if (params.value == null) return '$0.00';
    return `$${parseFloat(params.value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

// Format IQD currency
function formatIQD(params) {
    if (params.value == null) return '0 دینار';
    const num = parseFloat(params.value);
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(2)}M دینار`;
    } else if (num >= 1000) {
        return `${(num / 1000).toFixed(2)}K دینار`;
    } else {
        return `${num.toLocaleString('ar-EG')} دینار`;
    }
}

// Column definitions for AG Grid (RTL layout)
const columnDefs = [
    {
        headerName: 'کردارەکان',
        field: 'actions',
        width: 180,
        cellRenderer: actionCellRenderer,
        sortable: false,
        filter: false,
        pinned: 'left', // Right side for RTL
        cellStyle: { textAlign: 'center' }
    },
    {
        headerName: 'قەرزی سەرەتایی (دینار)',
        field: 'opening_debt_iqd',
        width: 180,
        valueFormatter: formatIQD,
        filter: 'agNumberColumnFilter',
        cellStyle: { textAlign: 'center' }
    },
    {
        headerName: 'قەرزی سەرەتایی (دۆلار)',
        field: 'opening_debt_usd',
        width: 180,
        valueFormatter: formatUSD,
        filter: 'agNumberColumnFilter',
        cellStyle: { textAlign: 'center' }
    },
    {
        headerName: 'ناوی کەس',
        field: 'name',
        width: 200,
        flex: 1,
        filter: 'agTextColumnFilter',
        cellStyle: { textAlign: 'right' }
    },
    {
        headerName: '#',
        field: 'rowNum',
        width: 80,
        cellRenderer: function(params) {
            return params.node.rowIndex + 1;
        },
        sortable: false,
        filter: false,
        pinned: 'left', // Left side for RTL
        cellStyle: { textAlign: 'center' }
    }
];

// Grid options
const gridOptions = {
    columnDefs: columnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        floatingFilter: true,
    },
    pagination: true,
    paginationPageSize: 20,
    paginationPageSizeSelector: [10, 20, 50, 100],
    rowHeight: 50,
    animateRows: true,
    localeText: {
        // Kurdish RTL localization
        page: 'پەڕە',
        more: 'زیاتر',
        to: 'بۆ',
        of: 'لە',
        next: 'دواتر',
        last: 'کۆتایی',
        first: 'سەرەتا',
        previous: 'پێشوو',
        loadingOoo: 'چاوەڕوان بە...',
        noRowsToShow: 'هیچ داتایەک نییە',
        filterOoo: 'فلتەر...',
        equals: 'یەکسان',
        notEqual: 'نا یەکسان',
        contains: 'تێدایە',
        notContains: 'تێدا نییە',
        startsWith: 'دەست پێدەکات',
        endsWith: 'کۆتایی دێت',
        searchOoo: 'گەڕان...',
        selectAll: 'هەمووی هەڵبژێرە',
    },
    onGridReady: function(params) {
        gridApi = params.api;
        loadPersons();
    },
    domLayout: 'normal',
    suppressMenuHide: true,
};

async function loadPersons() {
    try {
        const res = await fetch('../process/person_other_expenses/select_person.php');
        const data = await res.json();
        
        // Update summary cards
        if (data.summary) {
            updateSummaryCards(data.summary);
        }
        
        // Handle persons data (backward compatibility)
        const persons = data.persons || data;
        
        // Transform data for AG Grid (add rowNum and keep original fields)
        const rowData = persons.map((row, idx) => ({
            id: row.id,
            name: row.name,
            opening_debt_usd: parseFloat(row.opening_debt_usd) || 0,
            opening_debt_iqd: parseFloat(row.opening_debt_iqd) || 0,
            rowNum: idx + 1
        }));
        
        // Set row data to grid
        if (gridApi) {
            gridApi.setRowData(rowData);
        }
    } catch (error) {
        console.error('Error loading persons:', error);
        Swal.fire('هەڵە!', 'هەڵەیەک لە بارکردنی داتاکاندا ڕویدا', 'error');
    }
}

function updateSummaryCards(summary) {
    // Format numbers
    const formatUSD = (amount) => `$${parseFloat(amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const formatIQD = (amount) => {
        const num = parseFloat(amount || 0);
        if (num >= 1000000) {
            return `${(num / 1000000).toFixed(2)}M دینار`;
        } else if (num >= 1000) {
            return `${(num / 1000).toFixed(2)}K دینار`;
        } else {
            return `${num.toLocaleString('ar-EG')} دینار`;
        }
    };
    
    // Update USD card
    document.getElementById('totalDebtUSD').textContent = formatUSD(summary.total_debt_usd);
    document.getElementById('otherExpensesUSD').textContent = formatUSD(summary.other_expenses_debt.usd);
    document.getElementById('purchaseMaterialsUSD').textContent = formatUSD(summary.purchase_materials_debt.usd);
    document.getElementById('personsOpeningUSD').textContent = formatUSD(summary.persons_opening_debt.usd);
    
    // Update IQD card
    document.getElementById('totalDebtIQD').textContent = formatIQD(summary.total_debt_iqd);
    document.getElementById('otherExpensesIQD').textContent = formatIQD(summary.other_expenses_debt.iqd);
    document.getElementById('purchaseMaterialsIQD').textContent = formatIQD(summary.purchase_materials_debt.iqd);
    document.getElementById('personsOpeningIQD').textContent = formatIQD(summary.persons_opening_debt.iqd);
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AG Grid
    const gridDiv = document.querySelector('#personTable');
    if (gridDiv) {
        new agGrid.Grid(gridDiv, gridOptions);
    }
});