$(document).ready(function() {
    // Load initial data
    loadSummaryData();
    
    // Set up filter event listeners
    setupFilterListeners();
});

function loadSummaryData() {
    const filters = getCurrentFilters();
    
    // Show loading state
    $('#summary-cards').addClass('opacity-50');
    $('#customerSummaryTable tbody').html(`
        <tr>
            <td colspan="7" class="text-center">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span class="ms-2">چاوەڕوان...</span>
            </td>
        </tr>
    `);
    
    $.ajax({
        url: '../process/summery_concrete_receipts/get_informations.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            $('#summary-cards').removeClass('opacity-50');
            if (response.success) {
                updateSummaryCards(response.summary);
                updateCustomerSummaryTable(response.customer_summary);
            } else {
                showError(response.error || 'هەڵە لە وەرگرتنی داتا');
                updateSummaryCards({
                    total_receipts: 0,
                    total_meter: 0,
                    total_customers: 0,
                    average_meter: 0
                });
                updateCustomerSummaryTable([]);
            }
        },
        error: function(xhr, status, error) {
            $('#summary-cards').removeClass('opacity-50');
            console.error('Error loading summary data:', error);
            console.error('Response:', xhr.responseText);
            showError('هەڵە لە وەرگرتنی داتا');
            updateSummaryCards({
                total_receipts: 0,
                total_meter: 0,
                total_customers: 0,
                average_meter: 0
            });
            updateCustomerSummaryTable([]);
        }
    });
}

function getCurrentFilters() {
    return {
        customer_id: $('#filter_customer_id').val(),
        formulas_id: $('#filter_formulas_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    };
}

function updateSummaryCards(summary) {
    $('#total_receipts').text(summary.total_receipts.toLocaleString());
    $('#total_meter').text(summary.total_meter.toLocaleString());
    $('#total_customers').text(summary.total_customers.toLocaleString());
    $('#average_meter').text(summary.average_meter.toLocaleString());
}

function updateCustomerSummaryTable(customerSummary) {
    const tbody = $('#customerSummaryTable tbody');
    tbody.empty();
    
    if (customerSummary.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    هیچ داتایەک نەدۆزرایەوە
                </td>
            </tr>
        `);
        return;
    }
    
    customerSummary.forEach((customer, index) => {
        const formulasHtml = customer.formulas_used.map(formula => 
            `<span class="formula-badge me-1">${formula}</span>`
        ).join('');
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <strong>${customer.customer_name}</strong>
                    ${customer.mobile1 ? `<br><small class="text-muted">${customer.mobile1}</small>` : ''}
                </td>
                <td class="text-center">
                    <span class="badge bg-primary">${customer.receipt_count}</span>
                </td>
                <td class="text-center">
                    <strong>${customer.total_meter}</strong> م³
                </td>
                <td class="text-center">
                    ${customer.average_meter} م³
                </td>
                <td>
                    ${formulasHtml}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="showCustomerDetails(${customer.customer_id}, '${customer.customer_name}')">
                        <i class="fas fa-eye me-1"></i>وردەکاری
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function showCustomerDetails(customerId, customerName) {
    // Show loading in modal
    $('#customerDetailsContent').html(`
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">چاوەڕوان...</span>
            </div>
            <p class="mt-2">چاوەڕوان...</p>
        </div>
    `);
    
    $('#customerDetailsModal').modal('show');
    
    // Load customer details
    $.ajax({
        url: '../process/summery_concrete_receipts/get_informations.php',
        method: 'GET',
        data: {
            ...getCurrentFilters(),
            get_customer_details: 1,
            customer_id: customerId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.customer_details) {
                displayCustomerDetails(customerName, response.customer_details);
            } else {
                $('#customerDetailsContent').html(`
                    <div class="alert alert-danger">
                        هەڵە لە وەرگرتنی وردەکاری کڕیار
                    </div>
                `);
            }
        },
        error: function() {
            $('#customerDetailsContent').html(`
                <div class="alert alert-danger">
                    هەڵە لە وەرگرتنی وردەکاری کڕیار
                </div>
            `);
        }
    });
}

function displayCustomerDetails(customerName, receipts) {
    let html = `
        <div class="mb-3">
            <h4>وردەکاری کڕیار: ${customerName}</h4>
            <p class="text-muted">کۆی پسووڵەکان: ${receipts.length}</p>
        </div>
    `;
    
    if (receipts.length === 0) {
        html += `
            <div class="alert alert-info">
                هیچ پسووڵەیەک نەدۆزرایەوە بۆ ئەم کڕیارە
            </div>
        `;
    } else {
        html += `
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background: var(--kelly-green); color: white;">
                        <tr>
                            <th>ژمارەی پسووڵە</th>
                            <th>شوێن</th>
                            <th>وەرگر</th>
                            <th>بڕی مەتر سێجا</th>
                            <th>فۆرمۆلا</th>
                            <th>میکسەر</th>
                            <th>پەمپ</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        receipts.forEach(receipt => {
            html += `
                <tr>
                    <td><strong>${receipt.receipt_number}</strong></td>
                    <td>${receipt.location || '-'}</td>
                    <td>${receipt.receiver_name || '-'}</td>
                    <td class="text-center">
                        <span class="badge bg-success">${receipt.meter_amount} م³</span>
                    </td>
                    <td>
                        <span class="formula-badge">${receipt.formula_name || '-'}</span>
                    </td>
                    <td>${receipt.mixer_info || '-'}</td>
                    <td>${receipt.pump_info || '-'}</td>
                    <td>${formatDate(receipt.created_at)}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    $('#customerDetailsContent').html(html);
}

function setupFilterListeners() {
    // Filter inputs
    $('#filter_customer_id, #filter_formulas_id, #filter_date_from, #filter_date_to').on('change', function() {
        loadSummaryData();
    });
    
    // Quick filter buttons
    $('.filter-btn').on('click', function() {
        const filterType = $(this).data('filter');
        applyQuickFilter(filterType);
        loadSummaryData();
    });
}

function applyQuickFilter(filterType) {
    const today = new Date().toISOString().split('T')[0];
    const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    switch(filterType) {
        case 'today':
            $('#filter_date_from').val(today);
            $('#filter_date_to').val(today);
            break;
        case 'yesterday':
            $('#filter_date_from').val(yesterday);
            $('#filter_date_to').val(yesterday);
            break;
        case 'reset':
            $('#filter_customer_id').val('');
            $('#filter_formulas_id').val('');
            $('#filter_date_from').val('');
            $('#filter_date_to').val('');
            break;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-IQ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە',
        text: message,
        confirmButtonText: 'باشە'
    });
}
