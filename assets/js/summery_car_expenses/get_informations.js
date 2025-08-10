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
                displayCarExpensesSummary();
                updateSummaryCards();
            } else {
                console.error('Error loading data:', response.message);
                showErrorMessage('هەڵە لە بارکردنی داتا: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            showErrorMessage('هەڵە لە پەیوەندی بە سێرڤەر');
        },
        complete: function() {
            $('#summary-cards').removeClass('loading');
        }
    });
}

// Display car expenses summary in table
function displayCarExpensesSummary() {
    const tbody = $('#carSummaryTable tbody');
    tbody.empty();

    if (carExpensesData.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center text-muted">هیچ داتایەک نەدۆزرایەوە</td></tr>');
        return;
    }

    carExpensesData.forEach((car, index) => {
        const row = createCarExpensesRow(car, index + 1);
        tbody.append(row);
    });
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

// Update summary cards with current data
function updateSummaryCards() {
    $('#total_cars').text(summaryStats.total_cars || 0);
    $('#total_gas_expenses').text(formatCurrency(summaryStats.total_gas_expenses_usd, 'USD'));
    $('#total_material_expenses').text(formatCurrency(summaryStats.total_material_expenses_usd, 'USD'));
    $('#total_expenses').text(formatCurrency(summaryStats.total_expenses_usd, 'USD'));
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
