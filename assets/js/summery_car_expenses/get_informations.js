// Car Expenses Summary Information Handler

// Global variables
let carExpensesData = [];
let summaryStats = {
    total_cars: 0,
    total_gas_expenses: 0,
    total_material_expenses: 0,
    total_expenses: 0
};

// Load car expenses data based on filters
function loadCarExpensesData(filters = {}) {
    // Show loading state
    $('#summary-cards').addClass('loading');
    
    // Build query parameters
    const params = new URLSearchParams();
    if (filters.car_id) params.append('car_id', filters.car_id);
    if (filters.employee_id) params.append('employee_id', filters.employee_id);
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);

    // Make AJAX request
    $.ajax({
        url: '../process/summery_car_expenses/get_informations.php',
        method: 'GET',
        data: params.toString(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                carExpensesData = response.data || [];
                summaryStats = response.summary || {};
                
                // Debug logging
                console.log('Car expenses data:', carExpensesData);
                console.log('Summary stats:', summaryStats);
                
                displayCarExpensesSummary();
                updateSummaryCards();
            } else {
                console.error('Error loading data:', response.message);
                showErrorMessage('هەڵە لە بارکردنی داتا: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            showErrorMessage('هەڵە لە پەیوەندی بە سێرڤەر');
        },
        complete: function() {
            $('#summary-cards').removeClass('loading');
        }
    });
}

