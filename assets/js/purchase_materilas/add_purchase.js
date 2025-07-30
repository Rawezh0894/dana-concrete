$(document).ready(function() {
    // Initialize Select2
    $('.form-select').select2({
        theme: 'bootstrap-5'
    });

    // Set default date to today
    $('#purchase_date').val(new Date().toISOString().split('T')[0]);

    // Load next receipt number
    loadNextReceiptNumber();
    
    // Load current USD rate
    loadUsdRate();

    // Initialize with data from PHP if available, otherwise load via AJAX
    if (window.initialMaterials && window.initialMaterials.length > 0) {
        window.materials = window.initialMaterials;
        window.persons = window.initialPersons || [];
        populatePersonDropdown();
        addMaterialRow();
    } else {
        // Load materials and persons for dropdowns first, then add first material row
        loadMaterials().then(function() {
            loadPersons().then(function() {
                // Add first material row after materials are loaded
                addMaterialRow();
            });
        });
    }

    // Add material row button
    $('#addMaterialRow').click(function() {
        addMaterialRow();
    });

    // Handle form submission
    $('#addPurchaseForm').submit(function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return false;
        }

        // Collect form data
        const formData = collectFormData();
        
        // Submit form
        submitForm(formData);
    });

    // Calculate totals when any input changes
    $(document).on('input', '.material-row input', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateGrandTotal();
    });

    // Calculate totals when transfer loss, other loss, or currency type changes
    $(document).on('input', '#transfer_loss, #other_loss', function() {
        calculateGrandTotal();
    });

    $(document).on('change', '#currency_type', function() {
        calculateGrandTotal();
    });

    // Auto-fill prices when material is selected
    $(document).on('change', '.material-select', function() {
        const materialId = $(this).val();
        const row = $(this).closest('tr');
        
        if (materialId) {
            // Find the selected material from the materials array
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Auto-fill the price fields
                row.find('.price-usd-input').val(material.purchase_price_usd || 0);
                row.find('.price-iqd-input').val(material.purchase_price_iqd || 0);
                
                // Calculate totals
                calculateRowTotal(row);
                calculateGrandTotal();
            }
        } else {
            // Clear price fields if no material is selected
            row.find('.price-usd-input').val(0);
            row.find('.price-iqd-input').val(0);
            calculateRowTotal(row);
            calculateGrandTotal();
        }
    });

    // Remove material row
    $(document).on('click', '.remove-material-row', function() {
        $(this).closest('tr').remove();
        calculateGrandTotal();
        
        // Ensure at least one row exists
        if ($('#materialsTableBody tr').length === 0) {
            addMaterialRow();
        }
    });
});

function addMaterialRow() {
    const rowId = 'row_' + Date.now();
    const materials = window.materials || [];
    
    let materialsOptions = '<option value="">هەڵبژێرە</option>';
    if (materials.length > 0) {
        materials.forEach(function(material) {
            materialsOptions += `<option value="${material.id}">${material.name}</option>`;
        });
    } else {
        materialsOptions = '<option value="">کاڵاکان بار نەکراون...</option>';
    }

    const newRow = `
        <tr class="material-row" id="${rowId}">
            <td>
                <select class="form-select material-select" name="materials[${rowId}][material_id]" required>
                    ${materialsOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" name="materials[${rowId}][quantity]" 
                       min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" class="form-control price-usd-input" name="materials[${rowId}][price_per_unit_usd]" 
                       min="0" step="0.01" placeholder="0.00" value="0">
            </td>
            <td>
                <input type="number" class="form-control price-iqd-input" name="materials[${rowId}][price_per_unit_iqd]" 
                       min="0" step="0.01" placeholder="0.00" value="0">
            </td>
            <td>
                <input type="number" class="form-control total-usd-input" name="materials[${rowId}][total_price_usd]" 
                       readonly placeholder="0.00">
            </td>
            <td>
                <input type="number" class="form-control total-iqd-input" name="materials[${rowId}][total_price_iqd]" 
                       readonly placeholder="0.00">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-material-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#materialsTableBody').append(newRow);
    
    // Initialize Select2 for new row
    $(`#${rowId} .material-select`).select2({
        dropdownParent: $('#addPurchaseModal'),
        width: '100%',
        placeholder: "هەڵبژێرە",
        dir: "rtl"
    });
    
    // If materials are loaded after row creation, refresh this row's dropdown
    if (materials.length === 0 && window.materials && window.materials.length > 0) {
        refreshMaterialDropdowns();
    }
}

