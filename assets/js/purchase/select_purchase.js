async function loadPurchases(filterParams = '') {
    // Build URL with filters
    let url = '../process/purchase/select_purchase.php';
    if (filterParams) {
        url += '?' + filterParams;
    }
    
    let res = await fetch(url);
    let text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_purchase.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
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
        '#': idx + 1,
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
    TableController.renderWithPagination('#purchaseTable', mapped, columns, { pageSize: 10 });
}
document.addEventListener('DOMContentLoaded', loadPurchases);

// Function to handle dynamic price per kg fields in edit modal
function handleEditTypeChange() {
    const typeSelect = document.getElementById('edit_type');
    const iqdGroup = document.getElementById('edit_pricePerKgIqdGroup');
    const usdGroup = document.getElementById('edit_pricePerKgUsdGroup');
    
    if (typeSelect && iqdGroup && usdGroup) {
        if (typeSelect.value === 'دینار') {
            iqdGroup.style.display = 'block';
            usdGroup.style.display = 'none';
            document.getElementById('edit_price_per_kg_usd').value = '0';
        } else if (typeSelect.value === 'دۆلار') {
            iqdGroup.style.display = 'none';
            usdGroup.style.display = 'block';
            document.getElementById('edit_price_per_kg_iqd').value = '0';
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
    }
}

// Function to properly set select2 values
function setSelect2Value(selectElement, value) {
    console.log(`Setting select2 value for ${selectElement.id}:`, value);
    console.log(`Element type:`, selectElement.tagName);
    console.log(`Select2 initialized:`, $(selectElement).hasClass('select2-hidden-accessible'));
    
    if ($(selectElement).hasClass('select2-hidden-accessible')) {
        // This is a select2 element (either original SELECT or hidden input)
        $(selectElement).val(value).trigger('change');
        
        // Force select2 to update its display
        setTimeout(() => {
            $(selectElement).trigger('change.select2');
            // Additional force refresh
            $(selectElement).select2('destroy');
            $(selectElement).select2({
                dropdownParent: $('#editPurchaseModal'),
                width: '100%',
                placeholder: "هەڵبژێرە",
                dir: "rtl"
            });
            $(selectElement).val(value).trigger('change');
        }, 100);
        
        console.log(`Value set via select2 for ${selectElement.id}`);
    } else {
        // If select2 is not initialized yet, set the value directly and trigger change
        selectElement.value = value || '';
        $(selectElement).trigger('change');
        console.log(`Value set directly for ${selectElement.id}`);
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
            console.log('Driver ID from data:', data.driver_id);
            console.log('Location ID from data:', data.location_id);
            
            // Show modal first
            const modal = new bootstrap.Modal(document.getElementById('editPurchaseModal'));
            modal.show();
            
            // Wait for modal to be fully shown, then initialize select2 and populate fields
            $('#editPurchaseModal').off('shown.bs.modal.edit').on('shown.bs.modal.edit', function() {
                // Check if elements exist
                const driverSelect = document.getElementById('edit_driver_id');
                const locationSelect = document.getElementById('edit_location_id');
                console.log('Driver select element:', driverSelect);
                console.log('Location select element:', locationSelect);
                
                // Initialize select2 for edit modal
                initializeEditModalSelect2();
                
                // Wait a bit for select2 to initialize, then populate fields
                setTimeout(() => {
                    // Ensure select2 is fully rendered
                    $('#edit_driver_id, #edit_location_id').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                            $(this).select2({
                                dropdownParent: $('#editPurchaseModal'),
                                width: '100%',
                                placeholder: "هەڵبژێرە",
                                dir: "rtl"
                            });
                        }
                    });
                    
                    // Wait a bit more for select2 to be ready, then populate fields
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
                    'price_per_kg_iqd': 'edit_price_per_kg_iqd',
                    'price_per_kg_usd': 'edit_price_per_kg_usd',
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
                        const value = data[dataKey];
                        console.log(`Found input ${inputId}, setting value:`, value);
                        
                        // Check if it's a select2 element (either SELECT or hidden input with select2 class)
                        if (input.tagName === 'SELECT' || input.classList.contains('select2-hidden-accessible')) {
                            setSelect2Value(input, value);
                        } else {
                            input.value = value ?? '';
                        }
                        console.log(`Setting ${inputId} to:`, value);
                    } else {
                        console.warn(`Input element not found: ${inputId}`);
                    }
                }
                        
                        // Handle dynamic price per kg fields
                        handleEditTypeChange();
                        
                        // Trigger change events for dynamic fields
                        const typeSelect = document.getElementById('edit_type');
                        if (typeSelect) {
                            typeSelect.dispatchEvent(new Event('change'));
                        }
                    }, 100);
                }, 200);
            });
            
        } catch (error) {
            console.error('Error loading purchase for edit:', error);
            Swal.fire('هەڵە!', 'هەڵەیەک لە وەرگرتنی داتاکان هەیە', 'error');
        }
    }
});

async function loadPurchasesFiltered() {
    const from = document.getElementById('filter_from').value;
    const to = document.getElementById('filter_to').value;
    let url = '../process/purchase/select_purchase.php';
    const params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (params.length) url += '?' + params.join('&');
    let res = await fetch(url);
    let text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_purchase.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
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
        '#': idx + 1,
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
    TableController.renderWithPagination('#purchaseTable', mapped, columns, { pageSize: 10 });
}

// Remove filter and clear button event listeners
// Instead, filter automatically on input change
const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', loadPurchasesFiltered);
    toInput.addEventListener('input', loadPurchasesFiltered);
}

document.addEventListener('DOMContentLoaded', loadPurchases);

const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        document.getElementById('filter_from').value = '';
        document.getElementById('filter_to').value = '';
        loadPurchasesFiltered();
    });
}
