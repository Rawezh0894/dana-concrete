function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '';
    return Number(n).toLocaleString();
}
function formatUSD(n) {
    if (!n || isNaN(n)) return '';
    return '$' + formatNumber(Number(n).toFixed(2));
}
function formatIQD(n) {
    if (!n || isNaN(n)) return '';
    return formatNumber(Number(n).toFixed(0)) + ' د.ع';
}

function mapCashBoxRow(row, idx) {
    return {
        '#': idx + 1,
        date: row.date || '',
        type: row.type === 'deposit' ? 'زیادکردن' : (row.type === 'withdraw' ? 'کەمکردنەوە' : ''),
        in_out: `<span class="in-out-cell ${row.type === 'deposit' ? 'in-out-incoming' : 'in-out-outgoing'}">${row.type === 'deposit' ? 'هاتوو' : 'ڕۆشتوو'}</span>`,
        amount_iqd: formatIQD(row.amount_iqd),
        amount_usd: formatUSD(row.amount_usd),
        currency: row.currency || '',
        note: row.note ? `<div style="max-width: 400px; white-space: pre-wrap; word-wrap: break-word; text-align: right;">${row.note}</div>` : '',
        created_by_username: row.created_by_username || '',
        created_at: row.created_at || '',
        actions: `<button class='btn btn-primary btn-sm btn-edit-cashbox' data-id='${row.id}' data-row='${JSON.stringify(row)}'><i class='fa fa-edit'></i></button> <button class='btn btn-danger btn-sm btn-delete-cashbox' data-id='${row.id}'><i class='fa fa-trash'></i></button>`
    };
}

let currentCashBoxPage = 1;

