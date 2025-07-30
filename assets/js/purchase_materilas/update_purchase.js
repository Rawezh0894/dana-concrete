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
    
    // Load USD rate when edit modal is shown
    $('#editPurchaseModal').on('show.bs.modal', function() {
        loadUsdRateForEdit();
    });
});

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
        const quantity = parseFloat($(this).find('.edit-quantity-input').val()) || 0;
        const priceUsd = parseFloat($(this).find('.edit-price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.edit-price-iqd-input').val()) || 0;
        
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

function submitEditForm(formData) {
    $.ajax({
        url: '../process/purchase_materilas/update_purchase.php',
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
                        text: result.message || 'کڕینەکە بە سەرکەوتووی نوێکرایەوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Close modal
                        $('#editPurchaseModal').modal('hide');
                        
                        // Reload table
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
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

function loadMaterials() {
    return $.ajax({
        url: '../process/purchase_materilas/get_materials.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    window.materials = result.data;
                    // Refresh material dropdowns in edit form
                    refreshEditMaterialDropdowns();
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
                    // Populate person dropdown in edit form
                    populateEditPersonDropdown();
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

function populateEditPersonDropdown() {
    const persons = window.persons || [];
    let options = '<option value="">هەڵبژێرە</option>';
    if (persons.length > 0) {
        persons.forEach(function(person) {
            options += `<option value="${person.id}">${person.name}</option>`;
        });
    } else {
        options = '<option value="">درووشیارەکان بار نەکراون...</option>';
    }
    $('#edit_person_id').html(options);
    
    // Re-initialize Select2 for edit person dropdown
    $('#edit_person_id').select2({
        dropdownParent: $('#editPurchaseModal'),
        width: '100%',
        placeholder: "هەڵبژێرە",
        dir: "rtl"
    });
}

function refreshEditMaterialDropdowns() {
    const materials = window.materials || [];
    let options = '<option value="">هەڵبژێرە</option>';
    
    if (materials.length > 0) {
        materials.forEach(function(material) {
            options += `<option value="${material.id}">${material.name}</option>`;
        });
    } else {
        options = '<option value="">کاڵاکان بار نەکراون...</option>';
    }
    
    // Update all material select dropdowns in edit form
    $('.edit-material-select').each(function() {
        const currentValue = $(this).val();
        $(this).html(options);
        $(this).val(currentValue);
        
        // Re-initialize Select2 for this dropdown
        $(this).select2({
            dropdownParent: $('#editPurchaseModal'),
            width: '100%',
            placeholder: "هەڵبژێرە",
            dir: "rtl"
        });
    });
}

// Load USD to IQD exchange rate from API for edit modal
function loadUsdRateForEdit() {
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#edit_usd_to_iqd_rate').val(result.rate);
                    // Update display on page
                    updateUsdRateDisplay(result.rate);
                    // Recalculate totals if there are any existing values
                    calculateEditGrandTotal();
                } else {
                    // Use default rate if API fails
                    if (result.default_rate) {
                        $('#edit_usd_to_iqd_rate').val(result.default_rate);
                        updateUsdRateDisplay(result.default_rate);
                        calculateEditGrandTotal();
                    }
                    console.log('Error loading USD rate for edit: ' + result.error);
                }
            } catch (e) {
                console.error('Error parsing USD rate response for edit:', e);
                // Use default rate if parsing fails
                $('#edit_usd_to_iqd_rate').val(139250);
                updateUsdRateDisplay(139250);
                calculateEditGrandTotal();
            }
        },
        error: function(xhr, status, error) {
            // Use default rate if request fails
            $('#edit_usd_to_iqd_rate').val(139250);
            updateUsdRateDisplay(139250);
            calculateEditGrandTotal();
            console.error('Error loading USD rate for edit:', error);
        }
    });
}

// Update USD rate display on page
function updateUsdRateDisplay(rate) {
    const usdRateElement = document.getElementById('usdExchangeRate');
    if (usdRateElement) {
        usdRateElement.textContent = rate + ' د.ع';
        console.log('USD rate display updated:', rate);
    }
}
