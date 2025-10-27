let debtTable = null;

function formatDebtAmount(val, currency) {
    if (!val || isNaN(val)) return '-';
    const n = Number(val).toLocaleString('en-US');
    if (currency === 'usd') return n + ' $';
    if (currency === 'iqd') return n + ' د.ع';
    return n;
}

function loadDebts() {
    // Show loading indicator
    if ($('#debtTable tbody').length > 0) {
        $('#debtTable tbody').html('<tr><td colspan="8" class="text-center text-muted"><span class="spinner-border spinner-border-sm"></span> چاوەڕوان بە...</td></tr>');
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
            
            // Destroy existing table if it exists
            if (debtTable) {
                debtTable.destroy();
                $('#debtTable tbody').empty();
            }
            
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
                    url: '//cdn.datatables.net/plug-ins/2.3.4/i18n/ckb.json'
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'desc']] // Sort by date descending
            });
        });
}

// Auto-load on tab show
$(document).on('shown.bs.tab', 'button[data-bs-target="#debt"]', loadDebts);

// Also load on page ready if debt tab is active
$(function() {
    if ($('#debt').hasClass('active')) loadDebts();
});
