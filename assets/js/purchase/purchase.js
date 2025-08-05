// Dynamic bin select based on material
$(document).on('change', '#material_id', function() {
    const material = $('#material_id option:selected').text().trim();
    const $bin = $('#bin_id');
    $bin.val('');
    $bin.find('option').hide();
    $bin.find('option[value=""]').show(); // always show default
    if (material === 'لمی ڕەش' || material === 'لمی کەسارە') {
        $bin.find('option:contains("چاوی ١")').show();
        $bin.find('option:contains("چاوی ٢")').show();
    } else if (material === 'چەو') {
        $bin.find('option:contains("چاوی ٣")').show();
        $bin.find('option:contains("چاوی ٤")').show();
    } else if (material === 'چیمەنتۆ') {
        $bin.find('option:contains("سایلۆی ١")').show();
        $bin.find('option:contains("سایلۆی ٢")').show();
    } else if (material === 'دەرمان') {
        $bin.find('option:contains("تەنکی دەرمان ١")').show();
    } else if (material === 'گاز') {
        $bin.find('option:contains("تەکی گاز ١")').show();
    } else {
        $bin.find('option').show(); // fallback: show all
    }
    
    // Handle gas material - divide kg by 1000
    handleGasMaterialConversion();
    
    // Show/hide helper text for gas material
    toggleGasHelperText();
});

// Function to handle gas material conversion
function handleGasMaterialConversion() {
    const material = $('#material_id option:selected').text().trim();
    const $kgInput = $('#kg');
    const $editKgInput = $('#edit_kg');
    
    if (material === 'گاز') {
        // For add modal
        if ($kgInput.length && $kgInput.val()) {
            const currentValue = parseFloat($kgInput.val()) || 0;
            if (currentValue > 0) {
                const convertedValue = currentValue / 1000;
                $kgInput.val(convertedValue.toFixed(3));
                console.log(`Gas material detected: ${currentValue} kg converted to ${convertedValue} kg`);
            }
        }
        
        // For edit modal
        if ($editKgInput.length && $editKgInput.val()) {
            const currentValue = parseFloat($editKgInput.val()) || 0;
            if (currentValue > 0) {
                const convertedValue = currentValue / 1000;
                $editKgInput.val(convertedValue.toFixed(3));
                console.log(`Gas material detected (edit): ${currentValue} kg converted to ${convertedValue} kg`);
            }
        }
    }
}

// Shared logic for add and edit purchase modals
function togglePricePerKgInputsFor(typeSelector, iqdGroupSelector, usdGroupSelector) {
    var type = $(typeSelector).val();
    if (type === 'دینار') {
        $(iqdGroupSelector).show();
        $(usdGroupSelector).hide();
    } else if (type === 'دۆلار') {
        $(iqdGroupSelector).hide();
        $(usdGroupSelector).show();
    } else {
        $(iqdGroupSelector).hide();
        $(usdGroupSelector).hide();
    }
}

function updateAmountsFor(prefix) {
    const kg = parseFloat($('#' + prefix + 'kg').val()) || 0;
    const type = $('#' + prefix + 'type').val();
    let pricePerKg = 0;
    if (type === 'دینار') {
        pricePerKg = parseFloat($('#' + prefix + 'price_per_kg_iqd').val()) || 0;
    } else if (type === 'دۆلار') {
        pricePerKg = parseFloat($('#' + prefix + 'price_per_kg_usd').val()) || 0;
    }
    const price = parseFloat($('#' + prefix + 'price').val()) || 0;
    const amount_iqd = parseFloat($('#' + prefix + 'amount_iqd').val()) || 0;
    const paid_usd = parseFloat($('#' + prefix + 'paid_usd').val()) || 0;
    const paid_iqd = parseFloat($('#' + prefix + 'paid_iqd').val()) || 0;
    const exchange_rate = parseFloat($('#' + prefix + 'exchange_rate').val()) || 1;
    const amount = (kg / 1000) * pricePerKg;
    const remainingUsdFocused = document.activeElement === document.getElementById(prefix + 'remaining_usd');
    const remainingIqdFocused = document.activeElement === document.getElementById(prefix + 'remaining_iqd');
    if (type === 'دینار') {
        $('#' + prefix + 'price').prop('readonly', true).val(0);
        $('#' + prefix + 'amount_iqd').prop('readonly', false).val(amount.toFixed(2));
        $('#' + prefix + 'remaining_usd').prop('readonly', true);
        $('#' + prefix + 'remaining_iqd').prop('readonly', false);
        const paid_usd_to_iqd = paid_usd * exchange_rate / 100;
        const remaining_iqd = amount_iqd - (paid_iqd + paid_usd_to_iqd);
        if (!remainingIqdFocused) $('#' + prefix + 'remaining_iqd').val(remaining_iqd.toFixed(2));
        $('#' + prefix + 'remaining_usd').val(0);
    } else if (type === 'دۆلار') {
        $('#' + prefix + 'amount_iqd').prop('readonly', true).val(0);
        $('#' + prefix + 'price').prop('readonly', false).val(amount.toFixed(2));
        $('#' + prefix + 'remaining_iqd').prop('readonly', true);
        $('#' + prefix + 'remaining_usd').prop('readonly', false);
        const paid_iqd_to_usd = paid_iqd * 100 / exchange_rate;
        const remaining_usd = price - (paid_usd + paid_iqd_to_usd);
        if (!remainingUsdFocused) $('#' + prefix + 'remaining_usd').val(remaining_usd.toFixed(2));
        $('#' + prefix + 'remaining_iqd').val(0);
    } else {
        $('#' + prefix + 'price').prop('readonly', false).val(0);
        $('#' + prefix + 'amount_iqd').prop('readonly', false).val(0);
        $('#' + prefix + 'remaining_usd').prop('readonly', false);
        $('#' + prefix + 'remaining_iqd').prop('readonly', false);
        $('#' + prefix + 'remaining_usd').val(0);
        $('#' + prefix + 'remaining_iqd').val(0);
    }
}