function calculateRowTotal(row) {
    const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
    const priceUsd = parseFloat(row.find('.price-usd-input').val()) || 0;
    const priceIqd = parseFloat(row.find('.price-iqd-input').val()) || 0;
    
    const totalUsd = quantity * priceUsd;
    const totalIqd = quantity * priceIqd;
    
    row.find('.total-usd-input').val(totalUsd.toFixed(2));
    row.find('.total-iqd-input').val(totalIqd.toFixed(2));
}

function calculateGrandTotal() {
    let totalUsd = 0;
    let totalIqd = 0;
    
    // Calculate materials totals
    $('.material-row').each(function() {
        totalUsd += parseFloat($(this).find('.total-usd-input').val()) || 0;
        totalIqd += parseFloat($(this).find('.total-iqd-input').val()) || 0;
    });
    
    // Get transfer loss and other loss values
    const transferLoss = parseFloat($('#transfer_loss').val()) || 0;
    const otherLoss = parseFloat($('#other_loss').val()) || 0;
    const usdToIqdRate = parseFloat($('#usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#currency_type').val();
    
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
        $('#total_usd').val(totalUsd.toFixed(2));
        $('#total_iqd').val(''); // Clear IQD total when currency is USD
    } else if (currencyType === 'دینار') {
        $('#total_usd').val(''); // Clear USD total when currency is IQD
        $('#total_iqd').val(totalIqd.toFixed(2));
    } else {
        // If no currency selected, show both
        $('#total_usd').val(totalUsd.toFixed(2));
        $('#total_iqd').val(totalIqd.toFixed(2));
    }
}

