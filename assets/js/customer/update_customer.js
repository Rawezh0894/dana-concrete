// Edit button click is handled in ag_grid_customer.js (avoid duplicate handlers)

$(document).ready(function () {
    const editCustomerForm = $('#editCustomerForm');
    if (editCustomerForm.length) {
        let isUpdating = false;

        editCustomerForm.on('submit', function (e) {
            e.preventDefault();

            if (isUpdating) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return false;
            }

            isUpdating = true;
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

            const companyCheckbox = this.querySelector('#editCustomerIsCompany');
            const recipientCheckbox = this.querySelector('#editCustomerIsRecipient');
            const formData = new FormData(this);
            // Force flags explicitly (same approach that works for is_recipient)
            formData.set('is_company', companyCheckbox && companyCheckbox.checked ? '1' : '0');
            formData.set('is_recipient', recipientCheckbox && recipientCheckbox.checked ? '1' : '0');

            // Disabled debt fields are omitted from FormData — re-enable briefly so values are sent
            const usdInput = this.querySelector('#editCustomerOpeningDebtUsd');
            const iqdInput = this.querySelector('#editCustomerOpeningDebtIqd');
            const usdWasDisabled = usdInput?.disabled;
            const iqdWasDisabled = iqdInput?.disabled;
            if (usdInput) usdInput.disabled = false;
            if (iqdInput) iqdInput.disabled = false;
            formData.set('opening_debt_usd', usdInput?.value || '0');
            formData.set('opening_debt_iqd', iqdInput?.value || '0');
            if (usdInput) usdInput.disabled = usdWasDisabled;
            if (iqdInput) iqdInput.disabled = iqdWasDisabled;

            $.ajax({
                url: '../process/customer/update_customer.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (data) {
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCustomerModal'));
                    if (data.success) {
                        modal.hide();
                        if (typeof reloadCustomers === 'function') {
                            reloadCustomers();
                        } else if (typeof loadCustomers === 'function') {
                            loadCustomers();
                        }
                        if (typeof loadSummaryStats === 'function') loadSummaryStats();

                        const companySaved = Number(data.is_company) === 1;
                        const companyRequested = companyCheckbox && companyCheckbox.checked;
                        if (companyRequested && !companySaved) {
                            swalAlert(
                                'ئاگاداری',
                                'نوێکرایەوە، بەڵام is_company لە داتابەیس پاشەکەوت نەبوو. ستوونی is_company بپشکنە.',
                                'warning'
                            );
                        } else {
                            swalAlert('سەرکەوتوو', 'زانیاری کڕیار نوێکرایەوە!', 'success');
                        }
                    } else {
                        swalAlert('هەڵە', data.message || 'هەڵەیەک هەیە', 'error');
                    }
                },
                error: function (xhr) {
                    let msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) msg = parsed.message;
                    } catch (err) { /* ignore */ }
                    swalAlert('هەڵە', msg, 'error');
                },
                complete: function () {
                    isUpdating = false;
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalBtnText);
                }
            });
        });
    }

    const editOpeningDebtUsd = $('#editCustomerOpeningDebtUsd');
    const editOpeningDebtIqd = $('#editCustomerOpeningDebtIqd');

    if (editOpeningDebtUsd.length && editOpeningDebtIqd.length) {
        editOpeningDebtUsd.on('input', function () {
            if ($(this).val() > 0) {
                editOpeningDebtIqd.val('');
                editOpeningDebtIqd.prop('disabled', true);
            } else {
                editOpeningDebtIqd.prop('disabled', false);
            }
        });

        editOpeningDebtIqd.on('input', function () {
            if ($(this).val() > 0) {
                editOpeningDebtUsd.val('');
                editOpeningDebtUsd.prop('disabled', true);
            } else {
                editOpeningDebtUsd.prop('disabled', false);
            }
        });
    }
});
