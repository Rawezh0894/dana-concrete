// Global variables
let expensesData = [];
let expenseTypeChart = null;
let carExpenseChart = null;

// Load expenses data
function loadExpensesData() {
    showLoading();
    
    const filters = getCurrentFilters();
    console.log('🚗 Loading expenses data with filters:', filters);
    console.log('📅 Date range:', filters.from_date, 'to', filters.to_date);
    
    // Add timeout
    const timeoutId = setTimeout(() => {
        hideLoading();
        showError('کاتی چاوەڕوانی تەواو بوو. تکایە دووبارە هەوڵ بدە');
    }, 30000); // 30 seconds
    
    const apiUrl = `../process/summery_car_expenses/get_informations.php?${new URLSearchParams(filters)}`;
    console.log('🌐 API URL:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            clearTimeout(timeoutId);
            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Received data:', data);
            console.log('📊 Data count:', data.data ? data.data.length : 0);
            console.log('📊 Summary:', data.summary);
            hideLoading();
            if (data.success) {
                expensesData = data.data;
                updateSummaryStats(data.summary);
                updateExpensesTable(data.data);
                updateCharts(data.summary);
                updateTotalRecords(data.data.length);
                
                // Show message if exists
                if (data.message) {
                    console.log('💬 Message:', data.message);
                    showInfo(data.message);
                }
            } else {
                console.error('❌ API Error:', data.error);
                showError('هەڵە لە وەرگرتنی داتاکان: ' + (data.error || 'هەڵەی نەناسراو'));
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            hideLoading();
            console.error('💥 Fetch Error:', error);
            console.error('💥 Error stack:', error.stack);
            showError('هەڵە لە پەیوەندی بە سێرڤەر: ' + error.message);
        });
}

// Get current filter values
function getCurrentFilters() {
    return {
        car_id: $('#filter_car_id').val(),
        employee_id: $('#filter_employee_id').val(),
        expense_type: $('#filter_expense_type').val(),
        payment_type: $('#filter_payment_type').val(),
        from_date: $('#filter_from_date').val(),
        to_date: $('#filter_to_date').val()
    };
}

// Update summary statistics
function updateSummaryStats(summary) {
    $('#total_usd').text('$' + formatNumber(summary.total_usd));
    $('#total_iqd').text(formatNumber(summary.total_iqd) + ' د.ع');
    $('#total_cars').text(summary.total_cars);
    $('#total_expenses').text(summary.total_expenses);
    $('#total_gas').text(formatNumber(summary.total_gas) + ' لیتر');
    $('#total_materials').text(summary.total_materials);
    
    // Add visual feedback for empty data
    if (summary.total_expenses === 0) {
        $('.summary-card').addClass('opacity-50');
        $('.summary-card small').text('هیچ داتایەک نییە');
    } else {
        $('.summary-card').removeClass('opacity-50');
        $('.summary-card small').each(function() {
            const originalText = $(this).data('original-text') || $(this).text();
            $(this).data('original-text', originalText);
            $(this).text(originalText);
        });
    }
    
    // Update expense type summary
    updateExpenseTypeSummary(expensesData);
}