function validateForm() {
    // Check if receipt number is provided
    if (!$('#receipt_number').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە ژمارەی پسووڵە بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check receipt number format (KR-XXXX)
    const receiptNumber = $('#receipt_number').val().trim();
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
    if (!$('#person_id').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە درووشیار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if purchase date is provided
    if (!$('#purchase_date').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە بەروار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if currency type is selected
    if (!$('#currency_type').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە جۆری دراو هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if USD to IQD rate is provided
    if (!$('#usd_to_iqd_rate').val() || parseFloat($('#usd_to_iqd_rate').val()) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە نرخی 100 دۆلار بە دینار بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check if at least one material is added
    if ($('#materialsTableBody tr').length === 0) {
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
    $('.material-row').each(function() {
        const materialId = $(this).find('.material-select').val();
        const quantity = parseFloat($(this).find('.quantity-input').val());
        
        if (materialId && quantity > 0) {
            hasValidMaterial = true;
        }
    });
    
    if (!hasValidMaterial) {
        Swal.fire({
            icon: 'warning',
            title: 'هەڵە',
            text: 'تکایە لانیکەم یەک کاڵای بەردەست هەڵبژێرە و بڕی بەردەست بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    return true;
}

function collectFormData() {
    const formData = new FormData();
    
    // Receipt details
    formData.append('receipt_number', $('#receipt_number').val());
    formData.append('person_id', $('#person_id').val());
    formData.append('purchase_date', $('#purchase_date').val());
    formData.append('currency_type', $('#currency_type').val());
    formData.append('notes', $('#notes').val());
    formData.append('transfer_loss', $('#transfer_loss').val() || 0);
    formData.append('other_loss', $('#other_loss').val() || 0);
    formData.append('usd_to_iqd_rate', $('#usd_to_iqd_rate').val() || 0);
    
    // Materials data
    const materials = [];
    $('.material-row').each(function() {
        const materialId = $(this).find('.material-select').val();
        const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
        const priceUsd = parseFloat($(this).find('.price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.price-iqd-input').val()) || 0;
        
        if (materialId && quantity > 0) {
            materials.push({
                material_id: materialId,
                quantity: quantity,
                price_per_unit_usd: priceUsd,
                price_per_unit_iqd: priceIqd,
                total_price_usd: quantity * priceUsd,
                total_price_iqd: quantity * priceIqd
            });
        }
    });
    
    formData.append('materials', JSON.stringify(materials));
    
    return formData;
}

function submitForm(formData) {
    $.ajax({
        url: '../process/purchase_materilas/add_purchase.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const result = JSON.parse(response);
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: result.message || 'کڕینەکە بە سەرکەوتووی زیاد کراوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Reset form
                        resetForm();
                        
                        // Reload table
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
                        }
                        
                        // Close modal
                        $('#addPurchaseModal').modal('hide');
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

function resetForm() {
    $('#addPurchaseForm')[0].reset();
    $('#materialsTableBody').empty();
    $('#total_usd').val('0.00');
    $('#total_iqd').val('0.00');
    $('#transfer_loss').val('0');
    $('#other_loss').val('0');
    $('#usd_to_iqd_rate').val('140000');
    $('#purchase_date').val(new Date().toISOString().split('T')[0]);
    
    // Reset Select2 dropdowns
    $('#person_id').val('').trigger('change');
    $('#currency_type').val('').trigger('change');
    
    loadNextReceiptNumber();
    loadUsdRate(); // Load current USD rate
    addMaterialRow();
}

// Load materials data for dropdowns
function loadMaterials() {
    return $.ajax({
        url: '../process/purchase_materilas/get_materials.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    window.materials = result.data;
                    // Refresh material dropdowns
                    refreshMaterialDropdowns();
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
    return $.ajax({
        url: '../process/purchase_materilas/get_persons.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    window.persons = result.data;
                    // Populate person dropdown
                    populatePersonDropdown();
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

function populatePersonDropdown() {
    const persons = window.persons || [];
    let options = '<option value="">هەڵبژێرە</option>';
    persons.forEach(function(person) {
        options += `<option value="${person.id}">${person.name}</option>`;
    });
    $('#person_id').html(options);
    
    // Re-initialize Select2 for person dropdown
    $('#person_id').select2({
        dropdownParent: $('#addPurchaseModal'),
        width: '100%',
        placeholder: "هەڵبژێرە",
        dir: "rtl"
    });
}

function refreshMaterialDropdowns() {
    const materials = window.materials || [];
    let options = '<option value="">هەڵبژێرە</option>';
    
    if (materials.length > 0) {
        materials.forEach(function(material) {
            options += `<option value="${material.id}">${material.name}</option>`;
        });
    } else {
        options = '<option value="">کاڵاکان بار نەکراون...</option>';
    }
    
    // Update all material select dropdowns
    $('.material-select').each(function() {
        const currentValue = $(this).val();
        $(this).html(options);
        $(this).val(currentValue);
        
        // Re-initialize Select2 for this dropdown
        $(this).select2({
            dropdownParent: $('#addPurchaseModal'),
            width: '100%',
            placeholder: "هەڵبژێرە",
            dir: "rtl"
        });
    });
}

// Load materials data on page load
loadMaterials();

// Load next receipt number
function loadNextReceiptNumber() {
    $.ajax({
        url: '../process/purchase_materilas/get_next_receipt_number.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#receipt_number').val(result.receipt_number);
                } else {
                    console.error('Error loading receipt number:', result.error);
                }
            } catch (e) {
                console.error('Error parsing receipt number response:', e);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading receipt number:', error);
        }
    });
}

// Load USD to IQD exchange rate from API
function loadUsdRate() {
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        dataType: 'json',
        success: function(result) {
            if (result.success) {
                $('#usd_to_iqd_rate').val(result.rate);
                // Recalculate totals if there are any existing values
                calculateGrandTotal();
            } else {
                // Use default rate if API fails
                if (result.default_rate) {
                    $('#usd_to_iqd_rate').val(result.default_rate);
                    calculateGrandTotal();
                }
                console.log('Error loading USD rate: ' + result.error);
            }
        },
        error: function(xhr, status, error) {
            // Use default rate if request fails
            $('#usd_to_iqd_rate').val(139250);
            calculateGrandTotal();
            console.error('Error loading USD rate:', error);
        }
    });
}
