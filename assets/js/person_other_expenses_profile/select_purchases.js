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
        'notes',
        'کردارەکان'
    ];
    
    // Format the data for TableController with expandable functionality
    const formattedData = purchases.map((purchase, index) => ({
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
        notes: purchase.notes || '-',
        actions: `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-info btn-sm" onclick="showPurchaseDetails('${purchase.receipt_number}')" title="پیشاندانی وردەکاری">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="togglePurchaseItems('${purchase.receipt_number}')" title="پیشاندانی کاڵاکان">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        `
    }));
    
    // Use TableController to render with pagination and search
    TableController.renderWithPagination('#purchasesTable', formattedData, columns, {
        pageSize: 10,
        currentPage: 1
    });
}

// Show purchase details in a modal
function showPurchaseDetails(receiptNumber) {
    // Fetch detailed information for this receipt
    $.ajax({
        url: '../process/person_other_expenses_profile/get_purchase_details.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showPurchaseDetailsModal(response.data);
            } else {
                console.error('Error loading purchase details:', response.error);
                alert('هەڵە لە بارکردنی وردەکاری پسووڵە');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
    });
}

// Show purchase details modal
function showPurchaseDetailsModal(purchaseData) {
    const modalContent = `
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-receipt me-2"></i>وردەکاری پسووڵەی ${purchaseData.receipt_number}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">زانیاری سەرەکی:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>ژمارەی پسووڵە:</strong></td><td>${purchaseData.receipt_number}</td></tr>
                        <tr><td><strong>بەروار:</strong></td><td>${purchaseData.purchase_date}</td></tr>
                        <tr><td><strong>جۆری دراو:</strong></td><td>${purchaseData.currency_type}</td></tr>
                        <tr><td><strong>جۆری مامەڵە:</strong></td><td>${purchaseData.payment_type}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">زانیاری پارە:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>کۆی نرخ بە دۆلار:</strong></td><td>$${formatNumber(purchaseData.total_price_usd)}</td></tr>
                        <tr><td><strong>کۆی نرخ بە دینار:</strong></td><td>${formatNumber(purchaseData.total_price_iqd)} د.ع</td></tr>
                        <tr><td><strong>پارەی دراو بە دۆلار:</strong></td><td>$${formatNumber(purchaseData.paid_amount_usd)}</td></tr>
                        <tr><td><strong>پارەی دراو بە دینار:</strong></td><td>${formatNumber(purchaseData.paid_amount_iqd)} د.ع</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-warning">ماوە:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>ماوە بە دۆلار:</strong></td><td>$${formatNumber(purchaseData.remaining_amount_usd)}</td></tr>
                        <tr><td><strong>ماوە بە دینار:</strong></td><td>${formatNumber(purchaseData.remaining_amount_iqd)} د.ع</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-info">زانیاری تر:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>ژمارەی کاڵاکان:</strong></td><td>${purchaseData.materials_count}</td></tr>
                        <tr><td><strong>تێبینی:</strong></td><td>${purchaseData.notes || '-'}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Create and show modal
    showModal('وردەکاری پسووڵە', modalContent);
}

// Toggle purchase items display
function togglePurchaseItems(receiptNumber) {
    // Check if items are already shown
    const existingRow = $(`#purchase-items-${receiptNumber.replace(/[^a-zA-Z0-9]/g, '')}`);
    
    if (existingRow.length > 0) {
        // Hide items
        existingRow.remove();
        return;
    }
    
    // Fetch items for this receipt
    $.ajax({
        url: '../process/person_other_expenses_profile/get_purchase_items.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showPurchaseItems(receiptNumber, response.data);
            } else {
                console.error('Error loading purchase items:', response.error);
                alert('هەڵە لە بارکردنی کاڵاکان');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
    });
}

// Show purchase items below the receipt row
function showPurchaseItems(receiptNumber, items) {
    const safeReceiptNumber = receiptNumber.replace(/[^a-zA-Z0-9]/g, '');
    
    // Find the receipt row and insert items below it
    const receiptRow = $(`#purchasesTable tbody tr:contains('${receiptNumber}')`).first();
    
    if (receiptRow.length > 0) {
        const itemsRow = `
            <tr id="purchase-items-${safeReceiptNumber}" class="purchase-items-row">
                <td colspan="14" class="p-0">
                    <div class="purchase-items-content p-3" style="background-color: #f8f9fa; border-left: 4px solid var(--kelly-green);">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-list me-2"></i>کاڵاکانی پسووڵەی ${receiptNumber}
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>کاڵا</th>
                                        <th>بڕ</th>
                                        <th>یەکەی نرخ</th>
                                        <th>کۆی نرخ بە دۆلار</th>
                                        <th>کۆی نرخ بە دینار</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(item => `
                                        <tr>
                                            <td>${item.material_name || '-'}</td>
                                            <td>${item.quantity || '-'}</td>
                                            <td>${item.unit_price || '-'}</td>
                                            <td>$${formatNumber(item.total_price_usd || 0)}</td>
                                            <td>${formatNumber(item.total_price_iqd || 0)} د.ع</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        
        receiptRow.after(itemsRow);
    }
}

// Show modal function
function showModal(title, content) {
    // Remove existing modal if any
    $('.purchase-modal').remove();
    
    const modal = `
        <div class="modal fade purchase-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    ${content}
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modal);
    
    // Show modal
    const modalElement = $('.purchase-modal');
    modalElement.modal('show');
    
    // Remove modal from DOM when hidden
    modalElement.on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// Format number function
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
} 