// Update expenses table
function updateExpensesTable(data) {
    const tbody = $('#expensesTableBody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="14" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <br>هیچ داتایەک نەدۆزرا
                </td>
            </tr>
        `);
        return;
    }
    
    data.forEach((expense, index) => {
        const row = `
            <tr class="expense-row" data-expense-id="${expense.id || index}">
                <td>
                    <button class="btn btn-sm btn-outline-primary expand-btn" onclick="toggleExpenseDetails(${index})" title="پیشاندانی وردەکاری">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    ${index + 1}
                </td>
                <td>${formatDate(expense.date)}</td>
                <td>${expense.car_name || '-'}</td>
                <td>${expense.employee_name || '-'}</td>
                <td>
                    <span class="badge bg-${getExpenseTypeColor(expense.expense_type)} expense-type-badge">
                        <i class="fas ${getExpenseTypeIcon(expense.expense_type)} me-1"></i>
                        ${expense.expense_type}
                    </span>
                </td>
                <td>${expense.purpose || '-'}</td>
                <td>${expense.gas_liters ? formatNumber(expense.gas_liters) + ' لیتر' : '-'}</td>
                <td>${expense.material_quantity ? formatNumber(expense.material_quantity) + ' ' + (expense.material_unit_type || '') : '-'}</td>
                <td>${expense.amount_iqd ? formatNumber(expense.amount_iqd) + ' د.ع' : '-'}</td>
                <td>${expense.amount_usd ? '$' + formatNumber(expense.amount_usd) : '-'}</td>
                <td>
                    <span class="badge bg-${expense.payment_type === 'نەقد' ? 'success' : 'warning'}">
                        ${expense.payment_type}
                    </span>
                </td>
                <td>${expense.invoice_number || '-'}</td>
                <td>${formatDate(expense.date)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info btn-sm" onclick="showExpenseSummary(${index})" title="پوختەی خەرجی">
                            <i class="fas fa-chart-pie"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="showExpenseDetails(${index})" title="وردەکاری">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <tr class="expense-details-row" id="expense-details-${index}" style="display: none;">
                <td colspan="14" class="p-0">
                    <div class="expense-details-content p-3" style="background-color: #f8f9fa; border-left: 4px solid var(--kelly-green);">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>وردەکاری خەرجی
                                </h6>
                                <div class="expense-details-info">
                                    <p><strong>مەبەست:</strong> ${expense.purpose || 'هیچ مەبەستێک دیاری نەکراوە'}</p>
                                    <p><strong>بەرواری درووستکردن:</strong> ${formatDate(expense.created_at || expense.date)}</p>
                                    <p><strong>ژمارەی فاکتۆر:</strong> ${expense.invoice_number || 'هیچ ژمارەیەک دیاری نەکراوە'}</p>
                                    <p><strong>تێبینی:</strong> ${expense.notes || 'هیچ تێبینێک نییە'}</p>
                                </div>
                            </div>
                                                         <div class="col-md-6">
                                 <h6 class="text-success mb-3">
                                     <i class="fas fa-calculator me-2"></i>پوختەی خەرجی
                                 </h6>
                                 <div class="expense-summary-info">
                                     <div class="row">
                                         <div class="col-6">
                                             <div class="summary-item">
                                                 <span class="label">بڕی دینار:</span>
                                                 <span class="value text-primary">${expense.amount_iqd ? formatNumber(expense.amount_iqd) + ' د.ع' : '0 د.ع'}</span>
                                             </div>
                                         </div>
                                         <div class="col-6">
                                             <div class="summary-item">
                                                 <span class="label">بڕی دۆلار:</span>
                                                 <span class="value text-success">${expense.amount_usd ? '$' + formatNumber(expense.amount_usd) : '$0.00'}</span>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="row mt-2">
                                         <div class="col-6">
                                             <div class="summary-item">
                                                 <span class="label">بڕی گاز:</span>
                                                 <span class="value text-warning">${expense.gas_liters ? formatNumber(expense.gas_liters) + ' لیتر' : '0 لیتر'}</span>
                                             </div>
                                         </div>
                                         <div class="col-6">
                                             <div class="summary-item">
                                                 <span class="label">بڕی کاڵا:</span>
                                                 <span class="value text-info">${expense.material_quantity ? formatNumber(expense.material_quantity) + ' ' + (expense.material_unit_type || '') : '0'}</span>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="row mt-2">
                                         <div class="col-12">
                                             <div class="summary-item">
                                                 <span class="label">جۆری خەرجی:</span>
                                                 <span class="value">
                                                     <span class="badge bg-${getExpenseTypeColor(expense.expense_type)} expense-type-badge">
                                                         <i class="fas ${getExpenseTypeIcon(expense.expense_type)} me-1"></i>
                                                         ${expense.expense_type}
                                                     </span>
                                                 </span>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Update charts
function updateCharts(summary) {
    if (summary.expense_type_distribution && summary.expense_type_distribution.length > 0) {
        updateExpenseTypeChart(summary.expense_type_distribution);
    } else {
        showEmptyChart('expenseTypeChart', 'هیچ داتایەک نییە بۆ پیشاندان');
    }
    
    if (summary.car_expenses && summary.car_expenses.length > 0) {
        updateCarExpenseChart(summary.car_expenses);
    } else {
        showEmptyChart('carExpenseChart', 'هیچ داتایەک نییە بۆ پیشاندان');
    }
}

// Show empty chart message
function showEmptyChart(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw empty message
        ctx.fillStyle = '#6c757d';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(message, canvas.width / 2, canvas.height / 2);
    }
}

// Handle chart resize
function handleChartResize() {
    if (expenseTypeChart) {
        expenseTypeChart.resize();
    }
    if (carExpenseChart) {
        carExpenseChart.resize();
    }
}

// Add resize event listener
window.addEventListener('resize', handleChartResize);

// Update expense type pie chart
function updateExpenseTypeChart(data) {
    const ctx = document.getElementById('expenseTypeChart').getContext('2d');
    
    if (expenseTypeChart) {
        expenseTypeChart.destroy();
    }
    
    const chartData = {
        labels: data.map(item => item.type),
        datasets: [{
            data: data.map(item => item.amount),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
    
    expenseTypeChart = new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    bottom: 10
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 12
                        },
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${formatNumber(value)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Update car expenses bar chart
function updateCarExpenseChart(data) {
    const ctx = document.getElementById('carExpenseChart').getContext('2d');
    
    if (carExpenseChart) {
        carExpenseChart.destroy();
    }
    
    const chartData = {
        labels: data.map(item => item.car_name),
        datasets: [{
            label: 'خەرجی بە دۆلار',
            data: data.map(item => item.total_usd),
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false
        }, {
            label: 'خەرجی بە دینار',
            data: data.map(item => item.total_iqd / 1000), // Convert to thousands for better display
            backgroundColor: 'rgba(255, 206, 86, 0.8)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false
        }]
    };
    
    carExpenseChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    bottom: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            if (this.getDatasetForEvent) {
                                const datasetIndex = this.getDatasetForEvent(0).datasetIndex;
                                if (datasetIndex === 1) {
                                    return formatNumber(value) + 'K د.ع';
                                }
                            }
                            return '$' + formatNumber(value);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.parsed.y;
                            if (context.datasetIndex === 1) {
                                return `${label}: ${formatNumber(value * 1000)} د.ع`;
                            }
                            return `${label}: $${formatNumber(value)}`;
                        }
                    }
                }
            }
        }
    });
}

