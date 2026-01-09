// Load summary data and populate cards for employee_expenses page
function loadSummaryData() {
    const monthFilter = $('#month-filter').val();
    const employeeFilter = $('#employee-filter').val();
    const startDate = $('#start-date').val();
    const endDate = $('#end-date').val();

    let url = '../process/employee_payments/get_expenses_summary.php';
    const params = new URLSearchParams();

    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (monthFilter) params.append('month', monthFilter);
    if (employeeFilter) params.append('employee', employeeFilter);

    if (params.toString()) {
        url += '?' + params.toString();
    }

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.error('Error loading summary:', result.error);
                return;
            }

            const data = result.data.summary;
            const filters = result.data.filters;

            // Calculate compound values
            const salaryAndBonus = data.total_salary + data.total_bonus;
            const totalIncome = data.total_salary + data.total_bonus + data.total_overtime;
            const totalDeductions = data.total_deduction + data.total_penalty;
            const netPayable = totalIncome - totalDeductions;

            // Calculate daily balance (always for current month, regardless of filters)
            // Get current date info
            const today = new Date();
            const currentYear = today.getFullYear();
            const currentMonth = today.getMonth() + 1;
            const currentDay = today.getDate();
            
            // Get days in current month
            const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
            
            // Calculate total income (salary + bonus + overtime) for current month
            // Note: This should be calculated from current month data, not filtered data
            // For now, we'll use the filtered data but calculate based on current month days
            const totalIncomeAmount = data.total_salary + data.total_bonus + data.total_overtime;
            
            // Calculate daily rate: total income / days in month
            const dailyRate = daysInMonth > 0 ? totalIncomeAmount / daysInMonth : 0;
            
            // Calculate balance up to today: daily rate × days passed
            const balanceUpToToday = dailyRate * currentDay;
            
            // Subtract deductions (penalty + deduction)
            const totalDeductionsAmount = data.total_deduction + data.total_penalty;
            const finalDailyBalance = balanceUpToToday - totalDeductionsAmount;
            
            // Format details text
            const detailsText = `(${formatCurrency(totalIncomeAmount)} ÷ ${daysInMonth} × ${currentDay}) - ${formatCurrency(totalDeductionsAmount)}`;

            // Update summary cards
            $('#total-salary').text(formatCurrency(data.total_salary));
            $('#total-bonus').text(formatCurrency(data.total_bonus));
            $('#total-salary-bonus').text(formatCurrency(salaryAndBonus));
            $('#total-overtime').text(formatCurrency(data.total_overtime));
            $('#net-payable').text(formatCurrency(netPayable));
            
            // Update daily balance card
            $('#daily-balance').text(formatCurrency(Math.max(0, finalDailyBalance)));
            $('#daily-balance-details').text(detailsText);

            $('#total-advance').text(formatCurrency(data.total_advance));
            $('#total-deduction').text(formatCurrency(data.total_deduction));
            $('#total-penalty').text(formatCurrency(data.total_penalty));

            // Populate filter dropdowns if not already populated
            // Only populate if month filter is empty (first load)
            if ($('#month-filter option').length <= 1 && filters && filters.months) {
                populateMonthFilter(filters.months);
            }
            if ($('#employee-filter option').length <= 1 && filters && filters.employees) {
                populateEmployeeFilter(filters.employees);
            } else {
                // Re-initialize Select2 if already populated
                initializeEmployeeSelect2();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Populate month filter dropdown
function populateMonthFilter(months) {
    const monthFilter = $('#month-filter');
    monthFilter.find('option:not(:first)').remove();

    months.forEach(month => {
        const option = $('<option></option>')
            .val(month.expense_date)
            .text(formatMonth(month.expense_date));
        monthFilter.append(option);
    });
}

// Initialize Select2 for employee filter
function initializeEmployeeSelect2() {
    const employeeFilter = $('#employee-filter');
    if (employeeFilter.length === 0) return;

    // Destroy existing Select2 if exists
    if (employeeFilter.hasClass('select2-hidden-accessible')) {
        try {
            employeeFilter.select2('destroy');
        } catch (e) {
            console.log('Error destroying select2:', e);
        }
    }

    // Initialize Select2
    employeeFilter.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'هەموو کارمەندەکان',
        allowClear: true,
        dir: 'rtl'
    });
}

// Populate employee filter dropdown
function populateEmployeeFilter(employees) {
    const employeeFilter = $('#employee-filter');
    employeeFilter.find('option:not(:first)').remove();

    employees.forEach(employee => {
        const option = $('<option></option>')
            .val(employee.id)
            .text(employee.name);
        employeeFilter.append(option);
    });

    // Initialize Select2 after populating
    initializeEmployeeSelect2();
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US').format(amount) + ' د.ع';
}

// Format month display
function formatMonth(monthString) {
    if (!monthString) return '';

    const [year, month] = monthString.split('-');
    const monthNames = [
        'کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان',
        'ئایار', 'حوزەیران', 'تەمموز', 'ئاب',
        'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'
    ];

    return monthNames[parseInt(month) - 1] + ' ' + year;
}

// Initialize summary functionality
$(document).ready(function () {
    // Load initial data
    loadSummaryData();

    // Handle filter changes
    $('#month-filter, #start-date, #end-date').on('change', function () {
        loadSummaryData();
        if (typeof loadExpenses === 'function') {
            loadExpenses();
        }
        if (typeof loadBalances === 'function') {
            loadBalances();
        }
    });

    // Handle employee filter change - use Select2 event
    $(document).on('change', '#employee-filter', function () {
        loadSummaryData();
        if (typeof loadExpenses === 'function') {
            loadExpenses();
        }
        if (typeof loadBalances === 'function') {
            loadBalances();
        }
    });
});

// Export functions for use in other scripts
window.employeeExpensesSummary = {
    loadSummaryData,
    formatCurrency,
    formatMonth
};

