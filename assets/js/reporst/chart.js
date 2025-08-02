// Chart.js rendering for reports page
// Assumes Chart.js is loaded and canvas elements with correct IDs exist

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }
    
    fetch('../process/reporst/get_information.php')
        .then(res => res.json())
        .then(result => {
            if (!result.success) {
                console.error('Failed to fetch data:', result.error);
                return;
            }
            const data = result.data;
            
            // Render charts with error handling
            try {
                renderStockByMaterial(data);
                renderIncomeByMonthYear(data);
                renderSalesVsExpenses(data);
                renderDebtAnalysis(data);
                renderEmployeePerformance(data);
                renderCarExpenses(data);
            } catch (error) {
                console.error('Chart rendering error:', error);
            }
        })
        .catch(error => {
            console.error('Failed to fetch chart data:', error);
        });
});

function renderStockByMaterial(data) {
    const ctx = document.getElementById('chart-stock-material');
    if (!ctx) {
        console.warn('chart-stock-material canvas not found');
        return;
    }
    
    // Create sample data if not available
    const stockData = {
        'چیمەنتۆ': data.stock?.total_value * 0.4 || 1000,
        'لمی ڕەش': data.stock?.total_value * 0.3 || 800,
        'چەو': data.stock?.total_value * 0.2 || 600,
        'دەرمان': data.stock?.total_value * 0.1 || 400
    };
    
    const labels = Object.keys(stockData);
    const values = Object.values(stockData);
    
    try {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(0,151,167,0.8)',
                        'rgba(255,193,7,0.8)',
                        'rgba(220,53,69,0.8)',
                        'rgba(40,167,69,0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12 },
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: 'ستۆک بە جۆری ماتریاڵ',
                        font: { size: 16, weight: 'bold' }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering stock chart:', error);
    }
}

function renderIncomeByMonthYear(data) {
    const ctx = document.getElementById('chart-income-by-month-year');
    if (!ctx) {
        console.warn('chart-income-by-month-year canvas not found');
        return;
    }
    
    // Create sample data for the last 6 months
    const months = ['کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران'];
    const incomeData = [12000, 15000, 18000, 14000, 16000, 20000];
    const expenseData = [8000, 10000, 12000, 9000, 11000, 14000];
    
    try {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'داهات (USD)',
                    data: incomeData,
                    borderColor: 'rgba(0,151,167,1)',
                    backgroundColor: 'rgba(0,151,167,0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'خەرجی (USD)',
                    data: expenseData,
                    borderColor: 'rgba(220,53,69,1)',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: { 
                        display: true, 
                        position: 'top',
                        labels: { font: { size: 12 } }
                    },
                    title: {
                        display: true,
                        text: 'گۆڕانکاری داهات و خەرجی بە مانگ',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    x: { 
                        title: { display: true, text: 'مانگ' },
                        grid: { display: false }
                    },
                    y: { 
                        title: { display: true, text: 'بڕ (USD)' }, 
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.1)' }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering income chart:', error);
    }
}

function renderSalesVsExpenses(data) {
    const ctx = document.getElementById('chart-sales-vs-expenses');
    if (!ctx) {
        console.warn('chart-sales-vs-expenses canvas not found');
        return;
    }
    
    const salesAmount = (data.sales?.cash?.usd || 0) + (data.sales?.credit?.usd || 0);
    const expensesAmount = data.total_expenses?.usd || 0;
    const profit = salesAmount - expensesAmount;
    
    try {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['فرۆشتن', 'خەرجی', 'قازانج'],
                datasets: [{
                    label: 'بڕ (USD)',
                    data: [salesAmount, expensesAmount, profit],
                    backgroundColor: [
                        'rgba(40,167,69,0.8)',
                        'rgba(220,53,69,0.8)',
                        'rgba(0,151,167,0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'فرۆشتن vs خەرجی vs قازانج',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: { display: true, text: 'بڕ (USD)' }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering sales vs expenses chart:', error);
    }
}

function renderDebtAnalysis(data) {
    const ctx = document.getElementById('chart-debt-analysis');
    if (!ctx) {
        console.warn('chart-debt-analysis canvas not found');
        return;
    }
    
    const customerDebt = data.customer?.usd || 0;
    const companyDebt = data.company?.usd || 0;
    const personDebt = data.person?.usd || 0;
    
    try {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['قەرزی کڕیارەکان', 'قەرزی کۆمپانیاکان', 'قەرزی کەسانی تر'],
                datasets: [{
                    data: [customerDebt, companyDebt, personDebt],
                    backgroundColor: [
                        'rgba(0,151,167,0.8)',
                        'rgba(255,193,7,0.8)',
                        'rgba(220,53,69,0.8)'
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 } }
                    },
                    title: {
                        display: true,
                        text: 'شیکردنەوەی قەرزەکان',
                        font: { size: 16, weight: 'bold' }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering debt analysis chart:', error);
    }
}

function renderEmployeePerformance(data) {
    const ctx = document.getElementById('chart-employee-performance');
    if (!ctx) {
        console.warn('chart-employee-performance canvas not found');
        return;
    }
    
    // Sample employee performance data
    const employees = ['شاخەوان', 'بازیان', 'دانا', 'بەرزان'];
    const performance = [85, 92, 78, 88];
    
    try {
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: employees,
                datasets: [{
                    label: 'کارایی (%)',
                    data: performance,
                    backgroundColor: 'rgba(0,151,167,0.2)',
                    borderColor: 'rgba(0,151,167,1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(0,151,167,1)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    title: {
                        display: true,
                        text: 'کارایی کارمەندان',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20 }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering employee performance chart:', error);
    }
}

function renderCarExpenses(data) {
    const ctx = document.getElementById('chart-car-expenses');
    if (!ctx) {
        console.warn('chart-car-expenses canvas not found');
        return;
    }
    
    // Sample car expense data
    const cars = ['M10', 'M11', 'M12', 'M13', 'M14'];
    const gasExpenses = [1200, 1500, 900, 1800, 1100];
    const maintenanceExpenses = [500, 800, 300, 1200, 600];
    
    try {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: cars,
                datasets: [{
                    label: 'خەرجی گاز (USD)',
                    data: gasExpenses,
                    backgroundColor: 'rgba(255,193,7,0.8)',
                    borderColor: 'rgba(255,193,7,1)',
                    borderWidth: 1
                }, {
                    label: 'خەرجی چاککردنەوە (USD)',
                    data: maintenanceExpenses,
                    backgroundColor: 'rgba(220,53,69,0.8)',
                    borderColor: 'rgba(220,53,69,1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: {
                    title: {
                        display: true,
                        text: 'خەرجی سەیارەکان',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: { display: true, text: 'بڕ (USD)' }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering car expenses chart:', error);
    }
}
