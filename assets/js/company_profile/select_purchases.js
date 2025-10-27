function loadPurchases() {
    const url = new URL('../process/company_profile/select_purchases.php', window.location.href);
    url.searchParams.append('id', COMPANY_ID);
    if (typeof currentFilters !== 'undefined') {
        if (currentFilters.from_date) url.searchParams.append('from_date', currentFilters.from_date);
        if (currentFilters.to_date) url.searchParams.append('to_date', currentFilters.to_date);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(purchases => {
            if (purchases.error) {
                document.querySelector('#purchasesTable tbody').innerHTML = `<tr><td colspan="20" class="text-danger">${purchases.error}</td></tr>`;
                return;
            }
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
            const columns = [
                '#', 'company_name', 'location_name', 'driver_name', 'invoice_number', 'material_name', 'date',
                'payment_type', 'type', 'kg', 'price_per_kg_usd', 'price_per_kg_iqd', 'price', 'amount_iqd', 'exchange_rate',
                'paid_usd', 'paid_iqd', 'remaining_usd', 'remaining_iqd'
            ];
            const data = purchases.map((row, idx) => ({
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
                remaining_iqd: formatIQD(row.remaining_iqd)
            }));
            TableController.renderWithPagination('#purchasesTable', data, columns, { pageSize: 10 });
        });
}

document.addEventListener('DOMContentLoaded', function() {
    loadPurchases();
});
