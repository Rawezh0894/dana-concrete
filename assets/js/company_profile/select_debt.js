let debtTable = null;

function formatDebtAmount(val, currency) {
    if (!val || isNaN(val)) return '-';
    const n = Number(val).toLocaleString('en-US');
    if (currency === 'usd') return n + ' $';
    if (currency === 'iqd') return n + ' د.ع';
    return n;
}

function loadDebts() {
    // Destroy existing table if it exists
    if (debtTable) {
        debtTable.destroy();
        debtTable = null;
        $('#debtTable').empty();
    }
    
    const url = new URL('../process/company_profile/select_debt.php', window.location.href);
    url.searchParams.append('company_id', COMPANY_ID);
    if (typeof currentFilters !== 'undefined') {
        if (currentFilters.from_date) url.searchParams.append('from_date', currentFilters.from_date);
        if (currentFilters.to_date) url.searchParams.append('to_date', currentFilters.to_date);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(debts => {
            if (!debts || debts.length === 0) {
                $('#debtTable').html(`<tr><td colspan="7" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
                return;
            }
            // Prepare data for DataTables
            const tableData = debts.map((debt) => [
                debt.date,
                Number(debt.amount_usd).toLocaleString('en-US') + ' $',
                Number(debt.amount_iqd).toLocaleString('en-US') + ' د.ع',
                Number(debt.discount_usd || 0).toLocaleString('en-US') + ' $',
                Number(debt.dollar_rate).toLocaleString('en-US') + ' د.ع',
                debt.note || '',
                `
                    <button class="btn btn-sm btn-primary me-1 edit-debt-btn"
                        data-id="${debt.id}"
                        data-date="${debt.date}"
                        data-amount_usd="${debt.amount_usd}"
                        data-amount_iqd="${debt.amount_iqd}"
                        data-discount_usd="${debt.discount_usd || 0}"
                        data-dollar_rate="${debt.dollar_rate}"
                        data-note="${debt.note || ''}"
                        title="دەستکاری">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-debt" data-id="${debt.id}" title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                `
            ]);
            
            // Initialize DataTable
            debtTable = new DataTable('#debtTable', {
                data: tableData,
                columns: [
                    { title: 'بەروار' },
                    { title: 'بڕی دۆلار' },
                    { title: 'بڕی دینار' },
                    { title: 'داشکاندن (دۆلار)' },
                    { title: 'نرخی دۆلار' },
                    { title: 'تێبینی' },
                    { title: 'کردارەکان' }
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
                order: [[0, 'desc']], // Sort by date descending
                initComplete: function() {
                    // Add column-specific search inputs
                    this.api().columns().every(function() {
                        const column = this;
                        const header = $(column.header());
                        const title = header.text();
                        header.html('<div>' + title + '</div><input type="text" class="form-control form-control-sm mt-1 column-search" placeholder="گەڕان..." />');
                        
                        $('.column-search', header).on('keyup change', function() {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });
        })
        .catch(error => {
            console.error('Error loading debts:', error);
            $('#debtTable').html(`<tr><td colspan="7" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
        });
}

// Auto-load on tab show
$(document).on('shown.bs.tab', 'button[data-bs-target="#debt"]', loadDebts);

// Also load on page ready if debt tab is active
$(function() {
    if ($('#debt').hasClass('active')) loadDebts();
});
