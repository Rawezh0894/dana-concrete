let gridApi = null;
let gridColumnApi = null;

// Format numbers
function formatNumber(num) {
    return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatUSD(amount) {
    return `$${formatNumber(amount)}`;
}

function formatIQD(amount) {
    return `${formatNumber(amount)} د.ع`;
}

// AG Grid column definitions with consistent widths
const columnDefs = [
    {
        headerName: '#',
        field: 'index',
        width: 70,
        pinned: 'right',
        cellRenderer: (params) => params.node.rowIndex + 1,
        sortable: false,
        filter: false,
        suppressSizeToFit: true,
        lockPosition: true
    },
    {
        headerName: 'ناوی کەس',
        field: 'name',
        flex: 2,
        minWidth: 200,
        maxWidth: 400,
        sortable: true,
        filter: 'agTextColumnFilter',
        filterParams: {
            buttons: ['reset', 'apply'],
            debounceMs: 200
        },
        cellStyle: {
            justifyContent: 'flex-start',
            paddingRight: '15px'
        }
    },
    {
        headerName: 'قەرزی سەرەتایی (دۆلار)',
        field: 'opening_debt_usd',
        width: 200,
        sortable: true,
        filter: 'agNumberColumnFilter',
        filterParams: {
            buttons: ['reset', 'apply'],
            allowedCharPattern: '\\d\\-\\,\\.'
        },
        valueFormatter: (params) => {
            return params.value != null ? formatUSD(params.value) : formatUSD(0);
        },
        cellClass: 'text-end',
        suppressSizeToFit: true,
        comparator: (valueA, valueB) => {
            const numA = parseFloat(valueA) || 0;
            const numB = parseFloat(valueB) || 0;
            return numA - numB;
        }
    },
    {
        headerName: 'قەرزی سەرەتایی (دینار)',
        field: 'opening_debt_iqd',
        width: 200,
        sortable: true,
        filter: 'agNumberColumnFilter',
        filterParams: {
            buttons: ['reset', 'apply'],
            allowedCharPattern: '\\d\\-\\,\\.'
        },
        valueFormatter: (params) => {
            return params.value != null ? formatIQD(params.value) : formatIQD(0);
        },
        cellClass: 'text-end',
        suppressSizeToFit: true,
        comparator: (valueA, valueB) => {
            const numA = parseFloat(valueA) || 0;
            const numB = parseFloat(valueB) || 0;
            return numA - numB;
        }
    },
    {
        headerName: 'کردارەکان',
        field: 'actions',
        width: 160,
        pinned: 'left',
        sortable: false,
        filter: false,
        suppressSizeToFit: true,
        lockPosition: true,
        cellRenderer: (params) => {
            const person = params.data;
            return `
                <div style="display: flex; gap: 5px; justify-content: center; align-items: center;">
                    <button class="btn btn-sm btn-warning edit-person"
                        data-id="${person.id}"
                        data-name="${person.name}"
                        data-opening_debt_usd="${person.opening_debt_usd || 0}"
                        data-opening_debt_iqd="${person.opening_debt_iqd || 0}"
                        title="دەستکاری">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-person" 
                        data-id="${person.id}"
                        title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-info person-details" 
                        data-id="${person.id}"
                        title="پڕۆفایل">
                        <i class="fa fa-user"></i>
                    </button>
                </div>
            `;
        },
        cellClass: 'text-center'
    }
];

// AG Grid options
const gridOptions = {
    columnDefs: columnDefs,
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: false,
        suppressSizeToFit: false,
        minWidth: 100
    },
    rowData: [],
    pagination: true,
    paginationPageSize: 20,
    paginationPageSizeSelector: [10, 20, 50, 100],
    suppressColumnVirtualisation: true,
    animateRows: true,
    rowSelection: 'single',
    enableCellTextSelection: true,
    ensureDomOrder: true,
    localeText: {
        // Kurdish/RTL locale text overrides
        page: 'لاپەڕە',
        more: 'زیاتر',
        to: 'بۆ',
        of: 'لە',
        next: 'دواتر',
        last: 'کۆتایی',
        first: 'یەکەم',
        previous: 'پێشوو',
        loadingOoo: 'چاوەڕوان بە...',
        noRowsToShow: 'هیچ داتایەک نەدۆزرایەوە',
        filterOoo: 'فلتەر...',
        equals: 'یەکسان',
        notEqual: 'نا یەکسان',
        lessThan: 'بچووکتر لە',
        greaterThan: 'گەورەتر لە',
        contains: 'تێدایە',
        notContains: 'تێدانییە',
        startsWith: 'دەست پێدەکات',
        endsWith: 'کۆتای پێدێت',
        searchOoo: 'گەڕان...',
        applyFilter: 'جێبەجێکردن',
        resetFilter: 'گەڕاندنەوە',
        clearFilter: 'سڕینەوەی فلتەر',
        rows: 'ڕیزی',
        selectedRows: 'ڕیزی هەڵبژێردراو'
    },
    enableRtl: true,
    suppressHorizontalScroll: false,
    onFirstDataRendered: (params) => {
        // Auto-size columns to fit, but respect min/max widths
        params.api.sizeColumnsToFit();
    },
    onGridReady: (params) => {
        gridApi = params.api;
        gridColumnApi = params.columnApi;
        loadPersons();
    }
};

