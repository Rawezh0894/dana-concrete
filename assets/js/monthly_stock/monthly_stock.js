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

// Load stock history
function loadStockHistory() {
    const binId = $('#filter_bin').val() || '';
    const startDate = $('#filter_start_date').val() || '';
    const endDate = $('#filter_end_date').val() || '';
    
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
                displayStockHistory(response.data);
            } else {
                console.error('Error loading stock history:', response.message);
            }
        },
        error: function() {
            console.error('Error loading stock history');
        }
    });
}

// Display stock history in table
function displayStockHistory(data) {
    const tbody = $('#stockHistoryTable tbody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    هیچ تۆمارێک نەدۆزرایەوە
                </td>
            </tr>
        `);
        return;
    }
    
    data.forEach(function(item) {
        const row = `
            <tr>
                <td>${item.bin_name || ''}</td>
                <td>${item.material_type || ''}</td>
                <td class="text-end">${formatNumber(item.amount || 0)}</td>
                <td class="text-end">${formatNumber(item.total_value || 0)} د.ع</td>
                <td class="text-end">${formatNumber(item.average_price || 0)} د.ع</td>
                <td>${item.month_year || ''}</td>
                <td>${formatDate(item.recorded_date)}</td>
                <td>${item.created_by_username || 'سیستەم'}</td>
            </tr>
        `;
        tbody.append(row);
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
                $('#total-bins').text(response.current_stock.length);
                
                // Count unique months
                const uniqueMonths = [...new Set(response.data.map(item => item.month_year))];
                $('#recorded-months').text(uniqueMonths.length);
                
                // Calculate current total amount
                const currentTotalAmount = response.current_stock.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
                $('#current-total-amount').text(formatNumber(currentTotalAmount));
                
                // Calculate current total value
                const currentTotalValue = response.current_stock.reduce((sum, item) => sum + parseFloat(item.total_value || 0), 0);
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
