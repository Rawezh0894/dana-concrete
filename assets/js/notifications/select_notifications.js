$(document).ready(function() {
    // Kurdish action names
    const actionMap = {
        'insert': 'زیادکردن',
        'update': 'نوێکردنەوە',
        'delete': 'سڕینەوە'
    };

    // Kurdish table names
    const tableMap = {
        'sales': 'فرۆشتن',
        'purchases': 'کڕین',
        'other_expenses': 'خەرجی تر',
        'concrete_receipts': 'پسوڵەی کۆنکرێت',
        'debt_payments': 'پارەدانی قەرزی کۆمپانیا',
        'customer_debt_payments': 'پارەدانی قەرزی کڕیار',
        'cash_box': 'صندوقی نەقد',
        'users': 'بەکارهێنەران',
        'customers': 'کڕیارەکان',
        'company': 'کۆمپانیاکان',
        'employees': 'کارمەندان',
        'materials': 'کاڵاکان',
        'cars': 'ئۆتۆمبێلەکان'
    };

    // Store notifications data globally
    let notificationsData = [];

    async function loadNotifications() {
        const search = $('#notificationSearch').val();
        const type = $('#notificationTypeFilter').val();
        const seen = $('#notificationSeenFilter').val();
        const date_filter = $('#notificationDateFilter').val();
        const pageSize = 10; // Default page size
        
        let url = '../process/notifications/select_notifications.php';
        const params = [];
        if (search) params.push('search=' + encodeURIComponent(search));
        if (type) params.push('type=' + encodeURIComponent(type));
        if (seen) params.push('seen=' + encodeURIComponent(seen));
        if (date_filter) params.push('date_filter=' + encodeURIComponent(date_filter));
        if (params.length) url += '?' + params.join('&');

        try {
            let res = await fetch(url);
            let text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Raw response from select_notifications.php:', text);
                alert('هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.');
                return;
            }

            if (!data.success) {
                TableController.renderWithPagination('#notificationsTable', [], columns, { pageSize: pageSize });
                return;
            }

            // Store notifications data globally
            notificationsData = data.notifications;

            const columns = [
                'select', 'action', 'table_name', 'description', 'username', 'created_at', 'seen', 'actions'
            ];

            const mapped = data.notifications.map((row, idx) => ({
                select: `<input type="checkbox" class="notification-checkbox" value="${row.id}">`,
                action: `<span class="badge bg-${row.action === 'insert' ? 'success' : row.action === 'update' ? 'warning text-dark' : 'danger'}">${row.action_ku}</span>`,
                table_name: `<span class="badge bg-info">${tableMap[row.table_name] || row.table_name}</span>`,
                description: `<div class="text-truncate" style="max-width: 200px;" title="${row.description || '-'}">${row.description || '-'}</div>`,
                username: row.username ? row.username : 'سیستەم',
                created_at: `<small>${row.created_at}</small>`,
                seen: row.seen == 0 ? '<span class="badge bg-warning text-dark">نەخوێندراو</span>' : '<span class="badge bg-secondary">خوێندرا</span>',
                actions: `
                    <button class="btn btn-sm btn-outline-info me-1 view-details-btn" data-index="${idx}" title="بینینی وردەکاری">
                        <i class="fa fa-eye"></i>
                    </button>
                    ${row.seen == 0 ? `<button class="btn btn-sm btn-outline-primary mark-seen-btn" data-id="${row.id}" title="خوێندرا">
                        <i class="fa fa-check"></i>
                    </button>` : ''}
                `
            }));

            TableController.renderWithPagination('#notificationsTable', mapped, columns, { pageSize: pageSize });
            
            // Update total count
            $('#notificationsTotal').html(`گشتی: ${data.total}`);
            
            // Reset select all
            $('#selectAllNotifications').prop('checked', false);
            $('#deleteSelectedNotifications').prop('disabled', true);
        } catch (error) {
            console.error('Error loading notifications:', error);
            TableController.renderWithPagination('#notificationsTable', [], columns, { pageSize: pageSize });
        }
    }

    // Search/filter events
    $('#notificationSearch, #notificationTypeFilter, #notificationSeenFilter, #notificationDateFilter').on('input change', function() {
        loadNotifications();
    });

    // View details
    $(document).on('click', '.view-details-btn', function() {
        const index = parseInt($(this).data('index'));
        const rowData = notificationsData[index];
        if (rowData) {
            showNotificationDetails(rowData);
        } else {
            console.error('Notification data not found for index:', index);
        }
    });

    function showNotificationDetails(notification) {
        // Populate modal with notification data
        $('#modal-action').text(actionMap[notification.action] || notification.action);
        $('#modal-table').text(tableMap[notification.table_name] || notification.table_name);
        $('#modal-record-id').text(notification.record_id || '-');
        $('#modal-username').text(notification.username || 'سیستەم');
        $('#modal-created-at').text(notification.created_at);
        $('#modal-ip').text(notification.ip_address || '-');
        $('#modal-description').text(notification.description || '-');

        // Parse and display old values
        let oldValuesHtml = '<p class="text-muted">هیچ زانیارییەک نییە</p>';
        if (notification.old_values && notification.old_values.trim() !== '') {
            try {
                const oldData = JSON.parse(notification.old_values);
                oldValuesHtml = formatJsonData(oldData);
            } catch (e) {
                oldValuesHtml = `<pre>${notification.old_values}</pre>`;
            }
        }
        $('#modal-old-values').html(oldValuesHtml);

        // Parse and display new values
        let newValuesHtml = '<p class="text-muted">هیچ زانیارییەک نییە</p>';
        if (notification.new_values && notification.new_values.trim() !== '') {
            try {
                const newData = JSON.parse(notification.new_values);
                newValuesHtml = formatJsonData(newData);
            } catch (e) {
                newValuesHtml = `<pre>${notification.new_values}</pre>`;
            }
        }
        $('#modal-new-values').html(newValuesHtml);

        // Display additional info
        let additionalInfoHtml = '<p class="text-muted">هیچ زانیارییەک نییە</p>';
        if (notification.additional_info && notification.additional_info.trim() !== '') {
            try {
                const additionalData = JSON.parse(notification.additional_info);
                additionalInfoHtml = formatJsonData(additionalData);
            } catch (e) {
                additionalInfoHtml = `<pre>${notification.additional_info}</pre>`;
            }
        }
        $('#modal-additional-info').html(additionalInfoHtml);

        // Show modal
        $('#notificationDetailsModal').modal('show');
    }

    function formatJsonData(data) {
        if (typeof data === 'object' && data !== null) {
            let html = '<div class="notification-details">';
            for (const [key, value] of Object.entries(data)) {
                html += `<div><span class="json-key">${key}:</span> <span class="json-value">${value}</span></div>`;
            }
            html += '</div>';
            return html;
        }
        return `<pre>${JSON.stringify(data, null, 2)}</pre>`;
    }

    // Mark as seen
    $(document).on('click', '.mark-seen-btn', function() {
        let id = $(this).data('id');
        $.post('../process/notifications/mark_seen.php', {id}, function(res) {
            loadNotifications();
        });
    });

    // Select all
    $(document).on('change', '#selectAllNotifications', function() {
        $('.notification-checkbox').prop('checked', this.checked);
        $('#deleteSelectedNotifications').prop('disabled', $('.notification-checkbox:checked').length === 0);
    });

    // Enable delete button if any selected
    $(document).on('change', '.notification-checkbox', function() {
        let all = $('.notification-checkbox').length;
        let checked = $('.notification-checkbox:checked').length;
        $('#selectAllNotifications').prop('checked', all === checked && all > 0);
        $('#deleteSelectedNotifications').prop('disabled', checked === 0);
    });

    // Bulk delete
    $('#deleteSelectedNotifications').on('click', function() {
        let ids = $('.notification-checkbox:checked').map(function(){return $(this).val();}).get();
        if (ids.length === 0) return;
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت هەموو هەڵبژێردراوەکان بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../process/notifications/delete_bulk.php',
                    method: 'POST',
                    data: {ids: ids},
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({icon:'success',title:'سڕایەوە',text:'هەموو هەڵبژێردراوەکان سڕایەوە'});
                            loadNotifications();
                        } else {
                            Swal.fire({icon:'error',title:'هەڵە',text:res.error || 'هەڵەیەک ڕویدا!'});
                        }
                    },
                    error: function(xhr, status, err) {
                        Swal.fire({icon:'error',title:'هەڵە',text:'هەڵەیەک ڕویدا!'});
                    }
                });
            }
        });
    });

    // Initial load
    loadNotifications();
}); 