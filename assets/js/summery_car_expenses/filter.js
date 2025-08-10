$(document).ready(function() {
    // Initialize Select2 for better dropdown experience
    $('#filter_car_id, #filter_employee_id').select2({
        placeholder: 'هەڵبژاردن...',
        allowClear: true,
        dir: 'rtl'
    });

    // Test button for loading all data without filters
    $('#test_all_data').on('click', function() {
        // Clear all filters
        $('#filter_car_id').val('').trigger('change');
        $('#filter_employee_id').val('').trigger('change');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        
        // Load data without date filters
        loadCarExpensesSummary();
        
        // Show message
        Swal.fire({
            icon: 'info',
            title: 'تاقیکردنەوە',
            text: 'هەموو داتای خەرجی سەیارەکان بەبێ فلتەر باردەکرێت',
            confirmButtonText: 'باشە'
        });
    });

    // Debug info button
    $('#show_debug_info').on('click', function() {
        const debugSummary = $('#debug_summary');
        const debugContent = $('#debug_content');
        
        if (debugSummary.is(':visible')) {
            debugSummary.hide();
            return;
        }
        
        // Show debug info
        debugContent.html(`
            <div class="row">
                <div class="col-md-6">
                    <strong>فلتەرەکانی ئێستا:</strong><br>
                    سەیارە: ${$('#filter_car_id').val() || 'هەموو'}<br>
                    کارمەند: ${$('#filter_employee_id').val() || 'هەموو'}<br>
                    لە بەرواری: ${$('#filter_date_from').val() || 'هەموو'}<br>
                    بۆ بەرواری: ${$('#filter_date_to').val() || 'هەموو'}
                </div>
                <div class="col-md-6">
                    <strong>داتای بەردەست:</strong><br>
                    ژمارەی سەیارەکان: <span id="debug_car_count">-</span><br>
                    کۆی خەرجی گاز: <span id="debug_gas_total">-</span><br>
                    کۆی خەرجی کاڵا: <span id="debug_material_total">-</span><br>
                    کۆی گشتی: <span id="debug_total">-</span>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">زانیاری زیاتر لە console دەبینرێت</small>
            </div>
        `);
        
        debugSummary.show();
        
        // Update debug info with current data
        if (typeof window.carExpensesData !== 'undefined') {
            $('#debug_car_count').text(window.carExpensesData.length || 0);
        }
        if (typeof window.summaryStats !== 'undefined') {
            $('#debug_gas_total').text('$' + (window.summaryStats.total_gas_expenses_usd || 0));
            $('#debug_material_total').text('$' + (window.summaryStats.total_material_expenses_usd || 0));
            $('#debug_total').text('$' + (window.summaryStats.total_expenses_usd || 0));
        }
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
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        const todayStr = today.toISOString().split('T')[0];
        const thirtyDaysAgoStr = thirtyDaysAgo.toISOString().split('T')[0];
        
        $('#filter_date_from').val(thirtyDaysAgoStr);
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
