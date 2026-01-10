let submitting = false;
document.addEventListener('DOMContentLoaded', function () {
    const addCompanyForm = document.getElementById('addCompanyForm');
    if (addCompanyForm) {
        addCompanyForm.addEventListener('submit', function (e) {
            if (submitting) return false;
            submitting = true;
            e.preventDefault();
            const formData = new FormData(addCompanyForm);
            if (!formData.has('opening_debt_usd')) formData.append('opening_debt_usd', 0);
            if (!formData.has('opening_debt_iqd')) formData.append('opening_debt_iqd', 0);
            var currency_type = $('#currency_type').val();
            formData.append('currency_type', currency_type);
            fetch('../process/company/add_company.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCompanyModal'));
                if (data.success) {
                    addCompanyForm.reset();
                    modal.hide();
                    // Trigger event to reload grid
                    $(document).trigger('companyAdded');
                    swalAlert('سەرکەوتوو', 'کۆمپانیا بەسەرکەوتوویی زیادکرا!', 'success');
                    $('#editCurrencyType').val(data.currency_type);
                } else {
                    swalAlert('هەڵە', data.message || 'هەڵەیەک هەیە', 'error');
                }
                submitting = false;
            })
            .catch(() => {
                swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                submitting = false;
            });
        });
    }
});

function handleCurrencyTypeChange() {
    var currency = $('#currency_type').val();
    if (currency === 'دینار') {
        $('#opening_debt_usd').val(0).prop('disabled', true);
        $('#opening_debt_iqd').prop('disabled', false);
    } else if (currency === 'دۆلار') {
        $('#opening_debt_iqd').val(0).prop('disabled', true);
        $('#opening_debt_usd').prop('disabled', false);
    } else {
        $('#opening_debt_usd, #opening_debt_iqd').val(0).prop('disabled', true);
    }
}
$('#currency_type').on('change', handleCurrencyTypeChange);
$('#addCompanyModal').on('show.bs.modal', function() {
    $('#addCompanyForm')[0].reset();
    $('#currency_type').val('');
    handleCurrencyTypeChange();
});