// Update total records count
function updateTotalRecords(count) {
    $('#total-records').text(count + ' خەرجی');
}

// Update expense type summary
function updateExpenseTypeSummary(data) {
    const summaryContainer = $('#expense-type-summary');
    summaryContainer.empty();
    
    if (data.length === 0) {
        summaryContainer.append(`
            <div class="col-12 text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <br>هیچ داتایەک نییە
            </div>
        `);
        return;
    }
    
    // Group expenses by type
    const expenseTypeGroups = {};
    data.forEach(expense => {
        if (!expenseTypeGroups[expense.expense_type]) {
            expenseTypeGroups[expense.expense_type] = {
                count: 0,
                total_iqd: 0,
                total_usd: 0,
                total_gas: 0,
                total_materials: 0
            };
        }
        
        expenseTypeGroups[expense.expense_type].count++;
        expenseTypeGroups[expense.expense_type].total_iqd += parseFloat(expense.amount_iqd || 0);
        expenseTypeGroups[expense.expense_type].total_usd += parseFloat(expense.amount_usd || 0);
        expenseTypeGroups[expense.expense_type].total_gas += parseFloat(expense.gas_liters || 0);
        expenseTypeGroups[expense.expense_type].total_materials += parseFloat(expense.material_quantity || 0);
    });
    
    // Create summary cards for each expense type
    Object.keys(expenseTypeGroups).forEach(expenseType => {
        const group = expenseTypeGroups[expenseType];
        const colorClass = getExpenseTypeColor(expenseType);
        const icon = getExpenseTypeIcon(expenseType);
        
        const summaryCard = `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card expense-type-card h-100 border-0 shadow-sm" style="border-left: 4px solid var(--${colorClass}) !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="expense-type-icon me-2">
                                <i class="fas ${icon} fa-2x text-${colorClass}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1 text-dark">${expenseType}</h6>
                                <small class="text-muted">${group.count} خەرجی</small>
                            </div>
                        </div>
                        
                        <div class="expense-type-stats">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <small class="text-muted d-block">دینار</small>
                                        <strong class="text-primary">${formatNumber(group.total_iqd)} د.ع</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <small class="text-muted d-block">دۆلار</small>
                                        <strong class="text-success">$${formatNumber(group.total_usd)}</strong>
                                    </div>
                                </div>
                            </div>
                            
                            ${group.total_gas > 0 ? `
                                <div class="row text-center mt-2">
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <small class="text-muted d-block">گاز</small>
                                            <strong class="text-warning">${formatNumber(group.total_gas)} لیتر</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item">
                                            <small class="text-muted d-block">کاڵا</small>
                                            <strong class="text-info">${formatNumber(group.total_materials)}</strong>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        summaryContainer.append(summaryCard);
    });
}