async function loadCashBoxEntriesFiltered(page = 1) {
    currentCashBoxPage = page;
    
    var from = $('#filter_from').val();
    var to = $('#filter_to').val();
    
    // Build request data
    const requestData = new FormData();
    if (from) requestData.append('from', from);
    if (to) requestData.append('to', to);
    requestData.append('page', page);
    requestData.append('limit', 10);
    
    try {
        const response = await fetch('../process/cash_box/select.php', {
            method: 'POST',
            body: requestData
        });
        
        const result = await response.json();
        
        if (result.success) {
            var data = result.data || [];
            var mapped = data.map((row, idx) => mapCashBoxRow(row, ((page - 1) * 10) + idx));
            var columns = ['#', 'date', 'type', 'in_out', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions'];
            
            // Render table without client-side pagination
            TableController.render('#cashBoxTable', mapped, columns);
            
            // Render server-side pagination if available
            if (result.pagination) {
                renderCashBoxPagination(result.pagination, data.length);
            }
        } else {
            TableController.render('#cashBoxTable', [], ['#', 'date', 'type', 'in_out', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions']);
            Swal.fire('هەڵە!', result.error || 'ناتوانرێت زانیاری بخوێنرێتەوە', 'error');
        }
    } catch (error) {
        console.error('Error loading cash box entries:', error);
        TableController.render('#cashBoxTable', [], ['#', 'date', 'type', 'in_out', 'amount_iqd', 'amount_usd', 'currency', 'note', 'created_by_username', 'created_at', 'actions']);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
    }
}

function renderCashBoxPagination(pagination, currentRecordsCount) {
    let paginationHtml = '<nav class="mt-3"><ul class="pagination justify-content-center">';
    
    // Previous button
    if (pagination.has_prev) {
        paginationHtml += `<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="${pagination.current_page - 1}">پێشوو</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">پێشوو</span></li>`;
    }
    
    // Page numbers (show max 5 pages around current)
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHtml += `<li class="page-item"><a class="page-link cashbox-page-link" href="#" data-page="${pagination.current_page + 1}">دواتر</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">دواتر</span></li>`;
    }
    
    paginationHtml += `</ul><p class="text-center text-muted mt-2">پیشاندانی ${currentRecordsCount} لە ${pagination.total_records} - پەڕە ${pagination.current_page} لە ${pagination.total_pages}</p></nav>`;
    
    // Remove existing pagination
    $('#cashBoxTable').closest('.table-responsive').next('nav').remove();
    // Add new pagination
    $('#cashBoxTable').closest('.table-responsive').after(paginationHtml);
}

// Pagination click handler
$(document).on('click', '.cashbox-page-link', function(e) {
    e.preventDefault();
    const page = parseInt($(this).data('page'));
    if (page) {
        loadCashBoxEntriesFiltered(page);
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    }
});

window.loadCashBoxEntries = loadCashBoxEntriesFiltered;

$(document).ready(function() {
    loadCashBoxEntriesFiltered(1);
    $('#filter_from, #filter_to').on('input change', function() {
        loadCashBoxEntriesFiltered(1);
    });
    $('#clearFilterBtn').on('click', function() {
        $('#filter_from').val('');
        $('#filter_to').val('');
        loadCashBoxEntriesFiltered(1);
    });
    
    // Excel export functionality
    $('#exportExcelBtn').on('click', function() {
        exportToExcel();
    });
    
    // Clear all cash box records
    $('#clearAllCashBoxBtn').on('click', function() {
        Swal.fire({
            title: 'ئایا دڵنیایت؟',
            html: '<div style="text-align: right; direction: rtl;">' +
                  '<p style="font-size: 18px; font-weight: bold; color: #dc3545;">ئەم کارە ناگەڕێتەوە!</p>' +
                  '<p>هەموو ریکۆردەکانی قاسە بە تەواوی دەسڕێتەوە.</p>' +
                  '<p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                  'تکایە دڵنیا بە کە دەتەوێت هەموو ریکۆردەکان بسڕیتەوە!</p>' +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'بەڵێ، هەموو ریکۆردەکان بسڕەوە',
            cancelButtonText: 'نەخێر، هەڵوەشێنەوە',
            input: 'text',
            inputPlaceholder: 'تایپ بکە: "دڵنیام" بۆ دڵنیابوون',
            inputValidator: (value) => {
                if (value !== 'دڵنیام') {
                    return 'تکایە "دڵنیام" بنووسە بۆ دڵنیابوون';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value === 'دڵنیام') {
                // Show loading
                Swal.fire({
                    title: 'چاوەڕوان بە...',
                    text: 'ریکۆردەکان دەسڕێنرێنەوە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Clear all records
                $.ajax({
                    url: '../process/cash_box/clear_all.php',
                    method: 'POST',
                    data: { confirm: 'true' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو!',
                                html: `<p>${response.message}</p>`,
                                timer: 3000,
                                showConfirmButton: true
                            }).then(() => {
                                // Reload table and summary
                                loadCashBoxEntriesFiltered(1);
                                if (typeof updateCashBoxSummary === 'function') {
                                    var dates = {
                                        from: $('#filter_from').val() || '',
                                        to: $('#filter_to').val() || ''
                                    };
                                    updateCashBoxSummary(dates.from, dates.to);
                                }
                            });
                        } else {
                            Swal.fire('هەڵە!', response.error || 'هەڵەیەک ڕووی دا', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Clear all error:', xhr, status, error);
                        Swal.fire('هەڵە!', 'هەڵەیەک لە کۆنێکتکردن: ' + error, 'error');
                    }
                });
            }
        });
    });
});

function exportToExcel() {
    var from = $('#filter_from').val();
    var to = $('#filter_to').val();
    var url = '../process/cash_box/export_excel.php';
    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (params.length) url += '?' + params.join('&');
    
    // Show loading state
    var originalText = $('#exportExcelBtn').html();
    $('#exportExcelBtn').html('<i class="fas fa-spinner fa-spin me-1"></i>چاوەڕوان بە...');
    $('#exportExcelBtn').prop('disabled', true);
    
    // Create a temporary link to trigger download
    var link = document.createElement('a');
    link.href = url;
    link.download = 'cash_box_export_' + new Date().toISOString().split('T')[0] + '.xls';
    
    // Add event listener to restore button state after download starts
    link.addEventListener('click', function() {
        setTimeout(function() {
            $('#exportExcelBtn').html(originalText);
            $('#exportExcelBtn').prop('disabled', false);
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: 'فایلەکە بە سەرکەوتوویی ئیکسپۆرت کرا',
                timer: 2000,
                showConfirmButton: false
            });
        }, 1000);
    });
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Fallback: restore button state after a delay
    setTimeout(function() {
        $('#exportExcelBtn').html(originalText);
        $('#exportExcelBtn').prop('disabled', false);
    }, 2000);
}
