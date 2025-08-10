$(document).ready(function() {
    // Initialize Select2 for better dropdown experience
    $('#filter_car_id, #filter_employee_id').select2({
        placeholder: 'هەڵبژاردن...',
        allowClear: true,
        dir: 'rtl'
    });

    // Filter buttons functionality
    $('.filter-btn').on('click', function() {
        const filterType = $(this).data('filter');
        applyFilter(filterType);
    });

    // Date filter changes
    $('#filter_date_from, #filter_date_to').on('change', function() {
        loadCarExpensesSummary();
    });

    // Car and employee filter changes
    $('#filter_car_id, #filter_employee_id').on('change', function() {
        loadCarExpensesSummary();
    });

    // Apply filter based on type
    function applyFilter(filterType) {
        const today = new Date();
        let dateFrom, dateTo;

        switch(filterType) {
            case 'today':
                dateFrom = today.toISOString().split('T')[0];
                dateTo = today.toISOString().split('T')[0];
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                dateFrom = yesterday.toISOString().split('T')[0];
                dateTo = yesterday.toISOString().split('T')[0];
                break;
            case 'reset':
                dateFrom = '';
                dateTo = '';
                $('#filter_car_id').val('').trigger('change');
                $('#filter_employee_id').val('').trigger('change');
                break;
            default:
                return;
        }

        $('#filter_date_from').val(dateFrom);
        $('#filter_date_to').val(dateTo);
        loadCarExpensesSummary();
    }

    // Get current filter values
    function getCurrentFilters() {
        return {
            car_id: $('#filter_car_id').val(),
            employee_id: $('#filter_employee_id').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };
    }

    // Load car expenses summary with current filters
    function loadCarExpensesSummary() {
        const filters = getCurrentFilters();
        
        // Show loading state
        $('#summary-cards').addClass('loading');
        $('#carSummaryTable tbody').html('<tr><td colspan="8" class="text-center">سڕینەوە...</td></tr>');

        // Call the main function to load data
        if (typeof loadCarExpensesData === 'function') {
            loadCarExpensesData(filters);
        }
    }

    // Initialize filters with current date
    function initializeFilters() {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        $('#filter_date_from').val(todayStr);
        $('#filter_date_to').val(todayStr);
        
        // Load initial data
        loadCarExpensesSummary();
    }

    // Initialize on page load
    initializeFilters();

    // Export filters to global scope for other scripts
    window.carExpensesFilters = {
        getCurrentFilters: getCurrentFilters,
        loadCarExpensesSummary: loadCarExpensesSummary,
        applyFilter: applyFilter
    };
});
