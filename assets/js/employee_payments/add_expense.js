$(function () {
    // Handle old forms (for backward compatibility)
    $('#addPaymentForm, #addExpenseForm').on('submit', function (e) {
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
                $('#addPaymentForm, #addExpenseForm')[0].reset();
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
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                // Reload balances after a short delay to ensure trigger has updated
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addPaymentModal, #addExpenseModal').modal('hide');
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

    // Handle Income Expense Form (مووچە/بەخشیش/کاروانحیسابی)
    $('#addIncomeExpenseForm').on('submit', function (e) {
        e.preventDefault();

        // Check if at least one income field has a value
        var salary = parseFloat($('#income_salary').val()) || 0;
        var bonus = parseFloat($('#income_bonus').val()) || 0;
        var overtime = parseFloat($('#income_overtime').val()) || 0;

        var total = salary + bonus + overtime;

        if (total <= 0) {
            swalAlert('هەڵە', 'تکایە لانیکەم یەک جۆری خەرجی بنووسە (مووچە، بەخشیش، یان کاروانحیسابی)', 'error');
            return;
        }

        // Build form data
        var formData = {
            employee_id: $('#income_employee_id').val(),
            expense_date: $('#income_expense_date').val(),
            notes: $('#income_notes').val(),
            salary: salary,
            bonus: bonus,
            overtime: overtime,
            advance: 0,
            deduction: 0,
            penalty: 0,
            payment_amount_usd: parseFloat($('#income_payment_amount_usd').val()) || 0,
            payment_amount_iqd: parseFloat($('#income_payment_amount_iqd').val()) || 0,
            exchange_rate: parseFloat($('#income_exchange_rate').val()) || 0
        };

        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addIncomeExpenseForm')[0].reset();
                // Reset to current month
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#income_expense_date').val(month);
                $('#income_total_add').val('0 د.ع');

                // Reload all data
                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                // Reload balances after a short delay to ensure trigger has updated
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addIncomeExpenseModal').modal('hide');
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

    // Handle Deduction Expense Form (پێشەکی/کەمکردنەوە/سزا)
    $('#addDeductionExpenseForm').on('submit', function (e) {
        e.preventDefault();

        var expenseType = $('#deduction_expense_type').val();
        var amount = parseFloat($('#deduction_amount').val()) || 0;

        if (!expenseType) {
            swalAlert('هەڵە', 'تکایە جۆری خەرجی هەلبژێرە', 'error');
            return;
        }

        if (amount <= 0) {
            swalAlert('هەڵە', 'تکایە بڕی خەرجی بنووسە', 'error');
            return;
        }

        // Build form data based on selected type
        var formData = {
            employee_id: $('#deduction_employee_id').val(),
            expense_date: $('#deduction_expense_date').val(),
            notes: $('#deduction_notes').val(),
            salary: 0,
            bonus: 0,
            overtime: 0,
            advance: expenseType === 'advance' ? amount : 0,
            deduction: expenseType === 'deduction' ? amount : 0,
            penalty: expenseType === 'penalty' ? amount : 0,
            overtime_payment: expenseType === 'overtime_payment' ? amount : 0,
            payment_amount_usd: parseFloat($('#deduction_payment_amount_usd').val()) || 0,
            payment_amount_iqd: parseFloat($('#deduction_payment_amount_iqd').val()) || 0,
            exchange_rate: parseFloat($('#deduction_exchange_rate').val()) || 0
        };

        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addDeductionExpenseForm')[0].reset();
                // Reset to current month
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#deduction_expense_date').val(month);

                // Reload all data
                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                // Reload balances after a short delay to ensure trigger has updated
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addDeductionExpenseModal').modal('hide');
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

    function refreshIncomePaymentEquiv() {
        var usd = parseFloat($('#income_payment_amount_usd').val()) || 0;
        var iqd = parseFloat($('#income_payment_amount_iqd').val()) || 0;
        var rate = parseFloat($('#income_exchange_rate').val()) || 0;
        if (usd > 0 && rate <= 0) {
            $('#income_payment_equiv_wrap').show();
            $('#income_payment_equiv').text('— (نرخ پێویستە)');
            return;
        }
        var eq = iqd + usd * rate;
        if (usd > 0 || iqd > 0) {
            $('#income_payment_equiv_wrap').show();
            $('#income_payment_equiv').text(Math.round(eq).toLocaleString('en-US'));
        } else {
            $('#income_payment_equiv_wrap').hide();
        }
    }

    function refreshDeductionPaymentEquiv() {
        var usd = parseFloat($('#deduction_payment_amount_usd').val()) || 0;
        var iqd = parseFloat($('#deduction_payment_amount_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;
        var eq = iqd + usd * rate;
        if (usd > 0 || iqd > 0) {
            $('#deduction_payment_equiv_wrap').show();
            $('#deduction_payment_equiv').text(Math.round(eq).toLocaleString('en-US'));
        } else {
            $('#deduction_payment_equiv_wrap').hide();
        }
    }

    $('#income_payment_amount_usd, #income_payment_amount_iqd, #income_exchange_rate').on('input change', refreshIncomePaymentEquiv);
    $('#deduction_payment_amount_usd, #deduction_payment_amount_iqd, #deduction_exchange_rate').on('input change', refreshDeductionPaymentEquiv);
});

