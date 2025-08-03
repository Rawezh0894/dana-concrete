$(document).ready(function() {
    // Initialize with data from PHP if available, otherwise load via AJAX
    if (window.initialMaterials && window.initialMaterials.length > 0) {
        window.materials = window.initialMaterials;
        window.persons = window.initialPersons || [];
        populateEditPersonDropdown();
    } else {
        // Load materials and persons for dropdowns
        loadMaterials();
        loadPersons();
    }
    
    // Handle edit form submission
    $('#editPurchaseForm').submit(function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateEditForm()) {
            return false;
        }

        // Collect form data
        const formData = collectEditFormData();
        
        // Submit form
        submitEditForm(formData);
    });

    // Add material row button for edit modal
    $('#editAddMaterialRow').click(function() {
        addEditMaterialRow();
    });

    // Calculate totals when any input changes in edit modal
    $(document).on('input', '.edit-material-row input', function() {
        calculateEditRowTotal($(this).closest('tr'));
        calculateEditGrandTotal();
    });

    // Calculate totals when transfer loss, other loss, or currency type changes in edit modal
    $(document).on('input', '#edit_transfer_loss, #edit_other_loss', function() {
        calculateEditGrandTotal();
    });

    $(document).on('change', '#edit_currency_type', function() {
        calculateEditGrandTotal();
    });

    // Auto-fill prices and unit type when material is selected in edit modal
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

function addEditMaterialRow() {
    const rowId = 'edit_row_' + Date.now();
    const materials = window.materials || [];
    
    let materialsOptions = '<option value="">هەڵبژێرە</option>';
    if (materials.length > 0) {
        materials.forEach(function(material) {
            materialsOptions += `<option value="${material.id}">${material.name} (${material.unit_type || 'دانە'})</option>`;
        });
    } else {
        materialsOptions = '<option value="">کاڵاکان بار نەکراون...</option>';
    }

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
                       min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" class="form-control edit-price-usd-input" name="edit_materials[${rowId}][price_per_unit_usd]" 
                       min="0" step="0.01" placeholder="0.00" value="0">
            </td>
            <td>
                <input type="number" class="form-control edit-price-iqd-input" name="edit_materials[${rowId}][price_per_unit_iqd]" 
                       min="0" step="0.01" placeholder="0.00" value="0">
            </td>
            <td>
                <input type="number" class="form-control edit-total-usd-input" name="edit_materials[${rowId}][total_price_usd]" 
                       readonly placeholder="0.00">
            </td>
            <td>
                <input type="number" class="form-control edit-total-iqd-input" name="edit_materials[${rowId}][total_price_iqd]" 
                       readonly placeholder="0.00">
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
    
    // If materials are loaded after row creation, refresh this row's dropdown
    if (materials.length === 0 && window.materials && window.materials.length > 0) {
        refreshEditMaterialDropdowns();
    }
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
        transferLossUsd = transferLoss;
        otherLossUsd = otherLoss;
        transferLossIqd = transferLoss * usdToIqdRate;
        otherLossIqd = otherLoss * usdToIqdRate;
    } else if (currencyType === 'دینار') {
        transferLossIqd = transferLoss;
        otherLossIqd = otherLoss;
        transferLossUsd = usdToIqdRate > 0 ? transferLoss / usdToIqdRate : 0;
        otherLossUsd = usdToIqdRate > 0 ? otherLoss / usdToIqdRate : 0;
    }
    
    // Add losses to totals
    totalUsd += transferLossUsd + otherLossUsd;
    totalIqd += transferLossIqd + otherLossIqd;
    
    // Update total fields
    $('#edit_total_usd').val(totalUsd.toFixed(2));
    $('#edit_total_iqd').val(totalIqd.toFixed(2));
}

