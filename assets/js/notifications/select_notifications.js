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
        
        // Show loading state
        $('#notificationsList').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">چاوەڕوان بە...</span></div></td></tr>');
        $('#notificationsPagination').html('');
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
                    
                    // Previous button
                    pagHtml += `<button class="btn btn-sm btn-outline-secondary mx-1" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage-1}" aria-label="پەڕەی پێشوو">`;
                    pagHtml += `<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 15L8 10L13 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                    pagHtml += `</button>`;
                    
                    // Page numbers with smart ellipsis
                    let lastEllipsis = false;
                    for (let i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || Math.abs(i - currentPage) <= 2) {
                            pagHtml += `<button class="btn btn-sm ${i === currentPage ? 'btn-success active' : 'btn-outline-secondary'} mx-1" data-page="${i}">${i}</button>`;
                            lastEllipsis = false;
                        } else if (!lastEllipsis && (i === currentPage - 3 || i === currentPage + 3)) {
                            pagHtml += `<span class="mx-1">...</span>`;
                            lastEllipsis = true;
                        }
                    }
                    
                    // Next button
                    pagHtml += `<button class="btn btn-sm btn-outline-secondary mx-1" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage+1}" aria-label="پەڕەی دواتر">`;
                    pagHtml += `<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 5L12 10L7 15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                    pagHtml += `</button>`;
                    
                    pagHtml += `</div>`;
                }
                $('#notificationsPagination').html(pagHtml);
                $('#notificationsTotal').html(`گشتی: ${total} ئاگادارکردنەوە | پەڕە: ${currentPage} لە ${totalPages}`);
                
                // Show/hide go to page container based on total pages
                if (totalPages > 5) {
                    $('#goToPageContainer').show();
                    $('#goToPageInput').attr('max', totalPages);
                } else {
                    $('#goToPageContainer').hide();
                }
                
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
    
    // Go to page functionality
    $('#goToPageBtn').on('click', function() {
        let page = parseInt($('#goToPageInput').val());
        if (!isNaN(page) && page >= 1 && page <= totalPages && page !== currentPage) {
            fetchNotifications(page);
            $('#goToPageInput').val('');
        }
    });
    
    // Go to page on Enter key
    $('#goToPageInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#goToPageBtn').click();
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