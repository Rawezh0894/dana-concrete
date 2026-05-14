$(function () {
    function deductionLedgerIqd() {
        var usd = parseFloat($('#deduction_amount_usd').val()) || 0;
        var iqd = parseFloat($('#deduction_amount_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;
        return Math.round((iqd + usd * rate) * 100) / 100;
    }

    function refreshDeductionLedgerDisplay() {
        var v = deductionLedgerIqd();
        $('#deduction_ledger_total_display').text(v.toLocaleString('en-US'));
    }

    $('#deduction_amount_usd, #deduction_amount_iqd, #deduction_exchange_rate').on('input change', refreshDeductionLedgerDisplay);

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

    $('#addDeductionExpenseForm').on('submit', function (e) {
        e.preventDefault();

        var expenseType = $('#deduction_expense_type').val();
        var ledger = deductionLedgerIqd();
        var usd = parseFloat($('#deduction_amount_usd').val()) || 0;
        var iqd = parseFloat($('#deduction_amount_iqd').val()) || 0;
        var rate = parseFloat($('#deduction_exchange_rate').val()) || 0;

        if (!expenseType) {
            swalAlert('هەڵە', 'تکایە جۆری خەرجی هەلبژێرە', 'error');
            return;
        }

        if (ledger <= 0) {
            swalAlert('هەڵە', 'کۆی خەرجی بە دینار دەبێت گەورەتر بێت لە سفر (دۆلار×نرخ + دینار).', 'error');
            return;
        }

        if (usd > 0 && rate <= 0) {
            swalAlert('هەڵە', 'کاتێک بڕی دۆلار هەیە، نرخی گۆڕینەوە پێویستە.', 'error');
            return;
        }

        var formData = {
            employee_id: $('#deduction_employee_id').val(),
            expense_date: $('#deduction_expense_date').val(),
            notes: $('#deduction_notes').val(),
            salary: 0,
            bonus: 0,
            overtime: 0,
            advance: expenseType === 'advance' ? ledger : 0,
            deduction: expenseType === 'deduction' ? ledger : 0,
            penalty: expenseType === 'penalty' ? ledger : 0,
            overtime_payment: expenseType === 'overtime_payment' ? ledger : 0,
            amount_usd: usd,
            amount_iqd: iqd,
            exchange_rate: rate
        };

        $.post('../process/employee_payments/add_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addDeductionExpenseForm')[0].reset();
                var now = new Date();
                var month = (now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
                $('#deduction_expense_date').val(month);
                refreshDeductionLedgerDisplay();

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

    refreshDeductionLedgerDisplay();
});
