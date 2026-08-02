// Function to format currency
function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

// Function to format number
function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Function to load purchase summary data
function loadPurchaseSummary(filterParams = '') {
    // If no params provided, try to get them from filters if they exist
    if (!filterParams) {
        const params = new URLSearchParams();
        const companyId = $('#filter_company').val();
        const locationId = $('#filter_location').val();
        const driverId = $('#filter_driver').val();
        const materialId = $('#filter_material').val();
        const fromDate = $('#filter_from').val();
        const toDate = $('#filter_to').val();
        
        if (companyId) params.append('company_id', companyId);
        if (locationId) params.append('location_id', locationId);
        if (driverId) params.append('driver_id', driverId);
        if (materialId) params.append('material_id', materialId);
        if (fromDate) params.append('from', fromDate);
        if (toDate) params.append('to', toDate);
        
        filterParams = params.toString();
    }

    // Build URL with filters
    let url = '../process/purchase/get_summary.php';
    if (filterParams) {
        url += '?' + filterParams;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            console.log('Summary Data Received:', result);
            if (!result.success) {
                console.error('Error loading summary:', result.error);
                return;
            }
            
            const data = result.data;
            
            // Update total price cards
            if (data.total_price_usd !== undefined) {
                $('#total-price-usd').text('$' + formatCurrency(data.total_price_usd));
            }
            if (data.total_price_iqd !== undefined) {
                $('#total-price-iqd').text(formatNumber(data.total_price_iqd));
            }
            
            // Update total invoices card
            if (data.total_invoices !== undefined) {
                $('#total-invoices').text(formatNumber(data.total_invoices));
            }
            
            // Remove any previously added material cards
            $('.dynamic-material-card').remove();
            
            // Add material cards
            if (data.materials_kg && Array.isArray(data.materials_kg)) {
                data.materials_kg.forEach(material => {
                    const kg = material.total_kg ? material.total_kg : 0;
                    const cardHtml = `
                        <div class="col-xl-2 col-lg-3 col-md-4 mb-3 dynamic-material-card">
                            <div class="card text-center shadow card-gradient-info card-animate-hover">
                                <div class="card-body">
                                    <i class="fas fa-box card-icon"></i>
                                    <h6 class="card-title">${material.material_name}</h6>
                                    <div class="fs-4 fw-bold" style="font-size: 1.1rem !important;">${formatNumber(kg)} کگم</div>
                                    <small class="text-light">کۆی گشتی کێش</small>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#purchaseSummaryCards').append(cardHtml);
                });
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
        });
}

// exportPurchaseSummaryToExcel: defined in purchase.js

// Initialize summary cards when page loads
$(document).ready(function() {
    // Load initial summary data
    loadPurchaseSummary();
    
    // Update summary when filters change
    $('#filter_from, #filter_to, #filter_company, #filter_location, #filter_driver, #filter_material').on('change', function() {
        loadPurchaseSummary();
    });
    
    // Update summary when clear filter is clicked
    $('#clearFilterBtn').on('click', function() {
        setTimeout(loadPurchaseSummary, 100);
    });
});

// Make function globally available for refresh after add/edit/delete
window.loadPurchaseSummary = loadPurchaseSummary;