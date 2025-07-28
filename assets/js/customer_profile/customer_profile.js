document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer profile page loaded, CUSTOMER_ID:', typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : 'undefined');
    
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
        // Initialize all functions with error handling
        try {
            if (typeof loadCustomerReturnDebts === 'function') {
                console.log('Loading customer return debts...');
                loadCustomerReturnDebts(CUSTOMER_ID);
            } else {
                console.warn('loadCustomerReturnDebts function not available');
            }
            
            if (typeof loadCustomerSales === 'function') {
                console.log('Loading customer sales...');
                loadCustomerSales(CUSTOMER_ID);
            } else {
                console.warn('loadCustomerSales function not available');
            }
            
            if (typeof loadCustomerSummaryCards === 'function') {
                console.log('Loading customer summary cards...');
                loadCustomerSummaryCards();
            } else {
                console.warn('loadCustomerSummaryCards function not available');
            }
        } catch (error) {
            console.error('Error initializing customer profile:', error);
        }
    } else {
        console.error('CUSTOMER_ID is not defined or invalid');
    }
});

// Add global error handler for unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    // Optionally show user-friendly error message
    if (typeof Swal !== 'undefined') {
        Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە سیستەمەکە', 'error');
    }
});
