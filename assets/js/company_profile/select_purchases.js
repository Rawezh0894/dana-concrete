let purchasesTable = null;

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
                $('#purchasesTable tbody').html(`<tr><td colspan="20" class="text-danger">${purchases.error}</td></tr>`);
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
            
            // Prepare data for DataTables
            const tableData = purchases.map((row) => [
                row.company_name || '',
                row.location_name || row.location || '',
                row.driver_name || row.driver || '',
                row.invoice_number || '',
                row.material_name || '',
                row.date || '',
                row.payment_type || '',
                row.type || '',
                formatNumber(row.kg),
                formatUSD(row.price_per_kg_usd),
                formatIQD(row.price_per_kg_iqd),
                row.type === 'دینار' ? formatIQD(row.price) : (row.type === 'دۆلار' ? formatUSD(row.price) : formatNumber(row.price)),
                formatIQD(row.amount_iqd),
                formatNumber(row.exchange_rate),
                formatUSD(row.paid_usd),
                formatIQD(row.paid_iqd),
                formatUSD(row.remaining_usd),
                formatIQD(row.remaining_iqd)
            ]);
            
            // Destroy existing table if it exists
            if (purchasesTable) {
                purchasesTable.destroy();
                $('#purchasesTable tbody').empty();
            }
            
            // Initialize DataTable
            purchasesTable = new DataTable('#purchasesTable', {
                data: tableData,
                columns: [
                    { title: 'کۆمپانیا' },
                    { title: 'شوێن' },
                    { title: 'شۆفێر' },
                    { title: 'ژمارەی پسوڵە' },
                    { title: 'مەواد' },
                    { title: 'بەروار' },
                    { title: 'جۆری پارەدان' },
                    { title: 'جۆری دراو' },
                    { title: 'کیلۆگرام' },
                    { title: 'نرخی یەک کیلۆ بە دۆلار' },
                    { title: 'نرخی یەک کیلۆ بە دینار' },
                    { title: 'نرخ' },
                    { title: 'بڕی پارە بە دینار' },
                    { title: 'نرخی 100 دۆلار بە دینار' },
                    { title: 'پارەی دراو بە دۆلار' },
                    { title: 'پارەی دراو بە دینار' },
                    { title: 'پارەی ماوە بە دۆلار' },
                    { title: 'پارەی ماوە بە دینار' }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.3.4/i18n/ckb.json'
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[5, 'desc']] // Sort by date descending
            });
        });
}

document.addEventListener('DOMContentLoaded', function() {
    loadPurchases();
});
