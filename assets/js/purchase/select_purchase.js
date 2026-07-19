let currentPurchasePage = 1;
let currentFilterParams = '';
let currentSearchTerm = '';
let purchaseSearchTimeout = null;

function refreshPurchaseTable(options = {}) {
    const { resetPage = false } = options;
    const targetPage = resetPage ? 1 : currentPurchasePage || 1;
    
    if (typeof loadPurchases === 'function') {
        loadPurchases(currentFilterParams || '', targetPage, currentSearchTerm || '');
    }
}

window.refreshPurchaseTable = refreshPurchaseTable;

async function loadPurchases(filterParams = '', page = 1, searchTerm = '') {
    currentPurchasePage = page;
    currentFilterParams = filterParams;
    currentSearchTerm = searchTerm;
    
    // Build request data
    const requestData = new FormData();
    
    // Add basic filters from URL params
    const params = new URLSearchParams(filterParams);
    for (const [key, value] of params) {
        if (value) {
            requestData.append(key, value);
        }
    }
    
    // Add pagination
    requestData.append('page', page);
    requestData.append('limit', 10);
    
    // Add search term
    if (searchTerm) {
        requestData.append('search', searchTerm);
    }
    
    // Add column filters (this is the main fix for URL length issue)
    if (Object.keys(activeColumnFilters).length > 0) {
        requestData.append('column_filters', JSON.stringify(activeColumnFilters));
    }
    
    // Use POST method to avoid URL length issues
    const response = await fetch('../process/purchase/select_purchase.php', {
        method: 'POST',
        body: requestData
    });
    
    const text = await response.text();
    let result;
    try {
        result = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_purchase.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
    }
    
    // Handle both old and new response formats
    let data;
    if (result.success && Array.isArray(result.data)) {
        data = result.data;
    } else if (Array.isArray(result)) {
        data = result;
        result = { data: result }; // Wrap for consistency
    } else {
        console.error('Unexpected response format:', result);
        data = [];
    }
    
    const columns = [
        '#', 'company_name', 'location_name', 'driver_name', 'invoice_number', 'material_name', 'date',
        'payment_type', 'type', 'kg', 'price_per_kg_usd', 'price_per_kg_iqd', 'price', 'amount_iqd', 'exchange_rate',
        'paid_usd', 'paid_iqd', 'remaining_usd', 'remaining_iqd', 'bin_name', 'actions'
    ];
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function formatUSD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(2)) + ' $';
    }
    function formatIQD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(0)) + ' د.ع';
    }
    const mapped = data.map((row, idx) => ({
        '#': ((page - 1) * 10) + idx + 1,
        company_name: row.company_name || '',
        location_name: row.location_name || row.location || '',
        driver_name: row.driver_name || row.driver || '',
        invoice_number: row.invoice_number || '',
        material_name: row.material_name || '',
        date: row.date || '',
        payment_type: row.payment_type || '',
        type: row.type || '',
        kg: formatNumber(row.kg),
        price_per_kg_usd: formatUSD(row.price_per_kg_usd),
        price_per_kg_iqd: formatIQD(row.price_per_kg_iqd),
        price: row.type === 'دینار' ? formatIQD(row.price) : (row.type === 'دۆلار' ? formatUSD(row.price) : formatNumber(row.price)),
        amount_iqd: formatIQD(row.amount_iqd),
        exchange_rate: formatNumber(row.exchange_rate),
        paid_usd: formatUSD(row.paid_usd),
        paid_iqd: formatIQD(row.paid_iqd),
        remaining_usd: formatUSD(row.remaining_usd),
        remaining_iqd: formatIQD(row.remaining_iqd),
        bin_name: row.bin_name || row.bin_id || '',
        actions: `${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-primary btn-sm edit-purchase' data-id='${row.id}' title='دەستکاری'><i class='fa fa-edit'></i></button>` : ''} ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-danger btn-sm delete-purchase' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}`
    }));
    
    // Store original data for filter dropdowns
    originalPurchaseData = mapped;
    
    // Render table without client-side pagination
    TableController.render('#purchaseTable', mapped, columns);
    
    // Add Excel-style column filters
    addExcelStyleFilters('#purchaseTable', mapped, columns);
    
    // Render server-side pagination if available
    if (result.pagination) {
        renderPurchasePagination(result.pagination, data.length);
    }
}

