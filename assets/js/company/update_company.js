document.addEventListener('DOMContentLoaded', function () {
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
                    // Trigger event to reload grid
                    $(document).trigger('companyUpdated');
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
        $('#editOpeningDebtUsd').val(0).prop('disabled', true);
        $('#editOpeningDebtIqd').prop('disabled', false);
    } else if (currency === 'دۆلار') {
        $('#editOpeningDebtIqd').val(0).prop('disabled', true);
        $('#editOpeningDebtUsd').prop('disabled', false);
    } else {
        $('#editOpeningDebtUsd, #editOpeningDebtIqd').val(0).prop('disabled', true);
    }
}
$('#editCurrencyType').on('change', handleEditCurrencyTypeChange);
