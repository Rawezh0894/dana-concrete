/**
 * Raw Material Sales - Main JavaScript
 * Handles all client-side functionality for raw material sales
 */

$(document).ready(function() {
    // Initialize
    initializeSelect2();
    loadSales();
    setupEventListeners();
    setDefaultDates();
});

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('.select2-customer, .select2-company').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#addSaleModal'),
        allowClear: true,
        placeholder: 'هەڵبژێرە...'
    });
}

/**
 * Set default date filters to current month
 */
function setDefaultDates() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    $('#filterFrom').val(formatDate(firstDay));
    $('#filterTo').val(formatDate(lastDay));
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

/**
 * Setup all event listeners
 */
function setupEventListeners() {
    // Buyer type change - Add form
    $('input[name="buyer_type"]').on('change', function() {
        updateBuyerFields($(this).val(), 'add');
    });

    // Buyer type change - Edit form
    $('#editSaleModal input[name="buyer_type"]').on('change', function() {
        updateBuyerFields($(this).val(), 'edit');
    });

    // Bin selection change - Add form
    $('#binSelect').on('change', function() {
        updateBinInfo($(this), 'add');
    });

    // Bin selection change - Edit form
    $('#editBinId').on('change', function() {
        updateBinInfo($(this), 'edit');
    });

    // Quantity/Price change - Add form
    $('input[name="quantity_kg"], input[name="unit_price"]').on('input', function() {
        calculateTotal('add');
    });

    // Quantity/Price change - Edit form
    $('#editQuantityKg, #editUnitPrice').on('input', function() {
        calculateTotal('edit');
    });

    // Payment type change - Add form
    $('select[name="payment_type"]').on('change', function() {
        if ($(this).val() === 'نەقد') {
            const total = parseFloat($('input[name="total_price"]').val()) || 0;
            $('input[name="paid_amount"]').val(total);
        }
    });

    // Payment type change - Edit form
    $('#editPaymentType').on('change', function() {
        if ($(this).val() === 'نەقد') {
            const total = parseFloat($('#editTotalPrice').val()) || 0;
            $('#editPaidAmount').val(total);
        }
    });

    // Filter changes
    $('#filterFrom, #filterTo, #filterBuyerType, #filterMaterial, #filterPayment').on('change', function() {
        loadSales();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#filterFrom, #filterTo').val('');
        $('#filterBuyerType, #filterMaterial, #filterPayment').val('');
        setDefaultDates();
        loadSales();
    });

    // Add sale form submit
    $('#addSaleForm').on('submit', function(e) {
        e.preventDefault();
        addSale();
    });

    // Edit sale form submit
    $('#editSaleForm').on('submit', function(e) {
        e.preventDefault();
        updateSale();
    });

    // Initialize buyer fields for add form
    updateBuyerFields('دەرەوە', 'add');
}

/**
 * Update buyer fields based on buyer type
 */
function updateBuyerFields(type, mode) {
    const prefix = mode === 'add' ? '' : 'edit';
    const suffix = mode === 'add' ? 'Fields' : 'Fields';
    
    if (mode === 'add') {
        // Hide all buyer fields first
        $('#customerFields, #companyFields, #externalNameField, #externalPhoneField').addClass('hidden');
        
        // Show relevant fields
        if (type === 'کڕیار') {
            $('#customerFields').removeClass('hidden');
        } else if (type === 'کۆمپانیا') {
            $('#companyFields').removeClass('hidden');
        } else {
            $('#externalNameField, #externalPhoneField').removeClass('hidden');
        }
    } else {
        // Edit form
        $('#editCustomerFields, #editCompanyFields, #editExternalNameField, #editExternalPhoneField').addClass('hidden');
        
        if (type === 'کڕیار') {
            $('#editCustomerFields').removeClass('hidden');
        } else if (type === 'کۆمپانیا') {
            $('#editCompanyFields').removeClass('hidden');
        } else {
            $('#editExternalNameField, #editExternalPhoneField').removeClass('hidden');
        }
    }
}

/**
 * Update bin info display
 */
function updateBinInfo($select, mode) {
    const option = $select.find('option:selected');
    const infoDiv = mode === 'add' ? '#binInfo' : '#editBinInfo';
    const currencyLabel = mode === 'add' ? '#currencyLabel, #totalCurrencyLabel' : '#editCurrencyLabel, #editTotalCurrencyLabel';
    
    if (option.val()) {
        const material = option.data('material');
        const available = parseFloat(option.data('available'));
        const price = parseFloat(option.data('price'));
        const currency = option.data('currency');
        
        $(infoDiv).html(`
            <i class="bi bi-info-circle text-primary"></i>
            جۆری مەواد: <strong>${material}</strong> | 
            بڕی بەردەست: <strong>${number_format(available, 2)} کگم</strong> | 
            نرخی تێچوو: <strong>${number_format(price, 4)} ${currency === 'دۆلار' ? '$' : 'د.ع'}</strong>
        `);
        
        // Update currency labels
        $(currencyLabel).text(currency === 'دۆلار' ? '$' : 'د.ع');
    } else {
        $(infoDiv).html('');
    }
}

