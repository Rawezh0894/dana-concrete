$(function () {
    function updateLedgerFromCashFields() {
        var usd = parseFloat($('#update_amount_usd').val()) || 0;
        var iqd = parseFloat($('#update_amount_iqd').val()) || 0;
        var rate = parseFloat($('#update_exchange_rate').val()) || 0;
        var ledger = Math.round((iqd + usd * rate) * 100) / 100;
        $('#update_amount').val(ledger);
        $('#update_ledger_display').text(ledger.toLocaleString('en-US'));
    }

    $('#update_amount_usd, #update_amount_iqd, #update_exchange_rate').on('input change', updateLedgerFromCashFields);

    $(document).on('click', '.update-expense', function () {
        var expenseId = $(this).data('id');

        $.get('../process/employee_payments/get_expense.php', { id: expenseId }, function (response) {
            if (!response.success) {
                swalAlert('هەڵە', response.message || 'هەڵە لە وەرگرتنی زانیاری', 'error');
                return;
            }

            var expense = response.data;

            $('#update_expense_id').val(expense.id);
            $('#update_employee_id').val(expense.employee_id);
            $('#update_expense_type').val(expense.expense_type);
            $('#update_amount_usd').val(expense.amount_usd != null ? expense.amount_usd : 0);
            $('#update_amount_iqd').val(expense.amount_iqd != null ? expense.amount_iqd : 0);
            $('#update_exchange_rate').val(expense.exchange_rate != null ? expense.exchange_rate : 0);
            updateLedgerFromCashFields();
            $('#update_expense_date').val(expense.expense_date);
            $('#update_notes').val(expense.notes || '');

            $('#updateExpenseModal').modal('show');
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە وەرگرتنی زانیاری', 'error');
        });
    });

    $('#updateExpenseForm').on('submit', function (e) {
        e.preventDefault();
        updateLedgerFromCashFields();

        var formData = $(this).serialize();

        $.post('../process/employee_payments/update_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی نوێکرایەوە!', 'success');
                $('#updateExpenseModal').modal('hide');

                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                setTimeout(function () {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
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
