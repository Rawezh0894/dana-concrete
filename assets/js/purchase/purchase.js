// Dynamic bin select based on material
$(document).on('change', '#material_id', function () {
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
    
    var materialIqdGroupSelector = iqdGroupSelector.replace('materialCost', 'materialCost');
    var materialUsdGroupSelector = usdGroupSelector.replace('materialCost', 'materialCost');
    var freightIqdGroupSelector = iqdGroupSelector.replace('materialCost', 'freightCost');
    var freightUsdGroupSelector = usdGroupSelector.replace('materialCost', 'freightCost');

    if (type === 'دینار') {
        $(iqdGroupSelector).show();
        $(usdGroupSelector).hide();
        $(freightIqdGroupSelector).show();
        $(freightUsdGroupSelector).hide();
    } else if (type === 'دۆلار') {
        $(iqdGroupSelector).hide();
        $(usdGroupSelector).show();
        $(freightIqdGroupSelector).hide();
        $(freightUsdGroupSelector).show();
    } else {
        $(iqdGroupSelector).hide();
        $(usdGroupSelector).hide();
        $(freightIqdGroupSelector).hide();
        $(freightUsdGroupSelector).hide();
    }
}

function updateAmountsFor(prefix) {
    const kg = parseFloat($('#' + prefix + 'kg').val()) || 0;
    const type = $('#' + prefix + 'type').val();
    
    let materialTotal = 0;
    let freightTotal = 0;
    
    if (type === 'دینار') {
        materialTotal = parseFloat($('#' + prefix + 'material_cost_iqd').val()) || 0;
        freightTotal = parseFloat($('#' + prefix + 'freight_cost_iqd').val()) || 0;
    } else if (type === 'دۆلار') {
        materialTotal = parseFloat($('#' + prefix + 'material_cost_usd').val()) || 0;
        freightTotal = parseFloat($('#' + prefix + 'freight_cost_usd').val()) || 0;
    }
    
    const exchange_rate = parseFloat($('#' + prefix + 'exchange_rate').val()) || 1;
    
    // Calculate and set hidden price per kg values for backward compatibility
    if (kg > 0) {
        if (type === 'دینار') {
            $('#' + prefix + 'price_per_kg_iqd').val((materialTotal / (kg / 1000)).toFixed(2));
            $('#' + prefix + 'freight_price_per_kg_iqd').val((freightTotal / (kg / 1000)).toFixed(2));
            $('#' + prefix + 'price_per_kg_usd').val(0);
            $('#' + prefix + 'freight_price_per_kg_usd').val(0);
        } else if (type === 'دۆلار') {
            $('#' + prefix + 'price_per_kg_usd').val((materialTotal / (kg / 1000)).toFixed(2));
            $('#' + prefix + 'freight_price_per_kg_usd').val((freightTotal / (kg / 1000)).toFixed(2));
            $('#' + prefix + 'price_per_kg_iqd').val(0);
            $('#' + prefix + 'freight_price_per_kg_iqd').val(0);
        }
    } else {
        $('#' + prefix + 'price_per_kg_iqd').val(0);
        $('#' + prefix + 'price_per_kg_usd').val(0);
        $('#' + prefix + 'freight_price_per_kg_iqd').val(0);
        $('#' + prefix + 'freight_price_per_kg_usd').val(0);
    }
    
    const totalAmount = materialTotal + freightTotal;
    
    // Update total hidden and readonly cost fields
    if (type === 'دینار') {
        $('#' + prefix + 'total_freight_cost_iqd').val(freightTotal.toFixed(0));
        $('#' + prefix + 'total_freight_cost_usd').val(0);
        $('#' + prefix + 'amount_iqd').val(totalAmount.toFixed(0));
        $('#' + prefix + 'price').val(0);
    } else if (type === 'دۆلار') {
        $('#' + prefix + 'total_freight_cost_usd').val(freightTotal.toFixed(2));
        $('#' + prefix + 'total_freight_cost_iqd').val(0);
        $('#' + prefix + 'price').val(totalAmount.toFixed(2));
        $('#' + prefix + 'amount_iqd').val(0);
    } else {
        $('#' + prefix + 'total_freight_cost_usd').val(0);
        $('#' + prefix + 'total_freight_cost_iqd').val(0);
        $('#' + prefix + 'price').val(0);
        $('#' + prefix + 'amount_iqd').val(0);
    }
    
    // Handle total paid amounts mapping to legacy hidden fields
    const paid_to_location_usd = parseFloat($('#' + prefix + 'paid_to_location_usd').val()) || 0;
    const paid_to_driver_usd = parseFloat($('#' + prefix + 'paid_to_driver_usd').val()) || 0;
    const total_paid_usd = paid_to_location_usd + paid_to_driver_usd;
    $('#' + prefix + 'paid_usd').val(total_paid_usd.toFixed(2));
    
    const paid_to_location_iqd = parseFloat($('#' + prefix + 'paid_to_location_iqd').val()) || 0;
    const paid_to_driver_iqd = parseFloat($('#' + prefix + 'paid_to_driver_iqd').val()) || 0;
    const total_paid_iqd = paid_to_location_iqd + paid_to_driver_iqd;
    $('#' + prefix + 'paid_iqd').val(total_paid_iqd.toFixed(2));
    
    // Remaining calculation
    if (type === 'دینار') {
        const paid_usd_to_iqd = total_paid_usd * exchange_rate / 100;
        let remaining_iqd = totalAmount - (total_paid_iqd + paid_usd_to_iqd);
        if (remaining_iqd < 0) remaining_iqd = 0;
        $('#' + prefix + 'remaining_iqd').val(remaining_iqd.toFixed(0));
        $('#' + prefix + 'remaining_usd').val(0);
    } else if (type === 'دۆلار') {
        const paid_iqd_to_usd = total_paid_iqd * 100 / exchange_rate;
        let remaining_usd = totalAmount - (total_paid_usd + paid_iqd_to_usd);
        if (remaining_usd < 0) remaining_usd = 0;
        $('#' + prefix + 'remaining_usd').val(remaining_usd.toFixed(2));
        $('#' + prefix + 'remaining_iqd').val(0);
    } else {
        $('#' + prefix + 'remaining_usd').val(0);
        $('#' + prefix + 'remaining_iqd').val(0);
    }
}

$(document).ready(function () {
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
    const materialCostIqd = document.getElementById('material_cost_iqd');
    if (materialCostIqd) materialCostIqd.value = 0;
    const materialCostUsd = document.getElementById('material_cost_usd');
    if (materialCostUsd) materialCostUsd.value = 0;
    updateAmountsFor('purchase');
});

function buildPurchaseExportFormData(extraFields) {
    const formData = new FormData();
    formData.append('company_id', $('#filter_company').val() || '');
    formData.append('location_id', $('#filter_location').val() || '');
    formData.append('driver_id', $('#filter_driver').val() || '');
    formData.append('material_id', $('#filter_material').val() || '');
    formData.append('from_date', $('#filter_from').val() || '');
    formData.append('to_date', $('#filter_to').val() || '');
    if (extraFields) {
        Object.keys(extraFields).forEach(function (key) {
            formData.append(key, extraFields[key]);
        });
    }
    return formData;
}

function runPurchaseExport(formData, downloadFilename, loadingText, successText) {
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: loadingText,
        allowOutsideClick: false,
        didOpen: function () {
            Swal.showLoading();
        }
    });

    fetch('../process/purchase/export_excel.php', {
        method: 'POST',
        body: formData
    })
        .then(function (response) {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Network response was not ok');
        })
        .then(function (blob) {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = downloadFilename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: successText,
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(function (error) {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک لە ئیکسپۆرتکردن هەیە. تکایە دواتر هەوڵ بدەوە'
            });
        });
}