// Display car expenses summary in table
function displayCarExpensesSummary() {
    const tbody = document.querySelector('#carSummaryTable tbody');
    if (!tbody) {
        console.error('Table body not found');
        return;
    }

    if (carExpensesData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <div class="py-4">
                        <i class="fas fa-info-circle fa-2x mb-3 text-info"></i>
                        <h6>هیچ داتایەک نەدۆزرایەوە</h6>
                        <p class="mb-0">تکایە بەرواری هەڵبژێرە یان فلتەرەکان بگۆڕە</p>
                        <small class="text-muted">دەتوانیت دوگمەی "تاقیکردنەوەی هەموو داتا" بەکاربهێنیت</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = carExpensesData.map(car => `
        <tr>
            <td>${car.car_id}</td>
            <td>${car.car_name || '-'}</td>
            <td>${car.employee_name || '-'}</td>
            <td>${car.expense_count || 0}</td>
            <td class="text-end">${formatCurrency(car.total_gas_expenses_iqd || 0, 'دینار')}</td>
            <td class="text-end">${formatCurrency(car.total_material_expenses_iqd || 0, 'دینار')}</td>
            <td class="text-end">${formatCurrency(car.total_expenses_iqd || 0, 'دینار')}</td>
            <td>
                <span class="badge ${car.payment_status === 'pending' ? 'bg-warning' : 'bg-success'}">
                    ${car.payment_status === 'pending' ? 'قەرز' : 'پارەدراو'}
                </span>
                <button class="btn btn-sm btn-outline-primary ms-2" onclick="viewCarDetails(${car.car_id})">
                    <i class="fas fa-eye"></i> وردەکاری
                </button>
            </td>
        </tr>
    `).join('');
}

// Create table row for car expenses
function createCarExpensesRow(car, index) {
    const gasExpenses = formatCurrency(car.total_gas_expenses_usd, 'USD');
    const materialExpenses = formatCurrency(car.total_material_expenses_usd, 'USD');
    const totalExpenses = formatCurrency(car.total_expenses_usd, 'USD');
    const paymentStatus = getPaymentStatusBadge(car.payment_status);

    return `
        <tr>
            <td>${index}</td>
            <td>
                <strong>${escapeHtml(car.car_name)}</strong>
                ${car.employee_name ? `<br><small class="text-muted">${escapeHtml(car.employee_name)}</small>` : ''}
            </td>
            <td>
                <span class="badge bg-primary">${car.expense_count}</span>
            </td>
            <td class="text-success">${gasExpenses}</td>
            <td class="text-warning">${materialExpenses}</td>
            <td class="text-danger fw-bold">${totalExpenses}</td>
            <td>${paymentStatus}</td>
            <td>
                <button class="btn btn-sm btn-view-details" onclick="viewCarDetails(${car.car_id})">
                    <i class="fas fa-eye me-1"></i>وردەکاری
                </button>
            </td>
        </tr>
    `;
}

// Update summary cards with data
function updateSummaryCards() {
    if (!summaryStats) {
        console.log('No summary stats available');
        return;
    }

    console.log('Updating summary cards with:', summaryStats);

    // Update total cars
    const totalCarsElement = document.getElementById('total_cars');
    if (totalCarsElement) {
        totalCarsElement.textContent = summaryStats.total_cars || 0;
    }

    // Update total gas expenses (always in دینار)
    const totalGasExpensesElement = document.getElementById('total_gas_expenses');
    if (totalGasExpensesElement) {
        const gasAmount = summaryStats.total_gas_expenses_iqd || 0;
        totalGasExpensesElement.textContent = formatCurrency(gasAmount, 'دینار');
    }

    // Update total material expenses (converted to دینار)
    const totalMaterialExpensesElement = document.getElementById('total_material_expenses');
    if (totalMaterialExpensesElement) {
        const materialAmount = summaryStats.total_material_expenses_iqd || 0;
        totalMaterialExpensesElement.textContent = formatCurrency(materialAmount, 'دینار');
    }

    // Update total expenses (converted to دینار)
    const totalExpensesElement = document.getElementById('total_expenses');
    if (totalExpensesElement) {
        const totalAmount = summaryStats.total_expenses_iqd || 0;
        totalExpensesElement.textContent = formatCurrency(totalAmount, 'دینار');
    }

    // Update debug info if visible
    updateDebugInfo();
}

// Update debug information display
function updateDebugInfo() {
    const debugContent = document.getElementById('debug_content');
    if (!debugContent) return;

    const debugInfo = `
        <div class="row">
            <div class="col-md-6">
                <h6>فلتەرەکان:</h6>
                <ul class="list-unstyled">
                    <li><strong>سەیارە:</strong> ${window.currentFilters?.car_id || 'هەموو'}</li>
                    <li><strong>کارمەند:</strong> ${window.currentFilters?.employee_id || 'هەموو'}</li>
                    <li><strong>لە بەروار:</strong> ${window.currentFilters?.date_from || 'هەموو'}</li>
                    <li><strong>بۆ بەروار:</strong> ${window.currentFilters?.date_to || 'هەموو'}</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>ئامارەکان:</h6>
                <ul class="list-unstyled">
                    <li><strong>کۆی سەیارەکان:</strong> ${summaryStats?.total_cars || 0}</li>
                    <li><strong>کۆی خەرجی گاز:</strong> ${formatCurrency(summaryStats?.total_gas_expenses_iqd || 0, 'دینار')}</li>
                    <li><strong>کۆی خەرجی کاڵا:</strong> ${formatCurrency(summaryStats?.total_material_expenses_iqd || 0, 'دینار')}</li>
                    <li><strong>کۆی گشتی:</strong> ${formatCurrency(summaryStats?.total_expenses_iqd || 0, 'دینار')}</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>داتای سەیارەکان:</h6>
                <small class="text-muted">کۆی: ${carExpensesData?.length || 0} سەیارە</small>
                ${carExpensesData && carExpensesData.length > 0 ? `
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>سەیارە</th>
                                    <th>کارمەند</th>
                                    <th>خەرجی گاز</th>
                                    <th>خەرجی کاڵا</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${carExpensesData.map(car => `
                                    <tr>
                                        <td>${car.car_name || car.car_id}</td>
                                        <td>${car.employee_name || '-'}</td>
                                        <td>${formatCurrency(car.total_gas_expenses_iqd || 0, 'دینار')}</td>
                                        <td>${formatCurrency(car.total_material_expenses_iqd || 0, 'دینار')}</td>
                                        <td>${formatCurrency(car.total_expenses_iqd || 0, 'دینار')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : '<p class="text-muted">هیچ داتایەک نەدۆزرایەوە</p>'}
            </div>
        </div>
    `;

    debugContent.innerHTML = debugInfo;
}

// View car details in modal
function viewCarDetails(carId) {
    // Show loading in modal
    $('#carDetailsContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-3">سڕینەوە...</p></div>');
    $('#carDetailsModal').modal('show');

    // Load car details
    $.ajax({
        url: '../process/summery_car_expenses/get_car_details.php',
        method: 'GET',
        data: { car_id: carId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayCarDetails(response.data);
            } else {
                $('#carDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هەڵە لە بارکردنی وردەکاری: ${response.message}
                    </div>
                `);
            }
        },
        error: function() {
            $('#carDetailsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    هەڵە لە پەیوەندی بە سێرڤەر
                </div>
            `);
        }
    });
}

// Display car details in modal
function displayCarDetails(carData) {
    const detailsHtml = `
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-car me-2"></i>
                            ${escapeHtml(carData.car_name)}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-gas-pump fa-3x text-success mb-2"></i>
                                    <h6>کۆی خەرجی گاز</h6>
                                    <h4 class="text-success">${formatCurrency(carData.total_gas_expenses_usd, 'USD')}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-boxes fa-3x text-warning mb-2"></i>
                                    <h6>کۆی خەرجی کاڵا</h6>
                                    <h4 class="text-warning">${formatCurrency(carData.total_material_expenses_usd, 'USD')}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-dollar-sign fa-3x text-danger mb-2"></i>
                                    <h6>کۆی گشتی</h6>
                                    <h4 class="text-danger">${formatCurrency(carData.total_expenses_usd, 'USD')}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-list fa-3x text-info mb-2"></i>
                                    <h6>ژمارەی خەرجیەکان</h6>
                                    <h4 class="text-info">${carData.expense_count}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-list-alt me-2"></i>
                            وردەکاری خەرجیەکان
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>بەروار</th>
                                        <th>مەبەست</th>
                                        <th>جۆری خەرجی</th>
                                        <th>بڕی گاز</th>
                                        <th>کاڵا</th>
                                        <th>بڕی کاڵا</th>
                                        <th>کۆی نرخ</th>
                                        <th>دۆخی پارەدان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${carData.expenses.map(expense => `
                                        <tr>
                                            <td>${formatDate(expense.date)}</td>
                                            <td>${escapeHtml(expense.purpose)}</td>
                                            <td>${escapeHtml(expense.expense_type)}</td>
                                            <td>${expense.gas_liters ? expense.gas_liters + ' لیتر' : '-'}</td>
                                            <td>${expense.material_name || '-'}</td>
                                            <td>${expense.material_quantity ? expense.material_quantity + ' ' + (expense.usage_unit_type || '') : '-'}</td>
                                            <td>${formatCurrency(expense.total_cost_usd, 'USD')}</td>
                                            <td>${getPaymentStatusBadge(expense.payment_status)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#carDetailsContent').html(detailsHtml);
}

// Utility functions
function formatCurrency(amount, currency) {
    if (!amount || isNaN(amount)) return '0';
    const num = parseFloat(amount);
    if (currency === 'USD') {
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        return num.toLocaleString('ar-IQ') + ' د.ع';
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-IQ');
}

function getPaymentStatusBadge(status) {
    switch(status) {
        case 'paid':
            return '<span class="badge bg-success">پارەی داوە</span>';
        case 'pending':
            return '<span class="badge bg-warning">چاوەڕوان</span>';
        case 'overdue':
            return '<span class="badge bg-danger">دواکەوتوو</span>';
        default:
            return '<span class="badge bg-secondary">نەزانراو</span>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showErrorMessage(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە',
        text: message,
        confirmButtonText: 'باشە'
    });
}

// Export functions to global scope
window.loadCarExpensesData = loadCarExpensesData;
window.viewCarDetails = viewCarDetails;
window.carExpensesData = carExpensesData;
window.summaryStats = summaryStats;
window.updateDebugInfo = updateDebugInfo;