// Get expense type color
function getExpenseTypeColor(type) {
    const colors = {
        'بەکارهێنانی کاڵای کۆگا': 'primary',
        'بەکارهێنانی گاز': 'success',
        'خەرجی تر': 'info',
        'خواردنگە': 'warning',
        'ئۆفیس': 'secondary',
        'خەرجی سەیارە': 'danger',
        'خەرجی ڕێگە': 'dark',
        'خەرجی پاراستن': 'purple',
        'خەرجی سووڕان': 'teal',
        'خەرجی بەڕێوەبردنی': 'indigo',
        'خەرجی گەشەپێدان': 'pink',
        'خەرجی پێویستەکان': 'orange'
    };
    return colors[type] || 'secondary';
}

// Get expense type icon
function getExpenseTypeIcon(type) {
    const icons = {
        'بەکارهێنانی کاڵای کۆگا': 'fa-boxes',
        'بەکارهێنانی گاز': 'fa-gas-pump',
        'خەرجی تر': 'fa-ellipsis-h',
        'خواردنگە': 'fa-utensils',
        'ئۆفیس': 'fa-building',
        'خەرجی سەیارە': 'fa-car',
        'خەرجی ڕێگە': 'fa-road',
        'خەرجی پاراستن': 'fa-tools',
        'خەرجی سووڕان': 'fa-oil-can',
        'خەرجی بەڕێوەبردنی': 'fa-cogs',
        'خەرجی گەشەپێدان': 'fa-chart-line',
        'خەرجی پێویستەکان': 'fa-clipboard-list'
    };
    return icons[type] || 'fa-money-bill';
}

// Format number with commas
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-IQ');
}

// Show error message
function showError(message) {
    // You can implement a better error display system
    alert('هەڵە: ' + message);
}

