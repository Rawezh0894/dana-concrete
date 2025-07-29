$(function() {
    function formatMoney(val) {
        return Number(val).toLocaleString('en-US') + ' د.ع';
    }
    function loadPayments() {
        const columns = ['#', 'employee_name', 'salary', 'karwanhisabi', 'bonus', 'total', 'pay_month', 'created_at', 'actions'];
        TableController.showLoading('#employeePaymentsTable', columns);
        
        // Get filter values
        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();
        
        // Build query parameters
        const params = new URLSearchParams();
        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);
        
        const url = '../process/employee_payments/select.php' + (params.toString() ? '?' + params.toString() : '');
        
        $.get(url, function(res) {
            if (!res || !Array.isArray(res)) {
                TableController.render('#employeePaymentsTable', [], columns);
                return;
            }
            res.forEach(row => {
                row.salary = formatMoney(row.salary);
                row.karwanhisabi = formatMoney(row.karwanhisabi);
                row.bonus = formatMoney(row.bonus);
                row.total = formatMoney(row.total);
                row.actions = `
                    <button class="btn btn-sm btn-primary edit-payment" data-id="${row.id}" data-employee-id="${row.employee_id}"><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-payment" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                `;
            });
            TableController.renderWithPagination('#employeePaymentsTable', res, columns);
        }, 'json');
    }
    loadPayments();
    window.loadEmployeePayments = loadPayments;
});
