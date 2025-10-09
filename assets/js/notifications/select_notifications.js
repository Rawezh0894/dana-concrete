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
        'cars': 'ئۆتۆمبێلەکان',
        'notes': 'تێبینیەکان'
    };

    // Store notifications data globally
    let notificationsData = [];
    let currentPage = 1;
    let perPage = 100; // Default items per page

    async function loadNotifications(page = 1) {
        currentPage = page;
        const search = $('#notificationSearch').val();
        const type = $('#notificationTypeFilter').val();
        const seen = $('#notificationSeenFilter').val();
        const date_filter = $('#notificationDateFilter').val();
        
        let url = '../process/notifications/select_notifications.php';
        const params = [];
        params.push('page=' + page);
        params.push('limit=' + perPage);
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

            const columns = [
                'select', 'action', 'table_name', 'description', 'username', 'created_at', 'seen', 'actions'
            ];

            if (!data.success) {
                TableController.render('#notificationsTable', [], columns);
                return;
            }

            // Store notifications data globally
            notificationsData = data.notifications;

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

            // Render table without internal pagination (since we're using server-side pagination)
            TableController.render('#notificationsTable', mapped, columns);
            
            // Update total count and pagination info
            if (data.pagination) {
                $('#notificationsTotal').html(`پیشاندانی ${data.notifications.length} لە ${data.pagination.total_records} - پەڕە ${data.pagination.current_page} لە ${data.pagination.total_pages}`);
                renderPagination(data.pagination);
            } else {
                $('#notificationsTotal').html(`گشتی: ${data.total}`);
            }
            
            // Reset select all
            $('#selectAllNotifications').prop('checked', false);
            $('#deleteSelectedNotifications').prop('disabled', true);
        } catch (error) {
            console.error('Error loading notifications:', error);
            const columns = ['select', 'action', 'table_name', 'description', 'username', 'created_at', 'seen', 'actions'];
            TableController.render('#notificationsTable', [], columns);
        }
    }

    function renderPagination(pagination) {
        let paginationHtml = '<nav class="mt-3"><ul class="pagination justify-content-center">';
        
        // Previous button
        if (pagination.has_prev) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">پێشوو</a></li>`;
        } else {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">پێشوو</span></li>`;
        }
        
        // Page numbers (show max 5 pages around current)
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`;
        }
        
        // Next button
        if (pagination.has_next) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">دواتر</a></li>`;
        } else {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">دواتر</span></li>`;
        }
        
        paginationHtml += '</ul></nav>';
        
        // Remove existing pagination
        $('#notificationsTable').closest('.table-responsive').next('nav').remove();
        // Add new pagination
        $('#notificationsTable').closest('.table-responsive').after(paginationHtml);
    }

    // Pagination click handler
    $(document).on('click', '.pagination a.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page) {
            loadNotifications(page);
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        }
    });

    // Search/filter events - reset to page 1
    $('#notificationSearch, #notificationTypeFilter, #notificationSeenFilter, #notificationDateFilter').on('input change', function() {
        loadNotifications(1);
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
                const translatedKey = translateKey(key);
                const formattedValue = formatValue(key, value);
                html += `<div class="detail-row"><span class="json-key">${translatedKey}:</span> <span class="json-value">${formattedValue}</span></div>`;
            }
            html += '</div>';
            return html;
        }
        return `<pre>${JSON.stringify(data, null, 2)}</pre>`;
    }

    function translateKey(key) {
        const translations = {
            // Sales
            'customer_id': 'ناسنامەی کڕیار',
            'customer_name': 'ناوی کڕیار',
            'formula_id': 'ناسنامەی فۆرمۆلا',
            'formula_name': 'ناوی فۆرمۆلا',
            'quantity': 'بڕ',
            'price_per_cubic': 'نرخ بۆ هەر مەتر سێج',
            'total_amount': 'کۆی بڕ',
            'payment_type': 'جۆری پارەدان',
            'amount_paid_usd': 'بڕی پارەدەر بە دۆلار',
            'amount_paid_iq': 'بڕی پارەدەر بە دینار',
            'order_date': 'بەرواری داواکاری',
            'invoice_number': 'ژمارەی پسوڵە',
            'notes': 'تێبینی',
            'recipient': 'وەرگر',
            'location': 'شوێن',
            'remaining_amount': 'بڕی ماوە',
            'discount': 'داشکاندن',

            // Purchases
            'company_id': 'ناسنامەی کۆمپانیا',
            'company_name': 'ناوی کۆمپانیا',
            'material_id': 'ناسنامەی مادە',
            'material_name': 'ناوی مادە',
            'driver': 'شۆفێر',
            'amount_iqd': 'بڕ بە دینار',
            'kg': 'کیلۆگرام',
            'price': 'نرخ',
            'exchange_rate': 'نرخی گۆڕانکاری',
            'type': 'جۆر',
            'paid_usd': 'پارەدەر بە دۆلار',
            'paid_iqd': 'پارەدەر بە دینار',
            'remaining_usd': 'ماوە بە دۆلار',
            'remaining_iqd': 'ماوە بە دینار',
            'bin_id': 'ناسنامەی بن',
            'price_per_kg_iqd': 'نرخ بۆ هەر کیلۆ بە دینار',
            'price_per_kg_usd': 'نرخ بۆ هەر کیلۆ بە دۆلار',
            'date': 'بەروار',

            // Other Expenses
            'person_id': 'ناسنامەی کەس',
            'person_name': 'ناوی کەس',
            'employee_id': 'ناسنامەی کارمەند',
            'employee_name': 'ناوی کارمەند',
            'car_id': 'ناسنامەی سەیارە',
            'car_name': 'ناوی سەیارە',
            'gas_liters': 'لیتر گاز',
            'expense_type': 'جۆری خەرجی',
            'material_quantity': 'بڕی مادە',
            'material_purchase_price_iqd': 'نرخی کڕینی مادە بە دینار',
            'material_purchase_price_usd': 'نرخی کڕینی مادە بە دۆلار',
            'material_total_cost': 'کۆی تێچووی مادە',
            'gas_purchase_price_input': 'نرخی کڕینی گاز',
            'gas_total_cost': 'کۆی تێچووی گاز',
            'currency_type': 'جۆری دراو',

            // Employee Payments
            'salary': 'مووچە',
            'karwanhisabi': 'کاروانحیسابی',
            'bonus': 'پاداشت',
            'total': 'کۆی',
            'pay_month': 'مانگی پارەدان',

            // Concrete Receipts
            'receipt_number': 'ژمارەی پسوڵە',
            'meter_amount': 'بڕ بە مەتر سێج',
            'formulas_id': 'ناسنامەی فۆرمۆلا',
            'pump_car_id': 'ناسنامەی سەیارەی پۆمپ',
            'pump_car_name': 'ناوی سەیارەی پۆمپ',
            'pump_driver_id': 'ناسنامەی شۆفێری پۆمپ',
            'pump_driver_name': 'ناوی شۆفێری پۆمپ',
            'mixer_car_id': 'ناسنامەی سەیارەی مایکسەر',
            'mixer_car_name': 'ناوی سەیارەی مایکسەر',
            'mixer_driver_id': 'ناسنامەی شۆفێری مایکسەر',
            'mixer_driver_name': 'ناوی شۆفێری مایکسەر',
            'receiver_name': 'ناوی وەرگر',

            // Notes
            'customer_name': 'ناوی کڕیار',
            'location': 'شوێن',
            'recipient': 'وەرگر',
            'formula_name': 'ناوی فۆرمۆلا',
            'mixer_car_name': 'ناوی سەیارەی مایکسەر',
            'mixer_driver_name': 'ناوی شۆفێری مایکسەر',
            'pump_car_name': 'ناوی سەیارەی پۆمپ',
            'pump_driver_name': 'ناوی شۆفێری پۆمپ',

            // Additional Info
            'action_type': 'جۆری چالاکی',
            'receipt_type': 'جۆری پسوڵە',
            'amount_m3': 'بڕ بە مەتر سێج',
            'delivery_components': 'کۆمپۆنێنتەکانی گەیاندن',
            'payment_status': 'دۆخی پارەدان',
            'currency_used': 'دراوی بەکارهێنراو',
            'total_paid': 'کۆی پارەدەر',
            'remaining_debt': 'قەرزی ماوە',
            'expense_category': 'پۆلێنی خەرجی',
            'payment_components': 'کۆمپۆنێنتەکانی پارەدان',
            'total_amount': 'کۆی بڕ'
        };
        return translations[key] || key;
    }

    function formatValue(key, value) {
        // Handle special cases
        if (key === 'delivery_components' && typeof value === 'object') {
            let html = '<div class="nested-object">';
            for (const [subKey, subValue] of Object.entries(value)) {
                const translatedSubKey = translateKey(subKey);
                html += `<div class="nested-item"><span class="nested-key">${translatedSubKey}:</span> <span class="nested-value">${subValue}</span></div>`;
            }
            html += '</div>';
            return html;
        }

        if (key === 'payment_components' && typeof value === 'object') {
            let html = '<div class="nested-object">';
            for (const [subKey, subValue] of Object.entries(value)) {
                const translatedSubKey = translateKey(subKey);
                html += `<div class="nested-item"><span class="nested-key">${translatedSubKey}:</span> <span class="nested-value">${subValue}</span></div>`;
            }
            html += '</div>';
            return html;
        }

        // Handle payment types
        if (key === 'payment_type') {
            const paymentTypes = {
                'نەقد': 'نەقد',
                'قەرز': 'قەرز',
                'cash': 'نەقد',
                'credit': 'قەرز'
            };
            return paymentTypes[value] || value;
        }

        // Handle currency types
        if (key === 'currency_type') {
            const currencyTypes = {
                'دۆلار': 'دۆلار',
                'دینار': 'دینار',
                'USD': 'دۆلار',
                'IQD': 'دینار'
            };
            return currencyTypes[value] || value;
        }

        // Handle expense types
        if (key === 'expense_type') {
            const expenseTypes = {
                'بەکارهێنانی گاز': 'بەکارهێنانی گاز',
                'کڕینی مادە': 'کڕینی مادە',
                'خەرجی تر': 'خەرجی تر',
                'gas_consumption': 'بەکارهێنانی گاز',
                'material_purchase': 'کڕینی مادە',
                'other_expense': 'خەرجی تر'
            };
            return expenseTypes[value] || value;
        }

        // Handle action types
        if (key === 'action_type') {
            const actionTypes = {
                'sale_creation': 'دروستکردنی فرۆشتن',
                'sale_update': 'نوێکردنەوەی فرۆشتن',
                'sale_deletion': 'سڕینەوەی فرۆشتن',
                'purchase_creation': 'دروستکردنی کڕین',
                'purchase_update': 'نوێکردنەوەی کڕین',
                'purchase_deletion': 'سڕینەوەی کڕین',
                'other_expense_creation': 'دروستکردنی خەرجی تر',
                'other_expense_update': 'نوێکردنەوەی خەرجی تر',
                'other_expense_deletion': 'سڕینەوەی خەرجی تر',
                'employee_payment_creation': 'دروستکردنی پارەدانی کارمەند',
                'employee_payment_update': 'نوێکردنەوەی پارەدانی کارمەند',
                'employee_payment_deletion': 'سڕینەوەی پارەدانی کارمەند',
                'concrete_receipt_creation': 'دروستکردنی پسوڵەی کۆنکرێت',
                'concrete_receipt_update': 'نوێکردنەوەی پسوڵەی کۆنکرێت',
                'concrete_receipt_deletion': 'سڕینەوەی پسوڵەی کۆنکرێت',
                'customer_debt_payment': 'پارەدانی قەرزی کڕیار',
                'customer_debt_payment_update': 'نوێکردنەوەی پارەدانی قەرزی کڕیار',
                'customer_debt_payment_deletion': 'سڕینەوەی پارەدانی قەرزی کڕیار',
                'company_debt_payment': 'پارەدانی قەرزی کۆمپانیا',
                'company_debt_payment_update': 'نوێکردنەوەی پارەدانی قەرزی کۆمپانیا',
                'company_debt_payment_deletion': 'سڕینەوەی پارەدانی قەرزی کۆمپانیا'
            };
            return actionTypes[value] || value;
        }

        // Handle receipt types
        if (key === 'receipt_type') {
            const receiptTypes = {
                'concrete_delivery': 'گەیاندنی کۆنکرێت',
                'material_delivery': 'گەیاندنی مادە'
            };
            return receiptTypes[value] || value;
        }

        // Handle payment status
        if (key === 'payment_status') {
            const paymentStatus = {
                'paid': 'پارەدەر',
                'credit': 'قەرز'
            };
            return paymentStatus[value] || value;
        }

        // Format numbers
        if (typeof value === 'number') {
            if (key.includes('amount') || key.includes('price') || key.includes('total') || key.includes('paid') || key.includes('remaining')) {
                return value.toLocaleString('ku-IQ');
            }
        }

        // Default formatting
        if (value === null || value === undefined) {
            return '<span class="text-muted">هیچ</span>';
        }

        return value;
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