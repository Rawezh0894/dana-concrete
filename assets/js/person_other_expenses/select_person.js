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
        pinned: 'right', // Right side for RTL
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
        console.log('Grid ready, loading persons...');
        // Load data first
        loadPersons();
        // Setup controls after a short delay to ensure DOM elements are available
        setTimeout(function() {
            setupGridControls();
        }, 200);
    },
    domLayout: 'normal',
    suppressMenuHide: true,
    enableCellTextSelection: true,
    suppressCellFocus: false,
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
            console.log('Setting row data to grid:', rowData.length, 'rows');
            gridApi.setRowData(rowData);
            // Update stats after data is loaded
            setTimeout(updateGridStats, 100);
            
            // Auto-size columns after data is set
            if (rowData.length > 0) {
                setTimeout(function() {
                    if (gridApi) {
                        gridApi.sizeColumnsToFit();
                    }
                }, 200);
            }
        } else {
            console.error('Grid API not available when trying to set row data');
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

// Setup grid controls (search, filters, export)
function setupGridControls() {
    // Search functionality
    const searchInput = document.getElementById('gridSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            const searchValue = e.target.value;
            
            if (searchValue.trim()) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (gridApi) {
                    gridApi.setQuickFilter(searchValue);
                    updateGridStats();
                }
            }, 300);
        });
        
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';
            if (gridApi) {
                gridApi.setQuickFilter('');
                updateGridStats();
            }
        });
    }
    
    // Reset filters button
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            if (gridApi) {
                gridApi.setFilterModel(null);
                gridApi.setQuickFilter('');
                if (searchInput) {
                    searchInput.value = '';
                    clearSearchBtn.style.display = 'none';
                }
                updateGridStats();
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: 'هەموو فیلتەرەکان پاککرایەوە',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    }
    
    // Export data button
    const exportDataBtn = document.getElementById('exportDataBtn');
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', function() {
            if (gridApi) {
                const params = {
                    fileName: 'persons_list_' + new Date().toISOString().split('T')[0],
                    allColumns: true
                };
                gridApi.exportDataAsCsv(params);
                Swal.fire({
                    icon: 'success',
                    title: 'دۆزرایەوە!',
                    text: 'فایل CSV بە سەرکەوتوویی دۆزرایەوە',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }
    
    // Update stats when filters change
    if (gridApi) {
        gridApi.addEventListener('filterChanged', updateGridStats);
        gridApi.addEventListener('rowDataUpdated', updateGridStats);
    }
}

// Update grid statistics
function updateGridStats() {
    if (!gridApi) return;
    
    const displayedRows = gridApi.getDisplayedRowCount();
    const totalRows = gridApi.getModel().getRowCount();
    
    const statsElement = document.getElementById('gridStats');
    if (statsElement) {
        if (displayedRows < totalRows) {
            statsElement.textContent = `${displayedRows} لە ${totalRows} کەس (فیلتەرکراو)`;
        } else {
            statsElement.textContent = `${displayedRows} کەس`;
        }
    }
}

// Initialize AG Grid when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for AG Grid...');
    
    // Wait for AG Grid library to be loaded
    function initGrid() {
        // Check if agGrid is available
        if (typeof agGrid === 'undefined') {
            console.warn('AG Grid library not loaded yet, retrying in 100ms...');
            setTimeout(initGrid, 100);
            return;
        }
        
        console.log('AG Grid library found, initializing grid...');
        
        // Initialize AG Grid
        const gridDiv = document.querySelector('#personTable');
        if (!gridDiv) {
            console.error('Grid container #personTable not found in DOM');
            return;
        }
        
        try {
            new agGrid.Grid(gridDiv, gridOptions);
            console.log('AG Grid initialized successfully');
        } catch (error) {
            console.error('Error initializing AG Grid:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک لە دامەزراندنی تابلدا ڕویدا: ' + (error.message || error),
                    confirmButtonText: 'باشە'
                });
            } else {
                alert('هەڵە! هەڵەیەک لە دامەزراندنی تابلدا ڕویدا: ' + (error.message || error));
            }
        }
    }
    
    // Start initialization with a small delay to ensure all scripts are loaded
    setTimeout(initGrid, 50);
});