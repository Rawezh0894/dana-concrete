// Purchase Materials History for Person Profile
$(document).ready(function() {
    // Load purchases when the purchases tab is shown
    $('#purchases-tab').on('click', function() {
        loadPurchaseMaterialsHistory();
    });
    
    // Also load when the tab is shown via other means
    $('button[data-bs-target="#purchases"]').on('click', function() {
        loadPurchaseMaterialsHistory();
    });
});

function loadPurchaseMaterialsHistory() {
    // Show loading state using TableController
    const columns = ['#', 'receipt_number', 'purchase_date', 'materials_count', 'total_price_usd', 'total_price_iqd', 'currency_type', 'payment_type', 'paid_amount_usd', 'paid_amount_iqd', 'remaining_amount_usd', 'remaining_amount_iqd', 'notes'];
    TableController.showLoading('#purchasesTable', columns);
    
    $.ajax({
        url: '../process/person_other_expenses_profile/select_purchases.php',
        type: 'GET',
        data: { person_id: PERSON_ID },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderPurchaseMaterialsTable(response.data);
            } else {
                console.error('Error loading purchases:', response.error);
                // Show error state
                const tbody = $('#purchasesTable tbody');
                tbody.html('<tr><td colspan="13" class="text-center text-danger">هەڵە لە بارکردنی داتاکان</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Show error state
            const tbody = $('#purchasesTable tbody');
            tbody.html('<tr><td colspan="13" class="text-center text-danger">هەڵە لە پەیوەندی بە سێرڤەر</td></tr>');
        }
    });
}

function renderPurchaseMaterialsTable(purchases) {
    // Define columns for the table
    const columns = [
        '#', 
        'receipt_number', 
        'purchase_date', 
        'materials_count', 
        'total_price_usd', 
        'total_price_iqd', 
        'currency_type', 
        'payment_type', 
        'paid_amount_usd', 
        'paid_amount_iqd', 
        'remaining_amount_usd', 
        'remaining_amount_iqd', 
        'notes'
    ];
    
    // Format the data for TableController
    const formattedData = purchases.map(purchase => ({
        receipt_number: purchase.receipt_number || '-',
        purchase_date: purchase.purchase_date || '-',
        materials_count: purchase.materials_count || 0,
        total_price_usd: purchase.total_price_usd,
        total_price_iqd: purchase.total_price_iqd,
        currency_type: purchase.currency_type || '-',
        payment_type: purchase.payment_type || 'نەقد',
        paid_amount_usd: purchase.paid_amount_usd,
        paid_amount_iqd: purchase.paid_amount_iqd,
        remaining_amount_usd: purchase.remaining_amount_usd,
        remaining_amount_iqd: purchase.remaining_amount_iqd,
        notes: purchase.notes || '-'
    }));
    
    // Use TableController to render with pagination and search
    TableController.renderWithPagination('#purchasesTable', formattedData, columns, {
        pageSize: 10,
        currentPage: 1
    });
} 