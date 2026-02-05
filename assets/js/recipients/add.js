$(document).ready(function () {
    const addRecipientForm = $('#addRecipientForm');
    if (!addRecipientForm.length) return;

    let isSubmitting = false;

    addRecipientForm.off('submit').on('submit', function (e) {
        e.preventDefault();

        if (isSubmitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }

        const name = $('#recipient_name').val().trim();
        const phone1 = $('#recipient_phone1').val().trim();

        if (name === '' && phone1 === '') {
            showAlert('error', 'پێویستە ناو یان ژمارەی مۆبایل بنووسیت.');
            return false;
        }

        isSubmitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

        const formData = new FormData(this);

        $.ajax({
            url: '../process/recipients/add.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                const modalElement = document.getElementById('addRecipientModal');
                const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;

                if (data.success) {
                    addRecipientForm[0].reset();
                    modal?.hide();
                    const handledReload = typeof loadRecipients === 'function';
                    if (handledReload) loadRecipients();
                    $(document).trigger('recipientAdded', [{
                        skipReload: handledReload,
                        recipient: data.recipient || null
                    }]);
                    swalAlert('سەرکەوتوو', data.message || 'وەرگر زیادکرا.', 'success');
                } else {
                    swalAlert('هەڵە', data.message || 'نەتوانرا وەرگر زیادبکرێت.', 'error');
                }
            },
            error: function () {
                swalAlert('هەڵە', 'هەڵەیەک لە پەیوەندی هەیە.', 'error');
            },
            complete: function () {
                isSubmitting = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
});

