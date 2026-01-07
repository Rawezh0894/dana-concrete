// Load and display employee balance cards
function loadBalances() {
    const employeeFilter = $('#employee-filter').val();
    
    let url = '../process/employee_payments/get_balances.php';
    if (employeeFilter) {
        url += '?employee=' + employeeFilter;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.error('Error loading balances:', result.error);
                return;
            }
            
            const data = result.data;
            const balances = data.balances;
            
            // Update balance cards
            $('#total-payable').text(formatCurrency(balances.total_payable));
            $('#total-receivable').text(formatCurrency(balances.total_receivable));
            $('#net-balance').text(formatCurrency(balances.net_balance));
            $('#employee-count').text(balances.employee_count);
            
            // Color code net balance
            const netBalanceEl = $('#net-balance');
            if (balances.net_balance > 0) {
                netBalanceEl.removeClass('text-danger').addClass('text-success');
            } else if (balances.net_balance < 0) {
                netBalanceEl.removeClass('text-success').addClass('text-danger');
            } else {
                netBalanceEl.removeClass('text-success text-danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US').format(amount) + ' د.ع';
}

// Initialize balance loading
$(document).ready(function() {
    // Load initial balances
    loadBalances();
    
    // Reload balances when filters change
    $('#employee-filter').on('change', function() {
        loadBalances();
    });
    
    // Export function for use in other scripts
    window.loadBalances = loadBalances;
    
    // Reload balances when summary data is loaded
    if (window.employeePaymentsSummary) {
        const originalLoadSummaryData = window.employeePaymentsSummary.loadSummaryData;
        window.employeePaymentsSummary.loadSummaryData = function() {
            originalLoadSummaryData();
            loadBalances();
        };
    }
});

