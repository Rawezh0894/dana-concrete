let isDeletingRecipient = false;

$(document).on('click', '.delete-recipient-btn', function () {
    if (!(window.recipientPermissions && window.recipientPermissions.canDelete)) {
        swalAlert('ئاگاداری', 'ڕێگەت پێ نادرێت بۆ سڕینەوە.', 'warning');
        return;
    }

    if (isDeletingRecipient) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }

    const button = $(this);
    const recipientId = button.data('id');
    if (!recipientId) return;

    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'ئایا دەتەوێت ئەم وەرگرە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (!result.isConfirmed) return;

        isDeletingRecipient = true;
        const originalBtnText = button.html();
        button.prop('disabled', true);
        button.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

        $.ajax({
            url: '../process/recipients/delete.php',
            method: 'POST',
            data: { id: recipientId },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    Swal.fire('سڕایەوە', data.message || 'وەرگر سڕایەوە.', 'success');
                    const handledReload = typeof loadRecipients === 'function';
                    if (handledReload) loadRecipients();
                    $(document).trigger('recipientDeleted', [{ skipReload: handledReload }]);
                } else {
                    Swal.fire('هەڵە', data.message || 'نەتوانرا وەرگر بسڕدرێتەوە.', 'error');
                }
            },
            error: function () {
                Swal.fire('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
            },
            complete: function () {
                isDeletingRecipient = false;
                button.prop('disabled', false);
                button.html(originalBtnText);
            }
        });
    });
});


