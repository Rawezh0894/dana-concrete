let currentReportFilter = 'year';

function fetchAndRenderReportData() {
    const fromDate = document.getElementById('from-date')?.value;
    const toDate = document.getElementById('to-date')?.value;
    let url = `../process/reporst/get_information.php?filter=${currentReportFilter}`;
    if (fromDate) url += `&from_date=${fromDate}`;
    if (toDate) url += `&to_date=${toDate}`;
    fetch(url)
        .then(res => res.json())
        .then(result => {
            if (!result.success) {
                swalAlert('هەڵە', result.error, 'error');
                return;
            }
            const data = result.data;
            const usd_iqd_rate = data.usd_iqd_rate || 0;
            const company_debt_usd = Number(data.company.usd) + (Number(data.company.iqd) / (usd_iqd_rate / 100));
            const person_debt_usd = Number(data.person.usd) || 0;
            const purchases_cash_usd = Number(data.purchases.cash.usd) || 0;
            const purchases_credit_usd = Number(data.purchases.credit.usd) || 0;
            const purchases_usd = purchases_cash_usd + purchases_credit_usd;
            
            const cards = [
                {
                    key: 'customer',
                    label: 'کۆی قەرزی کڕیارەکان',
                    icon: 'fa-users',
                    gradient: 'card-gradient-success',
                    value: formatCurrency(Number(data.customer.usd) || 0, 'USD'),
                    subtitle: 'قەرزی کڕیارەکان'
                },
                {
                    key: 'company',
                    label: 'قەرزی ئێمە لەگەڵ کۆمپانیاکان',
                    icon: 'fa-building',
                    gradient: 'card-gradient-warning',
                    value: formatCurrency(Number(company_debt_usd) || 0, 'USD'),
                    subtitle: 'قەرزی کۆمپانیاکان'
                },
                {
                    key: 'person',
                    label: 'قەرزی ئێمە لەگەڵ کەسانی خەرجی تر',
                    icon: 'fa-user-tie',
                    gradient: 'card-gradient-purple',
                    value: formatCurrency(Number(person_debt_usd) || 0, 'USD'),
                    subtitle: 'قەرزی کەسانی تر'
                },
                {
                    key: 'purchases',
                    label: 'کۆی نرخی کڕین',
                    icon: 'fa-cart-plus',
                    gradient: 'card-gradient-teal',
                    value: formatCurrency(Number(purchases_usd) || 0, 'USD'),
                    subtitle: 'کۆی کڕینەکان'
                },
                {
                    key: 'sales',
                    label: 'کۆی نرخی فرۆشتن',
                    icon: 'fa-cash-register',
                    gradient: 'card-gradient-orange',
                    value: formatCurrency((Number(data.sales.cash.usd) || 0) + (Number(data.sales.credit.usd) || 0), 'USD'),
                    subtitle: 'کۆی فرۆشتنەکان'
                },
                {
                    key: 'remaining_purchases',
                    label: 'کۆی پارەی ماوەی کڕین',
                    icon: 'fa-wallet',
                    gradient: 'card-gradient-info',
                    value: formatCurrency(Number(data.remaining_purchases.usd) || 0, 'USD'),
                    subtitle: 'پارەی ماوە'
                },
                {
                    key: 'discounts',
                    label: 'کۆی داشکاندن',
                    icon: 'fa-percent',
                    gradient: 'card-gradient-dark',
                    value: formatCurrency(Number(data.discounts.usd) || 0, 'USD'),
                    subtitle: 'داشکاندنەکان'
                },
                {
                    key: 'net_profit',
                    label: 'قازانجی خاوێن',
                    icon: 'fa-coins',
                    gradient: 'card-gradient-success',
                    value: formatCurrency(Number(data.net_profit.usd) || 0, 'USD'),
                    subtitle: 'قازانجی پوخت'
                },
                {
                    key: 'total_expenses',
                    label: 'کۆی خەرجی',
                    icon: 'fa-money-bill-wave',
                    gradient: 'card-gradient-danger',
                    value: formatCurrency(Number(data.total_expenses.usd) || 0, 'USD'),
                    subtitle: 'کۆی هەموو خەرجییەکان'
                },
                {
                    key: 'dollar_rate',
                    label: 'نرخی ١٠٠ دۆلار',
                    icon: 'fa-dollar-sign',
                    gradient: 'card-gradient-light',
                    value: formatNumber(Number(data.usd_iqd_rate) || 0) + ' د.ع',
                    subtitle: 'نرخی ئێستا'
                }
            ];

            let html = '';
            cards.forEach(card => {
                html += `<div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-center shadow ${card.gradient} card-animate-hover">
                        <div class="card-body">
                            <i class="fas ${card.icon} card-icon"></i>
                            <h6 class="card-title text-white">${card.label}</h6>
                            <div class="fs-4 fw-bold text-white">${card.value}</div>
                            <small class="text-white">${card.subtitle}</small>
                        </div>
                    </div>
                </div>`;
            });
            
            document.getElementById('dashboard-summary-cards').innerHTML = html;
            if (typeof renderDashboardCards === 'function') renderDashboardCards(result);
            if (typeof renderCharts === 'function') renderCharts(result);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    fetchAndRenderReportData();
    // Filter button click
    document.querySelectorAll('#report-date-filter button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#report-date-filter button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentReportFilter = this.getAttribute('data-filter');
            // Clear date range inputs
            document.getElementById('from-date').value = '';
            document.getElementById('to-date').value = '';
            fetchAndRenderReportData();
        });
    });
    // Date range change
    document.getElementById('from-date').addEventListener('change', function() {
        // Remove active from filter buttons
        document.querySelectorAll('#report-date-filter button').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    document.getElementById('to-date').addEventListener('change', function() {
        document.querySelectorAll('#report-date-filter button').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    // Clear filters button
    document.getElementById('clear-filters-btn').addEventListener('click', function() {
        document.getElementById('from-date').value = '';
        document.getElementById('to-date').value = '';
        // Reset filter buttons to 'year'
        document.querySelectorAll('#report-date-filter button').forEach(b => b.classList.remove('active'));
        const yearBtn = document.querySelector('#report-date-filter button[data-filter="year"]');
        if (yearBtn) yearBtn.classList.add('active');
        currentReportFilter = 'year';
        fetchAndRenderReportData();
    });
});

// Helper to format currency
function formatCurrency(amount, currency) {
    if (currency === 'USD') {
        return Number(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' $';
    } else {
        return Number(amount).toLocaleString('en-US', {maximumFractionDigits: 0}) + ' دینار';
    }
}

function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