// Store original data and active filters
let originalPurchaseData = [];
let activeColumnFilters = {};

// Add Excel-style dropdown filters to table headers
function addExcelStyleFilters(tableSelector, data, columns) {
    // Don't override originalPurchaseData here, it's set in loadPurchases
    const table = document.querySelector(tableSelector);
    if (!table) return;
    
    const thead = table.querySelector('thead');
    if (!thead) return;
    
    let headerRow = thead.querySelector('tr');
    if (!headerRow) return;
    
    // Remove any existing filter icons
    headerRow.querySelectorAll('.excel-filter-icon').forEach(e => e.remove());
    
    // Add filter icon to each column (except # and actions)
    columns.forEach((col, idx) => {
        if (col === '#' || col === 'actions') return;
        
        const th = headerRow.children[idx];
        if (!th) return;
        
        // Create filter icon
        const filterIcon = document.createElement('i');
        filterIcon.className = 'fas fa-filter excel-filter-icon ms-2';
        filterIcon.style.cssText = 'cursor: pointer; font-size: 0.85rem; opacity: 0.6;';
        filterIcon.setAttribute('data-col', col);
        filterIcon.setAttribute('data-col-idx', idx);
        filterIcon.title = 'فلتەرکردن';
        
        // Check if column already has active filter
        if (activeColumnFilters[col] && activeColumnFilters[col].length > 0) {
            filterIcon.style.opacity = '1';
            filterIcon.style.color = '#28a745';
        }
        
        th.appendChild(filterIcon);
        
        // Add click event
        filterIcon.onclick = function(e) {
            e.stopPropagation();
            showFilterDropdown(col, idx, data, filterIcon);
        };
    });
}

