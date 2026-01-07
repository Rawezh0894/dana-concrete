$(function () {
    $('#addPaymentForm').on('submit', function (e) {
        e.preventDefault();
        
        // Check if at least one expense field has a value
        var salary = parseFloat($('#salary').val()) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var overtime = parseFloat($('#overtime').val()) || 0;
        var advance = parseFloat($('#advance').val()) || 0;
        var deduction = parseFloat($('#deduction').val()) || 0;
        var penalty = parseFloat($('#penalty').val()) || 0;
        
        var total = salary + bonus + overtime + advance + deduction + penalty;
        
        if (total <= 0) {
            swalAlert('هەڵە', 'تکایە لانیکەم یەک جۆری خەرجی بنووسە', 'error');
            return;
        }
        
        var formData = $(this).serialize();
        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addPaymentForm')[0].reset();
                // Reset to current month
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#expense_date').val(month);
                $('#total_add').val('0 د.ع');
                
                // Reload all data
                if (window.loadPayments) window.loadPayments();
                if (window.loadExpenses) window.loadExpenses();
                if (window.employeePaymentsSummary && window.employeePaymentsSummary.loadSummaryData) {
                    window.employeePaymentsSummary.loadSummaryData();
                }
                // Reload balances after a short delay to ensure trigger has updated
                setTimeout(function() {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addPaymentModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            let msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        msg = errorResponse.message;
                    }
                } catch (e) {
                    msg += '\n' + xhr.responseText;
                }
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });
});

