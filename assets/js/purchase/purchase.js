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
    const amountIqdFocused = document.activeElement === document.getElementById(prefix + 'amount_iqd');

    if (type === 'دینار') {
        $('#' + prefix + 'price').prop('readonly', true).val(0);
        // Allow manual input for amount_iqd when type is دینار
        $('#' + prefix + 'amount_iqd').prop('readonly', false);
        if (amountIqdFocused && kg > 0) {
            const calculatedPricePerTon = (amount_iqd / (kg / 1000));
            $('#' + prefix + 'price_per_kg_iqd').val(calculatedPricePerTon.toFixed(2));
        }

        if (!amountIqdFocused) {
            const currentPricePerTon = parseFloat($('#' + prefix + 'price_per_kg_iqd').val()) || 0;
            const calculatedAmount = (kg / 1000) * currentPricePerTon;
            const flooredAmount = Math.floor(calculatedAmount / 1000) * 1000;
            $('#' + prefix + 'amount_iqd').val(flooredAmount.toFixed(0));
        }

        // Also round paid_iqd if not focused to match the request "input of amount should be cut"
        const paidIqdFocused = document.activeElement === document.getElementById(prefix + 'paid_iqd');
        if (!paidIqdFocused) {
            const currentPaidIqd = parseFloat($('#' + prefix + 'paid_iqd').val()) || 0;
            const flooredPaidIqd = Math.floor(currentPaidIqd / 1000) * 1000;
            $('#' + prefix + 'paid_iqd').val(flooredPaidIqd.toFixed(0));
        }

        $('#' + prefix + 'remaining_usd').prop('readonly', true);
        $('#' + prefix + 'remaining_iqd').prop('readonly', false);
        const updated_paid_iqd = parseFloat($('#' + prefix + 'paid_iqd').val()) || 0;
        const paid_usd_to_iqd = paid_usd * exchange_rate / 100;
        const current_amount_iqd = parseFloat($('#' + prefix + 'amount_iqd').val()) || 0;
        let remaining_iqd = current_amount_iqd - (updated_paid_iqd + paid_usd_to_iqd);
        if (!remainingIqdFocused) {
            if (remaining_iqd < 0) {
                remaining_iqd = 0;
            }
            const flooredRemainingIqd = Math.floor(remaining_iqd / 1000) * 1000;
            $('#' + prefix + 'remaining_iqd').val(flooredRemainingIqd.toFixed(0));
        }
        $('#' + prefix + 'remaining_usd').val(0);
    } else if (type === 'دۆلار') {
        // Allow manual input for amount_iqd when type is دۆلار
        $('#' + prefix + 'amount_iqd').prop('readonly', false);
        $('#' + prefix + 'price').prop('readonly', false).val(amount.toFixed(2));
        $('#' + prefix + 'remaining_iqd').prop('readonly', true);
        $('#' + prefix + 'remaining_usd').prop('readonly', false);

        // Do not calculate amount_iqd from price when type is دۆلار
        // (Removing automatic calculation as requested)

        // Also round paid_iqd if not focused
        const paidIqdFocused = document.activeElement === document.getElementById(prefix + 'paid_iqd');
        if (!paidIqdFocused) {
            const p_iqd = parseFloat($('#' + prefix + 'paid_iqd').val()) || 0;
            const flooredP = Math.floor(p_iqd / 1000) * 1000;
            $('#' + prefix + 'paid_iqd').val(flooredP.toFixed(0));
        }

        const updated_paid_iqd = parseFloat($('#' + prefix + 'paid_iqd').val()) || 0;
        const paid_iqd_to_usd = updated_paid_iqd * 100 / exchange_rate;
        const current_price = parseFloat($('#' + prefix + 'price').val()) || 0;
        const current_paid_usd = parseFloat($('#' + prefix + 'paid_usd').val()) || 0;

        let remaining_usd = current_price - (current_paid_usd + paid_iqd_to_usd);
        if (remaining_usd < 0) remaining_usd = 0;

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
    const priceIqd = document.getElementById('price_per_kg_iqd');
    if (priceIqd) priceIqd.value = 0;
    const priceUsd = document.getElementById('price_per_kg_usd');
    if (priceUsd) priceUsd.value = 0;
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
