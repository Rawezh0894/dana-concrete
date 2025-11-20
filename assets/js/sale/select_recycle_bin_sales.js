async function loadRecycleBinSales() {
    let from = document.getElementById('filter_from')?.value;
    let to = document.getElementById('filter_to')?.value;
    let url = '../process/sale/select_recycle_bin_sales.php';
    const params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (params.length) url += '?' + params.join('&');
    let res = await fetch(url);
    let text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_recycle_bin_sales.php:', text);
        Swal.fire({icon:'error',title:'هەڵە',text:'هەڵەیەک لە وەلامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.'});
        return;
    }
    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'notes', 'discount', 'deleted_at', 'actions'
    ];
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function formatUSD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(2)) + ' $';
    }
    function formatIQD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(0)) + ' د.ع';
    }
    const mapped = data.map((row, idx) => ({
        '#': idx + 1,
        customer_name: row.customer_name || '',
        recipient: row.recipient || '',
        location: row.location || '',
        invoice_number: row.invoice_number || '',
        formula_name: row.formula_name || '',
        order_date: row.order_date || '',
        payment_type: row.payment_type || '',
        quantity: formatNumber(row.quantity),
        price_per_unit: formatUSD(row.price_per_unit),
        total_price: formatUSD(row.total_price),
        amount_paid_iq: formatIQD(row.amount_paid_iq),
        amount_paid_usd: formatUSD(row.amount_paid_usd),
        remaining_amount: formatUSD(row.remaining_amount),
        dolar_rate: formatNumber(row.dolar_rate),
        notes: row.notes || '',
        discount: row.discount || '',
        deleted_at: row.deleted_at || '',
        actions: `<button class='btn btn-success btn-sm restore-btn' data-id='${row.id}' title='گەڕاندنەوە'><i class='fa fa-undo'></i></button> <button class='btn btn-danger btn-sm delete-btn' data-id='${row.id}' title='سڕینەوەی هەموو'><i class='fa fa-trash'></i></button>`
    }));
    TableController.renderWithPagination('#recycleBinSalesTable', mapped, columns, { pageSize: 10 });
}

document.addEventListener('DOMContentLoaded', loadRecycleBinSales);

const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', loadRecycleBinSales);
    toInput.addEventListener('input', loadRecycleBinSales);
}
const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        loadRecycleBinSales();
    });
}

// Restore and delete actions with Swal
$(document).on('click', '.restore-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'دڵنیایت دەتەوێت ئەم مامەڵەیە گەڕێنیتەوە؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../process/sale/restore_from_recycle_bin.php', {id: id}, function(res) {
                if (res.success) {
                    loadRecycleBinSales();
                    Swal.fire({icon:'success',title:'سەرکەوتوو',text:'گەڕاندنەوە سەرکەوتوو بوو'});
                } else {
                    console.error('Restore sale failed:', res);
                    Swal.fire({icon:'error',title:'هەڵە',text:res.msg || 'هەڵەیەک هەیە'});
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Restore sale request failed:', {status, error, response: xhr?.responseText});
                Swal.fire({icon:'error',title:'هەڵە',text:'هەڵەیەک لە پەیوەندی هەیە'});
            });
        }
    });
});

$(document).on('click', '.delete-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'دڵنیایت دەتەوێت ئەم مامەڵەیە بە تەواوی بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../process/sale/delete_from_recycle_bin.php', {id: id}, function(res) {
                if (res.success) {
                    loadRecycleBinSales();
                    Swal.fire({icon:'success',title:'سەرکەوتوو',text:'سڕینەوەی هەموو سەرکەوتوو بوو'});
                } else {
                    Swal.fire({icon:'error',title:'هەڵە',text:res.msg || 'هەڵەیەک هەیە'});
                }
            }, 'json');
        }
    });
}); 