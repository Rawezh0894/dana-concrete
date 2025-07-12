document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
        if (typeof loadCustomerReturnDebts === 'function') loadCustomerReturnDebts(CUSTOMER_ID);
    }
});
