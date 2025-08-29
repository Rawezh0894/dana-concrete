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
});

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
    if (priceUsd) priceUsd.value = 0;
    updateAmountsFor('');
});

// Excel Export Function
function exportPurchaseToExcel() {
    // Get current filter values
    const companyId = $('#filter_company').val() || '';
    const locationId = $('#filter_location').val() || '';
    const driverId = $('#filter_driver').val() || '';
    const materialId = $('#filter_material').val() || '';
    const fromDate = $('#filter_from').val() || '';
    const toDate = $('#filter_to').val() || '';
    
    // Create form data
    const formData = new FormData();
    formData.append('company_id', companyId);
    formData.append('location_id', locationId);
    formData.append('driver_id', driverId);
    formData.append('material_id', materialId);
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'خەملێنراوە بۆ ئیکسپۆرتکردن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Make AJAX request to export
    fetch('../process/purchase/export_excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Network response was not ok');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `کڕینەکان_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو!',
            text: 'فایلەکە بە سەرکەوتوویی ئیکسپۆرت کرا',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        console.error('Export error:', error);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'هەڵەیەک لە ئیکسپۆرتکردن هەیە. تکایە دواتر هەوڵ بدەوە'
        });
    });
}
