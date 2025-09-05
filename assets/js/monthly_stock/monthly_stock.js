// Monthly Material Stock Management JavaScript

$(document).ready(function() {
    // Set default month to current month
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    $('#month_year').val(currentMonth);
    
    // Load initial data
    loadStockHistory();
    loadSummaryData();
    
    // Set up event listeners
    $('#filter_bin, #filter_start_date, #filter_end_date').on('change', function() {
        applyFilters();
    });
});

// Map stock history row for table display
function mapStockHistoryRow(row, idx) {
    return {
        'bin_name': row.bin_name || '',
        'material_type': row.material_type || '',
        'amount': formatNumber(row.amount || 0) + ' کیلۆ',
        'total_value': formatNumber(row.total_value || 0) + ' د.ع',
        'average_price': formatNumber(row.average_price || 0) + ' د.ع',
        'month_year': row.month_year || '',
        'recorded_date': formatDate(row.recorded_date),
        'created_by_username': row.created_by_username || 'سیستەم',
        'actions': `
            <button class="btn btn-sm btn-danger" onclick="deleteMonthlyStockRecord(${row.id}, '${row.bin_name}', '${row.month_year}')" title="سڕینەوە">
                <i class="fas fa-trash"></i>
            </button>
        `
    };
}

// Record monthly stock
function recordMonthlyStock() {
    const monthYear = $('#month_year').val();
    
    if (!monthYear) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'تکایە مانگ و ساڵ هەڵبژێرە'
        });
        return;
    }
    
    // Confirm action
    Swal.fire({
        title: 'دڵنیابوونەوە',
        text: `ئایا دڵنیایت کە دەتەوێت بڕی مەوادەکان بۆ مانگی ${monthYear} تۆمار بکەیت؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بەڵێ، تۆمار بکە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'چاوەڕوان بە...',
                text: 'بڕی مەوادەکان تۆمار دەکرێت',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Make AJAX request
            $.ajax({
                url: '../process/monthly_stock/record_monthly_stock.php',
                type: 'POST',
                data: {
                    month_year: monthYear
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload data
                        loadStockHistory();
                        loadSummaryData();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە ڕویدا'
                    });
                }
            });
        }
    });
}

// Load stock history with TableController
function loadStockHistory() {
    const binId = $('#filter_bin').val() || '';
    const startDate = $('#filter_start_date').val() || '';
    const endDate = $('#filter_end_date').val() || '';
    
    // Show loading state
    const columns = ['bin_name', 'material_type', 'amount', 'total_value', 'average_price', 'month_year', 'recorded_date', 'created_by_username', 'actions'];
    TableController.showLoading('#stockHistoryTable', columns);
    
    $.ajax({
        url: '../process/monthly_stock/get_stock_history.php',
        type: 'GET',
        data: {
            bin_id: binId,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data || [];
                const mapped = data.map(mapStockHistoryRow);
                TableController.renderWithPagination('#stockHistoryTable', mapped, columns, { 
                    pageSize: 10,
                    onRenderComplete: function() {
                        // Any additional actions after table render
                    }
                });
            } else {
                TableController.renderWithPagination('#stockHistoryTable', [], columns, { pageSize: 10 });
                Swal.fire('هەڵە!', response.message || 'ناتوانرێت زانیاری بخوێنرێتەوە', 'error');
            }
        },
        error: function() {
            TableController.renderWithPagination('#stockHistoryTable', [], columns, { pageSize: 10 });
            Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا لە کۆنێکتکردن.', 'error');
        }
    });
}



// Load summary data
function loadSummaryData() {
    $.ajax({
        url: '../process/monthly_stock/get_stock_history.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-bins').text(response.current_stock ? response.current_stock.length : 0);
                
                // Count unique months
                const uniqueMonths = response.data ? [...new Set(response.data.map(item => item.month_year))] : [];
                $('#recorded-months').text(uniqueMonths.length);
                
                // Calculate current total amount
                const currentTotalAmount = response.current_stock ? 
                    response.current_stock.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) : 0;
                $('#current-total-amount').text(formatNumber(currentTotalAmount));
                
                // Calculate current total value
                const currentTotalValue = response.current_stock ? 
                    response.current_stock.reduce((sum, item) => sum + parseFloat(item.total_value || 0), 0) : 0;
                $('#current-total-value').text(formatNumber(currentTotalValue) + ' د.ع');
            }
        },
        error: function() {
            console.error('Error loading summary data');
        }
    });
}

// Apply filters
function applyFilters() {
    loadStockHistory();
}

// Clear filters
function clearFilters() {
    $('#filter_bin').val('');
    $('#filter_start_date').val('');
    $('#filter_end_date').val('');
    loadStockHistory();
}

// Export to Excel
function exportToExcel() {
    const binId = $('#filter_bin').val() || '';
    const startDate = $('#filter_start_date').val() || '';
    const endDate = $('#filter_end_date').val() || '';
    
    // Show loading
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'فایلەکە ئامادە دەکرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Create form data
    const formData = new FormData();
    formData.append('bin_id', binId);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('export_type', 'monthly_stock_history');
    
    // Make AJAX request
    fetch('../process/monthly_stock/export_excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Network response was not ok');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `مێژووی_بڕی_مەوادەکان_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو!',
            text: 'فایلەکە بە سەرکەوتوویی ئیکسپۆرت کرا',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(error => {
        console.error('Export error:', error);
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: 'هەڵەیەک لە ئیکسپۆرتکردن هەیە. تکایە دواتر هەوڵ بدەوە'
        });
    });
}

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(num);
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-Arab-IQ');
}

// Delete monthly stock record
function deleteMonthlyStockRecord(recordId, binName, monthYear) {
    // Show confirmation dialog
    Swal.fire({
        title: 'دڵنیابوونەوە',
        html: `
            <div style="text-align: right; direction: rtl;">
                <p>ئایا دڵنیایت کە دەتەوێت ئەم تۆمارە بسڕیتەوە؟</p>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
                    <strong>شوێن:</strong> ${binName}<br>
                    <strong>مانگ:</strong> ${monthYear}
                </div>
                <p style="color: #dc3545; font-weight: bold;">ئەم کردارە ناگەڕێتەوە!</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'بەڵێ، بسڕەرەوە',
        cancelButtonText: 'نەخێر',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'چاوەڕوان بە...',
                text: 'تۆمارەکە سڕایەوە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Make delete request
            $.ajax({
                url: '../process/monthly_stock/delete_monthly_stock.php',
                type: 'POST',
                data: {
                    record_id: recordId
                },
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload data
                        loadStockHistory();
                        loadSummaryData();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە ڕویدا'
                    });
                }
            });
        }
    });
}