// Show filter dropdown menu
async function showFilterDropdown(column, columnIdx, data, iconElement) {
    // Remove any existing dropdown
    document.querySelectorAll('.excel-filter-dropdown').forEach(d => d.remove());
    
    // Show loading dropdown
    const loadingDropdown = document.createElement('div');
    loadingDropdown.className = 'excel-filter-dropdown';
    loadingDropdown.style.cssText = `
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        min-width: 200px;
        padding: 20px;
        text-align: center;
    `;
    const iconRect = iconElement.getBoundingClientRect();
    loadingDropdown.style.top = (iconRect.bottom + window.scrollY) + 'px';
    
    // Smart positioning: check if there's enough space on the right
    const dropdownWidth = 200; // min width
    const spaceOnRight = window.innerWidth - iconRect.right;
    const spaceOnLeft = iconRect.left;
    
    if (spaceOnRight < dropdownWidth && spaceOnLeft > dropdownWidth) {
        // Not enough space on right, but enough on left - align to right edge
        loadingDropdown.style.right = (window.innerWidth - iconRect.right) + 'px';
        loadingDropdown.style.left = 'auto';
    } else {
        // Use default left alignment
        loadingDropdown.style.left = Math.max(10, iconRect.left) + 'px';
    }
    
    loadingDropdown.innerHTML = '<i class="fas fa-spinner fa-spin"></i> چاوەڕوان بە...';
    document.body.appendChild(loadingDropdown);
    
    // Get unique values from server for the entire dataset
    let uniqueValues = [];
    try {
        const response = await fetch(`../process/purchase/get_unique_values.php?column=${column}`);
        const result = await response.json();
        if (result.success) {
            uniqueValues = result.values;
        } else {
            // Fallback to local data if server fails
            uniqueValues = [...new Set(data.map(row => row[column]))].filter(v => v !== '' && v !== null && v !== undefined);
            uniqueValues.sort();
        }
    } catch (e) {
        console.error('Error fetching unique values:', e);
        // Fallback to local data
        uniqueValues = [...new Set(data.map(row => row[column]))].filter(v => v !== '' && v !== null && v !== undefined);
        uniqueValues.sort();
    }
    
    // Remove loading dropdown
    loadingDropdown.remove();
    
    // Create dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'excel-filter-dropdown';
    dropdown.style.cssText = `
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        min-width: 200px;
        max-height: 300px;
        overflow-y: auto;
        padding: 8px 0;
    `;
    
    // Position dropdown with smart positioning
    dropdown.style.top = (iconRect.bottom + window.scrollY) + 'px';
    
    // Check available space and position accordingly
    const minDropdownWidth = 250;
    const availableSpaceRight = window.innerWidth - iconRect.right;
    const availableSpaceLeft = iconRect.left;
    
    if (availableSpaceRight < minDropdownWidth && availableSpaceLeft > minDropdownWidth) {
        // Not enough space on right, position to the left of the icon
        dropdown.style.right = (window.innerWidth - iconRect.right) + 'px';
        dropdown.style.left = 'auto';
    } else {
        // Default: position to the right, but ensure minimum 10px from edge
        const leftPosition = Math.max(10, Math.min(iconRect.left, window.innerWidth - minDropdownWidth - 10));
        dropdown.style.left = leftPosition + 'px';
        dropdown.style.right = 'auto';
    }
    
    // Add search box
    const searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding: 8px 12px; border-bottom: 1px solid #eee;';
    searchDiv.innerHTML = `
        <input type="text" class="form-control form-control-sm filter-search-input" placeholder="گەڕان..." style="margin: 0;">
    `;
    dropdown.appendChild(searchDiv);
    
    // Add "Select All" option
    const selectAllDiv = document.createElement('div');
    selectAllDiv.style.cssText = 'padding: 6px 12px; border-bottom: 1px solid #eee; margin-bottom: 4px;';
    selectAllDiv.innerHTML = `
        <label style="cursor: pointer; display: block; margin: 0;">
            <input type="checkbox" class="filter-select-all" ${!activeColumnFilters[column] || activeColumnFilters[column].length === 0 ? 'checked' : ''} style="margin-left: 8px;">
            <span>هەڵبژاردنی هەموو</span>
        </label>
    `;
    dropdown.appendChild(selectAllDiv);
    
    // Create container for value checkboxes
    const valuesContainer = document.createElement('div');
    valuesContainer.className = 'filter-values-container';
    valuesContainer.style.cssText = 'max-height: 200px; overflow-y: auto;';
    
    // Add value checkboxes
    uniqueValues.forEach(value => {
        const div = document.createElement('div');
        div.className = 'filter-value-item';
        div.style.cssText = 'padding: 4px 12px; cursor: pointer;';
        div.setAttribute('data-value', value.toString().toLowerCase());
        div.onmouseenter = function() { this.style.background = '#f8f9fa'; };
        div.onmouseleave = function() { this.style.background = 'white'; };
        
        const isChecked = !activeColumnFilters[column] || activeColumnFilters[column].length === 0 || activeColumnFilters[column].includes(value);
        
        div.innerHTML = `
            <label style="cursor: pointer; display: block; margin: 0;">
                <input type="checkbox" class="filter-value-checkbox" value="${value}" ${isChecked ? 'checked' : ''} style="margin-left: 8px;">
                <span>${value}</span>
            </label>
        `;
        valuesContainer.appendChild(div);
    });
    
    dropdown.appendChild(valuesContainer);
    
    // Add action buttons
    const actionsDiv = document.createElement('div');
    actionsDiv.style.cssText = 'padding: 8px 12px; border-top: 1px solid #eee; margin-top: 4px; display: flex; gap: 8px;';
    actionsDiv.innerHTML = `
        <button class="btn btn-sm btn-success filter-apply-btn" style="flex: 1;">جێبەجێکردن</button>
        <button class="btn btn-sm btn-secondary filter-clear-btn" style="flex: 1;">پاککردنەوە</button>
    `;
    dropdown.appendChild(actionsDiv);
    
    document.body.appendChild(dropdown);
    
    // Search functionality
    const searchInput = dropdown.querySelector('.filter-search-input');
    searchInput.oninput = function() {
        const searchTerm = this.value.toLowerCase();
        const items = valuesContainer.querySelectorAll('.filter-value-item');
        
        items.forEach(item => {
            const value = item.getAttribute('data-value');
            if (value.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    };
    
    // Focus search input
    setTimeout(() => searchInput.focus(), 100);
    
    // Select All logic
    const selectAllCheckbox = dropdown.querySelector('.filter-select-all');
    const valueCheckboxes = dropdown.querySelectorAll('.filter-value-checkbox');
    
    selectAllCheckbox.onchange = function() {
        // Only select visible checkboxes
        const visibleItems = Array.from(valuesContainer.querySelectorAll('.filter-value-item'))
            .filter(item => item.style.display !== 'none');
        visibleItems.forEach(item => {
            const cb = item.querySelector('.filter-value-checkbox');
            if (cb) cb.checked = this.checked;
        });
    };
    
    valueCheckboxes.forEach(cb => {
        cb.onchange = function() {
            // Update "Select All" based on visible checkboxes
            const visibleCheckboxes = Array.from(valuesContainer.querySelectorAll('.filter-value-item'))
                .filter(item => item.style.display !== 'none')
                .map(item => item.querySelector('.filter-value-checkbox'));
            const allChecked = visibleCheckboxes.every(c => c && c.checked);
            selectAllCheckbox.checked = allChecked;
        };
    });
    
    // Apply button
    dropdown.querySelector('.filter-apply-btn').onclick = function() {
        const selectedValues = Array.from(valueCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        if (selectedValues.length === uniqueValues.length) {
            // All selected = no filter
            delete activeColumnFilters[column];
        } else {
            activeColumnFilters[column] = selectedValues;
        }
        
        applyColumnFilters();
        dropdown.remove();
    };
    
    // Clear button
    dropdown.querySelector('.filter-clear-btn').onclick = function() {
        delete activeColumnFilters[column];
        applyColumnFilters();
        dropdown.remove();
    };
    
    // Close dropdown when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}

// Apply column filters to table (server-side)
function applyColumnFilters() {
    // Reload data from server with column filters
    loadPurchases(currentFilterParams, 1, currentSearchTerm);
    
    // Update filter status badge
    updateFilterStatusBadge();
}

// Clear all column filters
function clearAllColumnFilters() {
    activeColumnFilters = {};
    applyColumnFilters();
}

// Update filter status badge
function updateFilterStatusBadge() {
    const activeFiltersCount = Object.keys(activeColumnFilters).length;
    const btn = document.getElementById('clearColumnFiltersBtn');
    
    if (btn) {
        if (activeFiltersCount > 0) {
            btn.innerHTML = `<i class="fas fa-filter-circle-xmark me-1"></i>پاککردنەوەی فلتەرەکانی کۆڵۆم <span class="badge bg-danger ms-1">${activeFiltersCount}</span>`;
        } else {
            btn.innerHTML = `<i class="fas fa-filter-circle-xmark me-1"></i>پاککردنەوەی فلتەرەکانی کۆڵۆم`;
        }
    }
}

function renderPurchasePagination(pagination, currentRecordsCount) {
    let paginationHtml = '<nav class="mt-3"><ul class="pagination justify-content-center">';
    
    // Previous button
    if (pagination.has_prev) {
        paginationHtml += `<li class="page-item"><a class="page-link purchase-page-link" href="#" data-page="${pagination.current_page - 1}">پێشوو</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">پێشوو</span></li>`;
    }
    
    // Page numbers (show max 5 pages around current)
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link purchase-page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link purchase-page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `<li class="page-item"><a class="page-link purchase-page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHtml += `<li class="page-item"><a class="page-link purchase-page-link" href="#" data-page="${pagination.current_page + 1}">دواتر</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">دواتر</span></li>`;
    }
    
    paginationHtml += `</ul><p class="text-center text-muted mt-2">پیشاندانی ${currentRecordsCount} لە ${pagination.total_records} - پەڕە ${pagination.current_page} لە ${pagination.total_pages}</p></nav>`;
    
    // Remove existing pagination
    $('#purchaseTable').closest('.table-responsive').next('nav').remove();
    // Add new pagination
    $('#purchaseTable').closest('.table-responsive').after(paginationHtml);
}

// Pagination click handler
$(document).on('click', '.purchase-page-link', function(e) {
    e.preventDefault();
    const page = parseInt($(this).data('page'));
    if (page) {
        loadPurchases(currentFilterParams, page, currentSearchTerm);
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});

document.addEventListener('DOMContentLoaded', () => loadPurchases('', 1));

// Function to handle dynamic price per kg fields in edit modal
function handleEditTypeChange() {
    const typeSelect = document.getElementById('edit_type');
    const iqdGroup = document.getElementById('edit_materialCostIqdGroup');
    const usdGroup = document.getElementById('edit_materialCostUsdGroup');
    
    if (typeSelect && iqdGroup && usdGroup) {
        if (typeSelect.value === 'دینار') {
            iqdGroup.style.display = 'block';
            usdGroup.style.display = 'none';
            document.getElementById('edit_material_cost_usd').value = '0';
        } else if (typeSelect.value === 'دۆلار') {
            iqdGroup.style.display = 'none';
            usdGroup.style.display = 'block';
            document.getElementById('edit_material_cost_iqd').value = '0';
        } else {
            iqdGroup.style.display = 'block';
            usdGroup.style.display = 'block';
        }
    }
}

// Function to initialize select2 for edit modal
function initializeEditModalSelect2() {
    // Initialize select2 for driver and location in edit modal
    if (typeof enableSelect2 === 'function') {
        enableSelect2('#edit_driver_id', '#editPurchaseModal');
        enableSelect2('#edit_location_id', '#editPurchaseModal');
        enableSelect2('#edit_company_id', '#editPurchaseModal');
        enableSelect2('#edit_material_id', '#editPurchaseModal');
        enableSelect2('#edit_bin_id', '#editPurchaseModal');
    }
}

// Function to properly set select2 values
function setSelect2Value(selectElement, value) {
    if ($(selectElement).hasClass('select2-hidden-accessible')) {
        // This is a select2 element
        $(selectElement).val(value).trigger('change');
        
        // Force select2 to update its display
        setTimeout(() => {
            $(selectElement).trigger('change.select2');
        }, 50);
    } else {
        // Regular select element
        selectElement.value = value || '';
        $(selectElement).trigger('change');
    }
}

document.addEventListener('click', async function(e) {
    if (e.target.closest('.edit-purchase')) {
        const btn = e.target.closest('.edit-purchase');
        const id = btn.dataset.id;
        
        try {
            // وەرگرتنی زانیاری رکۆرد
            const res = await fetch(`../process/purchase/select_purchase.php?id=${id}`);
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const text = await res.text();
            console.log('Raw response for edit:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                console.error('Raw response:', text);
                Swal.fire('هەڵە!', 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە', 'error');
                return;
            }
            
            if (!data || Object.keys(data).length === 0) {
                Swal.fire('هەڵە!', 'هیچ داتایەک نەدۆزرایەوە', 'error');
                return;
            }
            
            console.log('Purchase data for edit:', data);
            
            const modalEl = document.getElementById('editPurchaseModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            
            $('#editPurchaseModal').off('shown.bs.modal.edit').on('shown.bs.modal.edit', function() {
                initializeEditModalSelect2();
                
                setTimeout(() => {
                    // پڕکردنەوەی خانەکان
                    const fieldMappings = {
                        'id': 'edit_id',
                        'company_id': 'edit_company_id',
                        'driver_id': 'edit_driver_id',
                        'location_id': 'edit_location_id',
                        'invoice_number': 'edit_invoice_number',
                        'material_id': 'edit_material_id',
                        'bin_id': 'edit_bin_id',
                        'date': 'edit_date',
                        'type': 'edit_type',
                        'kg': 'edit_kg',
                        'exchange_rate': 'edit_exchange_rate',
                        'payment_type': 'edit_payment_type',
                        'price': 'edit_price',
                        'amount_iqd': 'edit_amount_iqd',
                        'paid_usd': 'edit_paid_usd',
                        'paid_iqd': 'edit_paid_iqd',
                        'remaining_usd': 'edit_remaining_usd',
                        'remaining_iqd': 'edit_remaining_iqd'
                    };
                    
                    for (const [dataKey, inputId] of Object.entries(fieldMappings)) {
                        const input = document.getElementById(inputId);
                        if (input) {
                            let value = data[dataKey];
                            
                            // For select2 elements, we need to set the option text, not just the value
                            if (input.tagName === 'SELECT') {
                                if (inputId === 'edit_driver_id' && data.driver_name) {
                                    // For driver, set the option text to match the name
                                    $(input).find('option').each(function() {
                                        if ($(this).text().trim() === data.driver_name) {
                                            value = $(this).val();
                                        }
                                    });
                                } else if (inputId === 'edit_location_id' && data.location_name) {
                                    // For location, set the option text to match the name
                                    $(input).find('option').each(function() {
                                        if ($(this).text().trim() === data.location_name) {
                                            value = $(this).val();
                                        }
                                    });
                                } else if (inputId === 'edit_company_id' && data.company_name) {
                                    // For company, set the option text to match the name
                                    $(input).find('option').each(function() {
                                        if ($(this).text().trim() === data.company_name) {
                                            value = $(this).val();
                                        }
                                    });
                                } else if (inputId === 'edit_material_id' && data.material_name) {
                                    // For material, set the option text to match the name
                                    $(input).find('option').each(function() {
                                        if ($(this).text().trim() === data.material_name) {
                                            value = $(this).val();
                                        }
                                    });
                                } else if (inputId === 'edit_bin_id' && data.bin_name) {
                                    // For bin, set the option text to match the name
                                    $(input).find('option').each(function() {
                                        if ($(this).text().trim() === data.bin_name) {
                                            value = $(this).val();
                                        }
                                    });
                                }
                                
                                setSelect2Value(input, value);
                            } else {
                                input.value = value ?? '';
                            }
                            console.log(`Setting ${inputId} to:`, value);
                        } else {
                            console.warn(`Input element not found: ${inputId}`);
                        }
                    }
                    
                    // Set legacy hidden fields
                    if (document.getElementById('edit_price_per_kg_iqd')) document.getElementById('edit_price_per_kg_iqd').value = data.price_per_kg_iqd || '0';
                    if (document.getElementById('edit_price_per_kg_usd')) document.getElementById('edit_price_per_kg_usd').value = data.price_per_kg_usd || '0';
                    if (document.getElementById('edit_freight_price_per_kg_iqd')) document.getElementById('edit_freight_price_per_kg_iqd').value = data.freight_price_per_kg_iqd || '0';
                    if (document.getElementById('edit_freight_price_per_kg_usd')) document.getElementById('edit_freight_price_per_kg_usd').value = data.freight_price_per_kg_usd || '0';
                    if (document.getElementById('edit_total_freight_cost_iqd')) document.getElementById('edit_total_freight_cost_iqd').value = data.total_freight_cost_iqd || '0';
                    if (document.getElementById('edit_total_freight_cost_usd')) document.getElementById('edit_total_freight_cost_usd').value = data.total_freight_cost_usd || '0';
                    
                    // Custom calculation for material_cost and freight_cost based on kg
                    const kgTons = (parseFloat(data.kg) || 0) / 1000;
                    const materialCostIqd = kgTons * (parseFloat(data.price_per_kg_iqd) || 0);
                    const materialCostUsd = kgTons * (parseFloat(data.price_per_kg_usd) || 0);
                    const freightCostIqd = kgTons * (parseFloat(data.freight_price_per_kg_iqd) || 0);
                    const freightCostUsd = kgTons * (parseFloat(data.freight_price_per_kg_usd) || 0);
                    
                    if (document.getElementById('edit_material_cost_iqd')) document.getElementById('edit_material_cost_iqd').value = materialCostIqd.toFixed(0);
                    if (document.getElementById('edit_material_cost_usd')) document.getElementById('edit_material_cost_usd').value = materialCostUsd.toFixed(2);
                    if (document.getElementById('edit_freight_cost_iqd')) document.getElementById('edit_freight_cost_iqd').value = freightCostIqd.toFixed(0);
                    if (document.getElementById('edit_freight_cost_usd')) document.getElementById('edit_freight_cost_usd').value = freightCostUsd.toFixed(2);
                    
                    // Handle dynamic price per kg fields
                    handleEditTypeChange();
                    
                    // Trigger change events for dynamic fields
                    const typeSelect = document.getElementById('edit_type');
                    if (typeSelect) {
                        typeSelect.dispatchEvent(new Event('change'));
                    }
                }, 300);
            });
            
            modal.show();
            
        } catch (error) {
            console.error('Error loading purchase for edit:', error);
            Swal.fire('هەڵە!', 'هەڵەیەک لە وەرگرتنی داتاکان هەیە', 'error');
        }
    }
});
