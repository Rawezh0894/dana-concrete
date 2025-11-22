function loadRecipientSales() {
    if (!RECIPIENT_ID || RECIPIENT_ID <= 0) {
        TableController.showError('#recipientSalesTable', 'ناسنامەی وەرگر دروست نییە.');
        return;
    }

    const columns = [
        '#', 'customer_name', 'recipient', 'location', 'invoice_number', 'formula_name', 'order_date', 'payment_type',
        'quantity', 'price_per_unit', 'total_price', 'amount_paid_usd', 'amount_paid_iqd',
        'remaining_amount', 'dolar_rate', 'discount', 'notes'
    ];

    TableController.showLoading('#recipientSalesTable', columns);

    $.get('../process/recipient_profile/select.php', { recipient_id: RECIPIENT_ID }, function(response) {
        if (!response || !response.success || !Array.isArray(response.data)) {
            TableController.showError('#recipientSalesTable', response?.message || 'هەڵە لە وەرگرتنی داتا.');
            return;
        }

        const rows = response.data.map((sale, index) => ({
            '#': index + 1,
            customer_name: sale.customer_name || '-',
            recipient: sale.recipient || '-',
            location: sale.location || '-',
            invoice_number: sale.invoice_number || '-',
            formula_name: sale.formula_name || '-',
            order_date: sale.order_date || '-',
            payment_type: sale.payment_type || '-',
            quantity: formatNumber(sale.quantity, 2),
            price_per_unit: `${formatCurrency(sale.price_per_unit)} $`,
            total_price: `${formatCurrency(sale.total_price)} $`,
            amount_paid_usd: `${formatCurrency(sale.amount_paid_usd)} $`,
            amount_paid_iqd: `${formatInteger(sale.amount_paid_iq)} د.ع`,
            remaining_amount: `${formatCurrency(sale.remaining_amount)} $`,
            dolar_rate: formatInteger(sale.dolar_rate),
            discount: `${formatCurrency(sale.discount)} $`,
            notes: sale.notes || '-'
        }));

        TableController.renderWithPagination('#recipientSalesTable', rows, columns, { pageSize: 10 });
    }, 'json').fail(function() {
        TableController.showError('#recipientSalesTable', 'نەتوانرا پەیوەندی بەنێررێت.');
    });
}

function formatNumber(value, decimals = 0) {
    const number = Number(value) || 0;
    return number.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatCurrency(value) {
    return formatNumber(value, 2);
}

function formatInteger(value) {
    const number = Number(value) || 0;
    return number.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

$(document).ready(function() {
    loadRecipientSales();
});


