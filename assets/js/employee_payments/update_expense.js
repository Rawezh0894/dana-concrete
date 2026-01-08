$(function () {
    // Handle update button click
    $(document).on('click', '.update-expense', function () {
        const expenseId = $(this).data('id');
        
        // Load expense data
        $.get('../process/employee_payments/get_expense.php', { id: expenseId }, function (response) {
            if (!response.success) {
                swalAlert('هەڵە', response.message || 'هەڵە لە وەرگرتنی زانیاری', 'error');
                return;
            }
            
            const expense = response.data;
            
            // Fill form fields
            $('#update_expense_id').val(expense.id);
            $('#update_employee_id').val(expense.employee_id);
            $('#update_expense_type').val(expense.expense_type);
            $('#update_amount').val(expense.amount);
            $('#update_expense_date').val(expense.expense_date);
            $('#update_notes').val(expense.notes || '');
            
            // Show modal
            $('#updateExpenseModal').modal('show');
        }, 'json').fail(function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە وەرگرتنی زانیاری', 'error');
        });
    });
    
    // Handle update form submission
    $('#updateExpenseForm').on('submit', function (e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.post('../process/employee_payments/update_expense.php', formData, function (response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'خەرجی کارمەند بەسەرکەوتوویی نوێکرایەوە!', 'success');
                $('#updateExpenseModal').modal('hide');
                
                // Reload all data
                if (window.loadExpenses) window.loadExpenses();
                if (window.employeeExpensesSummary && window.employeeExpensesSummary.loadSummaryData) {
                    window.employeeExpensesSummary.loadSummaryData();
                }
                // Reload balances after a short delay to ensure trigger has updated
                setTimeout(function() {
                    if (window.loadBalances) window.loadBalances();
                }, 500);
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
