$(document).on('click', '.edit-recipient-btn', function () {
    if (!(window.recipientPermissions && window.recipientPermissions.canEdit)) {
        swalAlert('ئاگاداری', 'دەستپێڕاگەی دەستکاری کرداری وەرگر نییە.', 'warning');
        return;
    }

    const recipientId = $(this).data('id');
    if (!recipientId) return;

    $.get(`../process/recipients/select.php?id=${recipientId}`, function (response) {
        if (response.success && response.data) {
            const recipient = response.data;
            const modalElement = document.getElementById('editRecipientModal');
            const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;

            $('#editRecipientId').val(recipient.id || '');
            $('#editRecipientName').val(recipient.name || '');
            $('#editRecipientPhone1').val(recipient.phone1 || '');
            $('#editRecipientPhone2').val(recipient.phone2 || '');
            $('#editRecipientOpeningMeter').val(
                recipient.opening_meter_total !== null && recipient.opening_meter_total !== undefined
                    ? Number(recipient.opening_meter_total)
                    : ''
            );

            modal?.show();
        } else {
            swalAlert('هەڵە', response.message || 'نەتوانرا وەرگر بدۆزرێتەوە.', 'error');
        }
    }, 'json').fail(function () {
        swalAlert('هەڵە', 'هەڵەیەک لە پەیوەندی هەیە.', 'error');
    });
});

$(document).ready(function () {
    const editRecipientForm = $('#editRecipientForm');
    if (!editRecipientForm.length) return;

    let isUpdating = false;

    editRecipientForm.on('submit', function (e) {
        e.preventDefault();

        if (!(window.recipientPermissions && window.recipientPermissions.canEdit)) {
            swalAlert('ئاگاداری', 'دەستپێڕاگەی دەستکاری وەرگر نییە.', 'warning');
            return;
        }

        if (isUpdating) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }

        isUpdating = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

        const formData = new FormData(this);

        $.ajax({
            url: '../process/recipients/edit.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                const modalElement = document.getElementById('editRecipientModal');
                const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;

                if (data.success) {
                    modal?.hide();
                    const handledReload = typeof loadRecipients === 'function';
                    if (handledReload) loadRecipients();
                    $(document).trigger('recipientUpdated', [{ skipReload: handledReload }]);
                    swalAlert('سەرکەوتوو', data.message || 'زانیاری وەرگر نوێکرایەوە.', 'success');
                } else {
                    swalAlert('هەڵە', data.message || 'نوێکردنەوە سەرکەوتوو نەبوو.', 'error');
                }
            },
            error: function () {
                swalAlert('هەڵە', 'هەڵەیەک لە پەیوەندی هەیە.', 'error');
            },
            complete: function () {
                isUpdating = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
});


