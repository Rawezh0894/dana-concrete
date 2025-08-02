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
    
    // Load purchase materials table on page load
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

    // Auto-fill prices when material is selected in edit form
    $(document).on('change', '.edit-material-select', function() {
        const materialId = $(this).val();
        const row = $(this).closest('tr');
        
        if (materialId) {
            // Find the selected material from the materials array
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Auto-fill the price fields
                row.find('.edit-price-usd-input').val(material.purchase_price_usd || 0);
                row.find('.edit-price-iqd-input').val(material.purchase_price_iqd || 0);
                
                // Calculate totals
                calculateEditRowTotal(row);
                calculateEditGrandTotal();
            }
        } else {
            // Clear price fields if no material is selected
            row.find('.edit-price-usd-input').val(0);
            row.find('.edit-price-iqd-input').val(0);
            calculateEditRowTotal(row);
            calculateEditGrandTotal();
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

    $(document).on('change', '#edit_currency_type', function() {
        calculateEditGrandTotal();
    });

    // Load USD rate when edit modal opens
    $('#editPurchaseModal').on('show.bs.modal', function() {
        loadEditUsdRate();
    });
});

// Load USD to IQD exchange rate for edit form
function loadEditUsdRate() {
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        dataType: 'json',
        success: function(result) {
            if (result.success) {
                $('#edit_usd_to_iqd_rate').val(result.rate);
                // Recalculate totals if there are any existing values
                calculateEditGrandTotal();
            } else {
                // Use default rate if API fails
                if (result.default_rate) {
                    $('#edit_usd_to_iqd_rate').val(result.default_rate);
                    calculateEditGrandTotal();
                }
                console.log('Error loading USD rate: ' + result.error);
            }
        },
        error: function(xhr, status, error) {
            // Use default rate if request fails
            $('#edit_usd_to_iqd_rate').val(139250);
            calculateEditGrandTotal();
            console.error('Error loading USD rate:', error);
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
        dataType: 'json',
        data: {
            filter_from: filterFrom,
            filter_to: filterTo
        },
        success: function(result) {
            if (result.success) {
                renderPurchaseMaterialsTable(result.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: result.error || 'هەڵەیەک ڕوویدا',
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
    // Prepare data for TableController
    const tableData = data.map(function(item) {
        const actions = [];
        
        // View details button (always available)
        actions.push(`<button class='btn btn-info btn-sm view-purchase' data-id='${item.id}' title='وردەکاری'><i class='fa fa-eye'></i></button>`);
        
        if (window.userPermissions && window.userPermissions.canEdit) {
            actions.push(`<button class='btn btn-warning btn-sm edit-purchase' data-id='${item.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>`);
        }
        
        if (window.userPermissions && window.userPermissions.canDelete) {
            actions.push(`<button class='btn btn-danger btn-sm delete-purchase' data-id='${item.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`);
        }
        
        return {
            '#': '',
            'receipt_number': item.receipt_number || '-',
            'person_name': item.person_name || '-',
            'purchase_date': item.purchase_date || '-',
            'materials_count': item.materials_count || 0,
            'total_prices': `<div>USD: ${parseFloat(item.total_usd || 0).toFixed(2)}</div><div>IQD: ${parseFloat(item.total_iqd || 0).toFixed(2)}</div>`,
            'currency_type': item.currency_type || '-',
            'notes': item.notes || '-',
            'actions': actions.join(' ')
        };
    });
    
    // Define columns
    const columns = ['#', 'receipt_number', 'person_name', 'purchase_date', 'materials_count', 'total_prices', 'currency_type', 'notes', 'actions'];
    
    // Use TableController with pagination
    TableController.renderWithPagination('#purchaseMaterialsTable', tableData, columns, {
        pageSize: 10,
        currentPage: 1
    });
    
    // Attach event handlers for action buttons
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
    console.log('Loading purchase for view:', purchaseId);
    
    $.ajax({
        url: '../process/purchase_materilas/get_purchase.php',
        type: 'GET',
        data: { id: purchaseId },
        success: function(response) {
            console.log('View response:', response);
            
            try {
                const result = JSON.parse(response);
                console.log('Parsed result:', result);
                
                if (result.success) {
                    console.log('Populating view form with data:', result.data);
                    populateViewForm(result.data);
                    
                    // Check if modal exists
                    if ($('#viewPurchaseModal').length > 0) {
                        console.log('Modal found, showing...');
                        $('#viewPurchaseModal').modal('show');
                    } else {
                        console.error('Modal not found: #viewPurchaseModal');
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'مۆداڵەکە نەدۆزرایەوە',
                            confirmButtonText: 'باشە'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: result.error || 'هەڵەیەک ڕوویدا',
                        confirmButtonText: 'باشە'
                    });
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک لە وەڵامەکەدا هەیە',
                    confirmButtonText: 'باشە'
                });
                console.error('Response:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {xhr, status, error});
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
    $.ajax({
        url: '../process/purchase_materilas/get_purchase.php',
        type: 'GET',
        data: { id: purchaseId },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                
                if (result.success) {
                    populateEditForm(result.data);
                    $('#editPurchaseModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: result.error || 'هەڵەیەک ڕوویدا',
                        confirmButtonText: 'باشە'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک لە وەڵامەکەدا هەیە',
                    confirmButtonText: 'باشە'
                });
                console.error('Response:', response);
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

function populateViewForm(data) {
    // Populate receipt details
    $('#view_receipt_number').text(data.receipt_number);
    $('#view_person_name').text(data.person_name);
    $('#view_purchase_date').text(data.purchase_date);
    $('#view_currency_type').text(data.currency_type);
    $('#view_notes').text(data.notes || '-');
    $('#view_transfer_loss').text(parseFloat(data.transfer_loss || 0).toFixed(2));
    $('#view_other_loss').text(parseFloat(data.other_loss || 0).toFixed(2));
    $('#view_usd_to_iqd_rate').text(parseFloat(data.usd_to_iqd_rate || 0).toFixed(2));
    
    // Populate materials table
    populateViewMaterialsTable(data.materials);
    
    // Calculate and display totals
    calculateViewTotals(data.materials, data.transfer_loss, data.other_loss, data.currency_type, data.usd_to_iqd_rate);
}

function populateEditForm(data) {
    // Store the original materials data for later use
    window.editMaterialsData = data.materials || [];
    
    // Populate receipt details
    $('#edit_purchase_id').val(data.id);
    $('#edit_receipt_number').val(data.receipt_number).prop('readonly', true);
    $('#edit_person_id').val(data.person_id).trigger('change');
    $('#edit_purchase_date').val(data.purchase_date);
    $('#edit_currency_type').val(data.currency_type).trigger('change');
    $('#edit_notes').val(data.notes);
    $('#edit_transfer_loss').val(data.transfer_loss || 0);
    $('#edit_other_loss').val(data.other_loss || 0);
    $('#edit_usd_to_iqd_rate').val(data.usd_to_iqd_rate || 0);
    
    // Populate materials
    populateEditMaterialsTable(data.materials);
    
    // Calculate totals
    calculateEditGrandTotal();
    
    // Set up event handlers for dynamic calculations
    setupEditEventHandlers();
}

function setupEditEventHandlers() {
    // Handle quantity and price changes for total calculations
    $(document).on('input', '.edit-quantity-input, .edit-price-usd-input, .edit-price-iqd-input', function() {
        calculateEditRowTotal($(this).closest('tr'));
        calculateEditGrandTotal();
    });
    
    // Handle material selection changes
    $(document).on('change', '.edit-material-select', function() {
        const row = $(this).closest('tr');
        const materialId = $(this).val();
        const materials = window.materials || [];
        
        if (materialId) {
            const material = materials.find(m => m.id == materialId);
            if (material) {
                updateEditUnitTypeDisplay(row, material);
                fillEditPricesBasedOnUnitType(row, material);
                calculateEditRowTotal(row);
                calculateEditGrandTotal();
            }
        } else {
            updateEditUnitTypeDisplay(row, null);
        }
    });
    
    // Handle purchase unit changes
    $(document).on('change', '.edit-purchase-unit-select', function() {
        const row = $(this).closest('tr');
        const materialSelect = row.find('.edit-material-select');
        const materialId = materialSelect.val();
        const materials = window.materials || [];
        
        if (materialId) {
            const material = materials.find(m => m.id == materialId);
            if (material) {
                fillEditPricesBasedOnUnitType(row, material);
                calculateEditRowTotal(row);
                calculateEditGrandTotal();
            }
        }
    });
}

function populateViewMaterialsTable(materials) {
    const tbody = $('#viewMaterialsTableBody');
    tbody.empty();
    
    console.log('populateViewMaterialsTable called with materials:', materials);
    
    if (materials && materials.length > 0) {
        materials.forEach(function(material, index) {
            console.log('Processing material:', material);
            
            // Create unit type display text
            let unitTypeText = '';
            if (material.unit_type) {
                switch(material.unit_type) {
                    case 'carton':
                        unitTypeText = `کارتۆن (${material.pieces_per_carton || 1} دانە)`;
                        break;
                    case 'piece':
                        unitTypeText = 'دانە';
                        break;
                    case 'barrel':
                        unitTypeText = `بەرمیل (${material.bags_per_barrel || 1} دەبە × ${material.liters_per_bag || 1} لیتر)`;
                        break;
                    case 'bag':
                        unitTypeText = `دەبە (${material.liters_per_bag || 1} لیتر)`;
                        break;
                    case 'liter':
                        unitTypeText = 'لیتر';
                        break;
                    default:
                        unitTypeText = material.unit_type || '-';
                }
            }
            
            console.log('Unit type text:', unitTypeText);
            console.log('Material name:', material.material_name);
            console.log('Quantity:', material.quantity);
            
            tbody.append(`
                <tr>
                    <td>${index + 1}</td>
                    <td>${material.material_name || '-'}</td>
                    <td>${unitTypeText}</td>
                    <td>${parseFloat(material.quantity || 0).toFixed(2)}</td>
                    <td>${parseFloat(material.price_per_unit_usd || 0).toFixed(2)}</td>
                    <td>${parseFloat(material.price_per_unit_iqd || 0).toFixed(2)}</td>
                    <td>${parseFloat(material.price_per_bag || 0).toFixed(2)}</td>
                    <td>${parseFloat(material.total_price_usd || 0).toFixed(2)}</td>
                    <td>${parseFloat(material.total_price_iqd || 0).toFixed(2)}</td>
                </tr>
            `);
        });
    } else {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center">هیچ کاڵایەک نییە</td>
            </tr>
        `);
    }
}

function calculateViewTotals(materials, transferLoss, otherLoss, currencyType, usdToIqdRate) {
    console.log('calculateViewTotals called with:', {materials, transferLoss, otherLoss, currencyType, usdToIqdRate});
    
    let totalUsd = 0;
    let totalIqd = 0;
    
    // Calculate materials totals
    if (materials && materials.length > 0) {
        materials.forEach(function(material) {
            const materialUsd = parseFloat(material.total_price_usd || 0) || 0;
            const materialIqd = parseFloat(material.total_price_iqd || 0) || 0;
            totalUsd += materialUsd;
            totalIqd += materialIqd;
            console.log('Material:', material.name, 'USD:', materialUsd, 'IQD:', materialIqd);
        });
    }
    
    console.log('After materials calculation - Total USD:', totalUsd, 'Total IQD:', totalIqd);
    
    // Convert losses to appropriate currency based on currency type
    let transferLossUsd = 0;
    let transferLossIqd = 0;
    let otherLossUsd = 0;
    let otherLossIqd = 0;
    
    // Ensure losses are numbers
    const transferLossNum = parseFloat(transferLoss || 0) || 0;
    const otherLossNum = parseFloat(otherLoss || 0) || 0;
    const usdToIqdRateNum = parseFloat(usdToIqdRate || 0) || 0;
    
    console.log('Losses:', {transferLossNum, otherLossNum, usdToIqdRateNum});
    
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert IQD losses to USD
        if (usdToIqdRateNum > 0) {
            transferLossUsd = transferLossNum / (usdToIqdRateNum / 100);
            otherLossUsd = otherLossNum / (usdToIqdRateNum / 100);
        }
        transferLossIqd = transferLossNum;
        otherLossIqd = otherLossNum;
    } else {
        // If currency is IQD, losses are already in IQD
        transferLossIqd = transferLossNum;
        otherLossIqd = otherLossNum;
        // Convert to USD if rate is available
        if (usdToIqdRateNum > 0) {
            transferLossUsd = transferLossNum / (usdToIqdRateNum / 100);
            otherLossUsd = otherLossNum / (usdToIqdRateNum / 100);
        }
    }
    
    // Add losses to totals
    totalUsd += transferLossUsd + otherLossUsd;
    totalIqd += transferLossIqd + otherLossIqd;
    
    console.log('Final totals - USD:', totalUsd, 'IQD:', totalIqd);
    
    // Ensure totals are numbers before using toFixed
    totalUsd = parseFloat(totalUsd) || 0;
    totalIqd = parseFloat(totalIqd) || 0;
    
    // Display totals based on currency type
    if (currencyType === 'دۆلار') {
        $('#view_total_usd').text(totalUsd.toFixed(2));
        $('#view_total_iqd').text('');
    } else if (currencyType === 'دینار') {
        $('#view_total_usd').text('');
        $('#view_total_iqd').text(totalIqd.toFixed(2));
    } else {
        $('#view_total_usd').text(totalUsd.toFixed(2));
        $('#view_total_iqd').text(totalIqd.toFixed(2));
    }
}

function populateEditMaterialsTable(materials) {
    const tbody = $('#editMaterialsTableBody');
    tbody.empty();
    
    console.log('populateEditMaterialsTable called with materials:', materials);
    
    if (materials && materials.length > 0) {
        materials.forEach(function(material, index) {
            console.log(`Material ${index}:`, material);
            addEditMaterialRow(material);
        });
    } else {
        addEditMaterialRow();
    }
    
    // After adding all rows, set the purchase unit for existing materials
    setTimeout(function() {
        $('.edit-material-row').each(function() {
            const row = $(this);
            const materialSelect = row.find('.edit-material-select');
            const purchaseUnitSelect = row.find('.edit-purchase-unit-select');
            const materialId = materialSelect.val();
            
            console.log('Processing row with materialId:', materialId);
            
            if (materialId) {
                // Get the materials data from the original materials array passed to this function
                const originalMaterials = window.editMaterialsData || [];
                const material = originalMaterials.find(m => m.material_id == materialId);
                console.log('Found material from original data:', material);
                
                if (material) {
                    // Get the unit type from the purchase data
                    let purchaseUnitType = 'piece';
                    if (material.unit_type) {
                        purchaseUnitType = material.unit_type;
                    }
                    console.log('Setting purchase unit type to:', purchaseUnitType);
                    
                    // Set the purchase unit dropdown
                    purchaseUnitSelect.val(purchaseUnitType);
                    
                    // Get the material definition for display purposes
                    const materials = window.materials || [];
                    const materialDef = materials.find(m => m.id == materialId);
                    
                    // Update the display and prices
                    if (materialDef) {
                        updateEditUnitTypeDisplay(row, materialDef);
                        fillEditPricesBasedOnUnitType(row, materialDef);
                    }
                    calculateEditRowTotal(row);
                }
            }
        });
        calculateEditGrandTotal();
    }, 100);
}

function addEditMaterialRow(materialData = null) {
    const rowId = 'edit_row_' + Date.now();
    const materials = window.materials || [];
    
    console.log('addEditMaterialRow called with materialData:', materialData);
    
    let materialsOptions = '<option value="">هەڵبژێرە</option>';
    materials.forEach(function(material) {
        const selected = materialData && materialData.material_id == material.id ? 'selected' : '';
        materialsOptions += `<option value="${material.id}" ${selected} 
            data-unit-type="${material.unit_type || ''}"
            data-pieces-per-carton="${material.pieces_per_carton || ''}"
            data-bags-per-barrel="${material.bags_per_barrel || ''}"
            data-liters-per-bag="${material.liters_per_bag || ''}"
            data-liters-per-barrel="${material.liters_per_barrel || ''}"
            data-price-per-piece="${material.price_per_piece || ''}"
            data-price-per-liter="${material.price_per_liter || ''}"
            data-price-per-bag="${material.price_per_bag || ''}">${material.name}</option>`;
    });

    const newRow = `
        <tr class="edit-material-row" id="${rowId}">
            <td>
                <select class="form-select edit-material-select" name="edit_materials[${rowId}][material_id]" required>
                    ${materialsOptions}
                </select>
            </td>
            <td>
                <div class="edit-unit-type-display" style="font-size: 0.9em; color: #666; margin-bottom: 5px;"></div>
                <select class="form-select edit-purchase-unit-select" name="edit_materials[${rowId}][purchase_unit]" style="font-size: 0.8em;">
                    <option value="">هەڵبژێرە</option>
                </select>
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
                <input type="number" class="form-control edit-price-bag-input" name="edit_materials[${rowId}][price_per_bag]" 
                       min="0" step="0.01" placeholder="0.00" 
                       value="${materialData ? materialData.price_per_bag : '0'}">
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
    
    // Set up material change handler
    $(`#${rowId} .edit-material-select`).on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const materialId = $(this).val();
        
        if (materialId) {
            const material = materials.find(m => m.id == materialId);
            if (material) {
                updateEditUnitTypeDisplay($(this).closest('tr'), material);
                fillEditPricesBasedOnUnitType($(this).closest('tr'), material);
            }
        } else {
            updateEditUnitTypeDisplay($(this).closest('tr'), null);
        }
    });
    
    // Set up purchase unit change handler
    $(`#${rowId} .edit-purchase-unit-select`).on('change', function() {
        const row = $(this).closest('tr');
        const materialSelect = row.find('.edit-material-select');
        const selectedOption = materialSelect.find('option:selected');
        
        if (selectedOption.length > 0) {
            const material = materials.find(m => m.id == materialSelect.val());
            if (material) {
                fillEditPricesBasedOnUnitType(row, material);
            }
        }
        calculateEditRowTotal(row);
    });
    
    // If we have material data, populate the unit information
    if (materialData && materialData.material_id) {
        console.log('Processing materialData:', materialData);
        const material = materials.find(m => m.id == materialData.material_id);
        console.log('Found material definition:', material);
        
        if (material) {
            updateEditUnitTypeDisplay($(`#${rowId}`), material);
            
            // Set the purchase unit based on the existing data
            // First try to get from purchase data, then from material definition, then default to 'piece'
            let purchaseUnitType = 'piece';
            if (materialData.unit_type) {
                purchaseUnitType = materialData.unit_type;
            } else if (material.unit_type) {
                purchaseUnitType = material.unit_type;
            }
            
            console.log('Setting purchase unit type to:', purchaseUnitType, 'for material:', materialData.material_id);
            const purchaseUnitSelect = $(`#${rowId} .edit-purchase-unit-select`);
            console.log('Purchase unit select element:', purchaseUnitSelect.length > 0 ? 'found' : 'not found');
            purchaseUnitSelect.val(purchaseUnitType);
            console.log('Purchase unit select value after setting:', purchaseUnitSelect.val());
            
            fillEditPricesBasedOnUnitType($(`#${rowId}`), material);
        }
    }
    
    // Calculate row total
    calculateEditRowTotal($(`#${rowId}`));
}

function calculateEditRowTotal(row) {
    const quantity = parseFloat(row.find('.edit-quantity-input').val()) || 0;
    const priceUsd = parseFloat(row.find('.edit-price-usd-input').val()) || 0;
    const priceIqd = parseFloat(row.find('.edit-price-iqd-input').val()) || 0;
    const priceBag = parseFloat(row.find('.edit-price-bag-input').val()) || 0;
    
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
    
    // Get transfer loss and other loss values
    const transferLoss = parseFloat($('#edit_transfer_loss').val()) || 0;
    const otherLoss = parseFloat($('#edit_other_loss').val()) || 0;
    const usdToIqdRate = parseFloat($('#edit_usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#edit_currency_type').val();
    
    // Convert losses to appropriate currency based on currency type
    let transferLossUsd = 0;
    let transferLossIqd = 0;
    let otherLossUsd = 0;
    let otherLossIqd = 0;
    
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert IQD losses to USD
        if (usdToIqdRate > 0) {
            transferLossUsd = transferLoss / (usdToIqdRate / 100);
            otherLossUsd = otherLoss / (usdToIqdRate / 100);
        }
        transferLossIqd = transferLoss;
        otherLossIqd = otherLoss;
    } else {
        // If currency is IQD, losses are already in IQD
        transferLossIqd = transferLoss;
        otherLossIqd = otherLoss;
        // Convert to USD if rate is available
        if (usdToIqdRate > 0) {
            transferLossUsd = transferLoss / (usdToIqdRate / 100);
            otherLossUsd = otherLoss / (usdToIqdRate / 100);
        }
    }
    
    // Add losses to totals
    totalUsd += transferLossUsd + otherLossUsd;
    totalIqd += transferLossIqd + otherLossIqd;
    
    // Set totals based on currency type
    if (currencyType === 'دۆلار') {
        $('#edit_total_usd').val(totalUsd.toFixed(2));
        $('#edit_total_iqd').val(''); // Clear IQD total when currency is USD
    } else if (currencyType === 'دینار') {
        $('#edit_total_usd').val(''); // Clear USD total when currency is IQD
        $('#edit_total_iqd').val(totalIqd.toFixed(2));
    } else {
        // If no currency selected, show both
        $('#edit_total_usd').val(totalUsd.toFixed(2));
        $('#edit_total_iqd').val(totalIqd.toFixed(2));
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
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: result.message || 'کڕینەکە بە سەرکەوتووی سڕایەوە',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                loadPurchaseMaterialsTable();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: result.error || 'هەڵەیەک ڕوویدا',
                                confirmButtonText: 'باشە'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک لە وەڵامەکەدا هەیە',
                            confirmButtonText: 'باشە'
                        });
                        console.error('Response:', response);
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
    $.ajax({
        url: '../process/purchase_materilas/get_materials.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    window.materials = result.data;
                } else {
                    console.error('Error loading materials:', result.error);
                }
            } catch (e) {
                console.error('Error parsing materials response:', e);
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
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    window.persons = result.data;
                } else {
                    console.error('Error loading persons:', result.error);
                }
            } catch (e) {
                console.error('Error parsing persons response:', e);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading persons:', error);
        }
    });
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

function updateEditUnitTypeDisplay(row, material) {
    const unitTypeDisplay = row.find('.edit-unit-type-display');
    const purchaseUnitSelect = row.find('.edit-purchase-unit-select');
    
    if (!material) {
        unitTypeDisplay.text('');
        purchaseUnitSelect.html('<option value="">هەڵبژێرە</option>');
        return;
    }
    
    // Display unit type information
    let unitTypeText = '';
    switch(material.unit_type) {
        case 'carton':
            unitTypeText = `کارتۆن (${material.pieces_per_carton || 1} دانە)`;
            break;
        case 'piece':
            unitTypeText = 'دانە';
            break;
        case 'barrel':
            unitTypeText = `بەرمیل (${material.bags_per_barrel || 1} دەبە × ${material.liters_per_bag || 1} لیتر)`;
            break;
        case 'bag':
            unitTypeText = `دەبە (${material.liters_per_bag || 1} لیتر)`;
            break;
        case 'liter':
            unitTypeText = 'لیتر';
            break;
        default:
            unitTypeText = material.unit_type || '-';
    }
    unitTypeDisplay.text(unitTypeText);
    
    // Populate purchase unit options
    let purchaseUnitOptions = '<option value="">هەڵبژێرە</option>';
    switch(material.unit_type) {
        case 'carton':
            purchaseUnitOptions += '<option value="carton">کارتۆن</option>';
            purchaseUnitOptions += '<option value="piece">دانە</option>';
            break;
        case 'piece':
            purchaseUnitOptions += '<option value="piece">دانە</option>';
            break;
        case 'barrel':
            purchaseUnitOptions += '<option value="barrel">بەرمیل</option>';
            purchaseUnitOptions += '<option value="bag">دەبە</option>';
            purchaseUnitOptions += '<option value="liter">لیتر</option>';
            break;
        case 'bag':
            purchaseUnitOptions += '<option value="bag">دەبە</option>';
            purchaseUnitOptions += '<option value="liter">لیتر</option>';
            break;
        case 'liter':
            purchaseUnitOptions += '<option value="liter">لیتر</option>';
            break;
    }
    purchaseUnitSelect.html(purchaseUnitOptions);
}

function fillEditPricesBasedOnUnitType(row, material) {
    const currencyType = $('#edit_currency_type').val();
    const purchaseUnit = row.find('.edit-purchase-unit-select').val();
    
    if (!purchaseUnit) return;
    
    let priceUsd = 0;
    let priceIqd = 0;
    let priceBag = 0;
    
    switch(purchaseUnit) {
        case 'carton':
            priceUsd = material.purchase_price_usd || 0;
            priceIqd = material.purchase_price_iqd || 0;
            break;
        case 'piece':
            priceUsd = material.price_per_piece || 0;
            priceIqd = material.purchase_price_iqd || 0;
            break;
        case 'barrel':
            priceUsd = material.purchase_price_usd || 0;
            priceIqd = material.purchase_price_iqd || 0;
            priceBag = material.price_per_bag || 0;
            break;
        case 'bag':
            priceUsd = material.price_per_bag || 0;
            priceIqd = material.purchase_price_iqd || 0;
            priceBag = material.price_per_bag || 0;
            break;
        case 'liter':
            priceUsd = material.price_per_liter || 0;
            priceIqd = material.purchase_price_iqd || 0;
            break;
    }
    
    row.find('.edit-price-usd-input').val(priceUsd.toFixed(2));
    row.find('.edit-price-iqd-input').val(priceIqd.toFixed(2));
    row.find('.edit-price-bag-input').val(priceBag.toFixed(2));
}
