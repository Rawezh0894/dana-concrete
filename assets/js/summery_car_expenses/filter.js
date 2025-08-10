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

    // Show debug information
    $('#show_debug_info').on('click', function() {
        const debugSummary = $('#debug_summary');
        if (debugSummary.is(':visible')) {
            debugSummary.hide();
        } else {
            debugSummary.show();
            // Update debug info with current data
            if (typeof window.updateDebugInfo === 'function') {
                window.updateDebugInfo();
            } else {
                // Fallback debug display
                const debugContent = $('#debug_content');
                if (debugContent.length) {
                    const currentFilters = {
                        car_id: $('#filter_car_id').val() || 'هەموو',
                        employee_id: $('#filter_employee_id').val() || 'هەموو',
                        date_from: $('#filter_date_from').val() || 'هەموو',
                        date_to: $('#filter_date_to').val() || 'هەموو'
                    };
                    
                    const debugInfo = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>فلتەرەکان:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>سەیارە:</strong> ${currentFilters.car_id}</li>
                                    <li><strong>کارمەند:</strong> ${currentFilters.employee_id}</li>
                                    <li><strong>لە بەروار:</strong> ${currentFilters.date_from}</li>
                                    <li><strong>بۆ بەروار:</strong> ${currentFilters.date_to}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>ئامارەکان:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>کۆی سەیارەکان:</strong> ${window.summaryStats?.total_cars || 0}</li>
                                    <li><strong>کۆی خەرجی گاز:</strong> ${window.summaryStats?.total_gas_expenses_iqd || 0} د.ع</li>
                                    <li><strong>کۆی خەرجی کاڵا:</strong> ${window.summaryStats?.total_material_expenses_iqd || 0} د.ع</li>
                                    <li><strong>کۆی گشتی:</strong> ${window.summaryStats?.total_expenses_iqd || 0} د.ع</li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>داتای سەیارەکان:</h6>
                                <small class="text-muted">کۆی: ${window.carExpensesData?.length || 0} سەیارە</small>
                                ${window.carExpensesData && window.carExpensesData.length > 0 ? `
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>سەیارە</th>
                                                    <th>کارمەند</th>
                                                    <th>خەرجی گاز</th>
                                                    <th>خەرجی کاڵا</th>
                                                    <th>کۆی گشتی</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${window.carExpensesData.map(car => `
                                                    <tr>
                                                        <td>${car.car_name || car.car_id}</td>
                                                        <td>${car.employee_name || '-'}</td>
                                                        <td>${car.total_gas_expenses_iqd || 0} د.ع</td>
                                                        <td>${car.total_material_expenses_iqd || 0} د.ع</td>
                                                        <td>${car.total_expenses_iqd || 0} د.ع</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : '<p class="text-muted">هیچ داتایەک نەدۆزرایەوە</p>'}
                            </div>
                        </div>
                    `;
                    
                    debugContent.html(debugInfo);
                }
            }
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
