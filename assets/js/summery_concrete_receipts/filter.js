$(document).ready(function() {
    // Get filters from form
    function getFilters() {
        return {
            customer_id: $('#filter_customer_id').val(),
            formula_id: $('#filter_formula_id').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };
    }

    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    $('#filter_date_to').val(today);

    // Set date from 30 days ago as default
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    $('#filter_date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);

    // Quick filter buttons
    $('#filter_today').click(function() {
        const today = new Date().toISOString().split('T')[0];
        $('#filter_date_from').val(today);
        $('#filter_date_to').val(today);
        loadSummaryData();
    });

    $('#filter_yesterday').click(function() {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        $('#filter_date_from').val(yesterdayStr);
        $('#filter_date_to').val(yesterdayStr);
        loadSummaryData();
    });

    $('#filter_this_week').click(function() {
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        
        $('#filter_date_from').val(startOfWeek.toISOString().split('T')[0]);
        $('#filter_date_to').val(endOfWeek.toISOString().split('T')[0]);
        loadSummaryData();
    });

    $('#filter_this_month').click(function() {
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        $('#filter_date_from').val(startOfMonth.toISOString().split('T')[0]);
        $('#filter_date_to').val(endOfMonth.toISOString().split('T')[0]);
        loadSummaryData();
    });

    $('#filter_reset').click(function() {
        // Reset all filters
        $('#filter_customer_id').val('');
        $('#filter_formula_id').val('');
        
        // Set default date range (last 30 days)
        const today = new Date().toISOString().split('T')[0];
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        $('#filter_date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
        $('#filter_date_to').val(today);
        
        loadSummaryData();
    });

    // Date validation
    $('#filter_date_from, #filter_date_to').change(function() {
        const dateFrom = $('#filter_date_from').val();
        const dateTo = $('#filter_date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            Swal.fire({
                icon: 'warning',
                title: 'هەڵە لە بەروار',
                text: 'بەرواری دەستپێک نابێت لە بەرواری کۆتایی گەورەتر بێت',
                confirmButtonText: 'باشە'
            });
            
            // Reset the invalid date
            if ($(this).attr('id') === 'filter_date_from') {
                $('#filter_date_to').val(dateFrom);
            } else {
                $('#filter_date_from').val(dateTo);
            }
        }
    });

    // Export functionality
    $('#export_excel').click(function() {
        const filters = getFilters();
        const queryString = new URLSearchParams(filters).toString();
        window.open(`../process/summery_concrete_receipts/export_excel.php?${queryString}`, '_blank');
    });

    $('#export_pdf').click(function() {
        const filters = getFilters();
        const queryString = new URLSearchParams(filters).toString();
        window.open(`../process/summery_concrete_receipts/export_pdf.php?${queryString}`, '_blank');
    });

    // Print functionality
    $('#print_summary').click(function() {
        const filters = getFilters();
        const queryString = new URLSearchParams(filters).toString();
        window.open(`../process/summery_concrete_receipts/print_summary.php?${queryString}`, '_blank');
    });

    // Auto-load data when date filters change
    $('#filter_date_from, #filter_date_to').change(function() {
        loadSummaryData();
    });

    // Load summary data function (shared with get_informations.js)
    function loadSummaryData() {
        const filters = getFilters();
        
        $.ajax({
            url: '../process/summery_concrete_receipts/get_informations.php',
            type: 'GET',
            data: filters,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateSummaryCards(response.summary);
                    updateCustomerSummaryTable(response.customer_summary);
                } else {
                    showError('هەڵە لە وەرگرتنی داتا: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                showError('هەڵە لە پەیوەندی بە سێرڤەر: ' + error);
            }
        });
    }

    // Update summary cards
    function updateSummaryCards(summary) {
        $('#total_receipts').text(summary.total_receipts || 0);
        $('#total_meter_cubic').text((summary.total_meter_cubic || 0).toFixed(2));
        $('#total_customers').text(summary.total_customers || 0);
        $('#average_price').text((summary.average_meter_amount || 0).toFixed(2));
    }

    // Update customer summary table
    function updateCustomerSummaryTable(customerSummary) {
        const tbody = $('#customerSummaryTable tbody');
        tbody.empty();

        if (customerSummary.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center">هیچ داتایەک نییە</td></tr>');
            return;
        }

        customerSummary.forEach((customer, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${customer.customer_name}</td>
                    <td>${customer.receipt_count}</td>
                    <td>${parseFloat(customer.total_meter_cubic).toFixed(2)}</td>
                    <td>${customer.formulas_used || '-'}</td>
                    <td>${customer.locations || '-'}</td>
                    <td>${customer.receivers || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-details-btn" 
                                data-customer-id="${customer.customer_id}"
                                data-customer-name="${customer.customer_name}">
                            <i class="fas fa-eye"></i> وردەکاری
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Show error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: message,
            confirmButtonText: 'باشە'
        });
    }

});
