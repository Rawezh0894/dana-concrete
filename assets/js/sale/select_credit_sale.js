async function loadCreditSales() {
    const from = document.getElementById('filter_from').value;
    const to = document.getElementById('filter_to').value;
    let url = '../process/sale/select_credit_sale.php';
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
        console.error('Raw response from select_credit_sale.php:', text);
        console.error('Server response error. Check console for details.');
        return;
    }
    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'notes', 'discount'
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
        discount: formatUSD(row.discount)
    }));
    TableController.renderWithPagination('#creditSaleTable', mapped, columns, { pageSize: 10 });
}
document.addEventListener('DOMContentLoaded', loadCreditSales);

document.getElementById('filter_from').addEventListener('input', loadCreditSales);
document.getElementById('filter_to').addEventListener('input', loadCreditSales);
document.getElementById('clearFilterBtn').addEventListener('click', function() {
    document.getElementById('filter_from').value = '';
    document.getElementById('filter_to').value = '';
    loadCreditSales();
}); 