// Show info message
function showInfo(message) {
    // Create info alert
    const infoAlert = `
        <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-info-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert-info').remove();
    
    // Add new alert
    $('body').append(infoAlert);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert-info').fadeOut();
    }, 5000);
}

// Generate comprehensive report
function generateReport() {
    showLoading();
    
    const filters = getCurrentFilters();
    const reportData = {
        filters: filters,
        data: expensesData,
        generated_at: new Date().toISOString(),
        user: '<?php echo $_SESSION["user_name"] ?? "Unknown"; ?>'
    };
    
    // Create report content
    let reportContent = `
        <div style="font-family: Arial, sans-serif; direction: rtl; text-align: right;">
            <h2 style="color: #28a745; text-align: center;">ڕاپۆرتی خەرجیەکانی سەیارەکان</h2>
            <hr>
            <p><strong>بەرواری درووستکردن:</strong> ${new Date().toLocaleDateString('ku-IQ')}</p>
            <p><strong>بەکارهێنەر:</strong> ${reportData.user}</p>
            
            <h3>فلتەرەکان:</h3>
            <ul>
                ${filters.car_id ? `<li>سەیارە: ${$('#filter_car_id option:selected').text()}</li>` : ''}
                ${filters.employee_id ? `<li>کارمەند: ${$('#filter_employee_id option:selected').text()}</li>` : ''}
                ${filters.expense_type ? `<li>جۆری خەرجی: ${$('#filter_expense_type option:selected').text()}</li>` : ''}
                ${filters.payment_type ? `<li>جۆری پارەدان: ${$('#filter_payment_type option:selected').text()}</li>` : ''}
                ${filters.from_date ? `<li>لە بەروار: ${filters.from_date}</li>` : ''}
                ${filters.to_date ? `<li>بۆ بەروار: ${filters.to_date}</li>` : ''}
            </ul>
            
            <h3>ئامارەکان:</h3>
            <p>کۆی خەرجی بە دۆلار: $${$('#total_usd').text()}</p>
            <p>کۆی خەرجی بە دینار: ${$('#total_iqd').text()}</p>
            <p>ژمارەی سەیارەکان: ${$('#total_cars').text()}</p>
            <p>ژمارەی خەرجیەکان: ${$('#total_expenses').text()}</p>
        </div>
    `;
    
    // Create new window for report
    const reportWindow = window.open('', '_blank');
    reportWindow.document.write(`
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <title>ڕاپۆرتی خەرجیەکانی سەیارەکان</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background-color: #f2f2f2; }
                .summary { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            ${reportContent}
            <div class="summary">
                <h3>تەفەسڵی خەرجیەکان:</h3>
                ${generateReportTable()}
            </div>
        </body>
        </html>
    `);
    
    hideLoading();
}

// Generate report table
function generateReportTable() {
    if (expensesData.length === 0) {
        return '<p>هیچ داتایەک نەدۆزرا</p>';
    }
    
    let table = `
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>بەروار</th>
                    <th>سەیارە</th>
                    <th>کارمەند</th>
                    <th>جۆری خەرجی</th>
                    <th>مەبەست</th>
                    <th>بڕی دینار</th>
                    <th>بڕی دۆلار</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    expensesData.forEach((expense, index) => {
        table += `
            <tr>
                <td>${index + 1}</td>
                <td>${formatDate(expense.date)}</td>
                <td>${expense.car_name || '-'}</td>
                <td>${expense.employee_name || '-'}</td>
                <td>${expense.expense_type}</td>
                <td>${expense.purpose || '-'}</td>
                <td>${expense.amount_iqd ? formatNumber(expense.amount_iqd) + ' د.ع' : '-'}</td>
                <td>${expense.amount_usd ? '$' + formatNumber(expense.amount_usd) : '-'}</td>
            </tr>
        `;
    });
    
    table += '</tbody></table>';
    return table;
}

// Toggle expense details row
function toggleExpenseDetails(index) {
    const detailsRow = document.getElementById(`expense-details-${index}`);
    const expandBtn = document.querySelector(`[onclick="toggleExpenseDetails(${index})"] i`);
    
    if (detailsRow.style.display === 'none') {
        // Show details
        detailsRow.style.display = 'table-row';
        expandBtn.classList.remove('fa-chevron-down');
        expandBtn.classList.add('fa-chevron-up');
        expandBtn.parentElement.title = 'خەبەکردنەوەی وردەکاری';
    } else {
        // Hide details
        detailsRow.style.display = 'none';
        expandBtn.classList.remove('fa-chevron-up');
        expandBtn.classList.add('fa-chevron-down');
        expandBtn.parentElement.title = 'پیشاندانی وردەکاری';
    }
}

// Show expense summary in a modal
function showExpenseSummary(index) {
    const expense = expensesData[index];
    
    const summaryContent = `
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-chart-pie me-2"></i>پوختەی خەرجی - ${expense.car_name || 'سەیارەی نەناسراو'}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="summary-card bg-primary text-white p-3 rounded mb-3">
                        <h6><i class="fas fa-coins me-2"></i>بڕی دینار</h6>
                        <h4>${expense.amount_iqd ? formatNumber(expense.amount_iqd) + ' د.ع' : '0 د.ع'}</h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="summary-card bg-success text-white p-3 rounded mb-3">
                        <h6><i class="fas fa-dollar-sign me-2"></i>بڕی دۆلار</h6>
                        <h4>${expense.amount_usd ? '$' + formatNumber(expense.amount_usd) : '$0.00'}</h4>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="summary-card bg-warning text-dark p-3 rounded mb-3">
                        <h6><i class="fas fa-gas-pump me-2"></i>بڕی گاز</h6>
                        <h4>${expense.gas_liters ? formatNumber(expense.gas_liters) + ' لیتر' : '0 لیتر'}</h4>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="summary-card bg-info text-white p-3 rounded mb-3">
                        <h6><i class="fas fa-boxes me-2"></i>بڕی کاڵا</h6>
                        <h4>${expense.material_quantity ? formatNumber(expense.material_quantity) + ' ' + (expense.material_unit_type || '') : '0'}</h4>
                    </div>
                </div>
            </div>
            <div class="expense-details mt-3">
                <h6 class="text-primary">وردەکاری:</h6>
                <ul class="list-unstyled">
                    <li><strong>جۆری خەرجی:</strong> ${expense.expense_type}</li>
                    <li><strong>مەبەست:</strong> ${expense.purpose || 'هیچ مەبەستێک دیاری نەکراوە'}</li>
                    <li><strong>جۆری پارەدان:</strong> ${expense.payment_type}</li>
                    <li><strong>بەروار:</strong> ${formatDate(expense.date)}</li>
                </ul>
            </div>
        </div>
    `;
    
    // Create and show modal
    showModal('پوختەی خەرجی', summaryContent);
}

// Show expense details in a modal
function showExpenseDetails(index) {
    const expense = expensesData[index];
    
    const detailsContent = `
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-eye me-2"></i>وردەکاری خەرجی - ${expense.car_name || 'سەیارەی نەناسراو'}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">زانیاری سەرەکی:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>سەیارە:</strong></td><td>${expense.car_name || '-'}</td></tr>
                        <tr><td><strong>کارمەند:</strong></td><td>${expense.employee_name || '-'}</td></tr>
                        <tr><td><strong>جۆری خەرجی:</strong></td><td>${expense.expense_type}</td></tr>
                        <tr><td><strong>مەبەست:</strong></td><td>${expense.purpose || '-'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">زانیاری پارە:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>بڕی دینار:</strong></td><td>${expense.amount_iqd ? formatNumber(expense.amount_iqd) + ' د.ع' : '-'}</td></tr>
                        <tr><td><strong>بڕی دۆلار:</strong></td><td>${expense.amount_usd ? '$' + formatNumber(expense.amount_usd) : '-'}</td></tr>
                        <tr><td><strong>جۆری پارەدان:</strong></td><td>${expense.payment_type}</td></tr>
                        <tr><td><strong>ژمارەی فاکتۆر:</strong></td><td>${expense.invoice_number || '-'}</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-warning">زانیاری گاز و کاڵا:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>بڕی گاز:</strong></td><td>${expense.gas_liters ? formatNumber(expense.gas_liters) + ' لیتر' : '-'}</td></tr>
                        <tr><td><strong>بڕی کاڵا:</strong></td><td>${expense.material_quantity ? formatNumber(expense.material_quantity) + ' ' + (expense.material_unit_type || '') : '-'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-info">زانیاری تر:</h6>
                    <table class="table table-sm">
                        <tr><td><strong>بەروار:</strong></td><td>${formatDate(expense.date)}</td></tr>
                        <tr><td><strong>بەرواری درووستکردن:</strong></td><td>${formatDate(expense.created_at || expense.date)}</td></tr>
                        <tr><td><strong>تێبینی:</strong></td><td>${expense.notes || '-'}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Create and show modal
    showModal('وردەکاری خەرجی', detailsContent);
}

// Show modal function
function showModal(title, content) {
    // Remove existing modal if any
    $('.expense-modal').remove();
    
    const modal = `
        <div class="modal fade expense-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    ${content}
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modal);
    
    // Show modal
    const modalElement = $('.expense-modal');
    modalElement.modal('show');
    
    // Remove modal from DOM when hidden
    modalElement.on('hidden.bs.modal', function() {
        $(this).remove();
    });
}


