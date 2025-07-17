$(document).ready(function() {
    let currentPage = 1;
    let totalPages = 1;
    function fetchNotifications(page = 1) {
        currentPage = page;
        const search = $('#notificationSearch').val();
        const type = $('#notificationTypeFilter').val();
        const seen = $('#notificationSeenFilter').val();
        const date_filter = $('#notificationDateFilter').val();
        const limit = $('#notificationPageSize').val() || 5;
        $.ajax({
            url: '../process/notifications/select_notifications.php',
            method: 'GET',
            data: { search, type, seen, date_filter, page, limit },
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    let msg = res.error ? res.error : 'هەڵەیەک ڕویدا!';
                    $('#notificationsList').html('<tr><td colspan="8"><div class="alert alert-danger">'+msg+'</div></td></tr>');
                    $('#notificationsPagination').html('');
                    Swal.fire({icon:'error',title:'هەڵە',text:msg});
                    return;
                }
                let html = '';
                if (res.notifications.length === 0) {
                    html = '<tr><td colspan="8"><div class="alert alert-info">هیچ ئاگادارکردنەوەیەک نەدۆزرایەوە</div></td></tr>';
                } else {
                    res.notifications.forEach(function(n) {
                        html += `<tr>
                            <td><input type="checkbox" class="notification-checkbox" value="${n.id}"></td>
                            <td><span class="badge bg-${n.action === 'insert' ? 'success' : n.action === 'update' ? 'warning text-dark' : 'danger'}">${n.action_ku}</span></td>
                            <td>${n.table_name}</td>
                            <td class="description-col">${n.description}</td>
                            <td>${n.username ? n.username : 'سیستەم'}</td>
                            <td><small>${n.created_at}</small></td>
                            <td>${n.seen == 0 ? '<span class="badge bg-warning text-dark">نەخوێندراو</span>' : '<span class="badge bg-secondary">خوێندرا</span>'}</td>
                            <td>${n.seen == 0 ? `<button class="btn btn-sm btn-outline-primary mark-seen-btn" data-id="${n.id}">خوێندرا</button>` : ''}</td>
                        </tr>`;
                    });
                }
                $('#notificationsList').html(html);
                // Pagination
                let total = res.total;
                totalPages = Math.ceil(total / limit);
                let pagHtml = '';
                if (totalPages > 1) {
                    pagHtml += `<div class="table-pagination">`;
                    pagHtml += `<button class="prev-btn" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage-1}"><</button>`;
                    for (let i = 1; i <= totalPages; i++) {
                        pagHtml += `<button class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                    }
                    pagHtml += `<button class="next-btn" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage+1}">></button>`;
                    pagHtml += `</div>`;
                }
                $('#notificationsPagination').html(pagHtml);
                $('#notificationsTotal').html(`گشتی: ${total} | پەڕە: ${currentPage} / ${totalPages}`);
                // Reset select all
                $('#selectAllNotifications').prop('checked', false);
                $('#deleteSelectedNotifications').prop('disabled', true);
            },
            error: function(xhr, status, err) {
                $('#notificationsList').html('<tr><td colspan="8"><div class="alert alert-danger">هەڵەیەک ڕویدا!</div></td></tr>');
                $('#notificationsPagination').html('');
                Swal.fire({icon:'error',title:'هەڵە',text:'هەڵەیەک ڕویدا!'});
                console.error('AJAX هەڵە:', err);
            }
        });
    }

    // Kurdish action names
    const actionMap = {
        'insert': 'زیادکردن',
        'update': 'نوێکردنەوە',
        'delete': 'سڕینەوە'
    };

    // Search/filter events
    $('#notificationSearch, #notificationTypeFilter, #notificationSeenFilter, #notificationDateFilter').on('input change', function() {
        fetchNotifications(1);
    });

    // Page size change
    $('#notificationPageSize').on('change', function() {
        fetchNotifications(1);
    });

    // Pagination click
    $(document).on('click', '.table-pagination button', function(e) {
        e.preventDefault();
        let page = parseInt($(this).data('page'));
        if (!isNaN(page) && page !== currentPage && page >= 1 && page <= totalPages) {
            fetchNotifications(page);
        }
    });

    // Mark as seen
    $(document).on('click', '.mark-seen-btn', function() {
        let id = $(this).data('id');
        $.post('../process/notifications/mark_seen.php', {id}, function(res) {
            fetchNotifications(currentPage);
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
                            fetchNotifications(currentPage);
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
    fetchNotifications(1);
}); 