$(function () {
    function formatSalary(salary) {
        return Number(salary).toLocaleString('en-US') + ' د.ع';
    }

    function updateSummaryCards(summary) {
        $('#total_employees').text(summary.total_employees.toLocaleString());
        $('#total_salary').text(formatSalary(summary.total_salary));

        // Convert salary to dollars if dollar rate is available
        const dollarRateElement = $('#dollar_rate');
        if (dollarRateElement.length && dollarRateElement.text() !== '0' && dollarRateElement.text() !== 'جێبەجێکردن...') {
            const dollarRate = parseFloat(dollarRateElement.text().replace(/,/g, ''));
            if (dollarRate > 0) {
                const salaryInDollars = summary.total_salary / (dollarRate / 100);
                const formattedDollars = salaryInDollars.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                $('#total_salary').append(` <small class="text-muted">($${formattedDollars})</small>`);
            }
        }
    }

    function loadEmployees() {
        TableController.showLoading('#employeeTable', ['#', 'name', 'mobile', 'role', 'salary', 'actions']);
        $.get('../process/employee/select_employee.php', function (res) {
            if (!res || !res.employees || !Array.isArray(res.employees)) {
                TableController.render('#employeeTable', [], ['#', 'name', 'mobile', 'role', 'salary', 'actions']);
                updateSummaryCards({ total_employees: 0, total_salary: 0 });
                return;
            }

            // Update summary cards
            if (res.summary) {
                window.lastSummaryData = res.summary; // Store for reuse
                updateSummaryCards(res.summary);
            }

            // Add actions column (edit/delete buttons) and format salary
            res.employees.forEach(emp => {
                const rawSalary = emp.salary;
                emp.salary = formatSalary(emp.salary);
                emp.actions = `
                    <button class="btn btn-sm btn-primary edit-employee" data-id="${emp.id}" data-name="${emp.name}" data-mobile="${emp.mobile}" data-role="${emp.role}" data-salary="${rawSalary}"><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-employee" data-id="${emp.id}"><i class="fa fa-trash"></i></button>
                `;
            });
            TableController.renderWithPagination('#employeeTable', res.employees, ['#', 'name', 'mobile', 'role', 'salary', 'actions']);
        }, 'json');
    }
    loadEmployees();
    window.loadEmployees = loadEmployees;
    window.updateSummaryCards = updateSummaryCards;
});
