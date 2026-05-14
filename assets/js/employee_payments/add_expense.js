$(function () {
    // Deduction modal totals + loan hints are driven from pages/employee_expenses.php (refreshDeductionModalTotals).

    // Handle old forms (for backward compatibility)
    $('#addPaymentForm, #addExpenseForm').on('submit', function (e) {
        e.preventDefault();

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
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#expense_date').val(month);
                $('#total_add').val('0 د.ع');

                if (window.loadPayments) window.loadPayments();
                if (window.loadExpenses) window.loadExpenses();
                if (window.employeePaymentsSummary && window.employeePaymentsSummary.loadSummaryData) {
                    window.employeePaymentsSummary.loadSummaryData();
                }
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addPaymentModal, #addExpenseModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            var msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        msg = errorResponse.message;
                    }
                } catch (err) {
                    msg += '\n' + xhr.responseText;
                }
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });

    $('#addIncomeExpenseForm').on('submit', function (e) {
        e.preventDefault();

        var salary = parseFloat($('#income_salary').val()) || 0;
        var bonus = parseFloat($('#income_bonus').val()) || 0;
        var overtime = parseFloat($('#income_overtime').val()) || 0;
        var total = salary + bonus + overtime;

        if (total <= 0) {
            swalAlert('هەڵە', 'تکایە لانیکەم یەک جۆری خەرجی بنووسە (مووچە، بەخشیش، یان کاروانحیسابی)', 'error');
            return;
        }

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
            amount_usd: parseFloat($('#income_amount_usd').val()) || 0,
            amount_iqd: parseFloat($('#income_amount_iqd').val()) || 0,
            exchange_rate: parseFloat($('#income_exchange_rate').val()) || 0,
            deduct_loan_usd: parseFloat($('#income_deduct_loan_usd').val()) || 0,
            deduct_loan_iqd: parseFloat($('#income_deduct_loan_iqd').val()) || 0
        };

        var dUsd = formData.deduct_loan_usd;
        var dIqd = formData.deduct_loan_iqd;
        var maxU = parseFloat($('#income_deduct_loan_usd').data('maxOutstanding'));
        var maxI = parseFloat($('#income_deduct_loan_iqd').data('maxOutstanding'));
        if (!isNaN(maxU) && dUsd > maxU + 0.001) {
            swalAlert('هەڵە', 'کەمکردنەوەی دۆلار زیاترە لە قەرزی ماوە', 'error');
            return;
        }
        if (!isNaN(maxI) && dIqd > maxI + 0.001) {
            swalAlert('هەڵە', 'کەمکردنەوەی دینار زیاترە لە قەرزی ماوە', 'error');
            return;
        }

        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addIncomeExpenseForm')[0].reset();
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#income_expense_date').val(month);
                $('#income_total_add').val('0 د.ع');
                $('#income_cash_equiv_display').text('0');
                $('#income_deduct_loan_usd').val(0);
                $('#income_deduct_loan_iqd').val(0);
                if (typeof calcIncomeTotal === 'function') {
                    calcIncomeTotal();
                }

                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addIncomeExpenseModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            var msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        msg = errorResponse.message;
                    }
                } catch (err) {
                    msg += '\n' + xhr.responseText;
                }
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });

    function deductionNetCashIqd() {
        var usd = parseFloat($('#deduction_amount_usd').val()) || 0;
        var iqd = parseFloat($('#deduction_amount_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;
        return Math.round((iqd + usd * rate) * 100) / 100;
    }

    function deductionLoanDeductionEquiv() {
        var u = parseFloat($('#deduction_deduct_loan_usd').val()) || 0;
        var iq = parseFloat($('#deduction_deduct_loan_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;
        if (u > 0 && rate <= 0) {
            return null;
        }
        return Math.round((iq + u * rate) * 100) / 100;
    }

    $('#addDeductionExpenseForm').on('submit', function (e) {
        e.preventDefault();

        var expenseType = $('#deduction_expense_type').val();
        var netCash = deductionNetCashIqd();
        var usd = parseFloat($('#deduction_amount_usd').val()) || 0;
        var iqd = parseFloat($('#deduction_amount_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;
        var loanEquiv = deductionLoanDeductionEquiv();

        if (!expenseType) {
            swalAlert('هەڵە', 'تکایە جۆری خەرجی هەلبژێرە', 'error');
            return;
        }

        if (loanEquiv === null) {
            swalAlert('هەڵە', 'کاتێک کەمکردنەوەی قەرز بە دۆلار هەیە، نرخی گۆڕینەوە پێویستە.', 'error');
            return;
        }

        var grossLedger = Math.round((netCash + loanEquiv) * 100) / 100;

        if (grossLedger <= 0) {
            swalAlert('هەڵە', 'کۆی خەرجی گشتی بە دینار دەبێت گەورەتر بێت لە سفر.', 'error');
            return;
        }

        if (usd > 0 && rate <= 0) {
            swalAlert('هەڵە', 'کاتێک بڕی دۆلار هەیە، نرخی گۆڕینەوە پێویستە.', 'error');
            return;
        }

        var dUsd = parseFloat($('#deduction_deduct_loan_usd').val()) || 0;
        var dIqd = parseFloat($('#deduction_deduct_loan_iqd').val()) || 0;
        var maxU = parseFloat($('#deduction_deduct_loan_usd').data('maxOutstanding'));
        var maxI = parseFloat($('#deduction_deduct_loan_iqd').data('maxOutstanding'));
        if (!isNaN(maxU) && dUsd > maxU + 0.001) {
            swalAlert('هەڵە', 'کەمکردنەوەی دۆلار زیاترە لە قەرزی ماوە', 'error');
            return;
        }
        if (!isNaN(maxI) && dIqd > maxI + 0.001) {
            swalAlert('هەڵە', 'کەمکردنەوەی دینار زیاترە لە قەرزی ماوە', 'error');
            return;
        }

        var formData = {
            employee_id: $('#deduction_employee_id').val(),
            expense_date: $('#deduction_expense_date').val(),
            notes: $('#deduction_notes').val(),
            salary: 0,
            bonus: 0,
            overtime: 0,
            advance: expenseType === 'advance' ? grossLedger : 0,
            deduction: expenseType === 'deduction' ? grossLedger : 0,
            penalty: expenseType === 'penalty' ? grossLedger : 0,
            overtime_payment: expenseType === 'overtime_payment' ? grossLedger : 0,
            amount_usd: usd,
            amount_iqd: iqd,
            exchange_rate: rate,
            deduct_loan_usd: dUsd,
            deduct_loan_iqd: dIqd
        };

        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addDeductionExpenseForm')[0].reset();
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#deduction_expense_date').val(month);
                $('#deduction_deduct_loan_usd').val(0);
                $('#deduction_deduct_loan_iqd').val(0);
                if (typeof refreshDeductionModalTotals === 'function') {
                    refreshDeductionModalTotals();
                }

                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
                $('#addDeductionExpenseModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            var msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        msg = errorResponse.message;
                    }
                } catch (err) {
                    msg += '\n' + xhr.responseText;
                }
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });
});
