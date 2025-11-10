function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function updateCashBoxSummary(from, to) {
    $.ajax({
        url: '../process/cash_box/summary.php',
        method: 'GET',
        data: { from: from, to: to },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.data.total_usd_all !== undefined) {
                    $('#totalCashUsdAll').text('$' + Number(response.data.total_usd_all).toLocaleString());
                } else {
                    $('#totalCashUsdAll').text('$0');
                }
                
                // Update dollar rate card
                if (response.data.usd_iqd_rate !== undefined) {
                    $('#dollarRate').text(formatNumber(response.data.usd_iqd_rate) + ' د.ع');
                } else {
                    $('#dollarRate').text('0 د.ع');
                }
            } else {
                $('#totalCashUsdAll').text('$0');
                $('#dollarRate').text('0 د.ع');
            }
        },
        error: function() {
            $('#totalCashUsdAll').text('$0');
            $('#dollarRate').text('0 د.ع');
        }
    });
}

let originalCashTotal = 0;

// Enable editing on double-click
$(document).on('dblclick', '#totalCashUsdAll', function() {
    const currentText = $(this).text().replace('$', '').replace(/,/g, '');
    const currentValue = parseFloat(currentText) || 0;
    originalCashTotal = currentValue;
    
    $(this).addClass('d-none');
    $('#totalCashUsdAllInput').val(currentValue).removeClass('d-none').focus().select();
    $('#saveCashTotalBtn, #cancelCashTotalBtn').removeClass('d-none');
});

// Save edited value
$(document).on('click', '#saveCashTotalBtn', function() {
    const newValue = parseFloat($('#totalCashUsdAllInput').val()) || 0;
    
    // Save to database
    $.ajax({
        url: '../process/cash_box/save_total.php',
        method: 'POST',
        data: { total_usd_all: newValue },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#totalCashUsdAll').text('$' + Number(newValue).toLocaleString());
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو!',
                    text: 'کۆی پارە بە سەرکەوتوویی پاشەکەوت کرا',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('هەڵە!', response.error || 'هەڵەیەک ڕووی دا', 'error');
            }
        },
        error: function() {
            Swal.fire('هەڵە!', 'هەڵەیەک لە کۆنێکتکردن', 'error');
        },
        complete: function() {
            // Reset UI
            $('#totalCashUsdAllInput').addClass('d-none');
            $('#totalCashUsdAll').removeClass('d-none');
            $('#saveCashTotalBtn, #cancelCashTotalBtn').addClass('d-none');
        }
    });
});

// Cancel editing
$(document).on('click', '#cancelCashTotalBtn', function() {
    $('#totalCashUsdAllInput').addClass('d-none');
    $('#totalCashUsdAll').removeClass('d-none');
    $('#saveCashTotalBtn, #cancelCashTotalBtn').addClass('d-none');
});

// Reset to calculated value
$(document).on('click', '#resetCashTotalBtn', function() {
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'کۆی پارە بگەڕێتەوە بۆ بڕی هەژمارکراو؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سفر بکەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Reset in database
            $.ajax({
                url: '../process/cash_box/reset_total.php',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Reload summary
                        var dates = {
                            from: $('#filter_from').val(),
                            to: $('#filter_to').val()
                        };
                        updateCashBoxSummary(dates.from, dates.to);
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو!',
                            text: 'کۆی پارە بە سەرکەوتوویی سفر کرا',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('هەڵە!', response.error || 'هەڵەیەک ڕووی دا', 'error');
                    }
                },
                error: function() {
                    Swal.fire('هەڵە!', 'هەڵەیەک لە کۆنێکتکردن', 'error');
                }
            });
        }
    });
});

// Allow Enter key to save
$(document).on('keypress', '#totalCashUsdAllInput', function(e) {
    if (e.which === 13) { // Enter key
        $('#saveCashTotalBtn').click();
    } else if (e.which === 27) { // Escape key
        $('#cancelCashTotalBtn').click();
    }
});

$(document).ready(function() {
    function getFilterDates() {
        return {
            from: $('#filter_from').val(),
            to: $('#filter_to').val()
        };
    }
    // Initial summary
    var dates = getFilterDates();
    updateCashBoxSummary(dates.from, dates.to);

    // Update summary on filter change
    $('#filter_from, #filter_to').on('change', function() {
        var dates = getFilterDates();
        updateCashBoxSummary(dates.from, dates.to);
    });
    $('#clearFilterBtn').on('click', function() {
        setTimeout(function() {
            var dates = getFilterDates();
            updateCashBoxSummary(dates.from, dates.to);
        }, 100);
    });
    // Optionally, update after add/edit/delete
    window.updateCashBoxSummary = updateCashBoxSummary;
}); 