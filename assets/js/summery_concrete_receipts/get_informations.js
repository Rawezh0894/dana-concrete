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
    
    // Show loading state using TableController
    let columns = [
        '#', 
        'customer_name', 
        'location',
        'receipt_count', 
        'total_meter', 
        'payment_status', 
        'sale_status',
        'formulas', 
        'actions'
    ];
    
    // Add price-related columns if user has permission
    if (window.userPermissions && window.userPermissions.canViewPrices) {
        columns.splice(5, 0, 'total_price', 'notes');
    }
    TableController.showLoading('#customerSummaryTable', columns);
    
    $.ajax({
        url: '../process/summery_concrete_receipts/get_informations.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            $('#summary-cards').removeClass('opacity-50');
            console.log('Backend response:', response);
            if (response.success) {
                console.log('Customer summary data:', response.customer_summary);
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
        sale_status: $('#filter_sale_status').val(),
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
    // Define columns for the table
    let columns = [
        '#', 
        'customer_name', 
        'location',
        'receipt_count', 
        'total_meter', 
        'payment_status', 
        'sale_status',
        'formulas', 
        'actions'
    ];
    
    // Add price-related columns if user has permission
    if (window.userPermissions && window.userPermissions.canViewPrices) {
        columns.splice(5, 0, 'total_price', 'notes');
    }
    
    // Format the data for TableController
    console.log('Columns array:', columns);
    const formattedData = customerSummary.map(customer => {
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

        // Sale status display
        let saleStatusDisplay;
        switch(customer.sale_status) {
            case 'sent':
                saleStatusDisplay = '<span class="badge bg-success">نێردراوە بۆ فرۆشتن</span>';
                break;
            case 'partial':
                saleStatusDisplay = '<span class="badge bg-info">بەشێکی نێردراوە بۆ فرۆشتن</span>';
                break;
            case 'unsent':
            default:
                saleStatusDisplay = '<span class="badge bg-danger">نەنێردراوە بۆ فرۆشتن</span>';
                break;
        }
        
        const formattedRow = {
            customer_name: `
                <strong>${customer.customer_name}</strong>
                ${customer.mobile1 ? `<br><small class="text-muted">${customer.mobile1}</small>` : ''}
            `,
            location: customer.location || '-',
            receipt_count: `<span class="badge bg-primary">${customer.receipt_count}</span>`,
            total_meter: `<strong>${customer.total_meter}</strong> م³`,
            total_price: window.userPermissions.canViewPrices ? totalPrice : '-',
            notes: window.userPermissions.canViewPrices ? notesDisplay : '-',
            payment_status: paymentStatus,
            sale_status: saleStatusDisplay,
            formulas: formulasHtml,
            actions: `
                <button class="btn btn-sm btn-info" onclick="showCustomerDetails(${customer.customer_id}, '${customer.customer_name}')">
                    <i class="fas fa-eye me-1"></i>وردەکاری
                </button>
            `
        };
        console.log('Formatted row sample:', formattedRow);
        return formattedRow;
    });
    
    console.log('Formatted data sample:', formattedData.slice(0, 2));
    
    // Store data globally for pagination
    window.customerSummaryData = formattedData;
    
    // Use TableController to render with pagination and search
    TableController.renderWithPagination('#customerSummaryTable', formattedData, columns, {
        pageSize: 10,
        currentPage: 1
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
                <button class="btn btn-secondary btn-sm ms-2" onclick="copySelectedReceipts()">
                    <i class="fas fa-copy me-1"></i>کۆپی کردن
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="customerReceiptsTable">
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
                            <th>دۆخی فرۆشتن</th>
                            <th>فۆرمۆلا</th>
                            <th>میکسەر</th>
                            <th>پەمپ</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by TableController -->
                    </tbody>
                </table>
            </div>
        `;
    }
    
    $('#customerDetailsContent').html(html);
    
    // If there are receipts, render them using TableController
    if (receipts.length > 0) {
        renderCustomerReceiptsTable(receipts);
    }
}

function renderCustomerReceiptsTable(receipts) {
    // Sort receipts by receipt number (A-001, A-002, etc.)
    receipts.sort((a, b) => {
        // Extract numbers from receipt numbers (A-001 -> 1, A-002 -> 2)
        const aNum = parseInt(a.receipt_number.replace(/[^0-9]/g, '')) || 0;
        const bNum = parseInt(b.receipt_number.replace(/[^0-9]/g, '')) || 0;
        return aNum - bNum;
    });
    
    // Define columns for the table
    const columns = [
        'select', 
        'receipt_number', 
        'location', 
        'receiver_name', 
        'meter_amount', 
        'payment_status', 
        'sale_status',
        'formula_name', 
        'mixer_info', 
        'pump_info', 
        'created_at'
    ];
    if (window.userPermissions.canViewPrices) {
        columns.splice(5, 0, 'price_per_meter', 'notes');
    }
    
    // Format the data for TableController
    const formattedData = receipts.map(receipt => {
        const priceDisplay = receipt.price_per_meter ? 
            `<span class="badge bg-success">$${receipt.price_per_meter.toLocaleString()}</span>` : 
            `<span class="badge bg-secondary">نەدەراوە</span>`;
        
        // Payment status display for individual receipts
        const receiptPaymentStatus = receipt.payment_status === 'paid' ? 
            '<span class="badge bg-success">پارەی داوە</span>' : 
            '<span class="badge bg-warning">پارەی نەداوە</span>';

        // Sale status display for individual receipts
        const receiptSaleStatus = (receipt.is_sold == 1 || receipt.sale_status === 'sent') ? 
            '<span class="badge bg-success">نێردراوە بۆ فرۆشتن</span>' : 
            '<span class="badge bg-danger">نەنێردراوە بۆ فرۆشتن</span>';
        
        return {
            select: `<input type="checkbox" class="receipt-checkbox" value="${receipt.id}" data-receipt-number="${receipt.receipt_number}" data-receipt-data='${JSON.stringify(receipt)}'>`,
            receipt_number: `<strong>${receipt.receipt_number}</strong>`,
            location: receipt.location || '-',
            receiver_name: receipt.receiver_name || '-',
            meter_amount: `<span class="badge bg-info">${receipt.meter_amount} م³</span>`,
            price_per_meter: window.userPermissions.canViewPrices ? priceDisplay : '-',
            notes: window.userPermissions.canViewPrices ? (receipt.notes || '') : '-',
            payment_status: receiptPaymentStatus,
            sale_status: receiptSaleStatus,
            formula_name: `<span class="formula-badge">${receipt.formula_name || '-'}</span>`,
            mixer_info: receipt.mixer_info || '-',
            pump_info: receipt.pump_info || '-',
            created_at: formatDate(receipt.created_at)
        };
    });
    
    // Use TableController to render without pagination (show all data)
    TableController.render('#customerReceiptsTable', formattedData, columns);
}

// Global variables for price setting
let currentCustomerId = null;
let currentCustomerName = null;
let savedPriceData = null; // Store previous price data

function showCustomerDetails(customerId, customerName) {
    currentCustomerId = customerId;
    currentCustomerName = customerName;
    
    // Clear saved price data when switching customers
    savedPriceData = null;
    
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
    // Clear saved price data when deselecting all
    savedPriceData = null;
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
    let commonPrice = null;
    let commonNotes = '';
    let commonPaymentStatus = false;
    
    selectedReceipts.each(function() {
        const receiptNumber = $(this).data('receipt-number');
        const receiptData = $(this).data('receipt-data');
        receiptsList += `<div class="mb-1"><i class="fas fa-receipt me-2"></i>${receiptNumber}</div>`;
        
        // Get common values from selected receipts
        if (receiptData.price_per_meter && !commonPrice) {
            commonPrice = receiptData.price_per_meter;
        }
        if (receiptData.notes && !commonNotes) {
            commonNotes = receiptData.notes;
        }
        if (receiptData.payment_status === 'paid') {
            commonPaymentStatus = true;
        }
    });
    
    $('#selected_receipts_list').html(receiptsList);
    
    // Restore previous data if available, otherwise use common values from selected receipts
    if (savedPriceData) {
        $('#price_per_meter').val(savedPriceData.price_per_meter || '');
        $('#notes').val(savedPriceData.notes || '');
        $('#payment_status').prop('checked', savedPriceData.payment_status || false);
    } else {
        $('#price_per_meter').val(commonPrice || '');
        $('#notes').val(commonNotes || '');
        $('#payment_status').prop('checked', commonPaymentStatus);
    }
    
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
                
                // Save current form data for next time
                savedPriceData = {
                    price_per_meter: price || '',
                    notes: notes,
                    payment_status: paymentStatus === 'paid'
                };
                
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
    $('#filter_customer_id, #filter_formulas_id, #filter_sale_status, #filter_date_from, #filter_date_to').on('change', function() {
        loadSummaryData();
    });
    
    // Quick filter buttons
    $('.filter-btn').on('click', function() {
        const filterType = $(this).data('filter');
        applyQuickFilter(filterType);
        loadSummaryData();
    });
    
    // Page size selector
    $('#pageSizeSelector').on('change', function() {
        const newPageSize = parseInt($(this).val());
        if (window.customerSummaryData && window.customerSummaryData.length > 0) {
            // Re-render table with new page size
            let columns = [
                '#', 
                'customer_name', 
                'location',
                'receipt_count', 
                'total_meter', 
                'payment_status', 
                'sale_status',
                'formulas', 
                'actions'
            ];
            
            // Add price-related columns if user has permission
            if (window.userPermissions && window.userPermissions.canViewPrices) {
                columns.splice(5, 0, 'total_price', 'notes');
            }
            
            TableController.renderWithPagination('#customerSummaryTable', window.customerSummaryData, columns, {
                pageSize: newPageSize,
                currentPage: 1
            });
        }
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
            $('#filter_sale_status').val('');
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
    // Error occurred - no message display needed
    console.error('Error:', message);
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
    
    // Prepare data for sale form
    const saleData = {
        customer_id: currentCustomerId,
        customer_name: currentCustomerName,
        recipient: receivers.join(', '),
        location: locations.join(', '),
        invoice_number: invoiceNumbers.join(', '),
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

function copySelectedReceipts() {
    const selectedReceipts = $('.receipt-checkbox:checked');
    
    if (selectedReceipts.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'ئاگاداری',
            text: 'تکایە پسووڵەیەک هەڵبژێرە بۆ کۆپی کردن',
            confirmButtonText: 'باشە'
        });
        return;
    }
    
    // Collect only receipt numbers
    const receiptNumbers = [];
    selectedReceipts.each(function() {
        const receiptNumber = $(this).data('receipt-number');
        if (receiptNumber) {
            receiptNumbers.push(receiptNumber);
        }
    });
    
    // Format only receipt numbers for copying (horizontally)
    const copyText = receiptNumbers.join(' ');
    
    // Copy to clipboard
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(copyText).catch(err => {
            console.error('Failed to copy: ', err);
            fallbackCopyTextToClipboard(copyText);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(copyText);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (!successful) {
            throw new Error('Copy command was unsuccessful');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'ناتوانرێت زانیاری کۆپی بکرێت. تکایە بە دەستی کۆپی بکە',
            confirmButtonText: 'باشە'
        });
        
        // Show the text in a modal for manual copying
        showCopyModal(text);
    }
    
    document.body.removeChild(textArea);
}

function showCopyModal(text) {
    Swal.fire({
        title: 'کۆپی کردن بە دەستی',
        html: `
            <div style="text-align: right; direction: rtl;">
                <p>تکایە ئەم داتایە کۆپی بکە:</p>
                <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; direction: ltr; text-align: left;" onclick="this.select()">${text}</textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'دەستپێکردنەوە',
        cancelButtonText: 'داخستن',
        width: '80%'
    });
}

