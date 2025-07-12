async function loadRecycleBinPurchases() {
    let from = document.getElementById('filter_from')?.value;
    let to = document.getElementById('filter_to')?.value;
    let url = '../process/purchase/select_recycle_bin_purchases.php';
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
        console.error('Raw response from select_recycle_bin_purchases.php:', text);
        Swal.fire({icon:'error',title:'هەڵە',text:'هەڵەیەک لە وەلامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.'});
        return;
    }
    const columns = [
        '#', 'company_name', 'location_name', 'driver_name', 'invoice_number', 'material_name', 'date',
        'payment_type', 'type', 'kg', 'price_per_kg_usd', 'price_per_kg_iqd', 'price', 'amount_iqd', 'exchange_rate',
        'paid_usd', 'paid_iqd', 'remaining_usd', 'remaining_iqd', 'bin_name', 'deleted_at', 'actions'
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
        company_name: row.company_name || '',
        location_name: row.location_name || row.location || '',
        driver_name: row.driver_name || row.driver || '',
        invoice_number: row.invoice_number || '',
        material_name: row.material_name || '',
        date: row.date || '',
        payment_type: row.payment_type || '',
        type: row.type || '',
        kg: formatNumber(row.kg),
        price_per_kg_usd: formatUSD(row.price_per_kg_usd),
        price_per_kg_iqd: formatIQD(row.price_per_kg_iqd),
        price: row.type === 'دینار' ? formatIQD(row.price) : (row.type === 'دۆلار' ? formatUSD(row.price) : formatNumber(row.price)),
        amount_iqd: formatIQD(row.amount_iqd),
        exchange_rate: formatNumber(row.exchange_rate),
        paid_usd: formatUSD(row.paid_usd),
        paid_iqd: formatIQD(row.paid_iqd),
        remaining_usd: formatUSD(row.remaining_usd),
        remaining_iqd: formatIQD(row.remaining_iqd),
        bin_name: row.bin_name || row.bin_id || '',
        deleted_at: row.deleted_at || '',
        actions: `<button class='btn btn-success btn-sm restore-btn' data-id='${row.id}' title='گەڕاندنەوە'><i class='fa fa-undo'></i></button> <button class='btn btn-danger btn-sm delete-btn' data-id='${row.id}' title='سڕینەوەی هەموو'><i class='fa fa-trash'></i></button>`
    }));
    TableController.renderWithPagination('#recycleBinTable', mapped, columns, { pageSize: 10 });
}

document.addEventListener('DOMContentLoaded', loadRecycleBinPurchases);

const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', loadRecycleBinPurchases);
    toInput.addEventListener('input', loadRecycleBinPurchases);
}
const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        loadRecycleBinPurchases();
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
            $.post('../process/purchase/restore_from_recycle_bin.php', {id: id}, function(res) {
                if (res.success) {
                    loadRecycleBinPurchases();
                    Swal.fire({icon:'success',title:'سەرکەوتوو',text:'گەڕاندنەوە سەرکەوتوو بوو'});
                } else {
                    Swal.fire({icon:'error',title:'هەڵە',text:res.msg || 'هەڵەیەک هەیە'});
                }
            }, 'json');
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
            $.post('../process/purchase/delete_from_recycle_bin.php', {id: id}, function(res) {
                if (res.success) {
                    loadRecycleBinPurchases();
                    Swal.fire({icon:'success',title:'سەرکەوتوو',text:'سڕینەوەی هەموو سەرکەوتوو بوو'});
                } else {
                    Swal.fire({icon:'error',title:'هەڵە',text:res.msg || 'هەڵەیەک هەیە'});
                }
            }, 'json');
        }
    });
}); 