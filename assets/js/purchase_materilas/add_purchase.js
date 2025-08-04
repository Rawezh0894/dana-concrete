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
        
        // Check receipt number uniqueness
        const receiptNumber = $('#receipt_number').val().trim();
        $.ajax({
            url: '../process/purchase_materilas/check_receipt_number.php',
            type: 'POST',
            data: { receipt_number: receiptNumber },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'هەڵە',
                        text: response.error,
                        confirmButtonText: 'باشە'
                    });
                } else {
                    // Collect form data and submit
                    const formData = collectFormData();
                    submitForm(formData);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵە لە پشکنینی ژمارەی پسووڵە',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });

    // Calculate totals when any input changes
    $(document).on('input', '.material-row input', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateGrandTotal();
    });

    // Calculate totals when transfer loss, other loss, currency type, or USD rate changes
    $(document).on('input', '#transfer_loss, #other_loss', function() {
        calculateGrandTotal();
    });

    $(document).on('change', '#currency_type', function() {
        calculateGrandTotal();
        // Update transfer and other loss fields based on currency type
        updateAdditionalCostsFields();
    });

    $(document).on('input', '#usd_to_iqd_rate', function() {
        calculateGrandTotal();
        calculateRemainingAmounts();
    });

    // Calculate remaining amounts when paid amounts change
    $(document).on('input', '#paid_amount_usd, #paid_amount_iqd', function() {
        calculateRemainingAmounts();
    });

    // Handle payment type change
    $(document).on('change', '#payment_type', function() {
        const paymentType = $(this).val();
        
        if (paymentType === 'نەقد') {
            // For cash payment, clear remaining amounts
            $('#remaining_amount_usd').val('');
            $('#remaining_amount_iqd').val('');
        } else if (paymentType === 'قەرز') {
            // For credit payment, calculate remaining amounts
            calculateRemainingAmounts();
        }
    });

    // Auto-fill prices and unit type when material is selected
    $(document).on('change', '.material-select', function() {
        const materialId = $(this).val();
        const row = $(this).closest('tr');
        
        if (materialId) {
            // Find the selected material from the materials array
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Auto-fill the unit type
                row.find('.unit-type-display').text(material.unit_type || 'دانە');
                row.find('.unit-type-input').val(material.unit_type || 'دانە');
                
                // Populate unit type dropdown with available options
                populateUnitTypeDropdown(row, material);
                
                // Auto-fill the price fields based on currency type
                const currencyType = $('#currency_type').val();
                if (currencyType === 'دۆلار') {
                    row.find('.price-usd-input').val(material.purchase_price_usd || 0);
                    row.find('.price-iqd-input').val(0);
                } else if (currencyType === 'دینار') {
                    row.find('.price-usd-input').val(0);
                    row.find('.price-iqd-input').val(material.purchase_price_iqd || 0);
                } else {
                    row.find('.price-usd-input').val(material.purchase_price_usd || 0);
                    row.find('.price-iqd-input').val(material.purchase_price_iqd || 0);
                }
                
                // Calculate totals
                calculateRowTotal(row);
                calculateGrandTotal();
            }
        } else {
            // Clear fields if no material is selected
            row.find('.unit-type-display').text('دانە');
            row.find('.unit-type-input').val('دانە');
            row.find('.unit-type-select').empty().append('<option value="دانە">دانە</option>');
            row.find('.price-usd-input').val(0);
            row.find('.price-iqd-input').val(0);
            calculateRowTotal(row);
            calculateGrandTotal();
        }
    });

    // Update additional costs fields when currency type changes
    $(document).on('change', '#currency_type', function() {
        // Store current values as IQD before converting
        const currentTransferLoss = parseFloat($('#transfer_loss').val()) || 0;
        const currentOtherLoss = parseFloat($('#other_loss').val()) || 0;
        const usdToIqdRate = parseFloat($('#usd_to_iqd_rate').val()) || 0;
        const oldCurrencyType = $('#currency_type').attr('data-previous-value');
        
        // Convert current values to IQD if they were in USD
        let transferLossIqd = currentTransferLoss;
        let otherLossIqd = currentOtherLoss;
        
        if (oldCurrencyType === 'دۆلار') {
            transferLossIqd = currentTransferLoss * (usdToIqdRate / 100);
            otherLossIqd = currentOtherLoss * (usdToIqdRate / 100);
        }
        
        // Store IQD values
        $('#transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#other_loss').attr('data-iqd-value', otherLossIqd);
        
        // Update display based on new currency type
        updateAdditionalCostsFields();
        
        // Store new currency type
        $('#currency_type').attr('data-previous-value', $(this).val());
    });

    // Handle unit type change
    $(document).on('change', '.unit-type-select', function() {
        const row = $(this).closest('tr');
        const selectedUnitType = $(this).val();
        const materialId = row.find('.material-select').val();
        
        if (materialId && selectedUnitType) {
            const material = window.materials.find(m => m.id == materialId);
            if (material) {
                // Calculate price based on selected unit type
                calculatePriceForUnitType(row, material, selectedUnitType);
                calculateRowTotal(row);
                calculateGrandTotal();
            }
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
            materialsOptions += `<option value="${material.id}">${material.name} (${material.unit_type || 'دانە'})</option>`;
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
                <select class="form-select unit-type-select" name="materials[${rowId}][unit_type]" required>
                    <option value="دانە">دانە</option>
                </select>
                <input type="hidden" class="unit-type-input" name="materials[${rowId}][original_unit_type]" value="دانە">
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

function populateUnitTypeDropdown(row, material) {
    const $unitSelect = row.find('.unit-type-select');
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
    row.find('.unit-type-input').val(originalUnitType);
}

function calculatePriceForUnitType(row, material, selectedUnitType) {
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
    const currencyType = $('#currency_type').val();
    if (currencyType === 'دۆلار') {
        row.find('.price-usd-input').val(priceUsd.toFixed(2));
        row.find('.price-iqd-input').val(0);
    } else if (currencyType === 'دینار') {
        row.find('.price-usd-input').val(0);
        row.find('.price-iqd-input').val(priceIqd.toFixed(2));
    } else {
        row.find('.price-usd-input').val(priceUsd.toFixed(2));
        row.find('.price-iqd-input').val(priceIqd.toFixed(2));
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
    
    // Get transfer loss and other loss values (these are always in IQD)
    const transferLossIqd = parseFloat($('#transfer_loss').val()) || 0;
    const otherLossIqd = parseFloat($('#other_loss').val()) || 0;
    const usdToIqdRate = parseFloat($('#usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#currency_type').val();
    
    // Convert additional costs based on currency type
    let transferLossUsd = 0;
    let transferLossIqdConverted = 0;
    let otherLossUsd = 0;
    let otherLossIqdConverted = 0;
    
    if (currencyType === 'دۆلار') {
        // If currency is USD, convert IQD costs to USD and add to USD total only
        transferLossUsd = usdToIqdRate > 0 ? transferLossIqd / (usdToIqdRate / 100) : 0;
        otherLossUsd = usdToIqdRate > 0 ? otherLossIqd / (usdToIqdRate / 100) : 0;
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
    
    // Update total fields with formatted display
    $('#total_usd').val(`$${formatNumber(totalUsd)}`);
    $('#total_iqd').val(`${formatNumber(totalIqd)} د.ع`);
    
    // Also update hidden fields for calculations
    $('#total_usd').attr('data-raw-value', totalUsd);
    $('#total_iqd').attr('data-raw-value', totalIqd);
    $('#total_usd_raw').val(totalUsd);
    $('#total_iqd_raw').val(totalIqd);
    
    // Calculate remaining amounts
    calculateRemainingAmounts();
}

function calculateRemainingAmounts() {
    // Check payment type first
    const paymentType = $('#payment_type').val();
    
    if (paymentType === 'نەقد') {
        // For cash payment, clear remaining amounts
        $('#remaining_amount_usd').val('');
        $('#remaining_amount_iqd').val('');
        return;
    }
    
    // Get raw values from data attributes for calculations
    const totalUsd = parseFloat($('#total_usd').attr('data-raw-value')) || 0;
    const totalIqd = parseFloat($('#total_iqd').attr('data-raw-value')) || 0;
    const paidUsd = parseFloat($('#paid_amount_usd').val()) || 0;
    const paidIqd = parseFloat($('#paid_amount_iqd').val()) || 0;
    const usdToIqdRate = parseFloat($('#usd_to_iqd_rate').val()) || 0;
    const currencyType = $('#currency_type').val();
    
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
        $('#remaining_amount_usd').val(`$${formatNumber(remainingUsd)}`);
        $('#remaining_amount_iqd').val(''); // Clear IQD remaining when currency is USD
    } else if (currencyType === 'دینار') {
        $('#remaining_amount_usd').val(''); // Clear USD remaining when currency is IQD
        $('#remaining_amount_iqd').val(`${formatNumber(remainingIqd)} د.ع`);
    } else {
        // If no currency selected, show both
        $('#remaining_amount_usd').val(`$${formatNumber(remainingUsd)}`);
        $('#remaining_amount_iqd').val(`${formatNumber(remainingIqd)} د.ع`);
    }
}

function validateForm() {
    // Check if at least one material is added
    if ($('#materialsTableBody tr').length === 0) {
        Swal.fire({
            icon: 'error',
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
        const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
        const priceUsd = parseFloat($(this).find('.price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.price-iqd-input').val()) || 0;
        
        if (materialId && quantity > 0 && (priceUsd > 0 || priceIqd > 0)) {
            hasValidMaterial = true;
        }
    });
    
    if (!hasValidMaterial) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە لانیکەم یەک کاڵای بەردەست هەڵبژێرە و بڕ و نرخی بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Validate receipt number
    const receiptNumber = $('#receipt_number').val().trim();
    if (!receiptNumber) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە ژمارەی پسووڵە بنووسە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Check receipt number format (KR-XXXX)
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
    
    // Validate person
    const personId = $('#person_id').val();
    if (!personId) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە دروشیار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Validate date
    const purchaseDate = $('#purchase_date').val();
    if (!purchaseDate) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە بەروار هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    // Validate currency type
    const currencyType = $('#currency_type').val();
    if (!currencyType) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە جۆری دراو هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return false;
    }
    
    return true;
}

function collectFormData() {
    const formData = new FormData();
    
    // Add basic form fields
    formData.append('receipt_number', $('#receipt_number').val());
    formData.append('person_id', $('#person_id').val());
    formData.append('purchase_date', $('#purchase_date').val());
    formData.append('currency_type', $('#currency_type').val());
    formData.append('payment_type', $('#payment_type').val());
    formData.append('transfer_loss', $('#transfer_loss').val() || 0);
    formData.append('other_loss', $('#other_loss').val() || 0);
    formData.append('usd_to_iqd_rate', $('#usd_to_iqd_rate').val() || 0);
    formData.append('paid_amount_usd', $('#paid_amount_usd').val() || 0);
    formData.append('paid_amount_iqd', $('#paid_amount_iqd').val() || 0);
    // Extract numeric values from formatted remaining amounts
    const remainingUsdFormatted = $('#remaining_amount_usd').val() || '';
    const remainingIqdFormatted = $('#remaining_amount_iqd').val() || '';
    
    // Remove formatting and extract numeric value
    const remainingUsdNumeric = remainingUsdFormatted.replace(/[$,]/g, '').replace(/\s*\$\s*/, '') || 0;
    const remainingIqdNumeric = remainingIqdFormatted.replace(/[$,]/g, '').replace(/\s*د\.ع\s*/, '') || 0;
    
    formData.append('remaining_amount_usd', remainingUsdNumeric);
    formData.append('remaining_amount_iqd', remainingIqdNumeric);
    formData.append('notes', $('#notes').val() || '');
    
    // Collect materials data
    const materials = [];
    $('.material-row').each(function() {
        const materialId = $(this).find('.material-select').val();
        const unitType = $(this).find('.unit-type-select').val();
        const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
        const priceUsd = parseFloat($(this).find('.price-usd-input').val()) || 0;
        const priceIqd = parseFloat($(this).find('.price-iqd-input').val()) || 0;
        
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

function submitForm(formData) {
    const submitBtn = $('#addPurchaseForm button[type="submit"]');
    const originalText = submitBtn.text();
    
    submitBtn.prop('disabled', true).text('چاوەڕوان بە...');
    
    $.ajax({
        url: '../process/purchase_materilas/add_purchase.php',
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
                        text: result.message || 'کڕینەکە بە سەرکەوتووی زیاد کراوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        $('#addPurchaseModal').modal('hide');
                        resetForm();
                        // Refresh the purchase list without page reload
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
                        }
                        // Refresh summary cards without page reload
                        if (typeof loadSummaryCards === 'function') {
                            loadSummaryCards();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: result.error || 'زیادکردن سەرکەوتوو نەبوو',
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
                        text: 'کڕینەکە بە سەرکەوتووی زیاد کراوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        $('#addPurchaseModal').modal('hide');
                        resetForm();
                        // Refresh the purchase list without page reload
                        if (typeof loadPurchaseMaterialsTable === 'function') {
                            loadPurchaseMaterialsTable();
                        }
                        // Refresh summary cards without page reload
                        if (typeof loadSummaryCards === 'function') {
                            loadSummaryCards();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'زیادکردن سەرکەوتوو نەبوو! Response: ' + response,
                        confirmButtonText: 'باشە'
                    });
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

function resetForm() {
    $('#addPurchaseForm')[0].reset();
    $('#materialsTableBody').empty();
    $('#total_usd').val('0.00');
    $('#total_iqd').val('0.00');
    $('#purchase_date').val(new Date().toISOString().split('T')[0]);
    loadNextReceiptNumber();
    addMaterialRow();
}

function loadMaterials() {
    return $.ajax({
        url: '../process/purchase_materilas/get_materials.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.materials = response.data;
                populatePersonDropdown();
            } else {
                console.error('Failed to load materials:', response.error);
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
                populatePersonDropdown();
            } else {
                console.error('Failed to load persons:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading persons:', error);
        }
    });
}

function populatePersonDropdown() {
    const persons = window.persons || [];
    const $personSelect = $('#person_id');
    
    $personSelect.empty();
    $personSelect.append('<option value="">هەڵبژێرە</option>');
    
    persons.forEach(function(person) {
        $personSelect.append(`<option value="${person.id}">${person.name}</option>`);
    });
}

function refreshMaterialDropdowns() {
    const materials = window.materials || [];
    
    $('.material-select').each(function() {
        const $select = $(this);
        const currentValue = $select.val();
        
        $select.empty();
        $select.append('<option value="">هەڵبژێرە</option>');
        
        materials.forEach(function(material) {
            const selected = material.id == currentValue ? 'selected' : '';
            $select.append(`<option value="${material.id}" ${selected}>${material.name} (${material.unit_type || 'دانە'})</option>`);
        });
    });
}

function loadNextReceiptNumber() {
    $.ajax({
        url: '../process/purchase_materilas/get_next_receipt_number.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#receipt_number').val(response.next_number);
            } else {
                console.error('Failed to load next receipt number:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading next receipt number:', error);
        }
    });
}

function loadUsdRate() {
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
                $('#usd_to_iqd_rate').val(rate);
                // Trigger calculations after loading rate
                calculateGrandTotal();
                calculateRemainingAmounts();
                console.log('USD rate loaded successfully:', rate);
            } else {
                console.error('Failed to load USD rate from API:', response);
                // Fallback to local rate
                loadLocalUsdRate();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading USD rate from API:', error);
            // Fallback to local rate
            loadLocalUsdRate();
        }
    });
}

function loadLocalUsdRate() {
    $.ajax({
        url: '../process/purchase_materilas/get_usd_rate.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#usd_to_iqd_rate').val(response.rate);
            } else {
                console.error('Failed to load local USD rate:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading local USD rate:', error);
        }
    });
}

function updateAdditionalCostsFields() {
    const currencyType = $('#currency_type').val();
    const usdToIqdRate = parseFloat($('#usd_to_iqd_rate').val()) || 0;
    
    // Get current values (always stored in IQD)
    const transferLossIqd = parseFloat($('#transfer_loss').attr('data-iqd-value')) || parseFloat($('#transfer_loss').val()) || 0;
    const otherLossIqd = parseFloat($('#other_loss').attr('data-iqd-value')) || parseFloat($('#other_loss').val()) || 0;
    
    if (currencyType === 'دۆلار') {
        // Convert to USD for display
        const transferLossUsd = usdToIqdRate > 0 ? transferLossIqd / (usdToIqdRate / 100) : 0;
        const otherLossUsd = usdToIqdRate > 0 ? otherLossIqd / (usdToIqdRate / 100) : 0;
        
        $('#transfer_loss').val(transferLossUsd.toFixed(2));
        $('#other_loss').val(otherLossUsd.toFixed(2));
        
        // Store original IQD values
        $('#transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#other_loss').attr('data-iqd-value', otherLossIqd);
    } else if (currencyType === 'دینار') {
        // Show in IQD
        $('#transfer_loss').val(transferLossIqd.toFixed(2));
        $('#other_loss').val(otherLossIqd.toFixed(2));
        
        // Store original IQD values
        $('#transfer_loss').attr('data-iqd-value', transferLossIqd);
        $('#other_loss').attr('data-iqd-value', otherLossIqd);
    }
}

// Initial calculations when page loads
$(document).ready(function() {
    // Initial calculation
    calculateGrandTotal();
    calculateRemainingAmounts();
    
    // Load next receipt number
    loadNextReceiptNumber();
    
    // Load USD rate
    loadUsdRate();
});
