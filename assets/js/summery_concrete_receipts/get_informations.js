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
    
    // Calculate colspan based on permissions
    const baseCols = 5; // #, customer name, receipt count, total meter, formulas
    const priceCols = window.userPermissions.canViewPrices ? 2 : 0; // total price, notes
    const paymentCols = 1; // payment status
    const actionCols = 1; // actions
    const totalCols = baseCols + priceCols + paymentCols + actionCols;
    
    if (customerSummary.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="${totalCols}" class="text-center text-muted">
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
        
        // Payment status display
        let paymentStatus;
        switch(customer.payment_status) {
            case 'paid':
                paymentStatus = '<span class="badge bg-success">پارەی داوە</span>';
                break;
            case 'partial':
                paymentStatus = '<span class="badge bg-info">بەشی پارەی داوە</span>';
                break;
            case 'unpaid':
            default:
                paymentStatus = '<span class="badge bg-warning">پارەی نەداوە</span>';
                break;
        }
        
        let row = `
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
        `;
        
        // Add price-related columns only if user has permission
        if (window.userPermissions.canViewPrices) {
            row += `
                <td class="text-center">
                    ${totalPrice}
                </td>
                <td class="notes-cell">
                    ${notesDisplay}
                </td>
            `;
        }
        
        row += `
                <td class="text-center">
                    ${paymentStatus}
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
                    <i class="fas fa-check-square me-1"></i> هەموو
                </button>
                <button class="btn btn-warning btn-sm ms-2" onclick="deselectAllReceipts()">
                    <i class="fas fa-square me-1"></i>هەڵوەشاندنەوە
                </button>
                                 ${window.userPermissions.canSetPrices ? `
                 <button class="btn btn-primary btn-sm ms-2" onclick="openPriceSettingModal()">
                     <i class="fas fa-dollar-sign me-1"></i>دانانی نرخ
                 </button>
                 ` : ''}
                <button class="btn btn-info btn-sm ms-2" onclick="createSaleFromReceipts()">
                    <i class="fas fa-plus me-1"></i>زیادکردنی فرۆشتن
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
                            ${window.userPermissions.canViewPrices ? '<th>نرخی مەتر سێجا</th>' : ''}
                            ${window.userPermissions.canViewPrices ? '<th>تێبینی</th>' : ''}
                            <th>دۆخی پارەدان</th>
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
            
            // Payment status display for individual receipts
            const receiptPaymentStatus = receipt.payment_status === 'paid' ? 
                '<span class="badge bg-success">پارەی داوە</span>' : 
                '<span class="badge bg-warning">پارەی نەداوە</span>';
            
            html += `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="receipt-checkbox" value="${receipt.id}" data-receipt-number="${receipt.receipt_number}" data-receipt-data='${JSON.stringify(receipt)}'>
                    </td>
                    <td><strong>${receipt.receipt_number}</strong></td>
                    <td>${receipt.location || '-'}</td>
                    <td>${receipt.receiver_name || '-'}</td>
                    <td class="text-center">
                        <span class="badge bg-info">${receipt.meter_amount} م³</span>
                    </td>
            `;
            
            // Add price-related columns only if user has permission
            if (window.userPermissions.canViewPrices) {
                html += `
                    <td class="text-center">
                        ${priceDisplay}
                    </td>
                    <td class="notes-cell">${receipt.notes || ''}</td>
                `;
            }
            
            html += `
                    <td class="text-center">
                        ${receiptPaymentStatus}
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
    // Check permission
    if (!window.userPermissions.canSetPrices) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'توانای دەست گەیشتنت نییە بۆ دانانی نرخ',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
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
    // Check permission
    if (!window.userPermissions.canSetPrices) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'توانای دەست گەیشتنت نییە بۆ دانانی نرخ',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    const priceInput = $('#price_per_meter').val();
    const price = priceInput ? parseFloat(priceInput) : null;
    const notes = $('#notes').val();
    const paymentStatus = $('#payment_status').is(':checked') ? 'paid' : 'unpaid';
    const selectedReceipts = $('.receipt-checkbox:checked');
    
    // Check if user is trying to update payment status only
    const isPaymentStatusOnly = !priceInput && !notes.trim();
    
    // If not payment status only, price is required
    if (!isPaymentStatusOnly && (!price || price <= 0)) {
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
    const loadingText = isPaymentStatusOnly ? 
        'دۆخی پارەی دان پاشەکەوت دەکرێت' : 
        'نرخەکان پاشەکەوت دەکرێن';
    
    Swal.fire({
        title: 'چاوەڕوان...',
        text: loadingText,
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
            price_per_meter: price || '',
            notes: notes,
            payment_status: paymentStatus
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                const message = isPaymentStatusOnly ? 
                    'دۆخی پارەی دان بە سەرکەوتوویی پاشەکەوت کرا' : 
                    'نرخەکان بە سەرکەوتوویی پاشەکەوت کران';
                
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو',
                    text: message,
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

// Function to format invoice numbers in a compact way
function formatInvoiceNumbers(numbers) {
    if (!numbers || numbers.length === 0) return '';
    
    // Convert to numbers and sort
    const sortedNumbers = numbers.map(num => parseInt(num)).sort((a, b) => a - b);
    
    const ranges = [];
    let start = sortedNumbers[0];
    let end = sortedNumbers[0];
    
    for (let i = 1; i < sortedNumbers.length; i++) {
        if (sortedNumbers[i] === end + 1) {
            // Continue the range
            end = sortedNumbers[i];
        } else {
            // End current range and start new one
            if (start === end) {
                ranges.push(start.toString());
            } else {
                ranges.push(`${start}-${end}`);
            }
            start = sortedNumbers[i];
            end = sortedNumbers[i];
        }
    }
    
    // Add the last range
    if (start === end) {
        ranges.push(start.toString());
    } else {
        ranges.push(`${start}-${end}`);
    }
    
    return ranges.join(', ');
}

function createSaleFromReceipts() {
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
    
    // Collect data from selected receipts
    const receiptsData = [];
    let totalMeterAmount = 0;
    let invoiceNumbers = [];
    let locations = [];
    let receivers = [];
    let formulas = [];
    let dates = [];
    let pricePerMeter = null;
    let notes = [];
    
    selectedReceipts.each(function() {
        const receiptData = $(this).data('receipt-data');
        receiptsData.push(receiptData);
        
        // Collect unique values
        if (receiptData.receipt_number && !invoiceNumbers.includes(receiptData.receipt_number)) {
            invoiceNumbers.push(receiptData.receipt_number);
        }
        if (receiptData.location && !locations.includes(receiptData.location)) {
            locations.push(receiptData.location);
        }
        if (receiptData.receiver_name && !receivers.includes(receiptData.receiver_name)) {
            receivers.push(receiptData.receiver_name);
        }
        if (receiptData.formula_name && !formulas.includes(receiptData.formula_name)) {
            formulas.push(receiptData.formula_name);
        }
        if (receiptData.created_at) {
            dates.push(receiptData.created_at);
        }
        if (receiptData.price_per_meter && !pricePerMeter) {
            pricePerMeter = receiptData.price_per_meter;
        }
        
        if (receiptData.notes && receiptData.notes.trim() !== '' && !notes.includes(receiptData.notes.trim())) {
            notes.push(receiptData.notes.trim());
        }
        
        totalMeterAmount += parseFloat(receiptData.meter_amount || 0);
    });
    
    // Format invoice numbers using the new function
    const formattedInvoiceNumbers = formatInvoiceNumbers(invoiceNumbers);
    
    // Prepare data for sale form
    const saleData = {
        customer_id: currentCustomerId,
        customer_name: currentCustomerName,
        recipient: receivers.join(', '),
        location: locations.join(', '),
        invoice_number: formattedInvoiceNumbers,
        formula_name: formulas.join(', '),
        order_date: dates.length > 0 ? dates[0].split(' ')[0] : new Date().toISOString().split('T')[0],
        quantity: totalMeterAmount,
        price_per_unit: pricePerMeter || 0,
        total_price: totalMeterAmount * (pricePerMeter || 0),
        notes: notes.join('\n')
    };
    
    // Store data in localStorage for the sale page
    localStorage.setItem('saleFromReceipts', JSON.stringify(saleData));
    
    // Redirect to sale page
    window.location.href = 'add_sale.php';
}

