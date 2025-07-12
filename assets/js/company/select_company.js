function loadCompanies() {
    TableController.showLoading('#companyTable', ['#', 'name', 'opening_debt_usd', 'opening_debt_iqd', 'currency_type', 'actions']);
    fetch('../process/company/select_company.php')
        .then(res => res.json())
        .then(companies => {
            const data = companies.map((c, idx) => ({
                '#': idx + 1,
                name: c.name,
                opening_debt_usd: Number(c.opening_debt_usd).toLocaleString('en-US') + ' $',
                opening_debt_iqd: Number(c.opening_debt_iqd).toLocaleString('en-US') + ' د.ع',
                currency_type: c.currency_type || '',
                actions: `
                    <button class="btn btn-sm btn-primary me-1 edit-company-btn"
                        data-id="${c.id}"
                        data-name="${c.name}"
                        data-debt_usd="${c.debt_usd}"
                        data-debt_iqd="${c.debt_iqd}"
                        data-opening_debt_usd="${c.opening_debt_usd}"
                        data-opening_debt_iqd="${c.opening_debt_iqd}"
                        data-currency_type="${c.currency_type}"
                        title="دەستکاری">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger me-1 delete-company-btn" data-id="${c.id}" title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-info person-company-btn" data-id="${c.id}" title="زانیاری کەس">
                        <i class="fa fa-user"></i>
                    </button>
                `
            }));
            TableController.renderWithPagination('#companyTable', data, ['#', 'name', 'opening_debt_usd', 'opening_debt_iqd', 'currency_type', 'actions'], { pageSize: 10 });
        });
}
document.addEventListener('DOMContentLoaded', function() {
    loadCompanies();
    document.addEventListener('click', function(e) {
        if (e.target.closest('.person-company-btn')) {
            const btn = e.target.closest('.person-company-btn');
            const companyId = btn.getAttribute('data-id');
            window.open(`../pages/company_profile.php?id=${companyId}`, '_blank');
        }
        if (e.target.closest('.edit-company-btn')) {
            const btn = e.target.closest('.edit-company-btn');
            $('#editCompanyId').val(btn.getAttribute('data-id'));
            $('#editName').val(btn.getAttribute('data-name'));
            $('#editDebtUsd').val(btn.getAttribute('data-debt_usd'));
            $('#editDebtIqd').val(btn.getAttribute('data-debt_iqd'));
            $('#editOpeningDebtUsd').val(btn.getAttribute('data-opening_debt_usd'));
            $('#editOpeningDebtIqd').val(btn.getAttribute('data-opening_debt_iqd'));
            $('#editCurrencyType').val(btn.getAttribute('data-currency_type'));
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCompanyModal'));
            modal.show();
        }
    });
});
window.loadCompanies = loadCompanies;
