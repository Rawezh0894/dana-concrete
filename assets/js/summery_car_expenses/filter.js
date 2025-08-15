// Filter functionality for car expenses summary

// Apply filters
function applyFilters() {
    showLoading();
    
    // Validate date range
    const fromDate = $('#filter_from_date').val();
    const toDate = $('#filter_to_date').val();
    
    if (fromDate && toDate && fromDate > toDate) {
        hideLoading();
        showError('بەرواری دەستپێک نابێت لە بەرواری کۆتایی گەورەتر بێت');
        return;
    }
    
    // Load data with current filters
    loadExpensesData();
}

// Clear all filters
function clearFilters() {
    $('#filter_car_id').val('').trigger('change');
    $('#filter_employee_id').val('').trigger('change');
    $('#filter_expense_type').val('').trigger('change');
    $('#filter_payment_type').val('').trigger('change');
    
    // Reset to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#filter_from_date').val(firstDay.toISOString().split('T')[0]);
    $('#filter_to_date').val(today.toISOString().split('T')[0]);
    
    // Reload data
    loadExpensesData();
}

// Auto-apply filters when date changes
$(document).ready(function() {
    $('#filter_from_date, #filter_to_date').on('change', function() {
        // Auto-apply if both dates are set
        if ($('#filter_from_date').val() && $('#filter_to_date').val()) {
            applyFilters();
        }
    });
    
    // Auto-apply when select filters change
    $('#filter_car_id, #filter_employee_id, #filter_expense_type, #filter_payment_type').on('change', function() {
        applyFilters();
    });
});

// Show success message
function showSuccess(message) {
    // You can implement a better success display system
    const successAlert = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at the top of the container
    $('.container-fluid').prepend(successAlert);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        $('.alert-success').fadeOut();
    }, 3000);
}
