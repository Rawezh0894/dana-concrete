// Load summary data and populate cards for employee_expenses page
function loadSummaryData() {
    const monthFilter = $('#month-filter').val();
    const employeeFilter = $('#employee-filter').val();
    const fromDate = $('#date-from').val();
    const toDate = $('#date-to').val();
    
    let url = '../process/employee_payments/get_expenses_summary.php';
    const params = new URLSearchParams();
    
    if (monthFilter) params.append('month', monthFilter);
    if (employeeFilter) params.append('employee', employeeFilter);
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    
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
            
            const data = result.data;
            
            // Update summary cards
            $('#total-salary').text(formatCurrency(data.summary.total_salary));
            $('#total-bonus').text(formatCurrency(data.summary.total_bonus));
            $('#total-salary-bonus').text(formatCurrency(data.summary.total_salary_bonus));
            $('#total-overtime').text(formatCurrency(data.summary.total_overtime));
            $('#total-income').text(formatCurrency(data.summary.total_income));
            $('#total-advance').text(formatCurrency(data.summary.total_advance));
            $('#total-deduction').text(formatCurrency(data.summary.total_deduction));
            $('#total-penalty').text(formatCurrency(data.summary.total_penalty));
            $('#net-salary-balance').text(formatCurrency(data.summary.net_salary_balance));
            
            // Populate filter dropdowns if not already populated
            if ($('#month-filter option').length <= 1) {
                populateMonthFilter(data.filters.months);
            }
            if ($('#employee-filter option').length <= 1) {
                populateEmployeeFilter(data.filters.employees);
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
$(document).ready(function() {
    // Load initial data
    loadSummaryData();
    
    // Handle filter changes
    $('#month-filter').on('change', function() {
        loadSummaryData();
        if (typeof loadExpenses === 'function') {
            loadExpenses();
        }
        if (typeof loadBalances === 'function') {
            loadBalances();
        }
    });
    
    // Handle employee filter change - use Select2 event
    $(document).on('change', '#employee-filter', function() {
        loadSummaryData();
        if (typeof loadExpenses === 'function') {
            loadExpenses();
        }
        if (typeof loadBalances === 'function') {
            loadBalances();
        }
    });

    // Handle date range changes
    $('#date-from, #date-to').on('change', function() {
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

