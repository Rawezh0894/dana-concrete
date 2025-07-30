async function loadCustomerSalesTable(customerId) {
    let res = await fetch(`../process/customer_profile/select_sale.php?customer_id=${customerId}`);
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
        TableController.renderWithPagination('#salesTable', [], columns, { pageSize: 10 });
        return;
    }
    const columns = [
        '#', 'customer_name', 'location', 'recipient', 'invoice_number', 'formula_name', 'order_date',
        'payment_type', 'quantity', 'price_per_unit', 'total_price', 'amount_paid_iq', 'amount_paid_usd',
        'remaining_amount', 'dolar_rate', 'discount', 'notes'
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
        location: row.location || '-',
        recipient: row.recipient || '-',
        invoice_number: row.invoice_number || '-',
        formula_name: row.formula_name || '-',
        order_date: row.order_date || '-',
        payment_type: row.payment_type || '-',
        quantity: row.quantity !== null && row.quantity !== undefined && row.quantity !== '' ? formatNumber(row.quantity) + ' m³' : '-',
        price_per_unit: row.price_per_unit !== null && row.price_per_unit !== undefined && row.price_per_unit !== '' ? formatUSD(row.price_per_unit) : '-',
        total_price: row.total_price !== null && row.total_price !== undefined && row.total_price !== '' ? formatUSD(row.total_price) : '-',
        amount_paid_iq: row.amount_paid_iq !== null && row.amount_paid_iq !== undefined && row.amount_paid_iq !== '' ? formatIQD(row.amount_paid_iq) : '-',
        amount_paid_usd: row.amount_paid_usd !== null && row.amount_paid_usd !== undefined && row.amount_paid_usd !== '' ? formatUSD(row.amount_paid_usd) : '-',
        remaining_amount: row.remaining_amount !== null && row.remaining_amount !== undefined && row.remaining_amount !== '' ? formatUSD(row.remaining_amount) : '-',
        dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
        discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
        notes: row.notes || '-'
    }));
    TableController.renderWithPagination('#salesTable', mapped, columns, { pageSize: 10 });
}

// Global function to load customer sales (for use in other scripts)
async function loadCustomerSales(customerId) {
    if (!customerId || customerId <= 0) {
        console.error('Invalid customer ID for loading sales:', customerId);
        return;
    }
    await loadCustomerSalesTable(customerId);
}

// Make function globally available
window.loadCustomerSales = loadCustomerSales;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID && CUSTOMER_ID > 0) {
        loadCustomerSalesTable(CUSTOMER_ID);
    } else {
        console.error('Invalid CUSTOMER_ID for loading sales:', CUSTOMER_ID);
    }
});
