let rawMaterialSaleTable = null;
let rawMaterialSalesTableInitialized = false;

function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatUSD(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(2)) + ' $';
}

function formatIQD(n) {
    if (n === null || n === undefined || n === '' || isNaN(n)) return '-';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

function ensureDefaultDates() {
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    if (fromInput && toInput && !rawMaterialSalesTableInitialized) {
        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentYear = now.getFullYear();
        const fromDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`;
        const toDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate()}`;
        if (!fromInput.value) fromInput.value = fromDate;
        if (!toInput.value) toInput.value = toDate;
    }
}

function loadRawMaterialSalesTable() {
    ensureDefaultDates();
    
    if (rawMaterialSaleTable) {
        rawMaterialSaleTable.ajax.reload(null, false);
        return;
    }
    
    rawMaterialSaleTable = $('#rawMaterialSaleTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '../process/raw_material_sales/select_raw_material_sale.php',
            type: 'GET',
            data: function(d) {
                d.from = document.getElementById('filter_from')?.value || '';
                d.to = document.getElementById('filter_to')?.value || '';
                d.customer_id = document.getElementById('filter_customer')?.value || '';
                d.material_type = document.getElementById('filter_material_type')?.value || '';
            },
            error: function(xhr) {
                console.error('Error loading raw material sales:', xhr?.responseText || xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'نەتوانرا زانیارییەکان بخوێندرێنوە. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        },
        order: [[5, 'desc']],
        columns: [
            { data: 'customer_name', defaultContent: '-' },
            { data: 'recipient', defaultContent: '-' },
            { data: 'location', defaultContent: '-' },
            { data: 'invoice_number', defaultContent: '-' },
            { 
                data: 'material_type', 
                render: function(data) {
                    return materialTypes[data] || data;
                }
            },
            { data: 'order_date', defaultContent: '-' },
            { 
                data: 'quantity', 
                render: function(data) {
                    return data && data !== '' ? formatNumber(data) : '-';
                }
            },
            { data: 'unit', defaultContent: '-' },
            { 
                data: 'price_per_unit', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'total_price', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { data: 'payment_type', defaultContent: '-' },
            { 
                data: 'amount_paid_iq', 
                render: function(data) {
                    return formatIQD(data);
                }
            },
            { 
                data: 'amount_paid_usd', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'remaining_amount', 
                render: function(data) {
                    return formatUSD(data);
                }
            },
            { 
                data: 'dolar_rate', 
                render: function(data) {
                    return data && data !== '' ? formatNumber(data) : '-';
                }
            },
            { data: 'notes', defaultContent: '-' },
            { 
                data: null,
                orderable: false,
                searchable: false,
                render: function(data) {
                    const editBtn = window.userPermissions && window.userPermissions.canEdit
                        ? `<button class='btn btn-warning btn-sm edit-raw-material-sale' data-id='${data.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>`
                        : '';
                    const deleteBtn = window.userPermissions && window.userPermissions.canDelete
                        ? `<button class='btn btn-danger btn-sm delete-raw-material-sale' data-id='${data.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`
                        : '';
                    return `${editBtn} ${deleteBtn}`.trim() || '-';
                }
            }
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
                "last": "کۆتا",
                "next": "دواتر",
                "previous": "پێشوو"
            }
        }
    });
    
    rawMaterialSalesTableInitialized = true;
}

// Form submission
$(document).ready(function() {
    // Initialize Select2
    if (typeof initializeSelect2 === 'function') {
        initializeSelect2();
    }
    
    // Load table
    loadRawMaterialSalesTable();
    
    // Filter change handlers
    $('#filter_from, #filter_to, #filter_customer, #filter_material_type').on('change', function() {
        if (rawMaterialSaleTable) {
            rawMaterialSaleTable.ajax.reload();
        }
    });
    
    // Clear filter button
    $('#clearFilterBtn').on('click', function() {
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#filter_customer').val('');
        $('#filter_material_type').val('');
        if (rawMaterialSaleTable) {
            rawMaterialSaleTable.ajax.reload();
        }
    });
    
    // Calculate total price
    $('#rm_quantity, #rm_price_per_unit, #rm_discount').on('input', function() {
        const quantity = parseFloat($('#rm_quantity').val()) || 0;
        const pricePerUnit = parseFloat($('#rm_price_per_unit').val()) || 0;
        const discount = parseFloat($('#rm_discount').val()) || 0;
        const total = (quantity * pricePerUnit) - discount;
        $('#rm_total_price').val(total >= 0 ? total.toFixed(2) : 0);
    });
    
    // Calculate remaining amount
    $('#rm_total_price, #rm_amount_paid_usd, #rm_amount_paid_iq, #rm_dolar_rate').on('input', function() {
        const totalPrice = parseFloat($('#rm_total_price').val()) || 0;
        const amountPaidUSD = parseFloat($('#rm_amount_paid_usd').val()) || 0;
        const amountPaidIQ = parseFloat($('#rm_amount_paid_iq').val()) || 0;
        const dolarRate = parseFloat($('#rm_dolar_rate').val()) || 150000;
        
        const iqdInUSD = amountPaidIQ / (dolarRate / 100);
        const totalPaid = amountPaidUSD + iqdInUSD;
        const remaining = totalPrice - totalPaid;
        
        $('#rm_remaining_amount').val(remaining >= 0 ? remaining.toFixed(2) : 0);
    });
    
    // Refresh dollar rate
    $('#refreshDollarRateRM').on('click', function() {
        $.ajax({
            url: '../process/get_dollar_rate.php',
            type: 'GET',
            success: function(response) {
                if (response.success && response.rate) {
                    $('#rm_dolar_rate').val(response.rate);
                    $('#rm_total_price, #rm_amount_paid_usd, #rm_amount_paid_iq, #rm_dolar_rate').trigger('input');
                }
            }
        });
    });
    
    // Form submission
    $('#addRawMaterialSaleForm').on('submit', function(e) {
        e.preventDefault();
        
        if (submitting) return;
        submitting = true;
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '../process/raw_material_sales/add_raw_material_sale.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                submitting = false;
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: response.message
                    }).then(() => {
                        $('#addRawMaterialSaleModal').modal('hide');
                        $('#addRawMaterialSaleForm')[0].reset();
                        if (rawMaterialSaleTable) {
                            rawMaterialSaleTable.ajax.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message
                    });
                }
            },
            error: function() {
                submitting = false;
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕویدا. تکایە دووبارە هەوڵ بدەوە'
                });
            }
        });
    });
    
    // Set default date
    if (!$('#rm_order_date').val()) {
        $('#rm_order_date').val(new Date().toISOString().split('T')[0]);
    }
});

let submitting = false;

function exportRawMaterialSalesToExcel() {
    const from = $('#filter_from').val() || '';
    const to = $('#filter_to').val() || '';
    const customerId = $('#filter_customer').val() || '';
    const materialType = $('#filter_material_type').val() || '';
    
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);
    if (customerId) params.append('customer_id', customerId);
    if (materialType) params.append('material_type', materialType);
    
    window.location.href = '../process/raw_material_sales/export_raw_material_sales.php?' + params.toString();
}
