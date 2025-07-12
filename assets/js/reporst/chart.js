// Chart.js rendering for reports page
// Assumes Chart.js is loaded and canvas elements with correct IDs exist

document.addEventListener('DOMContentLoaded', function() {
    fetch('../process/reporst/get_information.php')
        .then(res => res.json())
        .then(result => {
            if (!result.success) return;
            const charts = result.data.charts;
            renderStockByMaterial(charts.stock_by_material);
            renderIncomeByMonthYear(charts.income_by_month_year);
        });
});

function renderStockByMaterial(data) {
    const ctx = document.getElementById('chart-stock-material').getContext('2d');
    const labels = Object.keys(data);
    const values = labels.map(k => data[k]);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'بڕی بەردەست',
                data: values,
                backgroundColor: 'rgba(0,151,167,0.7)'
            }]
        },
        options: { responsive: true }
    });
}

function renderIncomeByMonthYear(data) {
    const ctx = document.getElementById('chart-income-by-month-year').getContext('2d');
    // Prepare data: labels as 'YYYY-MM', values as income
    let labels = [];
    let values = [];
    Object.keys(data).sort().forEach(year => {
        Object.keys(data[year]).sort().forEach(month => {
            labels.push(`${year}-${month}`);
            values.push(data[year][month]);
        });
    });
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'داهات (USD)',
                data: values,
                borderColor: 'rgba(0,151,167,1)',
                backgroundColor: 'rgba(0,151,167,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                x: { title: { display: true, text: 'مانگ/ساڵ' } },
                y: { title: { display: true, text: 'داهات (USD)' }, beginAtZero: true }
            }
        }
    });
}