function exportPurchaseToExcel() {
    const dateStr = new Date().toISOString().split('T')[0];
    runPurchaseExport(
        buildPurchaseExportFormData({ export_format: 'excel' }),
        'کڕینەکان_' + dateStr + '.xls',
        'خەملێنراوە بۆ ئیکسپۆرتی Excel',
        'فایلی Excel (.xls) بە سەرکەوتوویی داگیرا'
    );
}

function exportPurchaseToCSV() {
    const dateStr = new Date().toISOString().split('T')[0];
    runPurchaseExport(
        buildPurchaseExportFormData({ export_format: 'csv' }),
        'کڕینەکان_' + dateStr + '.csv',
        'خەملێنراوە بۆ ئیکسپۆرتی CSV',
        'فایلی CSV بە سەرکەوتوویی داگیرا'
    );
}

function exportPurchaseMonthlyReport() {
    const dateStr = new Date().toISOString().split('T')[0];
    runPurchaseExport(
        buildPurchaseExportFormData({ export_type: 'monthly_report', export_format: 'excel' }),
        'ڕاپۆرتی_مانگانەی_کڕینەکان_' + dateStr + '.xls',
        'خەملێنراوە بۆ ئیکسپۆرتی ڕاپۆرتی مانگانە',
        'ڕاپۆرتی مانگانە بە سەرکەوتوویی ئیکسپۆرت کرا'
    );
}

function exportPurchaseMonthlyReportToCSV() {
    const dateStr = new Date().toISOString().split('T')[0];
    runPurchaseExport(
        buildPurchaseExportFormData({ export_type: 'monthly_report', export_format: 'csv' }),
        'ڕاپۆرتی_مانگانەی_کڕینەکان_' + dateStr + '.csv',
        'خەملێنراوە بۆ ئیکسپۆرتی CSV',
        'ڕاپۆرتی مانگانە بە CSV داگیرا'
    );
}

function exportPurchaseSummaryToExcel() {
    const dateStr = new Date().toISOString().split('T')[0];
    runPurchaseExport(
        buildPurchaseExportFormData({ export_type: 'summary', export_format: 'excel' }),
        'کورتەی_کڕینەکان_' + dateStr + '.xls',
        'خەملێنراوە بۆ ئیکسپۆرتی کورتە',
        'کورتە بە سەرکەوتوویی ئیکسپۆرت کرا'
    );
}
