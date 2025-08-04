$(document).ready(function() {
    // Initialize with data from PHP if available, otherwise load via AJAX
    if (window.initialMaterials && window.initialMaterials.length > 0) {
        window.materials = window.initialMaterials;
        window.persons = window.initialPersons || [];
    } else {
        // Load materials and persons for dropdowns
        loadMaterials();
        loadPersons();
    }
    
    // Load purchase materials table on page load without page refresh
    loadPurchaseMaterialsTable();
    
    // Filter functionality
    $('#filter_from, #filter_to').on('change', function() {
        loadPurchaseMaterialsTable();
    });
    
    // Clear filter button
    $('#clearFilterBtn').click(function() {
        $('#filter_from').val('');
        $('#filter_to').val('');
        loadPurchaseMaterialsTable();
    });

    // Auto-fill prices and unit type when material is selected in edit form
    $(document).on('change', '.edit-material-select', function() {
        const materialId = $(this).val();
        const row = $(this).closest('tr');
        
        if (materialId) {
            // Find the selected material from the materials array
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Auto-fill the unit type
                row.find('.edit-unit-type-display').text(material.unit_type || 'دانە');
                row.find('.edit-unit-type-input').val(material.unit_type || 'دانە');
                
                // Populate unit type dropdown with available options
                populateEditUnitTypeDropdown(row, material);
                
                // Auto-fill the price fields based on currency type
                const currencyType = $('#edit_currency_type').val();
                if (currencyType === 'دۆلار') {
                    row.find('.edit-price-usd-input').val(material.purchase_price_usd || 0);
                    row.find('.edit-price-iqd-input').val(0);
                } else if (currencyType === 'دینار') {
                    row.find('.edit-price-usd-input').val(0);
                    row.find('.edit-price-iqd-input').val(material.purchase_price_iqd || 0);
                } else {
                    row.find('.edit-price-usd-input').val(material.purchase_price_usd || 0);
                    row.find('.edit-price-iqd-input').val(material.purchase_price_iqd || 0);
                }
                
                // Calculate totals
                calculateEditRowTotal(row);
                calculateEditGrandTotal();
            }
        } else {
            // Clear fields if no material is selected
            row.find('.edit-unit-type-display').text('دانە');
            row.find('.edit-unit-type-input').val('دانە');
            row.find('.edit-unit-type-select').empty().append('<option value="دانە">دانە</option>');
            row.find('.edit-price-usd-input').val(0);
            row.find('.edit-price-iqd-input').val(0);
            calculateEditRowTotal(row);
            calculateEditGrandTotal();
        }
    });

    // Handle unit type change in edit modal
    $(document).on('change', '.edit-unit-type-select', function() {
        const row = $(this).closest('tr');
        const selectedUnitType = $(this).val();
        const materialId = row.find('.edit-material-select').val();
        
        if (materialId && selectedUnitType) {
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Calculate price based on selected unit type
                calculateEditPriceForUnitType(row, material, selectedUnitType);
                calculateEditRowTotal(row);
                calculateEditGrandTotal();
            }
        }
    });

    // Calculate totals when any input changes in edit form
    $(document).on('input', '.edit-material-row input', function() {
        calculateEditRowTotal($(this).closest('tr'));
        calculateEditGrandTotal();
    });

    // Calculate totals when transfer loss, other loss, or currency type changes in edit form
    $(document).on('input', '#edit_transfer_loss, #edit_other_loss', function() {
        calculateEditGrandTotal();
    });

    // Calculate remaining amounts when paid amounts change in edit form
    $(document).on('input', '#edit_paid_amount_usd, #edit_paid_amount_iqd', function() {
        calculateEditRemainingAmounts();
    });

    // Handle payment type change in edit form
    $(document).on('change', '#edit_payment_type', function() {
        const paymentType = $(this).val();
        
        if (paymentType === 'نەقد') {
            // For cash payment, clear remaining amounts
            $('#edit_remaining_amount_usd').val('');
            $('#edit_remaining_amount_iqd').val('');
        } else if (paymentType === 'قەرز') {
            // For credit payment, calculate remaining amounts
            calculateEditRemainingAmounts();
        }
    });

    // Calculate totals and remaining amounts when USD rate changes in edit form
    $(document).on('input', '#edit_usd_to_iqd_rate', function() {
        calculateEditGrandTotal();
        calculateEditRemainingAmounts();
    });

    $(document).on('change', '#edit_currency_type', function() {
        // Store current values as IQD before converting
        const currentTransferLoss = parseFloat($('#edit_transfer_loss').val()) || 0;
        const currentOtherLoss = parseFloat($('#edit_other_loss').val()) || 0;
        const usdToIqdRate = parseFloat($('#edit_usd_to_iqd_rate').val()) || 0;
        const oldCurrencyType = $('#edit_currency_type').attr('data-previous-value');
        
        // Convert current values to IQD if they were in USD
        let transferLossIqd = currentTransferLoss;
        let otherLossIqd = currentOtherLoss;
        
        if (oldCurrencyType === 'دۆلار') {
            transferLossIqd = currentTransferLoss * (usdToIqdRate / 100);
            otherLossIqd = currentOtherLoss * (usdToIqdRate / 100);
        }
        
        // Store IQD values
        $('#edit_transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#edit_other_loss').attr('data-iqd-value', otherLossIqd);
        
        // Update display based on new currency type
        if (typeof updateEditAdditionalCostsFields === 'function') {
            updateEditAdditionalCostsFields();
        }
        
        // Store new currency type
        $('#edit_currency_type').attr('data-previous-value', $(this).val());
        
        calculateEditGrandTotal();
    });

    // Load USD rate when edit modal opens
    $('#editPurchaseModal').on('show.bs.modal', function() {
        loadEditUsdRate();
    });
});

