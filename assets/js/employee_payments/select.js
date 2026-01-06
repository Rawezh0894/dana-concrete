(function (global) {
    const $ = global.jQuery || global.$;
    const URLSearchParamsCtor = global.URLSearchParams;
    const TableController = global.TableController;
    const doc = global.document;

    if (!$ || !doc || !TableController || !URLSearchParamsCtor) return;

    function formatSigned(operation, amount) {
        const n = Number(amount || 0);
        const abs = Math.abs(n).toLocaleString('en-US') + ' د.ع';
        if (operation === 'credit') return `<span class="text-success fw-bold">+${abs}</span>`;
        return `<span class="text-danger fw-bold">-${abs}</span>`;
    }

    function loadPayments() {
        const columns = ['#', 'employee_name', 'type', 'operation', 'amount', 'pay_month', 'transaction_date', 'description', 'actions'];
        TableController.showLoading('#employeePaymentsTable', columns);

        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();

        const params = new URLSearchParamsCtor();
        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);

        const url = '../process/employee_payments/get_ledger.php' + (params.toString() ? '?' + params.toString() : '');

        $.get(url, function (res) {
            const rows = (res && res.success && Array.isArray(res.data)) ? res.data : [];
            if (!rows.length) {
                TableController.render('#employeePaymentsTable', [], columns);
                return;
            }
            rows.forEach(row => {
                row.amount = formatSigned(row.operation, row.amount);
                row.actions = `
                    <button class="btn btn-sm btn-primary edit-payment" data-id="${row.id}" data-employee-id="${row.employee_id}"><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-payment" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                `;
            });
            TableController.renderWithPagination('#employeePaymentsTable', rows, columns);
        }, 'json');
    }

    $(function () {
        loadPayments();
        global.loadPayments = loadPayments;
        global.loadEmployeePayments = loadPayments;
    });
})(globalThis);
