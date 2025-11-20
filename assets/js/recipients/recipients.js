$(document).ready(function () {
    $(document).on('recipientAdded recipientUpdated recipientDeleted', function (event, meta) {
        if (meta && meta.skipReload) return;
        if (typeof loadRecipients === 'function') {
            loadRecipients();
        }
    });

    $('#addRecipientModal').on('hidden.bs.modal', function () {
        const form = document.getElementById('addRecipientForm');
        if (form) form.reset();
    });
});