// Load USD to IQD exchange rate for edit form
function loadEditUsdRate() {
    // API configuration
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API Response:', response);
            
            // Check different possible response formats
            let rate = null;
            
            if (response.success && response.data && response.data.price) {
                rate = response.data.price;
            } else if (response.value) {
                rate = response.value;
            } else if (response.price) {
                rate = response.price;
            } else if (response.rate) {
                rate = response.rate;
            }
            
            if (rate) {
                $('#edit_usd_to_iqd_rate').val(rate);
                // Recalculate totals if there are any existing values
                calculateEditGrandTotal();
                calculateEditRemainingAmounts();
                console.log('USD rate loaded successfully:', rate);
            } else {
                console.error('Failed to load USD rate from API:', response);
                // Fallback to local rate
                loadEditLocalUsdRate();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading USD rate from API:', error);
            // Fallback to local rate
            loadEditLocalUsdRate();
        }
    });
}

function loadEditLocalUsdRate() {
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_usd_to_iqd_rate').val(response.rate);
                // Recalculate totals if there are any existing values
                calculateEditGrandTotal();
            } else {
                console.log('Error loading local USD rate: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading local USD rate:', error);
        }
    });
}

function loadPurchaseMaterialsTable() {
    const filterFrom = $('#filter_from').val();
    const filterTo = $('#filter_to').val();
    
    // Define columns for loading state
    const columns = ['#', 'receipt_number', 'person_name', 'purchase_date', 'materials_count', 'total_prices', 'currency_type', 'notes', 'actions'];
    
    // Show loading using TableController
    TableController.showLoading('#purchaseMaterialsTable', columns);
    
    $.ajax({
        url: '../process/purchase_materilas/select_purchase.php',
        type: 'GET',
        data: {
            filter_from: filterFrom,
            filter_to: filterTo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderPurchaseMaterialsTable(response.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.error || 'هەڵەیەک ڕوویدا',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەی پەیوەندی بە سێرڤەرەوە: ' + error,
                confirmButtonText: 'باشە'
            });
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function renderPurchaseMaterialsTable(data) {
    // Helper to format numbers with commas
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    // Prepare data for TableController
    const tableData = data.map(function(item) {
        const actions = [];
        actions.push(`<button class='btn btn-info btn-sm view-purchase' data-id='${item.id}' title='وردەکاری'><i class='fa fa-eye'></i></button>`);
        if (window.userPermissions && window.userPermissions.canEdit) {
            actions.push(`<button class='btn btn-warning btn-sm edit-purchase' data-id='${item.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>`);
        }
        if (window.userPermissions && window.userPermissions.canDelete) {
            actions.push(`<button class='btn btn-danger btn-sm delete-purchase' data-id='${item.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`);
        }
        // Format prices
        const usd = formatNumber(item.total_usd || 0);
        const iqd = formatNumber(item.total_iqd || 0);
        return {
            '#': '',
            'receipt_number': item.receipt_number || '-',
            'person_name': item.person_name || '-',
            'purchase_date': item.purchase_date || '-',
            'materials_count': item.materials_count || 0,
            'total_usd': `$${usd}`,
            'total_iqd': `${iqd} د.ع`,
            'currency_type': item.currency_type || '-',
            'notes': item.notes || '-',
            'actions': actions.join(' ')
        };
    });
    // Define columns
    const columns = ['#', 'receipt_number', 'person_name', 'purchase_date', 'materials_count', 'total_usd', 'total_iqd', 'currency_type', 'notes', 'actions'];
    // Use TableController with pagination
    TableController.renderWithPagination('#purchaseMaterialsTable', tableData, columns, {
        pageSize: 10,
        currentPage: 1
    });
    attachActionHandlers();
}

function attachActionHandlers() {
    // View details button
    $('.view-purchase').off('click').on('click', function() {
        const purchaseId = $(this).data('id');
        loadPurchaseForView(purchaseId);
    });
    
    // Edit button
    $('.edit-purchase').off('click').on('click', function() {
        const purchaseId = $(this).data('id');
        loadPurchaseForEdit(purchaseId);
    });
    
    // Delete button
    $('.delete-purchase').off('click').on('click', function() {
        const purchaseId = $(this).data('id');
        deletePurchase(purchaseId);
    });
}

function loadPurchaseForView(purchaseId) {
    $.ajax({
        url: '../process/purchase_materilas/get_purchase.php',
        type: 'GET',
        data: { id: purchaseId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateViewForm(response.data);
                $('#viewPurchaseModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.error || 'هەڵەیەک ڕوویدا',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەی پەیوەندی بە سێرڤەرەوە: ' + error,
                confirmButtonText: 'باشە'
            });
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function loadPurchaseForEdit(purchaseId) {
    // First ensure materials are loaded
    let materialsPromise = Promise.resolve();
    if (!window.materials || window.materials.length === 0) {
        materialsPromise = loadMaterials();
    }
    
    // Then load purchase data
    materialsPromise.then(function() {
        $.ajax({
            url: '../process/purchase_materilas/get_purchase.php',
            type: 'GET',
            data: { id: purchaseId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    populateEditForm(response.data);
                    $('#editPurchaseModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.error || 'هەڵەیەک ڕوویدا',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەی پەیوەندی بە سێرڤەرەوە: ' + error,
                    confirmButtonText: 'باشە'
                });
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
}

function populateViewForm(data) {
    // Populate receipt details
    $('#view_receipt_number').text(data.receipt_number);
    $('#view_person_name').text(data.person_name);
    $('#view_purchase_date').text(data.purchase_date);
    $('#view_currency_type').text(data.currency_type);
    $('#view_payment_type').text(data.payment_type || 'نەقد');
    $('#view_notes').text(data.notes || '-');
    $('#view_transfer_loss').text(parseFloat(data.transfer_loss || 0).toFixed(2));
    $('#view_other_loss').text(parseFloat(data.other_loss || 0).toFixed(2));
    $('#view_usd_to_iqd_rate').text(parseFloat(data.usd_to_iqd_rate || 0).toFixed(2));
    $('#view_paid_amount_usd').text('$' + parseFloat(data.paid_amount_usd || 0).toFixed(2));
    $('#view_paid_amount_iqd').text(parseFloat(data.paid_amount_iqd || 0).toFixed(2) + ' د.ع');
    $('#view_remaining_amount_usd').text('$' + parseFloat(data.remaining_amount_usd || 0).toFixed(2));
    $('#view_remaining_amount_iqd').text(parseFloat(data.remaining_amount_iqd || 0).toFixed(2) + ' د.ع');
    
    // Populate materials table
    populateViewMaterialsTable(data.materials);
    
    // Calculate and display totals
    calculateViewTotals(data.materials, data.transfer_loss, data.other_loss, data.currency_type, data.usd_to_iqd_rate);
}

function populateEditForm(data) {
    // Populate receipt details
    $('#edit_purchase_id').val(data.id);
    $('#edit_receipt_number').val(data.receipt_number).prop('readonly', true);
    $('#edit_person_id').val(data.person_id).trigger('change');
    $('#edit_purchase_date').val(data.purchase_date);
    $('#edit_currency_type').val(data.currency_type).trigger('change');
    $('#edit_payment_type').val(data.payment_type || 'نەقد').trigger('change');
    $('#edit_notes').val(data.notes);
    $('#edit_transfer_loss').val(data.transfer_loss || 0);
    $('#edit_other_loss').val(data.other_loss || 0);
    $('#edit_usd_to_iqd_rate').val(data.usd_to_iqd_rate || 0);
    $('#edit_paid_amount_usd').val(data.paid_amount_usd || 0);
    $('#edit_paid_amount_iqd').val(data.paid_amount_iqd || 0);
    
    // Format remaining amounts for display
    const currencyType = data.currency_type || '';
    const remainingUsd = parseFloat(data.remaining_amount_usd || 0);
    const remainingIqd = parseFloat(data.remaining_amount_iqd || 0);
    
    if (currencyType === 'دۆلار') {
        $('#edit_remaining_amount_usd').val(`$${remainingUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#edit_remaining_amount_iqd').val('');
    } else if (currencyType === 'دینار') {
        $('#edit_remaining_amount_usd').val('');
        $('#edit_remaining_amount_iqd').val(`${remainingIqd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} د.ع`);
    } else {
        $('#edit_remaining_amount_usd').val(`$${remainingUsd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#edit_remaining_amount_iqd').val(`${remainingIqd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} د.ع`);
    }
    
    // Populate materials
    populateEditMaterialsTable(data.materials);
    
    // Calculate totals and remaining amounts
    calculateEditGrandTotal();
    calculateEditRemainingAmounts();
}

function populateViewMaterialsTable(materials) {
    // Helper to format numbers with commas
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    const tbody = $('#viewMaterialsTableBody');
    tbody.empty();
    
    if (materials && materials.length > 0) {
        materials.forEach(function(material, index) {
            const priceUsd = formatNumber(material.price_per_unit_usd || 0);
            const priceIqd = formatNumber(material.price_per_unit_iqd || 0);
            const totalUsd = formatNumber(material.total_price_usd || 0);
            const totalIqd = formatNumber(material.total_price_iqd || 0);
            
            tbody.append(`
                <tr>
                    <td>${index + 1}</td>
                    <td>${material.material_name || '-'}</td>
                    <td>${material.unit_type || 'دانە'}</td>
                    <td>${parseFloat(material.quantity || 0).toFixed(2)}</td>
                    <td>$${priceUsd}</td>
                    <td>${priceIqd} د.ع</td>
                    <td>$${totalUsd}</td>
                    <td>${totalIqd} د.ع</td>
                </tr>
            `);
        });
    } else {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center">هیچ کاڵایەک نییە</td>
            </tr>
        `);
    }
}

function calculateViewTotals(materials, transferLoss, otherLoss, currencyType, usdToIqdRate) {
    let totalUsd = 0;
    let totalIqd = 0;
    
    // Calculate materials totals
    if (materials && materials.length > 0) {
        materials.forEach(function(material) {
            totalUsd += parseFloat(material.total_price_usd || 0);
            totalIqd += parseFloat(material.total_price_iqd || 0);
        });
    }
    
    // Get transfer loss and other loss values (these are always in IQD)
    const transferLossIqd = parseFloat(transferLoss || 0);
    const otherLossIqd = parseFloat(otherLoss || 0);
    
    // Convert additional costs based on currency type
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert IQD costs to USD and add to USD total only
        const transferLossUsd = usdToIqdRate > 0 ? transferLossIqd / (usdToIqdRate / 100) : 0;
        const otherLossUsd = usdToIqdRate > 0 ? otherLossIqd / (usdToIqdRate / 100) : 0;
        totalUsd += transferLossUsd + otherLossUsd;
        // Don't calculate IQD total when currency is USD
        totalIqd = 0;
    } else if (currencyType === 'دینار') {
        // If currency is IQD, add costs directly to IQD total only
        totalIqd += transferLossIqd + otherLossIqd;
        // Don't calculate USD total when currency is IQD
        totalUsd = 0;
    }
    
    // Helper to format numbers with commas
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    // Display totals based on currency type
    if (currencyType === 'دۆلار') {
        $('#view_total_usd').text(`$${formatNumber(totalUsd)}`);
        $('#view_total_iqd').text('');
    } else if (currencyType === 'دینار') {
        $('#view_total_usd').text('');
        $('#view_total_iqd').text(`${formatNumber(totalIqd)} د.ع`);
    } else {
        $('#view_total_usd').text(`$${formatNumber(totalUsd)}`);
        $('#view_total_iqd').text(`${formatNumber(totalIqd)} د.ع`);
    }
    
    // Store raw values for calculations
    $('#view_total_usd').attr('data-raw-value', totalUsd);
    $('#view_total_iqd').attr('data-raw-value', totalIqd);
}

function populateEditMaterialsTable(materials) {
    const tbody = $('#editMaterialsTableBody');
    tbody.empty();
    
    if (materials && materials.length > 0) {
        materials.forEach(function(material) {
            addEditMaterialRow(material);
        });
    } else {
        addEditMaterialRow();
    }
}

function addEditMaterialRow(materialData = null) {
    const rowId = 'edit_row_' + Date.now();
    const materials = window.materials || [];
    
    let materialsOptions = '<option value="">هەڵبژێرە</option>';
    materials.forEach(function(material) {
        const selected = materialData && materialData.material_id == material.id ? 'selected' : '';
        materialsOptions += `<option value="${material.id}" ${selected}>${material.name} (${material.unit_type || 'دانە'})</option>`;
    });

    const newRow = `
        <tr class="edit-material-row" id="${rowId}">
            <td>
                <select class="form-select edit-material-select" name="edit_materials[${rowId}][material_id]" required>
                    ${materialsOptions}
                </select>
            </td>
            <td>
                <select class="form-select edit-unit-type-select" name="edit_materials[${rowId}][unit_type]" required>
                    <option value="دانە">دانە</option>
                </select>
                <input type="hidden" class="edit-unit-type-input" name="edit_materials[${rowId}][original_unit_type]" value="دانە">
            </td>
            <td>
                <input type="number" class="form-control edit-quantity-input" name="edit_materials[${rowId}][quantity]" 
                       min="0" step="0.01" placeholder="0.00" required 
                       value="${materialData ? materialData.quantity : ''}">
            </td>
            <td>
                <input type="number" class="form-control edit-price-usd-input" name="edit_materials[${rowId}][price_per_unit_usd]" 
                       min="0" step="0.01" placeholder="0.00" 
                       value="${materialData ? materialData.price_per_unit_usd : '0'}">
            </td>
            <td>
                <input type="number" class="form-control edit-price-iqd-input" name="edit_materials[${rowId}][price_per_unit_iqd]" 
                       min="0" step="0.01" placeholder="0.00" 
                       value="${materialData ? materialData.price_per_unit_iqd : '0'}">
            </td>
            <td>
                <input type="number" class="form-control edit-total-usd-input" name="edit_materials[${rowId}][total_price_usd]" 
                       readonly placeholder="0.00" 
                       value="${materialData ? (materialData.quantity * materialData.price_per_unit_usd).toFixed(2) : '0.00'}">
            </td>
            <td>
                <input type="number" class="form-control edit-total-iqd-input" name="edit_materials[${rowId}][total_price_iqd]" 
                       readonly placeholder="0.00" 
                       value="${materialData ? (materialData.quantity * materialData.price_per_unit_iqd).toFixed(2) : '0.00'}">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-edit-material-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#editMaterialsTableBody').append(newRow);
    
    // Initialize Select2 for new row
    $(`#${rowId} .edit-material-select`).select2({
        dropdownParent: $('#editPurchaseModal'),
        width: '100%',
        placeholder: "هەڵبژێرە",
        dir: "rtl"
    });
    
    // If material data exists, populate unit type dropdown
    if (materialData) {
        const material = window.materials.find(m => m.id == materialData.material_id);
        if (material) {
            populateEditUnitTypeDropdown($(`#${rowId}`), material);
            $(`#${rowId} .edit-unit-type-select`).val(materialData.unit_type || material.unit_type || 'دانە');
        }
    }
    
    // Calculate row total
    calculateEditRowTotal($(`#${rowId}`));
}

function populateEditUnitTypeDropdown(row, material) {
    const $unitSelect = row.find('.edit-unit-type-select');
    $unitSelect.empty();
    
    // Always include the material's original unit type
    const originalUnitType = material.unit_type || 'دانە';
    $unitSelect.append(`<option value="${originalUnitType}">${originalUnitType}</option>`);
    
    // Add other available unit types based on material's conversion data
    if (material.unit_type === 'کارتۆن' && material.pieces_per_carton) {
        $unitSelect.append('<option value="دانە">دانە</option>');
    } else if (material.unit_type === 'بەرمیل') {
        if (material.buckets_per_barrel) {
            $unitSelect.append('<option value="دەبە">دەبە</option>');
        }
        if (material.liters_per_barrel) {
            $unitSelect.append('<option value="لیتر">لیتر</option>');
        }
    } else if (material.unit_type === 'دەبە' && material.liters_per_bucket) {
        $unitSelect.append('<option value="لیتر">لیتر</option>');
    } else if (material.unit_type === 'لیتر') {
        // Can be purchased as liters
        $unitSelect.append('<option value="لیتر">لیتر</option>');
    } else if (material.unit_type === 'دانە') {
        // Can be purchased as pieces
        $unitSelect.append('<option value="دانە">دانە</option>');
    }
    
    // Set the original unit type as default
    $unitSelect.val(originalUnitType);
    row.find('.edit-unit-type-input').val(originalUnitType);
}

function calculateEditPriceForUnitType(row, material, selectedUnitType) {
    const originalUnitType = material.unit_type || 'دانە';
    let priceUsd = 0;
    let priceIqd = 0;
    
    if (selectedUnitType === originalUnitType) {
        // Same unit type, use original prices
        priceUsd = material.purchase_price_usd || 0;
        priceIqd = material.purchase_price_iqd || 0;
    } else {
        // Convert prices based on unit type
        if (originalUnitType === 'کارتۆن' && selectedUnitType === 'دانە' && material.pieces_per_carton) {
            priceUsd = (material.purchase_price_usd || 0) / material.pieces_per_carton;
            priceIqd = (material.purchase_price_iqd || 0) / material.pieces_per_carton;
        } else if (originalUnitType === 'بەرمیل' && selectedUnitType === 'دەبە' && material.buckets_per_barrel) {
            priceUsd = (material.purchase_price_usd || 0) / material.buckets_per_barrel;
            priceIqd = (material.purchase_price_iqd || 0) / material.buckets_per_barrel;
        } else if (originalUnitType === 'بەرمیل' && selectedUnitType === 'لیتر' && material.liters_per_barrel) {
            priceUsd = (material.purchase_price_usd || 0) / material.liters_per_barrel;
            priceIqd = (material.purchase_price_iqd || 0) / material.liters_per_barrel;
        } else if (originalUnitType === 'دەبە' && selectedUnitType === 'لیتر' && material.liters_per_bucket) {
            priceUsd = (material.purchase_price_usd || 0) / material.liters_per_bucket;
            priceIqd = (material.purchase_price_iqd || 0) / material.liters_per_bucket;
        } else {
            // Fallback to original prices
            priceUsd = material.purchase_price_usd || 0;
            priceIqd = material.purchase_price_iqd || 0;
        }
    }
    
    // Update price fields based on currency type
    const currencyType = $('#edit_currency_type').val();
    if (currencyType === 'دۆلار') {
        row.find('.edit-price-usd-input').val(priceUsd.toFixed(2));
        row.find('.edit-price-iqd-input').val(0);
    } else if (currencyType === 'دینار') {
        row.find('.edit-price-usd-input').val(0);
        row.find('.edit-price-iqd-input').val(priceIqd.toFixed(2));
    } else {
        row.find('.edit-price-usd-input').val(priceUsd.toFixed(2));
        row.find('.edit-price-iqd-input').val(priceIqd.toFixed(2));
    }
}

function calculateEditRowTotal(row) {
    const quantity = parseFloat(row.find('.edit-quantity-input').val()) || 0;
    const priceUsd = parseFloat(row.find('.edit-price-usd-input').val()) || 0;
    const priceIqd = parseFloat(row.find('.edit-price-iqd-input').val()) || 0;
    
    const totalUsd = quantity * priceUsd;
    const totalIqd = quantity * priceIqd;
    
    row.find('.edit-total-usd-input').val(totalUsd.toFixed(2));
    row.find('.edit-total-iqd-input').val(totalIqd.toFixed(2));
}

function calculateEditGrandTotal() {
    let totalUsd = 0;
    let totalIqd = 0;
    
    // Calculate materials totals
    $('.edit-material-row').each(function() {
        totalUsd += parseFloat($(this).find('.edit-total-usd-input').val()) || 0;
        totalIqd += parseFloat($(this).find('.edit-total-iqd-input').val()) || 0;
    });
    
    // Get transfer loss and other loss values (these are always in IQD)
    const transferLossIqd = parseFloat($('#edit_transfer_loss').val()) || 0;
    const otherLossIqd = parseFloat($('#edit_other_loss').val()) || 0;
    const usdToIqdRate = parseFloat($('#edit_usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#edit_currency_type').val();
    
    // Convert additional costs based on currency type
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert IQD costs to USD and add to USD total only
        const transferLossUsd = usdToIqdRate > 0 ? transferLossIqd / (usdToIqdRate / 100) : 0;
        const otherLossUsd = usdToIqdRate > 0 ? otherLossIqd / (usdToIqdRate / 100) : 0;
        totalUsd += transferLossUsd + otherLossUsd;
        // Don't calculate IQD total when currency is USD
        totalIqd = 0;
    } else if (currencyType === 'دینار') {
        // If currency is IQD, add costs directly to IQD total only
        totalIqd += transferLossIqd + otherLossIqd;
        // Don't calculate USD total when currency is IQD
        totalUsd = 0;
    }
    
    // Helper to format numbers with commas
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    // Set totals based on currency type
    if (currencyType === 'دۆلار') {
        $('#edit_total_usd').val(`$${formatNumber(totalUsd)}`);
        $('#edit_total_iqd').val(''); // Clear IQD total when currency is USD
    } else if (currencyType === 'دینار') {
        $('#edit_total_usd').val(''); // Clear USD total when currency is IQD
        $('#edit_total_iqd').val(`${formatNumber(totalIqd)} د.ع`);
    } else {
        // If no currency selected, show both
        $('#edit_total_usd').val(`$${formatNumber(totalUsd)}`);
        $('#edit_total_iqd').val(`${formatNumber(totalIqd)} د.ع`);
    }
    
    // Store raw values for calculations
    $('#edit_total_usd').attr('data-raw-value', totalUsd);
    $('#edit_total_iqd').attr('data-raw-value', totalIqd);
    
    // Calculate remaining amounts
    calculateEditRemainingAmounts();
}

function calculateEditRemainingAmounts() {
    // Check payment type first
    const paymentType = $('#edit_payment_type').val();
    
    if (paymentType === 'نەقد') {
        // For cash payment, clear remaining amounts
        $('#edit_remaining_amount_usd').val('');
        $('#edit_remaining_amount_iqd').val('');
        return;
    }
    
    // Get raw values from data attributes for calculations
    const totalUsd = parseFloat($('#edit_total_usd').attr('data-raw-value')) || 0;
    const totalIqd = parseFloat($('#edit_total_iqd').attr('data-raw-value')) || 0;
    const paidUsd = parseFloat($('#edit_paid_amount_usd').val()) || 0;
    const paidIqd = parseFloat($('#edit_paid_amount_iqd').val()) || 0;
    const usdToIqdRate = parseFloat($('#edit_usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#edit_currency_type').val();
    
    let remainingUsd = 0;
    let remainingIqd = 0;
    
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert paid IQD to USD and calculate remaining in USD only
        const paidIqdInUsd = usdToIqdRate > 0 ? paidIqd / (usdToIqdRate / 100) : 0;
        remainingUsd = totalUsd - paidUsd - paidIqdInUsd;
        // Don't calculate IQD remaining when currency is USD
        remainingIqd = 0;
    } else if (currencyType === 'دینار') {
        // If currency is IQD, convert paid USD to IQD and calculate remaining in IQD only
        const paidUsdInIqd = paidUsd * (usdToIqdRate / 100);
        remainingIqd = totalIqd - paidIqd - paidUsdInIqd;
        // Don't calculate USD remaining when currency is IQD
        remainingUsd = 0;
    } else {
        // If no currency type selected, calculate separately
        remainingUsd = totalUsd - paidUsd;
        remainingIqd = totalIqd - paidIqd;
    }
    
    // Helper to format numbers with commas
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    // Update remaining fields based on currency type
    if (currencyType === 'دۆلار') {
        $('#edit_remaining_amount_usd').val(`$${formatNumber(remainingUsd)}`);
        $('#edit_remaining_amount_iqd').val(''); // Clear IQD remaining when currency is USD
    } else if (currencyType === 'دینار') {
        $('#edit_remaining_amount_usd').val(''); // Clear USD remaining when currency is IQD
        $('#edit_remaining_amount_iqd').val(`${formatNumber(remainingIqd)} د.ع`);
    } else {
        // If no currency selected, show both
        $('#edit_remaining_amount_usd').val(`$${formatNumber(remainingUsd)}`);
        $('#edit_remaining_amount_iqd').val(`${formatNumber(remainingIqd)} د.ع`);
    }
}

function deletePurchase(purchaseId) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوە؟',
        text: 'ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سڕەوە',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/purchase_materilas/delete_purchase.php',
                type: 'POST',
                data: { id: purchaseId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: response.message || 'کڕینەکە بە سەرکەوتووی سڕایەوە',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            loadPurchaseMaterialsTable();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.error || 'هەڵەیەک ڕوویدا',
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەی پەیوەندی بە سێرڤەرەوە: ' + error,
                        confirmButtonText: 'باشە'
                    });
                    console.error('AJAX Error:', xhr.responseText);
                }
            });
        }
    });
}

function loadMaterials() {
    return $.ajax({
        url: '../process/purchase_materilas/get_materials.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.materials = response.data;
            } else {
                console.error('Error loading materials:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading materials:', error);
        }
    });
}

function loadPersons() {
    $.ajax({
        url: '../process/purchase_materilas/get_persons.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.persons = response.data;
            } else {
                console.error('Error loading persons:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading persons:', error);
        }
    });
}

function updateEditAdditionalCostsFields() {
    const currencyType = $('#edit_currency_type').val();
    const usdToIqdRate = parseFloat($('#edit_usd_to_iqd_rate').val()) || 0;
    
    // Get current values (always stored in IQD)
    const transferLossIqd = parseFloat($('#edit_transfer_loss').attr('data-iqd-value')) || parseFloat($('#edit_transfer_loss').val()) || 0;
    const otherLossIqd = parseFloat($('#edit_other_loss').attr('data-iqd-value')) || parseFloat($('#edit_other_loss').val()) || 0;
    
    if (currencyType === 'دۆلار') {
        // Convert to USD for display
        const transferLossUsd = usdToIqdRate > 0 ? transferLossIqd / (usdToIqdRate / 100) : 0;
        const otherLossUsd = usdToIqdRate > 0 ? otherLossIqd / (usdToIqdRate / 100) : 0;
        
        $('#edit_transfer_loss').val(transferLossUsd.toFixed(2));
        $('#edit_other_loss').val(otherLossUsd.toFixed(2));
        
        // Store original IQD values
        $('#edit_transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#edit_other_loss').attr('data-iqd-value', otherLossIqd);
    } else if (currencyType === 'دینار') {
        // Show in IQD
        $('#edit_transfer_loss').val(transferLossIqd.toFixed(2));
        $('#edit_other_loss').val(otherLossIqd.toFixed(2));
        
        // Store original IQD values
        $('#edit_transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#edit_other_loss').attr('data-iqd-value', otherLossIqd);
    }
}

// Edit modal event handlers
$(document).ready(function() {
    // Add material row in edit modal
    $('#editAddMaterialRow').click(function() {
        addEditMaterialRow();
    });
    
    // Calculate totals when any input changes in edit modal
    $(document).on('input', '.edit-material-row input', function() {
        calculateEditRowTotal($(this).closest('tr'));
        calculateEditGrandTotal();
    });
    
    // Remove material row in edit modal
    $(document).on('click', '.remove-edit-material-row', function() {
        $(this).closest('tr').remove();
        calculateEditGrandTotal();
        
        // Ensure at least one row exists
        if ($('#editMaterialsTableBody tr').length === 0) {
            addEditMaterialRow();
        }
    });
});
