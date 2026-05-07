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

// Excel Export Function (Switched to CSV for better compatibility)
function exportPurchaseToExcel(format = 'csv') {
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
    formData.append('export_format', 'csv');

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
            a.download = `کڕینەکان_${new Date().toISOString().split('T')[0]}.csv`;
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

// Monthly Report Export Function (Switched to CSV)
function exportPurchaseMonthlyReport(format = 'csv') {
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
    formData.append('export_type', 'monthly_report');
    formData.append('export_format', 'csv');

    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'خەملێنراوە بۆ ئیکسپۆرتی ڕاپۆرتی مانگانە',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Make AJAX request to export monthly report
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
            a.download = `ڕاپۆرتی_مانگانەی_کڕینەکان_و_شۆفێرەکان_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: 'ڕاپۆرتی مانگانە بە سەرکەوتوویی ئیکسپۆرت کرا',
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Monthly report export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک لە ئیکسپۆرتی ڕاپۆرتی مانگانە هەیە. تکایە دواتر هەوڵ بدەوە'
            });
        });
}

// CSV Export Functions
function exportPurchaseToCSV() {
    exportPurchaseToExcel('csv');
}

function exportPurchaseMonthlyReportToCSV() {
    exportPurchaseMonthlyReport('csv');
}

// Summary Export Function (Switched to CSV)
function exportPurchaseSummaryToExcel() {
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
    formData.append('export_type', 'summary');
    formData.append('export_format', 'csv');

    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'خەملێنراوە بۆ ئیکسپۆرتی کورتە',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Make AJAX request to export summary
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
            a.download = `کورتەی_کڕینەکان_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: 'کورتە بە سەرکەوتوویی ئیکسپۆرت کرا',
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Summary export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک لە ئیکسپۆرتی کورتە هەیە. تکایە دواتر هەوڵ بدەوە'
            });
        });
}