/**
 * Calculate total price
 */
function calculateTotal(mode) {
    let quantity, unitPrice, totalField, paidField, paymentType;
    
    if (mode === 'add') {
        quantity = parseFloat($('input[name="quantity_kg"]').val()) || 0;
        unitPrice = parseFloat($('input[name="unit_price"]').val()) || 0;
        totalField = $('input[name="total_price"]');
        paidField = $('input[name="paid_amount"]');
        paymentType = $('select[name="payment_type"]').val();
    } else {
        quantity = parseFloat($('#editQuantityKg').val()) || 0;
        unitPrice = parseFloat($('#editUnitPrice').val()) || 0;
        totalField = $('#editTotalPrice');
        paidField = $('#editPaidAmount');
        paymentType = $('#editPaymentType').val();
    }
    
    const total = quantity * unitPrice;
    totalField.val(total.toFixed(4));
    
    // Auto-fill paid amount for cash payments
    if (paymentType === 'نەقد') {
        paidField.val(total.toFixed(4));
    }
}

/**
 * Load sales data
 */
function loadSales() {
    const params = new URLSearchParams();
    
    if ($('#filterFrom').val()) params.append('from', $('#filterFrom').val());
    if ($('#filterTo').val()) params.append('to', $('#filterTo').val());
    if ($('#filterBuyerType').val()) params.append('buyer_type', $('#filterBuyerType').val());
    if ($('#filterMaterial').val()) params.append('material_type', $('#filterMaterial').val());
    if ($('#filterPayment').val()) params.append('payment_type', $('#filterPayment').val());
    
    $.ajax({
        url: '../process/raw_material_sales/select.php?' + params.toString(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderTable(response.data);
                updateSummary(response.summary);
            } else {
                showError(response.message || 'هەڵە لە وەرگرتنی داتا');
            }
        },
        error: function(xhr, status, error) {
            console.error('Load sales error:', error);
            showError('هەڵەی تەکنیکی ڕویدا');
        }
    });
}

/**
 * Render sales table
 */
function renderTable(sales) {
    const tbody = $('#salesTableBody');
    tbody.empty();
    
    if (!sales || sales.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="13" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    هیچ فرۆشتنێک نەدۆزرایەوە
                </td>
            </tr>
        `);
        return;
    }
    
    sales.forEach((sale, index) => {
        const currencySymbol = sale.currency_type === 'دۆلار' ? '$' : 'د.ع';
        const profitClass = parseFloat(sale.profit_amount) >= 0 ? 'profit-positive' : 'profit-negative';
        
        // Buyer type badge
        let buyerBadge = '';
        if (sale.buyer_type === 'کڕیار') {
            buyerBadge = '<span class="badge bg-primary buyer-type-badge">کڕیار</span>';
        } else if (sale.buyer_type === 'کۆمپانیا') {
            buyerBadge = '<span class="badge bg-success buyer-type-badge">کۆمپانیا</span>';
        } else {
            buyerBadge = '<span class="badge bg-secondary buyer-type-badge">دەرەوە</span>';
        }
        
        // Currency badge
        const currencyBadge = sale.currency_type === 'دۆلار' 
            ? '<span class="badge currency-badge currency-usd">USD</span>'
            : '<span class="badge currency-badge currency-iqd">IQD</span>';
        
        let actionsHtml = '';
        if (window.userPermissions.canEdit) {
            actionsHtml += `<button class="btn btn-sm btn-warning me-1" onclick="editSale(${sale.id})" title="نوێکردنەوە"><i class="bi bi-pencil"></i></button>`;
        }
        if (window.userPermissions.canDelete) {
            actionsHtml += `<button class="btn btn-sm btn-danger" onclick="deleteSale(${sale.id}, '${sale.invoice_number}')" title="سڕینەوە"><i class="bi bi-trash"></i></button>`;
        }
        
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${sale.invoice_number}</strong></td>
                <td>${sale.sale_date}</td>
                <td>
                    ${buyerBadge}<br>
                    <small>${sale.buyer_name || '-'}</small>
                </td>
                <td>${sale.bin_name || '-'}</td>
                <td>${sale.material_type} ${currencyBadge}</td>
                <td>${number_format(sale.quantity_kg, 2)}</td>
                <td>${number_format(sale.unit_price, 4)} ${currencySymbol}</td>
                <td><strong>${number_format(sale.total_price, 2)} ${currencySymbol}</strong></td>
                <td>${number_format(sale.paid_amount, 2)} ${currencySymbol}</td>
                <td class="${parseFloat(sale.remaining_amount) > 0 ? 'text-danger' : ''}">${number_format(sale.remaining_amount, 2)} ${currencySymbol}</td>
                <td class="${profitClass}">${number_format(sale.profit_amount, 2)} ${currencySymbol}</td>
                <td>${actionsHtml || '-'}</td>
            </tr>
        `);
    });
}

