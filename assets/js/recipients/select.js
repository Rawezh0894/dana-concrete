function loadRecipients() {
    const columns = ['#', 'name', 'phone1', 'phone2', 'recipient_type', 'opening_meter_total', 'actions'];
    TableController.showLoading('#recipientsTable', columns);

    const canEdit = !!(window.recipientPermissions && window.recipientPermissions.canEdit);
    const canDelete = !!(window.recipientPermissions && window.recipientPermissions.canDelete);

    $.get('../process/recipients/select.php', function(response) {
        if (response.success && Array.isArray(response.data)) {
            const rows = response.data.map((recipient, index) => {
                const actionButtons = [];

                actionButtons.push(`
                    <a class="btn btn-sm btn-info" href="recipient_profile.php?id=${recipient.id}" title="پرۆفایل">
                        <i class="fas fa-id-card"></i>
                    </a>
                `);

                if (canEdit) {
                    actionButtons.push(`
                        <button class="btn btn-sm btn-primary edit-recipient-btn" data-id="${recipient.id}" title="دەستکاری">
                            <i class="fas fa-edit"></i>
                        </button>
                    `);
                }

                if (canDelete) {
                    actionButtons.push(`
                        <button class="btn btn-sm btn-danger delete-recipient-btn" data-id="${recipient.id}" title="سڕینەوە">
                            <i class="fas fa-trash"></i>
                        </button>
                    `);
                }

                // Determine recipient type
                const recipientType = recipient.recipient_type || 'recipient_only';
                const typeBadge = recipientType === 'customer_and_recipient' 
                    ? '<span class="badge bg-success"><i class="fas fa-user-check"></i> کڕیار و وەرگر</span>'
                    : '<span class="badge bg-info"><i class="fas fa-user"></i> تەنها وەرگر</span>';

                return {
                    '#': index + 1,
                    name: recipient.name || '-',
                    phone1: recipient.phone1 || '-',
                    phone2: recipient.phone2 || '-',
                    recipient_type: typeBadge,
                    opening_meter_total: `${Number(recipient.opening_meter_total || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })} م³`,
                    actions: actionButtons.length ? actionButtons.join(' ') : '<span class="text-muted">-</span>'
                };
            });

            TableController.renderWithPagination(
                '#recipientsTable',
                rows,
                columns,
                { pageSize: 10 }
            );
        } else {
            TableController.showError('#recipientsTable', 'هەڵە لە وەرگرتنی داتای وەرگرەکان.');
        }
    }, 'json').fail(function() {
        TableController.showError('#recipientsTable', 'نەتوانرا پەیوەندی بەنێررێت.');
    });
}

$(document).ready(function() {
    loadRecipients();
});

