$(document).ready(function () {
    const addCustomerForm = $('#addCustomerForm');
    if (addCustomerForm.length) {
        let isSubmitting = false;

        addCustomerForm.on('submit', function (e) {
            e.preventDefault();

            if (isSubmitting) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return false;
            }

            isSubmitting = true;
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

            // Same pattern as is_recipient: checkbox name=is_company value=1
            const companyCheckbox = this.querySelector('#customer_is_company');
            const formData = new FormData(this);
            formData.set('is_company', companyCheckbox && companyCheckbox.checked ? '1' : '0');
            formData.set(
                'is_recipient',
                this.querySelector('#customer_is_recipient')?.checked ? '1' : '0'
            );

            $.ajax({
                url: '../process/customer/add_customer.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (data) {
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCustomerModal'));
                    if (data.success) {
                        addCustomerForm[0].reset();
                        $('#customer_is_recipient, #customer_is_company').prop('checked', false);
                        modal.hide();
                        if (typeof reloadCustomers === 'function') {
                            reloadCustomers();
                        } else if (typeof loadCustomers === 'function') {
                            loadCustomers();
                        }
                        if (typeof loadSummaryStats === 'function') loadSummaryStats();
                        swalAlert('سەرکەوتوو', 'کڕیار بەسەرکەوتوویی زیادکرا!', 'success');
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
                    isSubmitting = false;
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalBtnText);
                }
            });
        });
    }
});

function handleOpeningDebtCurrencyChange() {
    const openingDebtUsd = $('#customer_opening_debt_usd');
    const openingDebtIqd = $('#customer_opening_debt_iqd');

    if (openingDebtUsd.length && openingDebtIqd.length) {
        openingDebtUsd.on('input', function () {
            if ($(this).val() > 0) {
                openingDebtIqd.val('');
                openingDebtIqd.prop('disabled', true);
            } else {
                openingDebtIqd.prop('disabled', false);
            }
        });

        openingDebtIqd.on('input', function () {
            if ($(this).val() > 0) {
                openingDebtUsd.val('');
                openingDebtUsd.prop('disabled', true);
            } else {
                openingDebtUsd.prop('disabled', false);
            }
        });
    }
}

function handleEditOpeningDebtCurrencyChange() {
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
}

$(document).ready(function () {
    handleOpeningDebtCurrencyChange();
    handleEditOpeningDebtCurrencyChange();

    $('#customer_is_company').prop('checked', false);

    $('#addCustomerModal').on('show.bs.modal', function () {
        $('#addCustomerForm')[0].reset();
        $('#customer_opening_debt_usd, #customer_opening_debt_iqd').prop('disabled', false);
        $('#customer_is_recipient, #customer_is_company').prop('checked', false);
    });

    $('#editCustomerModal').on('show.bs.modal', function () {
        $('#editCustomerOpeningDebtUsd, #editCustomerOpeningDebtIqd').prop('disabled', false);
    });
});