/**
 * Update summary cards
 */
function updateSummary(summary) {
    $('#totalSales').text(summary.total_sales);
    $('#totalQuantity').text(number_format(summary.total_quantity_kg, 2) + ' کگم');
    $('#totalRevenueUSD').text('$' + number_format(summary.total_revenue_usd, 2));
    $('#totalRevenueIQD').text(number_format(summary.total_revenue_iqd, 0) + ' د.ع');
    
    const totalRemaining = summary.total_remaining_usd + (summary.total_remaining_iqd / 150000);
    $('#totalRemaining').text('$' + number_format(totalRemaining, 2));
    
    const totalProfit = summary.total_profit_usd + (summary.total_profit_iqd / 150000);
    $('#totalProfit').text('$' + number_format(totalProfit, 2));
}

/**
 * Add new sale
 */
function addSale() {
    const formData = new FormData($('#addSaleForm')[0]);
    
    $.ajax({
        url: '../process/raw_material_sales/add.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message || 'فرۆشتنەکە بە سەرکەوتووی زیادکرا');
                $('#addSaleModal').modal('hide');
                $('#addSaleForm')[0].reset();
                updateBuyerFields('دەرەوە', 'add');
                loadSales();
            } else {
                showError(response.message || 'هەڵە لە زیادکردن');
            }
        },
        error: function(xhr, status, error) {
            console.error('Add sale error:', error);
            showError('هەڵەی تەکنیکی ڕویدا');
        }
    });
}

/**
 * Edit sale - load data into modal
 */
function editSale(id) {
    $.ajax({
        url: '../process/raw_material_sales/select.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                const sale = response.data[0];
                populateEditForm(sale);
                $('#editSaleModal').modal('show');
            } else {
                showError('فرۆشتنەکە نەدۆزرایەوە');
            }
        },
        error: function() {
            showError('هەڵەی تەکنیکی ڕویدا');
        }
    });
}

/**
 * Populate edit form with sale data
 */
function populateEditForm(sale) {
    $('#editSaleId').val(sale.id);
    $('#editInvoiceNumber').val(sale.invoice_number);
    $('#editSaleDate').val(sale.sale_date);
    
    // Set buyer type
    $(`#editSaleModal input[name="buyer_type"][value="${sale.buyer_type}"]`).prop('checked', true);
    updateBuyerFields(sale.buyer_type, 'edit');
    
    // Set buyer info
    $('#editCustomerId').val(sale.customer_id);
    $('#editCompanyId').val(sale.company_id);
    $('#editExternalBuyerName').val(sale.external_buyer_name);
    $('#editExternalBuyerPhone').val(sale.external_buyer_phone);
    
    // Set bin and material info
    $('#editBinId').val(sale.bin_id);
    updateBinInfo($('#editBinId'), 'edit');
    
    // Set quantity and price
    $('#editQuantityKg').val(sale.quantity_kg);
    $('#editUnitPrice').val(sale.unit_price);
    $('#editTotalPrice').val(sale.total_price);
    
    // Set payment info
    $('#editPaymentType').val(sale.payment_type);
    $('#editPaidAmount').val(sale.paid_amount);
    $('#editExchangeRate').val(sale.exchange_rate);
    
    // Set notes
    $('#editNotes').val(sale.notes);
}

/**
 * Update sale
 */
function updateSale() {
    const formData = new FormData($('#editSaleForm')[0]);
    
    $.ajax({
        url: '../process/raw_material_sales/update.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message || 'فرۆشتنەکە بە سەرکەوتووی نوێکرایەوە');
                $('#editSaleModal').modal('hide');
                loadSales();
            } else {
                showError(response.message || 'هەڵە لە نوێکردنەوە');
            }
        },
        error: function() {
            showError('هەڵەی تەکنیکی ڕویدا');
        }
    });
}

/**
 * Delete sale
 */
function deleteSale(id, invoiceNumber) {
    Swal.fire({
        title: 'دڵنیایت؟',
        text: `ئایا دڵنیایت لە سڕینەوەی فرۆشتنی پسووڵە ${invoiceNumber}؟ بڕی مەواد دەگەڕێتەوە بۆ کۆگا.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بیسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/raw_material_sales/delete.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.message || 'فرۆشتنەکە سڕایەوە');
                        loadSales();
                    } else {
                        showError(response.message || 'هەڵە لە سڕینەوە');
                    }
                },
                error: function() {
                    showError('هەڵەی تەکنیکی ڕویدا');
                }
            });
        }
    });
}

/**
 * Format number with commas
 */
function number_format(number, decimals = 2) {
    if (isNaN(number)) return '0';
    return parseFloat(number).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * Show success message
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'سەرکەوتوو',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * Show error message
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە',
        text: message
    });
}
