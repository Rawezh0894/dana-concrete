 (function (global) {
    const $ = global.jQuery || global.$;
    const URLSearchParamsCtor = global.URLSearchParams;
    const fetchFn = global.fetch;
    const console = global.console;
    const doc = global.document;

    if (!$ || !doc || !URLSearchParamsCtor || !fetchFn) return;

    // Load ledger summary data and populate cards
    function loadSummaryData() {
        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();

        let url = '../process/employee_payments/get_balances_summary.php';
        const params = new URLSearchParamsCtor();

        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);

        if (params.toString()) url += '?' + params.toString();

        fetchFn(url)
            .then(r => r.json())
            .then(result => {
                if (!result.success) {
                    if (console && console.error) console.error('Error loading summary:', result.error || result.msg);
                    return;
                }
                const d = result.data || {};
                $('#total-balance').text(formatCurrency(d.total_balance || 0));
                $('#total-credit').text(formatCurrency(d.total_credit || d.total_payroll || 0));
                $('#total-paid').text(formatCurrency(d.total_paid_cash || d.total_paid || 0));
                $('#total-penalty').text(formatCurrency(d.total_penalty || 0));
            })
            .catch(err => {
                if (console && console.error) console.error(err);
            });
    }

    // Populate month filter dropdown
    function populateMonthFilter(months) {
    const monthFilter = $('#month-filter');
    monthFilter.find('option:not(:first)').remove();
    
    months.forEach(month => {
        const option = $('<option></option>')
            .val(month.month)
            .text(formatMonth(month.month));
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
    return Number(amount || 0).toLocaleString('en-US') + ' د.ع';
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
    
    return monthNames[parseInt(month, 10) - 1] + ' ' + year;
    }

    // Initialize summary functionality
    $(doc).ready(function() {
    // Load filters once
    fetchFn('../process/employee_payments/get_filters.php')
        .then(r => r.json())
        .then(result => {
            if (!result.success) return;
            const data = result.data || {};
            populateMonthFilter(data.months || []);
            populateEmployeeFilter(data.employees || []);
        })
        .finally(() => {
            // Load initial summary after filters
            loadSummaryData();
        });
    
    // Handle filter changes - use Select2 change event if available
    $('#month-filter').on('change', function() {
        loadSummaryData();
        if (typeof global.loadPayments === 'function') {
            global.loadPayments();
        } else if (typeof global.loadEmployeePayments === 'function') {
            global.loadEmployeePayments();
        }
    });
    
    // Handle employee filter change - use Select2 event
    $(doc).on('change', '#employee-filter', function() {
        loadSummaryData();
        if (typeof global.loadPayments === 'function') {
            global.loadPayments();
        } else if (typeof global.loadEmployeePayments === 'function') {
            global.loadEmployeePayments();
        }
    });
    });

    // Export functions for use in other scripts
    global.employeePaymentsSummary = {
        loadSummaryData,
        formatCurrency,
        formatMonth
    };
})(globalThis);