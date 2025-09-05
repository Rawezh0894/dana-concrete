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
    if ($(selectElement).hasClass('select2-hidden-accessible')) {
        $(selectElement).val(value).trigger('change');
    } else {
        selectElement.value = value || '';
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
                    if (input.tagName === 'SELECT') {
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
            
            // Initialize select2 for edit modal
            initializeEditModalSelect2();
            
            // show modal
            const modal = new bootstrap.Modal(document.getElementById('editPurchaseModal'));
            modal.show();
            
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
