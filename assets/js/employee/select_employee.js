$(function () {
    function formatSalary(salary) {
        return Number(salary).toLocaleString('en-US') + ' د.ع';
    }

    function updateSummaryCards(summary) {
        $('#total_employees').text(summary.total_employees.toLocaleString());
        $('#total_salary').text(formatSalary(summary.total_salary || 0));
        $('#total_bonus').text(formatSalary(summary.total_bonus || 0));
        $('#total_salary_plus_bonus').text(formatSalary(summary.total_salary_plus_bonus || 0));

        // Convert salary to dollars if dollar rate is available
        const dollarRateElement = $('#dollar_rate');
        if (dollarRateElement.length && dollarRateElement.text() !== '0' && dollarRateElement.text() !== 'جێبەجێکردن...') {
            const dollarRate = parseFloat(dollarRateElement.text().replace(/,/g, ''));
            if (dollarRate > 0) {
                const salaryInDollars = (summary.total_salary || 0) / (dollarRate / 100);
                const formattedDollars = salaryInDollars.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                $('#total_salary').append(` <small class="text-muted">($${formattedDollars})</small>`);
            }
        }
    }

    function updateRoleCards(roleStats) {
        if (!roleStats) return;
        
        const roleCardsContainer = $('#role_stats_cards');
        roleCardsContainer.empty();
        
        const roleColors = [
            'card-gradient-info',
            'card-gradient-success',
            'card-gradient-warning',
            'card-gradient-purple',
            'card-gradient-info',
            'card-gradient-success',
            'card-gradient-warning',
            'card-gradient-purple',
            'card-gradient-info',
            'card-gradient-success',
            'card-gradient-warning',
            'card-gradient-purple',
            'card-gradient-info',
            'card-gradient-success',
            'card-gradient-warning'
        ];
        
        const roleIcons = [
            'fa-shield-alt',
            'fa-truck',
            'fa-truck',
            'fa-user-tie',
            'fa-user-cog',
            'fa-user-friends',
            'fa-cog',
            'fa-tools',
            'fa-user-tie',
            'fa-utensils',
            'fa-user-tie',
            'fa-calculator',
            'fa-gavel',
            'fa-truck',
            'fa-user'
        ];
        
        let cardIndex = 0;
        Object.entries(roleStats).forEach(([role, count]) => {
            if (count > 0 || true) { // Show all roles even if count is 0
                const colorClass = roleColors[cardIndex % roleColors.length];
                const iconClass = roleIcons[cardIndex % roleIcons.length];
                
                const cardHtml = `
                    <div class="col-md-2 mb-3">
                        <div class="card text-center shadow ${colorClass} card-animate-hover">
                            <div class="card-body">
                                <i class="fas ${iconClass} card-icon"></i>
                                <h6 class="card-title" style="font-size: 0.85rem;">${role}</h6>
                                <div class="fs-4 fw-bold role-count" data-role="${role}">${count}</div>
                                <small class="text-light">کارمەند</small>
                            </div>
                        </div>
                    </div>
                `;
                roleCardsContainer.append(cardHtml);
                cardIndex++;
            }
        });
    }

    // Store current page for pagination preservation
    let currentEmployeePage = 1;
    
    function loadEmployees(preservePage = false) {
        // Get current page from pagination if preservePage is true
        if (preservePage) {
            const activePageBtn = document.querySelector('.table-pagination .btn-success.active');
            if (activePageBtn) {
                currentEmployeePage = parseInt(activePageBtn.textContent) || 1;
            }
        }
        
        TableController.showLoading('#employeeTable', ['#', 'name', 'mobile', 'role', 'salary', 'bonus', 'status', 'actions']);
        $.get('../process/employee/select_employee.php', function (res) {
            console.log('Response from select_employee.php:', res);
            
            // Handle error response
            if (res && res.success === false) {
                console.error('Error loading employees:', res.error);
                swalAlert('هەڵە', res.error || 'هەڵە لە وەرگرتنی زانیاری', 'error');
                TableController.render('#employeeTable', [], ['#', 'name', 'mobile', 'role', 'salary', 'bonus', 'status', 'actions']);
                updateSummaryCards({ total_employees: 0, total_salary: 0 });
                return;
            }
            
            if (!res || !res.employees || !Array.isArray(res.employees)) {
                console.warn('Invalid response format:', res);
                TableController.render('#employeeTable', [], ['#', 'name', 'mobile', 'role', 'salary', 'bonus', 'status', 'actions']);
                updateSummaryCards({ total_employees: 0, total_salary: 0 });
                return;
            }

            // Update summary cards
            if (res.summary) {
                window.lastSummaryData = res.summary; // Store for reuse
                updateSummaryCards(res.summary);
            }

            // Apply role filter if any roles are selected
            const selectedRoles = $('#filter_role').val();
            let filteredEmployees = res.employees;
            if (selectedRoles && selectedRoles.length > 0) {
                filteredEmployees = res.employees.filter(emp => {
                    const empRoles = (emp.role || '').split(',').map(r => r.trim());
                    return selectedRoles.some(selectedRole => empRoles.includes(selectedRole));
                });
            }

            // Calculate filtered role statistics
            let filteredRoleStats = {};
            if (res.role_stats) {
                if (selectedRoles && selectedRoles.length > 0) {
                    // Only show stats for selected roles
                    selectedRoles.forEach(role => {
                        filteredRoleStats[role] = res.role_stats[role] || 0;
                    });
                } else {
                    // Show all role stats
                    filteredRoleStats = res.role_stats;
                }
                updateRoleCards(filteredRoleStats);
            }

            // Use filtered employees for table
            res.employees = filteredEmployees;

            // Translate status to Kurdish
            const statusMap = {
                'active': 'چالاک',
                'inactive': 'نەچالاک',
                'on_leave': 'لە پشوودا',
                'resigned': 'دەستلەکارکێشان'
            };

            // Add actions column (edit/delete buttons) and format salary
            res.employees.forEach(emp => {
                const rawSalary = emp.salary;
                const rawBonus = emp.bonus || 0;
                const rawStatus = emp.status || 'active'; // Keep original English status for data attribute
                const rawRole = emp.role || ''; // Store original role value before modifying
                
                emp.salary = formatSalary(emp.salary);
                emp.bonus = formatSalary(rawBonus);
                emp.status = statusMap[emp.status] || emp.status; // Display Kurdish status in table
                
                // Handle multiple roles - display them nicely
                let roleDisplay = rawRole;
                if (typeof roleDisplay === 'string' && roleDisplay.includes(',')) {
                    // If comma-separated, show with badges
                    const roles = roleDisplay.split(',').map(r => r.trim()).filter(r => r);
                    roleDisplay = roles.map(r => `<span class="badge bg-secondary me-1">${r}</span>`).join('');
                } else if (roleDisplay) {
                    // Single role - also show as badge for consistency
                    roleDisplay = `<span class="badge bg-secondary">${roleDisplay}</span>`;
                }
                emp.role = roleDisplay;
                
                emp.actions = `
                    <button class="btn btn-sm btn-primary edit-employee" data-id="${emp.id}" data-name="${emp.name}" data-mobile="${emp.mobile}" data-role="${rawRole}" data-salary="${rawSalary}" data-bonus="${rawBonus}" data-status="${rawStatus}"><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-employee" data-id="${emp.id}"><i class="fa fa-trash"></i></button>
                `;
            });
            // Preserve current page if requested
            let paginationOptions = {};
            if (preservePage && currentEmployeePage > 1) {
                // Calculate if current page still exists after data changes
                const pageSize = 10; // Default page size
                const totalPages = Math.ceil(res.employees.length / pageSize);
                // If current page doesn't exist anymore, go to last page
                if (currentEmployeePage > totalPages && totalPages > 0) {
                    currentEmployeePage = totalPages;
                }
                paginationOptions = { currentPage: currentEmployeePage };
            }
            TableController.renderWithPagination('#employeeTable', res.employees, ['#', 'name', 'mobile', 'role', 'salary', 'bonus', 'status', 'actions'], paginationOptions);
        }, 'json').fail(function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە وەرگرتنی زانیاری کارمەندەکان', 'error');
            TableController.render('#employeeTable', [], ['#', 'name', 'mobile', 'role', 'salary', 'bonus', 'status', 'actions']);
            updateSummaryCards({ total_employees: 0, total_salary: 0 });
        });
    }
    loadEmployees();
    window.loadEmployees = loadEmployees;
    window.updateSummaryCards = updateSummaryCards;
    window.updateRoleCards = updateRoleCards;

    // Handle role filter change
    $(document).on('change', '#filter_role', function() {
        loadEmployees();
    });
});
