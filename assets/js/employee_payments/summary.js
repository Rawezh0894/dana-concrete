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
            .val(month.pay_month)
            .text(formatMonth(month.pay_month));
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
    
    // Handle filter changes - use Select2 change event if available
    $('#month-filter').on('change', function() {
        loadSummaryData();
        if (typeof loadPayments === 'function') {
            loadPayments();
        } else if (typeof loadEmployeePayments === 'function') {
            loadEmployeePayments();
        }
    });
    
    // Handle employee filter change - use Select2 event
    $(document).on('change', '#employee-filter', function() {
        loadSummaryData();
        if (typeof loadPayments === 'function') {
            loadPayments();
        } else if (typeof loadEmployeePayments === 'function') {
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