function validateEditForm() {
    // Check if receipt number is provided
    if (!$('#edit_receipt_number').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە ژمارەی پسووڵە بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check receipt number format (KR-XXXX)
    const receiptNumber = $('#edit_receipt_number').val().trim();
    const receiptPattern = /^KR-\d{4}$/;
    if (!receiptPattern.test(receiptNumber)) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'فۆرماتی ژمارەی پسووڵە هەڵەیە، دەبێت بە شێوەی KR-XXXX بێت',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if person is selected
    if (!$('#edit_person_id').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە درووشیار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if purchase date is provided
    if (!$('#edit_purchase_date').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە بەروار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if currency type is selected
    if (!$('#edit_currency_type').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە جۆری دراو هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if USD to IQD rate is provided
    if (!$('#edit_usd_to_iqd_rate').val() || parseFloat($('#edit_usd_to_iqd_rate').val()) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە نرخی 100 دۆلار بە دینار بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if at least one material is added
    if ($('#editMaterialsTableBody tr').length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە لانیکەم یەک کاڵا زیاد بکە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Validate each material row
    let hasValidMaterial = false;
    $('.edit-material-row').each(function() {
        const materialId = $(this).find('.edit-material-select').val();
        const quantity = parseFloat($(this).find('.edit-quantity-input').val());
        const priceUsd = parseFloat($(this).find('.edit-price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.edit-price-iqd-input').val()) || 0;
        
        if (materialId && quantity > 0 && (priceUsd > 0 || priceIqd > 0)) {
            hasValidMaterial = true;
        }
    });
    
    if (!hasValidMaterial) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە لانیکەم یەک کاڵای بەردەست هەڵبژێرە و بڕ و نرخی بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    return true;
}

function collectEditFormData() {
    const formData = new FormData();
    
    // Receipt details
    formData.append('id', $('#edit_purchase_id').val());
    formData.append('receipt_number', $('#edit_receipt_number').val());
    formData.append('person_id', $('#edit_person_id').val());
    formData.append('purchase_date', $('#edit_purchase_date').val());
    formData.append('currency_type', $('#edit_currency_type').val());
    formData.append('notes', $('#edit_notes').val());
    formData.append('transfer_loss', $('#edit_transfer_loss').val() || 0);
    formData.append('other_loss', $('#edit_other_loss').val() || 0);
    formData.append('usd_to_iqd_rate', $('#edit_usd_to_iqd_rate').val() || 0);
    
    // Materials data
    const materials = [];
    $('.edit-material-row').each(function() {
        const materialId = $(this).find('.edit-material-select').val();
        const unitType = $(this).find('.edit-unit-type-select').val();
        const quantity = parseFloat($(this).find('.edit-quantity-input').val()) || 0;
        const priceUsd = parseFloat($(this).find('.edit-price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.edit-price-iqd-input').val()) || 0;
        
        if (materialId && quantity > 0) {
            materials.push({
                material_id: materialId,
                unit_type: unitType,
                quantity: quantity,
                price_per_unit_usd: priceUsd,
                price_per_unit_iqd: priceIqd
            });
        }
    });
    
    formData.append('materials', JSON.stringify(materials));
    
    return formData;
}

function submitEditForm(formData) {
    const submitBtn = $('#editPurchaseForm button[type="submit"]');
    const originalText = submitBtn.text();
    
    submitBtn.prop('disabled', true).text('چاوەڕوان بە...');
    
    $.ajax({
        url: '../process/purchase_materilas/update_purchase.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Server response:', response);
            
            try {
                const result = JSON.parse(response);
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: result.message || 'کڕینەکە بە سەرکەوتووی نوێکرایەوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Close modal
                        $('#editPurchaseModal').modal('hide');
                        
                        // Reload table
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
                        }
                        
                        // Refresh summary cards if the function exists
                        if (typeof loadSummaryCards === 'function') {
                            loadSummaryCards();
                        }
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
                // If not JSON, treat as plain text
                console.log('Response is not JSON, treating as plain text');
                
                if (response.trim() === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: 'کڕینەکە بە سەرکەوتووی نوێکرایەوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        $('#editPurchaseModal').modal('hide');
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
                        }
                        if (typeof loadSummaryCards === 'function') {
                            loadSummaryCards();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک لە وەڵامەکەدا هەیە',
                        confirmButtonText: 'باشە'
                    });
                    console.error('Response:', response);
                }
            }
            
            submitBtn.prop('disabled', false).text(originalText);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            Swal.fire({
                icon: 'error',
                title: 'هەڵەی AJAX',
                text: 'هەڵە لە پەیوەندی بە سێرڤەر: ' + error + ' (Status: ' + xhr.status + ')',
                confirmButtonText: 'باشە'
            });
            
            submitBtn.prop('disabled', false).text(originalText);
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
                refreshEditMaterialDropdowns();
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
    return $.ajax({
        url: '../process/purchase_materilas/get_persons.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.persons = response.data;
                populateEditPersonDropdown();
            } else {
                console.error('Error loading persons:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading persons:', error);
        }
    });
}

function populateEditPersonDropdown() {
    const persons = window.persons || [];
    const $personSelect = $('#edit_person_id');
    
    $personSelect.empty();
    $personSelect.append('<option value="">هەڵبژێرە</option>');
    
    persons.forEach(function(person) {
        $personSelect.append(`<option value="${person.id}">${person.name}</option>`);
    });
    
    // Re-initialize Select2 for edit person dropdown
    $personSelect.select2({
        dropdownParent: $('#editPurchaseModal'),
        width: '100%',
        placeholder: "هەڵبژێرە",
        dir: "rtl"
    });
}

function refreshEditMaterialDropdowns() {
    const materials = window.materials || [];
    
    $('.edit-material-select').each(function() {
        const $select = $(this);
        const currentValue = $select.val();
        
        $select.empty();
        $select.append('<option value="">هەڵبژێرە</option>');
        
        materials.forEach(function(material) {
            const selected = material.id == currentValue ? 'selected' : '';
            $select.append(`<option value="${material.id}" ${selected}>${material.name} (${material.unit_type || 'دانە'})</option>`);
        });
        
        // Re-initialize Select2 for this dropdown
        $select.select2({
            dropdownParent: $('#editPurchaseModal'),
            width: '100%',
            placeholder: "هەڵبژێرە",
            dir: "rtl"
        });
    });
}
