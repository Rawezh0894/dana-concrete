$(function () {
    function formatMoney(val) {
        return Number(val).toLocaleString('en-US') + ' د.ع';
    }
    
    function loadExpenses() {
        const columns = ['#', 'employee_name', 'expense_type_kurdish', 'amount', 'expense_date', 'notes', 'created_at', 'actions'];
        TableController.showLoading('#employeeExpensesTable', columns);

        // Get filter values
        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();

        // Build query parameters
        const params = new URLSearchParams();
        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);

        const url = '../process/employee_payments/select_expenses.php' + (params.toString() ? '?' + params.toString() : '');

        $.get(url, function (res) {
            if (!res || !Array.isArray(res)) {
                TableController.render('#employeeExpensesTable', [], columns);
                return;
            }
            
            res.forEach((row, index) => {
                row['#'] = index + 1;
                row.amount = formatMoney(row.amount);
                row.actions = `
                    <button class="btn btn-sm btn-danger delete-expense" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                `;
            });
            
            TableController.renderWithPagination('#employeeExpensesTable', res, columns);
        }, 'json').fail(function(xhr) {
            console.error('Error loading expenses:', xhr.responseText);
            TableController.render('#employeeExpensesTable', [], columns);
        });
    }
    
    loadExpenses();
    window.loadExpenses = loadExpenses;
    
    // Reload when filters change
    $('#month-filter, #employee-filter').on('change', function() {
        loadExpenses();
    });
    
    // Make loadExpenses available globally
    window.loadExpenses = loadExpenses;
});

