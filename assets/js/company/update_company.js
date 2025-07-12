document.addEventListener('DOMContentLoaded', function () {
    // Fill modal with company data
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.edit-company-btn')) {
            const btn = e.target.closest('.edit-company-btn');
            document.getElementById('editCompanyId').value = btn.getAttribute('data-id');
            document.getElementById('editName').value = btn.getAttribute('data-name');
            document.getElementById('editDebtUsd').value = btn.getAttribute('data-debt_usd');
            document.getElementById('editDebtIqd').value = btn.getAttribute('data-debt_iqd');
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCompanyModal'));
            modal.show();
        }
    });
    // Handle update submit
    const editCompanyForm = document.getElementById('editCompanyForm');
    if (editCompanyForm) {
        editCompanyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(editCompanyForm);
            if (!formData.has('opening_debt_usd')) formData.append('opening_debt_usd', 0);
            if (!formData.has('opening_debt_iqd')) formData.append('opening_debt_iqd', 0);
            var editCurrencyType = $('#editCurrencyType').val();
            formData.append('currency_type', editCurrencyType);
            fetch('../process/company/update_company.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCompanyModal'));
                if (data.success) {
                    modal.hide();
                    loadCompanies();
                    swalAlert('سەرکەوتوو', 'زانیاری کۆمپانیا نوێکرایەوە!', 'success');
                    $('#editCurrencyType').val(data.currency_type);
                } else {
                    swalAlert('هەڵە', data.message || 'هەڵەیەک هەیە', 'error');
                }
            })
            .catch(() => swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error'));
        });
    }
});

function handleEditCurrencyTypeChange() {
    var currency = $('#editCurrencyType').val();
    if (currency === 'دینار') {
        $('#editOpeningDebtUsd, #editDebtUsd').val(0).prop('disabled', true);
        $('#editOpeningDebtIqd, #editDebtIqd').prop('disabled', false);
    } else if (currency === 'دۆلار') {
        $('#editOpeningDebtIqd, #editDebtIqd').val(0).prop('disabled', true);
        $('#editOpeningDebtUsd, #editDebtUsd').prop('disabled', false);
    } else {
        $('#editOpeningDebtUsd, #editDebtUsd, #editOpeningDebtIqd, #editDebtIqd').val(0).prop('disabled', true);
    }
}
$('#editCurrencyType').on('change', handleEditCurrencyTypeChange);
document.body.addEventListener('click', function (e) {
    if (e.target.closest('.edit-company-btn')) {
        setTimeout(handleEditCurrencyTypeChange, 100);
    }
});
