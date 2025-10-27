let purchasesTable = null;

function loadPurchases() {
    // Destroy existing table if it exists
    if (purchasesTable) {
        purchasesTable.destroy();
        purchasesTable = null;
        $('#purchasesTable').empty();
    }
    
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
                $('#purchasesTable').html(`<tr><td colspan="18" class="text-danger text-center">${purchases.error}</td></tr>`);
                return;
            }
            
            if (!purchases || purchases.length === 0) {
                $('#purchasesTable').html(`<tr><td colspan="18" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
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
                    },
                    "aria": {
                        "sortAscending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی زیادبوون",
                        "sortDescending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی کەمبوون"
                    }
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[5, 'desc']] // Sort by date descending
            });
        })
        .catch(error => {
            console.error('Error loading purchases:', error);
            $('#purchasesTable').html(`<tr><td colspan="18" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
        });
}

// Load purchases when tab is shown
$(document).on('shown.bs.tab', 'button[data-bs-target="#purchases"]', function() {
    if (!purchasesTable) {
        loadPurchases();
    }
});

// Also load on page ready if purchases tab is active
$(document).ready(function() {
    if ($('#purchases').hasClass('active')) {
        loadPurchases();
    }
});