async function loadPersons() {
    try {
        // Show loading
        if (gridApi) {
            gridApi.showLoadingOverlay();
        }
        
        const res = await fetch('../process/person_other_expenses/select_person.php');
        const data = await res.json();
        
        // Update summary cards
        if (data.summary) {
            updateSummaryCards(data.summary);
        }
        
        // Handle persons data (backward compatibility)
        const persons = data.persons || data;
        
        // Format the data for AG Grid
        const rowData = persons.map((row, idx) => ({
            index: idx + 1,
            id: row.id,
            name: row.name || '',
            opening_debt_usd: parseFloat(row.opening_debt_usd || 0),
            opening_debt_iqd: parseFloat(row.opening_debt_iqd || 0)
        }));
        
        // Set row data
        if (gridApi) {
            gridApi.setRowData(rowData);
            gridApi.hideOverlay();
        }
        
        // Attach event listeners after data is loaded
        setTimeout(() => {
            attachAllPersonEvents();
        }, 100);
        
    } catch (error) {
        console.error('Error loading persons:', error);
        if (gridApi) {
            gridApi.hideOverlay();
            gridApi.showNoRowsOverlay();
        }
    }
}

// Function to attach all person-related event listeners
function attachAllPersonEvents() {
    // Attach edit person events
    if (typeof attachEditPersonEvents === 'function') {
        attachEditPersonEvents();
    }
    
    // Attach delete person events
    if (typeof attachDeletePersonEvents === 'function') {
        attachDeletePersonEvents();
    }
    
    // Attach person-details button click events
    document.querySelectorAll('.person-details').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            window.location.href = `person_other_expenses_profile.php?id=${id}`;
        };
    });
    
    // Attach edit and delete events for dynamically created buttons
    document.querySelectorAll('.edit-person').forEach(btn => {
        if (!btn.hasAttribute('data-listener-attached')) {
            btn.setAttribute('data-listener-attached', 'true');
            if (typeof attachEditPersonEvents === 'function') {
                // Individual button handler
                btn.onclick = function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const opening_debt_usd = this.dataset.opening_debt_usd || 0;
                    const opening_debt_iqd = this.dataset.opening_debt_iqd || 0;
                    
                    if (document.getElementById('edit_person_id')) {
                        document.getElementById('edit_person_id').value = id;
                        document.getElementById('edit_person_name').value = name;
                        document.getElementById('edit_opening_debt_usd').value = opening_debt_usd;
                        document.getElementById('edit_opening_debt_iqd').value = opening_debt_iqd;
                        const modal = new bootstrap.Modal(document.getElementById('editPersonModal'));
                        modal.show();
                    }
                };
            }
        }
    });
    
    document.querySelectorAll('.delete-person').forEach(btn => {
        if (!btn.hasAttribute('data-listener-attached')) {
            btn.setAttribute('data-listener-attached', 'true');
            btn.onclick = function() {
                const id = this.dataset.id;
                if (typeof deletePerson === 'function') {
                    deletePerson(id);
                }
            };
        }
    });
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

// Initialize AG Grid when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const gridDiv = document.querySelector('#personGrid');
    if (gridDiv) {
        new agGrid.Grid(gridDiv, gridOptions);
    }
});

// Export loadPersons function for use in other scripts
window.loadPersons = loadPersons;
