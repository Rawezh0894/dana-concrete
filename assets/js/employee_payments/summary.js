// Load summary data and populate cards
function loadSummaryData() {
    const monthFilter = $('#month-filter').val();
    const employeeFilter = $('#employee-filter').val();
    
    let url = '../process/employee_payments/get_summary.php';
    const params = new URLSearchParams();
    
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
            
            const data = result.data;
            
            // Update cards
            $('#total-payments').text(formatCurrency(data.summary.total_payments));
            $('#total-salary').text(formatCurrency(data.summary.total_salary));
            $('#total-bonus').text(formatCurrency(data.summary.total_bonus));
            $('#total-karwanhisabi').text(formatCurrency(data.summary.total_karwanhisabi));
            
            // Populate filter dropdowns if not already populated
            if ($('#month-filter option').length <= 1) {
                populateMonthFilter(data.filters.months);
            }
            if ($('#employee-filter option').length <= 1) {
                populateEmployeeFilter(data.filters.employees);
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
            .val(month.pay_month)
            .text(formatMonth(month.pay_month));
        monthFilter.append(option);
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
    $('#month-filter, #employee-filter').on('change', function() {
        loadSummaryData();
        
        // Also trigger table refresh if table controller exists
        if (typeof loadEmployeePayments === 'function') {
            loadEmployeePayments();
        }
    });
});

// Export functions for use in other scripts
window.employeePaymentsSummary = {
    loadSummaryData,
    formatCurrency,
    formatMonth
}; 