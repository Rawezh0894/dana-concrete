function formatDebtAmount(val, currency) {
    if (!val || isNaN(val)) return '-';
    const n = Number(val).toLocaleString('en-US');
    if (currency === 'usd') return n + ' $';
    if (currency === 'iqd') return n + ' د.ع';
    return n;
}

function loadDebts() {
    TableController.showLoading('#debtTable', ['#', 'date', 'amount_usd', 'amount_iqd', 'dollar_rate', 'note', 'actions']);
    fetch(`../process/company_profile/select_debt.php?company_id=${COMPANY_ID}`)
        .then(res => res.json())
        .then(debts => {
            const data = debts.map((debt, idx) => ({
                '#': idx + 1,
                date: debt.date,
                amount_usd: Number(debt.amount_usd).toLocaleString('en-US') + ' $',
                amount_iqd: Number(debt.amount_iqd).toLocaleString('en-US') + ' د.ع',
                dollar_rate: Number(debt.dollar_rate).toLocaleString('en-US') + ' د.ع',
                note: debt.note || '',
                actions: `
                    <button class="btn btn-sm btn-primary me-1 edit-debt-btn"
                        data-id="${debt.id}"
                        data-date="${debt.date}"
                        data-amount_usd="${debt.amount_usd}"
                        data-amount_iqd="${debt.amount_iqd}"
                        data-dollar_rate="${debt.dollar_rate}"
                        data-note="${debt.note || ''}"
                        title="دەستکاری">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-debt" data-id="${debt.id}" title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                `
            }));
            TableController.renderWithPagination('#debtTable', data, ['#', 'date', 'amount_usd', 'amount_iqd', 'dollar_rate', 'note', 'actions'], { pageSize: 10 });
        });
}
// Auto-load on tab show
$(document).on('shown.bs.tab', 'button[data-bs-target="#debt"]', loadDebts);
// Also load on page ready if debt tab is active
$(function() {
    if ($('#debt').hasClass('active')) loadDebts();
});
