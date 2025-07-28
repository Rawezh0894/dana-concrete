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
}

function updateCustomerSummaryTable(customerSummary) {
    const tbody = $('#customerSummaryTable tbody');
    tbody.empty();
    
    if (customerSummary.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center text-muted">
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
        
        const totalPrice = customer.total_price ? 
            `<span class="badge bg-success">$${customer.total_price.toLocaleString()}</span>` : 
            `<span class="badge bg-secondary">نەدەراوە</span>`;
        
        const notesDisplay = customer.latest_notes ? 
            customer.latest_notes : 
            '-';
        
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
                    ${totalPrice}
                </td>
                <td class="notes-cell">
                    ${notesDisplay}
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
            <div class="mb-3">
                <button class="btn btn-success btn-sm" onclick="selectAllReceipts()">
                    <i class="fas fa-check-square me-1"></i>هەڵبژاردنی هەموو
                </button>
                <button class="btn btn-warning btn-sm ms-2" onclick="deselectAllReceipts()">
                    <i class="fas fa-square me-1"></i>هەڵوەشاندنەوەی هەموو
                </button>
                <button class="btn btn-primary btn-sm ms-2" onclick="openPriceSettingModal()">
                    <i class="fas fa-dollar-sign me-1"></i>دانانی نرخ
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background: var(--kelly-green); color: white;">
                        <tr>
                            <th>
                                <input type="checkbox" id="select_all_receipts" onchange="toggleAllReceipts(this)">
                            </th>
                            <th>ژمارەی پسووڵە</th>
                            <th>شوێن</th>
                            <th>وەرگر</th>
                            <th>بڕی مەتر سێجا</th>
                            <th>نرخی مەتر سێجا</th>
                            <th>تێبینی</th>
                            <th>فۆرمۆلا</th>
                            <th>میکسەر</th>
                            <th>پەمپ</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        receipts.forEach((receipt, index) => {
            const priceDisplay = receipt.price_per_meter ? 
                `<span class="badge bg-success">$${receipt.price_per_meter.toLocaleString()}</span>` : 
                `<span class="badge bg-secondary">نەدەراوە</span>`;
            
            html += `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="receipt-checkbox" value="${receipt.id}" data-receipt-number="${receipt.receipt_number}">
                    </td>
                    <td><strong>${receipt.receipt_number}</strong></td>
                    <td>${receipt.location || '-'}</td>
                    <td>${receipt.receiver_name || '-'}</td>
                    <td class="text-center">
                        <span class="badge bg-info">${receipt.meter_amount} م³</span>
                    </td>
                    <td class="text-center">
                        ${priceDisplay}
                    </td>
                    <td class="notes-cell">${receipt.notes || ''}</td>
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

// Global variables for price setting
let currentCustomerId = null;
let currentCustomerName = null;

function showCustomerDetails(customerId, customerName) {
    currentCustomerId = customerId;
    currentCustomerName = customerName;
    
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

// Price setting functions
function toggleAllReceipts(checkbox) {
    $('.receipt-checkbox').prop('checked', checkbox.checked);
}

function selectAllReceipts() {
    $('.receipt-checkbox').prop('checked', true);
    $('#select_all_receipts').prop('checked', true);
}

function deselectAllReceipts() {
    $('.receipt-checkbox').prop('checked', false);
    $('#select_all_receipts').prop('checked', false);
}

function openPriceSettingModal() {
    const selectedReceipts = $('.receipt-checkbox:checked');
    
    if (selectedReceipts.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'ئاگاداری',
            text: 'تکایە پسووڵەیەک هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    // Show selected receipts in the modal
    let receiptsList = '';
    selectedReceipts.each(function() {
        const receiptNumber = $(this).data('receipt-number');
        receiptsList += `<div class="mb-1"><i class="fas fa-receipt me-2"></i>${receiptNumber}</div>`;
    });
    
    $('#selected_receipts_list').html(receiptsList);
    $('#price_per_meter').val('');
    $('#notes').val('');
    $('#priceSettingModal').modal('show');
}

function savePricePerMeter() {
    const price = parseFloat($('#price_per_meter').val());
    const notes = $('#notes').val();
    const selectedReceipts = $('.receipt-checkbox:checked');
    
    if (!price || price <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'تکایە نرخی مەتر سێجا بنووسە',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    if (selectedReceipts.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'ئاگاداری',
            text: 'تکایە پسووڵەیەک هەڵبژێرە',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    const receiptIds = [];
    selectedReceipts.each(function() {
        receiptIds.push($(this).val());
    });
    
    // Show loading
    Swal.fire({
        title: 'چاوەڕوان...',
        text: 'نرخەکان پاشەکەوت دەکرێن',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Save prices
    $.ajax({
        url: '../process/summery_concrete_receipts/update_prices.php',
        method: 'POST',
        data: {
            receipt_ids: receiptIds,
            price_per_meter: price,
            notes: notes
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: 'نرخەکان بە سەرکەوتوویی پاشەکەوت کران',
                    confirmButtonText: 'باشە'
                });
                
                // Close price setting modal
                $('#priceSettingModal').modal('hide');
                
                // Reload customer details to show updated prices
                showCustomerDetails(currentCustomerId, currentCustomerName);
                
                // Reload summary data to update totals
                loadSummaryData();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: response.error || 'هەڵە لە پاشەکەوتکردنی نرخەکان',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵە لە پاشەکەوتکردنی نرخەکان',
                confirmButtonText: 'باشە'
            });
        }
    });
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
            // Don't reset date inputs - keep them as today
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