$(document).ready(function() {
    // Set default date to yesterday
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const now = new Date();
        now.setDate(now.getDate() - 1);
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }
    // Set default price_per_kg_iqd and price_per_kg_usd to 0
    const priceIqd = document.getElementById('price_per_kg_iqd');
    if (priceIqd) priceIqd.value = 0;
    const priceUsd = document.getElementById('price_per_kg_usd');
    
    // Add event listeners for kg input changes to handle gas conversion
    $(document).on('input', '#kg', function() {
        const material = $('#material_id option:selected').text().trim();
        if (material === 'گاز') {
            const currentValue = parseFloat($(this).val()) || 0;
            if (currentValue > 0) {
                const convertedValue = currentValue / 1000;
                $(this).val(convertedValue.toFixed(3));
                console.log(`Gas material - kg input converted: ${currentValue} to ${convertedValue}`);
                
                // Show a small notification
                showGasConversionNotification(currentValue, convertedValue);
            }
        }
    });
    
    $(document).on('input', '#edit_kg', function() {
        const material = $('#edit_material_id option:selected').text().trim();
        if (material === 'گاز') {
            const currentValue = parseFloat($(this).val()) || 0;
            if (currentValue > 0) {
                const convertedValue = currentValue / 1000;
                $(this).val(convertedValue.toFixed(3));
                console.log(`Gas material - edit kg input converted: ${currentValue} to ${convertedValue}`);
                
                // Show a small notification
                showGasConversionNotification(currentValue, convertedValue);
            }
        }
    });
    if (priceUsd) priceUsd.value = 0;
    updateAmountsFor('purchase');
    
    // Add event listener for edit modal material change
    $(document).on('change', '#edit_material_id', function() {
        const material = $('#edit_material_id option:selected').text().trim();
        const $editBin = $('#edit_bin_id');
        $editBin.val('');
        $editBin.find('option').hide();
        $editBin.find('option[value=""]').show(); // always show default
        if (material === 'لمی ڕەش' || material === 'لمی کەسارە') {
            $editBin.find('option:contains("چاوی ١")').show();
            $editBin.find('option:contains("چاوی ٢")').show();
        } else if (material === 'چەو') {
            $editBin.find('option:contains("چاوی ٣")').show();
            $editBin.find('option:contains("چاوی ٤")').show();
        } else if (material === 'چیمەنتۆ') {
            $editBin.find('option:contains("سایلۆی ١")').show();
            $editBin.find('option:contains("سایلۆی ٢")').show();
        } else if (material === 'دەرمان') {
            $editBin.find('option:contains("تەنکی دەرمان ١")').show();
        } else if (material === 'گاز') {
            $editBin.find('option:contains("تەکی گاز ١")').show();
        } else {
            $editBin.find('option').show(); // fallback: show all
        }
        
        // Handle gas material conversion for edit modal
        handleEditGasMaterialConversion();
        
        // Show/hide helper text for gas material in edit modal
        toggleEditGasHelperText();
    });
    
    // Function to handle gas material conversion for edit modal
    function handleEditGasMaterialConversion() {
        const material = $('#edit_material_id option:selected').text().trim();
        const $editKgInput = $('#edit_kg');
        
        if (material === 'گاز') {
            if ($editKgInput.length && $editKgInput.val()) {
                const currentValue = parseFloat($editKgInput.val()) || 0;
                if (currentValue > 0) {
                    const convertedValue = currentValue / 1000;
                    $editKgInput.val(convertedValue.toFixed(3));
                    console.log(`Gas material detected (edit modal): ${currentValue} kg converted to ${convertedValue} kg`);
                    
                    // Show a small notification
                    showGasConversionNotification(currentValue, convertedValue);
                }
            }
        }
    }
    
    // Function to show gas conversion notification
    function showGasConversionNotification(originalValue, convertedValue) {
        // Remove any existing notification
        $('.gas-conversion-notification').remove();
        
        // Create notification element
        const notification = $(`
            <div class="gas-conversion-notification alert alert-info alert-dismissible fade show" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>گاز:</strong> ${originalValue} کگم دابەشی 1000 کراوە = ${convertedValue} کگم
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        // Add to body
        $('body').append(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Function to toggle gas helper text for add modal
    function toggleGasHelperText() {
        const material = $('#material_id option:selected').text().trim();
        const $helperText = $('#kg-helper-text');
        
        if (material === 'گاز') {
            $helperText.show();
        } else {
            $helperText.hide();
        }
    }
    
    // Function to toggle gas helper text for edit modal
    function toggleEditGasHelperText() {
        const material = $('#edit_material_id option:selected').text().trim();
        const $helperText = $('#edit-kg-helper-text');
        
        if (material === 'گاز') {
            $helperText.show();
        } else {
            $helperText.hide();
        }
    }
});
