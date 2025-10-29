// Purchase Materials History for Person Profile
let purchasesTable = null;

function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatUSD(num) {
    return num ? `$${formatNumber(num)}` : '$0';
}

function formatIQD(num) {
    return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
}

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
    // Destroy existing table if it exists
    if (purchasesTable) {
        purchasesTable.destroy();
        purchasesTable = null;
        $('#purchasesTable').empty();
    }
    
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
                // Create empty DataTable with error message
                purchasesTable = new DataTable('#purchasesTable', {
                    data: [],
                    columns: [
                        { title: 'ژمارەی پسووڵە' },
                        { title: 'بەروار' },
                        { title: 'کۆی کاڵاکان' },
                        { title: 'کۆی نرخ بە دۆلار' },
                        { title: 'کۆی نرخ بە دینار' },
                        { title: 'جۆری دراو' },
                        { title: 'جۆری مامەڵە' },
                        { title: 'پارەی دراو بە دۆلار' },
                        { title: 'پارەی دراو بە دینار' },
                        { title: 'پارەی ماوە بە دۆلار' },
                        { title: 'پارەی ماوە بە دینار' },
                        { title: 'تێبینی' },
                        { title: 'کردارەکان' }
                    ],
                    language: {
                        "processing": "چاوەڕوان بە...",
                        "search": "گەڕان:",
                        "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                        "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                        "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                        "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                        "loadingRecords": "لۆدینگ...",
                        "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                        "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                        "paginate": {
                            "first": "یەکەم",
                            "previous": "پێشوو",
                            "next": "دواتر",
                            "last": "کۆتایی"
                        }
                    },
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    order: [[1, 'desc']],
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                        { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
                    ]
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Create empty DataTable with error message
            purchasesTable = new DataTable('#purchasesTable', {
                data: [],
                columns: [
                    { title: 'ژمارەی پسووڵە' },
                    { title: 'بەروار' },
                    { title: 'کۆی کاڵاکان' },
                    { title: 'کۆی نرخ بە دۆلار' },
                    { title: 'کۆی نرخ بە دینار' },
                    { title: 'جۆری دراو' },
                    { title: 'جۆری مامەڵە' },
                    { title: 'پارەی دراو بە دۆلار' },
                    { title: 'پارەی دراو بە دینار' },
                    { title: 'پارەی ماوە بە دۆلار' },
                    { title: 'پارەی ماوە بە دینار' },
                    { title: 'تێبینی' },
                    { title: 'کردارەکان' }
                ],
                language: {
                    "processing": "چاوەڕوان بە...",
                    "search": "گەڕان:",
                    "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                    "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                    "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                    "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                    "loadingRecords": "لۆدینگ...",
                    "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                    "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                    "paginate": {
                        "first": "یەکەم",
                        "previous": "پێشوو",
                        "next": "دواتر",
                        "last": "کۆتایی"
                    }
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[1, 'desc']],
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
                ]
            });
        }
    });
}

