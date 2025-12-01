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
                renderMaterialConsumption(data);
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
    
    // Use real data from database
    const stockData = data.charts?.stock_by_material || {};
    
    // If no data, show message
    if (Object.keys(stockData).length === 0) {
        stockData['هیچ داتایەک نییە'] = 1;
    }
    
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
                        'rgba(40,167,69,0.8)',
                        'rgba(108,117,125,0.8)',
                        'rgba(23,162,184,0.8)'
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
    
    // Use real data from database
    const monthlyData = data.charts?.monthly_data || {};
    
    // Prepare data for chart
    const months = [];
    const incomeData = [];
    const expenseData = [];
    
    // Kurdish month names
    const kurdishMonths = {
        '01': 'کانوونی دووەم',
        '02': 'شوبات',
        '03': 'ئازار',
        '04': 'نیسان',
        '05': 'ئایار',
        '06': 'حوزەیران',
        '07': 'تەمموز',
        '08': 'ئاب',
        '09': 'ئەیلوول',
        '10': 'تشرینی یەکەم',
        '11': 'تشرینی دووەم',
        '12': 'کانوونی یەکەم'
    };
    
    Object.keys(monthlyData).forEach(year => {
        Object.keys(monthlyData[year]).forEach(month => {
            const monthData = monthlyData[year][month];
            months.push(`${kurdishMonths[month] || month} ${year}`);
            incomeData.push(monthData.income || 0);
            expenseData.push(monthData.expenses || 0);
        });
    });
    
    // If no data, use sample data
    if (months.length === 0) {
        const sampleMonths = ['کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران'];
        const sampleIncome = [12000, 15000, 18000, 14000, 16000, 20000];
        const sampleExpenses = [8000, 10000, 12000, 9000, 11000, 14000];
        
        months.push(...sampleMonths);
        incomeData.push(...sampleIncome);
        expenseData.push(...sampleExpenses);
    }
    
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
    
    // Use real data from database
    const salesVsExpenses = data.charts?.sales_vs_expenses || {};
    const salesAmount = salesVsExpenses.sales || 0;
    const expensesAmount = salesVsExpenses.expenses || 0;
    const profit = salesVsExpenses.profit || 0;
    
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
    
    // Use real data from database
    const debtAnalysis = data.charts?.debt_analysis || {};
    const customerDebt = debtAnalysis.customer_debt || 0;
    const companyDebt = debtAnalysis.company_debt || 0;
    const personDebt = debtAnalysis.person_debt || 0;
    
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
    
    // Use real data from database
    const employeePerformance = data.charts?.employee_performance || {};
    
    const employees = Object.keys(employeePerformance);
    const performance = Object.values(employeePerformance);
    
    // If no data, use sample data
    if (employees.length === 0) {
        employees.push('شاخەوان', 'بازیان', 'دانا', 'بەرزان');
        performance.push(85, 92, 78, 88);
    }
    
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
    
    // Use real data from database
    const carExpenses = data.charts?.car_expenses || {};
    
    const cars = Object.keys(carExpenses);
    const gasExpenses = cars.map(car => carExpenses[car]?.gas_cost || 0);
    const maintenanceExpenses = cars.map(car => carExpenses[car]?.expense_count * 100 || 0); // Sample maintenance cost
    
    // If no data, use sample data
    if (cars.length === 0) {
        cars.push('M10', 'M11', 'M12', 'M13', 'M14');
        gasExpenses.push(1200, 1500, 900, 1800, 1100);
        maintenanceExpenses.push(500, 800, 300, 1200, 600);
    }
    
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

function renderMaterialConsumption(data) {
    const ctx = document.getElementById('chart-material-consumption');
    if (!ctx) {
        console.warn('chart-material-consumption canvas not found');
        return;
    }
    
    // Use real data from database
    // cement_cem1 = دەلتا + لاڤارج (سایلۆی یەک)
    // cement_cem2 = ماس (سایلۆی دوو)
    const materialData = data.material_consumption?.tons || {};
    
    // If no data, show message
    if (Object.keys(materialData).length === 0) {
        console.warn('No material consumption data available');
        return;
    }
    
    const labels = [
        'لمی کەسارە (چاوی ١)',
        'لمی ڕەش (چاوی ٢)',
        'چەوی چاوی ٣',
        'چەوی چاوی ٤',
        'چیمەنتۆی سایلۆی ١ (دەلتا + لاڤارج)',
        'چیمەنتۆی سایلۆی ٢ (ماس)'
    ];
    
    const values = [
        materialData.black_sand || 0,
        materialData.brown_sand || 0,
        materialData.gravel_bin3 || 0,
        materialData.gravel_bin4 || 0,
        materialData.cement_cem1 || 0,
        materialData.cement_cem2 || 0
    ];
    
    try {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'بەکارهێنان (تۆن)',
                    data: values,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2.5,
                plugins: {
                    title: {
                        display: true,
                        text: 'بەکارهێنانی ماتریاڵەکان بە جۆر (دەلتا + لاڤارج + ماس)',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: { display: true, text: 'بڕ (تۆن)' }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering material consumption chart:', error);
    }
}

// Material consumption breakdown:
// - cement_cem1: دەلتا + لاڤارج (سایلۆی یەک)
// - cement_cem2: ماس (سایلۆی دوو)
// - black_sand: لمی کەسارە (چاوی یەک)
// - brown_sand: لمی ڕەش (چاوی دوو)
// - gravel_bin3: چەوی چاوی سێ
// - gravel_bin4: چەوی چاوی چوار
// - Total cement = دەلتا + لاڤارج + ماس
// - Total materials = لمی کەسارە + لمی ڕەش + چەوی چاوی ٣ + چەوی چاوی ٤ + دەلتا + لاڤارج + ماس
