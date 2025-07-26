async function loadSalesTable() {
    let res = await fetch('../process/sale/select_sale.php');
    let text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Raw response from select_sale.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
    }
    if (!data.success) {
        TableController.renderWithPagination('#saleTable', [], columns, { pageSize: 10 });
        return;
    }
    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'notes', 'discount', 'actions'
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
    const mapped = data.data.map((row, idx) => ({
        '#': idx + 1,
        customer_name: row.customer_name || '-',
        recipient: row.recipient || '-',
        location: row.location || '-',
        invoice_number: row.invoice_number || '-',
        formula_name: row.formula_name || '-',
        order_date: row.order_date || '-',
        payment_type: row.payment_type || '-',
        quantity: 'M³' + (row.quantity !== null && row.quantity !== undefined && row.quantity !== '' ? formatNumber(row.quantity) : '-'),
        price_per_unit: row.price_per_unit !== null && row.price_per_unit !== undefined && row.price_per_unit !== '' ? formatUSD(row.price_per_unit) : '-',
        total_price: row.total_price !== null && row.total_price !== undefined && row.total_price !== '' ? formatUSD(row.total_price) : '-',
        amount_paid_iq: row.amount_paid_iq !== null && row.amount_paid_iq !== undefined && row.amount_paid_iq !== '' ? formatIQD(row.amount_paid_iq) : '-',
        amount_paid_usd: row.amount_paid_usd !== null && row.amount_paid_usd !== undefined && row.amount_paid_usd !== '' ? formatUSD(row.amount_paid_usd) : '-',
        remaining_amount: row.remaining_amount !== null && row.remaining_amount !== undefined && row.remaining_amount !== '' ? formatUSD(row.remaining_amount) : '-',
        dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
        notes: row.notes || '-',
        discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
        actions: `<button class='btn btn-warning btn-sm edit-sale' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button> <button class='btn btn-danger btn-sm delete-sale' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>`
    }));
    TableController.renderWithPagination('#saleTable', mapped, columns, { pageSize: 10 });
}
document.addEventListener('DOMContentLoaded', loadSalesTable);
window.reloadSales = loadSalesTable;

$(document).ready(function() {
    function loadSales() {
        $.ajax({
            url: '../process/sale/select_sale.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.length === 0) {
                        $('#saleTable tbody').html('<tr><td colspan="18">هیچ فرۆشتنێک نیە</td></tr>');
                        return;
                    }
                    let rows = '';
                    response.data.forEach(function(sale, idx) {
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
                        rows += `<tr>
                            <td>${idx + 1}</td>
                            <td>${sale.customer_name || '-'}</td>
                            <td>${sale.recipient || '-'}</td>
                            <td>${sale.location || '-'}</td>
                            <td>${sale.invoice_number || '-'}</td>
                            <td>${sale.formula_name || '-'}</td>
                            <td>${sale.order_date || '-'}</td>
                            <td>${sale.payment_type || '-'}</td>
                            <td>${sale.quantity !== null && sale.quantity !== undefined && sale.quantity !== '' ? formatNumber(sale.quantity) + ' m³' : '-'}</td>
                            <td>${sale.price_per_unit !== null && sale.price_per_unit !== undefined && sale.price_per_unit !== '' ? formatUSD(sale.price_per_unit) : '-'}</td>
                            <td>${sale.total_price !== null && sale.total_price !== undefined && sale.total_price !== '' ? formatUSD(sale.total_price) : '-'}</td>
                            <td>${sale.amount_paid_iq !== null && sale.amount_paid_iq !== undefined && sale.amount_paid_iq !== '' ? formatIQD(sale.amount_paid_iq) : '-'}</td>
                            <td>${sale.amount_paid_usd !== null && sale.amount_paid_usd !== undefined && sale.amount_paid_usd !== '' ? formatUSD(sale.amount_paid_usd) : '-'}</td>
                            <td>${sale.remaining_amount !== null && sale.remaining_amount !== undefined && sale.remaining_amount !== '' ? formatUSD(sale.remaining_amount) : '-'}</td>
                            <td>${sale.dolar_rate !== null && sale.dolar_rate !== undefined && sale.dolar_rate !== '' ? formatNumber(sale.dolar_rate) : '-'}</td>
                            <td>${sale.notes || '-'}</td>
                            <td>${sale.discount !== null && sale.discount !== undefined && sale.discount !== '' ? formatUSD(sale.discount) : '-'}</td>
                            <td>
                                ${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-sm btn-warning edit-sale' data-id='${sale.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''}
                                ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-sm btn-danger delete-sale' data-id='${sale.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}
                            </td>
                        </tr>`;
                    });
                    $('#saleTable tbody').html(rows);
                } else {
                    $('#saleTable tbody').html('<tr><td colspan="18">هەڵەیەک روویدا</td></tr>');
                }
            },
            error: function() {
                $('#saleTable tbody').html('<tr><td colspan="18">هەڵەیەک روویدا</td></tr>');
            }
        });
    }
    loadSales();
    window.reloadSales = loadSales;
});

async function loadSalesFiltered() {
    const from = document.getElementById('filter_from').value;
    const to = document.getElementById('filter_to').value;
    let url = '../process/sale/select_sale.php';
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
        console.error('Raw response from select_sale.php:', text);
        alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
        return;
    }
    if (!data.success) {
        TableController.renderWithPagination('#saleTable', [], columns, { pageSize: 10 });
        return;
    }
    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'notes', 'discount', 'actions'
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
    const mapped = data.data.map((row, idx) => ({
        '#': idx + 1,
        customer_name: row.customer_name || '-',
        recipient: row.recipient || '-',
        location: row.location || '-',
        invoice_number: row.invoice_number || '-',
        formula_name: row.formula_name || '-',
        order_date: row.order_date || '-',
        payment_type: row.payment_type || '-',
        quantity: 'M³' + (row.quantity !== null && row.quantity !== undefined && row.quantity !== '' ? formatNumber(row.quantity) : '-'),
        price_per_unit: row.price_per_unit !== null && row.price_per_unit !== undefined && row.price_per_unit !== '' ? formatUSD(row.price_per_unit) : '-',
        total_price: row.total_price !== null && row.total_price !== undefined && row.total_price !== '' ? formatUSD(row.total_price) : '-',
        amount_paid_iq: row.amount_paid_iq !== null && row.amount_paid_iq !== undefined && row.amount_paid_iq !== '' ? formatIQD(row.amount_paid_iq) : '-',
        amount_paid_usd: row.amount_paid_usd !== null && row.amount_paid_usd !== undefined && row.amount_paid_usd !== '' ? formatUSD(row.amount_paid_usd) : '-',
        remaining_amount: row.remaining_amount !== null && row.remaining_amount !== undefined && row.remaining_amount !== '' ? formatUSD(row.remaining_amount) : '-',
        dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
        notes: row.notes || '-',
        discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
        actions: `${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-warning btn-sm edit-sale' data-id='${row.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''} ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-danger btn-sm delete-sale' data-id='${row.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}`
    }));
    TableController.renderWithPagination('#saleTable', mapped, columns, { pageSize: 10 });
}

const fromInput = document.getElementById('filter_from');
const toInput = document.getElementById('filter_to');
if (fromInput && toInput) {
    fromInput.addEventListener('input', loadSalesFiltered);
    toInput.addEventListener('input', loadSalesFiltered);
}
const clearBtn = document.getElementById('clearFilterBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        loadSalesFiltered();
    });
}
document.addEventListener('DOMContentLoaded', loadSalesFiltered);