function renderPurchaseMaterialsTable(purchases) {
    if (!purchases || purchases.length === 0) {
        purchasesTable = new DataTable('#purchasesTable', {
            data: [],
            columns: [
                { title: 'ژمارەی پسووڵە' },
                { title: 'بەروار' },
                { title: 'کۆی کاڵاکان' },
                { title: 'کۆی نرخ بە دۆلار' },
                { title: 'کۆی نرخ بە دینار' },
                { title: 'جۆری دراو' },
                { title: 'جۆری مامەڵە' },
                { title: 'پارەی دراو بە دۆلار' },
                { title: 'پارەی دراو بە دینار' },
                { title: 'پارەی ماوە بە دۆلار' },
                { title: 'پارەی ماوە بە دینار' },
                { title: 'تێبینی' },
                { title: 'کردارەکان' }
            ],
            language: {
                "processing": "چاوەڕوان بە...",
                "search": "گەڕان:",
                "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                "loadingRecords": "لۆدینگ...",
                "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                "paginate": {
                    "first": "یەکەم",
                    "previous": "پێشوو",
                    "next": "دواتر",
                    "last": "کۆتایی"
                }
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[1, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
            ]
        });
        return;
    }
    
    // Format the data for DataTables
    const tableData = purchases.map((purchase, index) => [
        purchase.receipt_number || '-',
        purchase.purchase_date || '-',
        purchase.materials_count || 0,
        formatUSD(purchase.total_price_usd || 0),
        formatIQD(purchase.total_price_iqd || 0),
        purchase.currency_type || '-',
        purchase.payment_type || 'نەقد',
        formatUSD(purchase.paid_amount_usd || 0),
        formatIQD(purchase.paid_amount_iqd || 0),
        formatUSD(purchase.remaining_amount_usd || 0),
        formatIQD(purchase.remaining_amount_iqd || 0),
        purchase.notes || '-',
        `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-info btn-sm" onclick="showPurchaseDetails('${purchase.receipt_number}')" title="پیشاندانی وردەکاری">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="togglePurchaseItems('${purchase.receipt_number}')" title="پیشاندانی کاڵاکان">
                    <i class="fas fa-list"></i>
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="debugPurchaseData('${purchase.receipt_number}')" title="پشکنینی داتا">
                    <i class="fas fa-bug"></i>
                </button>
            </div>
        `
    ]);
    
    purchasesTable = new DataTable('#purchasesTable', {
        data: tableData,
        columns: [
            { title: 'ژمارەی پسووڵە' },
            { title: 'بەروار' },
            { title: 'کۆی کاڵاکان' },
            { title: 'کۆی نرخ بە دۆلار' },
            { title: 'کۆی نرخ بە دینار' },
            { title: 'جۆری دراو' },
            { title: 'جۆری مامەڵە' },
            { title: 'پارەی دراو بە دۆلار' },
            { title: 'پارەی دراو بە دینار' },
            { title: 'پارەی ماوە بە دۆلار' },
            { title: 'پارەی ماوە بە دینار' },
            { title: 'تێبینی' },
            { title: 'کردارەکان' }
        ],
        language: {
            "processing": "چاوەڕوان بە...",
            "search": "گەڕان:",
            "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
            "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
            "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
            "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
            "loadingRecords": "لۆدینگ...",
            "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
            "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
            "paginate": {
                "first": "یەکەم",
                "previous": "پێشوو",
                "next": "دواتر",
                "last": "کۆتایی"
            }
        },
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
            { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
            { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
            { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
        ],
        rowCallback: function(row, data) {
            // Store receipt number for expandable rows functionality
            const receiptNumber = data[0];
            $(row).attr('data-receipt-number', receiptNumber);
        }
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
                                        <td>${item.quantity || '-'} ${item.unit_type || ''}</td>
                                        <td>${item.unit_price_display || '-'}</td>
                                        <td>$${formatNumber(item.total_price_usd || 0)}</td>
                                        <td>${formatNumber(item.total_price_iqd || 0)} د.ع</td>
                                    </tr>
                                `).join('')}
                                                            </tbody>
                                <tfoot class="table-info">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>کۆی گشتی:</strong></td>
                                        <td><strong>$${formatNumber(items.reduce((sum, item) => sum + (item.total_price_usd || 0), 0))}</strong></td>
                                        <td><strong>${formatNumber(items.reduce((sum, item) => sum + (item.total_price_iqd || 0), 0))} د.ع</strong></td>
                                    </tr>
                                </tfoot>
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

// Debug purchase data function
function debugPurchaseData(receiptNumber) {
    $.ajax({
        url: '../process/person_other_expenses_profile/debug_purchase_data.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showDebugModal(response.data);
            } else {
                console.error('Error loading debug data:', response.error);
                alert('هەڵە لە بارکردنی داتای پشکنین');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
    });
}

// Show debug modal
function showDebugModal(debugData) {
    const modalContent = `
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-bug me-2"></i>پشکنینی داتای پسووڵەی ${debugData.receipt_number}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary">کۆی گشتی:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>جۆر</th>
                                    <th>بە دۆلار</th>
                                    <th>بە دینار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>کۆی نەخشێندراو</td>
                                    <td>$${formatNumber(debugData.summary.total_stored_usd)}</td>
                                    <td>${formatNumber(debugData.summary.total_stored_iqd)} د.ع</td>
                                </tr>
                                <tr>
                                    <td>کۆی ژمێردراو</td>
                                    <td>$${formatNumber(debugData.summary.total_calculated_usd)}</td>
                                    <td>${formatNumber(debugData.summary.total_calculated_iqd)} د.ع</td>
                                </tr>
                                <tr class="table-warning">
                                    <td>جیاوازی</td>
                                    <td>$${formatNumber(debugData.summary.usd_difference)}</td>
                                    <td>${formatNumber(debugData.summary.iqd_difference)} د.ع</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-info">وردەکاری هەر ئایتمێک:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>کاڵا</th>
                                    <th>بڕ</th>
                                    <th>یەکەی نرخ</th>
                                    <th>کۆی نەخشێندراو</th>
                                    <th>کۆی ژمێردراو</th>
                                    <th>جیاوازی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${debugData.items.map(item => `
                                    <tr class="${item.usd_difference > 0.01 ? 'table-danger' : 'table-success'}">
                                        <td>${item.material_name || '-'}</td>
                                        <td>${item.quantity} ${item.unit_type || ''}</td>
                                        <td>$${formatNumber(item.unit_price_usd)}</td>
                                        <td>$${formatNumber(item.total_price_usd)}</td>
                                        <td>$${formatNumber(item.calculated_total_usd)}</td>
                                        <td>$${formatNumber(item.usd_difference)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-warning" onclick="repairPurchaseData('${debugData.receipt_number}')">
                <i class="fas fa-wrench me-2"></i>چاککردنەوەی داتا
            </button>
            <button type="button" class="btn btn-danger" onclick="fixRemainingAmounts('${debugData.receipt_number}')">
                <i class="fas fa-calculator me-2"></i>چاککردنەوەی بڕی ماوە
            </button>
            <button type="button" class="btn btn-info" onclick="checkRemainingAmounts('${debugData.receipt_number}')">
                <i class="fas fa-search me-2"></i>پشکنینی پارەی ماوە
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
        </div>
    `;
    
    // Create and show modal
    showModal('پشکنینی داتا', modalContent);
}

// Repair purchase data function
function repairPurchaseData(receiptNumber) {
    if (!confirm('دڵنیای لە چاککردنەوەی داتاکان؟ ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە.')) {
        return;
    }
    
    $.ajax({
        url: '../process/person_other_expenses_profile/repair_purchase_data.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('داتاکان بە سەرکەوتووی چاککرانەوە!');
                // Close the debug modal
                $('.purchase-modal').modal('hide');
                // Refresh the purchase items display
                togglePurchaseItems(receiptNumber);
            } else {
                console.error('Error repairing data:', response.error);
                alert('هەڵە لە چاککردنەوەی داتا: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
        });
}

// Check remaining amounts function
function checkRemainingAmounts(receiptNumber) {
    $.ajax({
        url: '../process/person_other_expenses_profile/check_remaining_amounts.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showRemainingAmountsModal(response.data);
            } else {
                console.error('Error checking remaining amounts:', response.error);
                alert('هەڵە لە پشکنینی پارەی ماوەکان');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
    });
}

// Show remaining amounts modal
function showRemainingAmountsModal(checkData) {
    const modalContent = `
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-search me-2"></i>پشکنینی پارەی ماوەی پسوووڵەی ${checkData.receipt_number}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary">کۆی پارەی ماوە:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>جۆر</th>
                                    <th>بە دۆلار</th>
                                    <th>بە دینار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>کۆی نەخشێندراو</td>
                                    <td>$${formatNumber(checkData.summary.total_stored_remaining_usd)}</td>
                                    <td>${formatNumber(checkData.summary.total_stored_remaining_iqd)} د.ع</td>
                                </tr>
                                <tr>
                                    <td>کۆی ژمێردراو</td>
                                    <td>$${formatNumber(checkData.summary.total_calculated_remaining_usd)}</td>
                                    <td>${formatNumber(checkData.summary.total_calculated_remaining_iqd)} د.ع</td>
                                </tr>
                                <tr class="${checkData.summary.has_issues ? 'table-danger' : 'table-success'}">
                                    <td>جیاوازی</td>
                                    <td>$${formatNumber(checkData.summary.total_usd_difference)}</td>
                                    <td>${formatNumber(checkData.summary.total_iqd_difference)} د.ع</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-info">وردەکاری هەر ئایتمێک:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>کاڵا</th>
                                    <th>بڕ</th>
                                    <th>کۆی نرخ</th>
                                    <th>پارەی دراو</th>
                                    <th>پارەی ماوەی نەخشێندراو</th>
                                    <th>پارەی ماوەی ژمێردراو</th>
                                    <th>جیاوازی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${checkData.items.map(item => `
                                    <tr class="${item.usd_difference > 0.01 ? 'table-danger' : 'table-success'}">
                                        <td>${item.material_name || '-'}</td>
                                        <td>${item.quantity} ${item.unit_type || ''}</td>
                                        <td>$${formatNumber(item.total_price_usd)}</td>
                                        <td>$${formatNumber(item.paid_amount_usd)}</td>
                                        <td>$${formatNumber(item.remaining_amount_usd)}</td>
                                        <td>$${formatNumber(item.calculated_remaining_usd)}</td>
                                        <td>$${formatNumber(item.usd_difference)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="fixRemainingAmounts('${checkData.receipt_number}')">
                <i class="fas fa-calculator me-2"></i>چاککردنەوەی پارەی ماوە
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
        </div>
    `;
    
    // Create and show modal
    showModal('پشکنینی پارەی ماوە', modalContent);
}

// Fix remaining amounts function
function fixRemainingAmounts(receiptNumber) {
    if (!confirm('دڵنیای لە چاککردنەوەی پارەی ماوەکان؟ ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە.')) {
        return;
    }
    
    $.ajax({
        url: '../process/person_other_expenses_profile/fix_remaining_amounts.php',
        type: 'GET',
        data: { 
            person_id: PERSON_ID,
            receipt_number: receiptNumber 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('پارەی ماوەکان بە سەرکەوتووی چاککرانەوە!');
                // Close the debug modal
                $('.purchase-modal').modal('hide');
                // Refresh the purchase items display
                togglePurchaseItems(receiptNumber);
                // Refresh the main purchases table
                loadPurchaseMaterialsHistory();
            } else {
                console.error('Error fixing remaining amounts:', response.error);
                alert('هەڵە لە چاککردنەوەی پارەی ماوەکان: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('هەڵە لە پەیوەندی بە سێرڤەر');
        }
    });
} 