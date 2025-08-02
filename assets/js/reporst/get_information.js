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
                    value: formatCurrency(data.customer.usd, 'USD'),
                    subtitle: 'قەرزی کڕیارەکان',
                    showBreakdown: false
                },
                {
                    key: 'company',
                    label: 'قەرزی ئێمە لەگەڵ کۆمپانیاکان',
                    icon: 'fa-building',
                    gradient: 'card-gradient-warning',
                    value: formatCurrency(company_debt_usd, 'USD'),
                    subtitle: 'قەرزی کۆمپانیاکان',
                    showBreakdown: false
                },
                {
                    key: 'person',
                    label: 'قەرزی ئێمە لەگەڵ کەسانی خەرجی تر',
                    icon: 'fa-user-tie',
                    gradient: 'card-gradient-purple',
                    value: formatCurrency(person_debt_usd, 'USD'),
                    subtitle: 'قەرزی کەسانی تر',
                    showBreakdown: false
                },
                {
                    key: 'purchases',
                    label: 'کۆی نرخی کڕین',
                    icon: 'fa-cart-plus',
                    gradient: 'card-gradient-teal',
                    value: formatCurrency(purchases_usd, 'USD'),
                    subtitle: 'کۆی کڕینەکان',
                    showBreakdown: true,
                    breakdown: {
                        cash: formatCurrency(purchases_cash_usd, 'USD'),
                        credit: formatCurrency(purchases_credit_usd, 'USD')
                    }
                },
                {
                    key: 'sales',
                    label: 'کۆی نرخی فرۆشتن',
                    icon: 'fa-cash-register',
                    gradient: 'card-gradient-orange',
                    value: formatCurrency((Number(data.sales.cash.usd) || 0) + (Number(data.sales.credit.usd) || 0), 'USD'),
                    subtitle: 'کۆی فرۆشتنەکان',
                    showBreakdown: true,
                    breakdown: {
                        cash: formatCurrency(Number(data.sales.cash.usd) || 0, 'USD'),
                        credit: formatCurrency(Number(data.sales.credit.usd) || 0, 'USD')
                    }
                },
                {
                    key: 'remaining_purchases',
                    label: 'کۆی پارەی ماوەی کڕین',
                    icon: 'fa-wallet',
                    gradient: 'card-gradient-info',
                    value: formatCurrency(Number(data.remaining_purchases.usd) || 0, 'USD'),
                    subtitle: 'پارەی ماوە',
                    showBreakdown: false
                },
                {
                    key: 'discounts',
                    label: 'کۆی داشکاندن',
                    icon: 'fa-percent',
                    gradient: 'card-gradient-dark',
                    value: formatCurrency(Number(data.discounts.usd) || 0, 'USD'),
                    subtitle: 'داشکاندنەکان',
                    showBreakdown: false
                },
                {
                    key: 'net_profit',
                    label: 'قازانجی خاوێن',
                    icon: 'fa-coins',
                    gradient: 'card-gradient-success',
                    value: formatCurrency(Number(data.net_profit.usd) || 0, 'USD'),
                    subtitle: 'قازانجی پوخت',
                    showBreakdown: false
                },
                {
                    key: 'total_expenses',
                    label: 'کۆی خەرجی',
                    icon: 'fa-money-bill-wave',
                    gradient: 'card-gradient-danger',
                    value: formatCurrency(Number(data.total_expenses.usd) || 0, 'USD'),
                    subtitle: 'کۆی هەموو خەرجییەکان',
                    showBreakdown: true,
                    breakdown: {
                        employee_payments: formatCurrency(Number(data.total_expenses.breakdown?.employee_payments) || 0, 'USD'),
                        other_expenses: formatCurrency(Number(data.total_expenses.breakdown?.other_expenses) || 0, 'USD'),
                        purchases: formatCurrency(Number(data.total_expenses.breakdown?.purchases) || 0, 'USD'),
                        purchase_materials: formatCurrency(Number(data.total_expenses.breakdown?.purchase_materials) || 0, 'USD')
                    }
                },
                {
                    key: 'dollar_rate',
                    label: 'نرخی ١٠٠ دۆلار',
                    icon: 'fa-dollar-sign',
                    gradient: 'card-gradient-light',
                    value: formatNumber(Number(data.usd_iqd_rate) || 0) + ' د.ع',
                    subtitle: 'نرخی ئێستا',
                    showBreakdown: false
                }
            ];

            let html = '';
            cards.forEach(card => {
                let breakdownHtml = '';
                if (card.showBreakdown && card.breakdown) {
                    if (card.key === 'total_expenses') {
                        // Special layout for total expenses with 4 breakdown items
                        breakdownHtml = `
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-white-50">پارەدان بە کارمەند</small>
                                    <div class="text-white small">${card.breakdown.employee_payments}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-white-50">خەرجی تر</small>
                                    <div class="text-white small">${card.breakdown.other_expenses}</div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-6">
                                    <small class="text-white-50">کڕین مەواد</small>
                                    <div class="text-white small">${card.breakdown.purchases}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-white-50">کڕینی کاڵا</small>
                                    <div class="text-white small">${card.breakdown.purchase_materials}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        // Standard layout for cash/credit breakdown
                        breakdownHtml = `
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-white-50">نەقد</small>
                                    <div class="text-white small">${card.breakdown.cash}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-white-50">قەرز</small>
                                    <div class="text-white small">${card.breakdown.credit}</div>
                                </div>
                            </div>
                        `;
                    }
                }
                
                html += `<div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-center shadow ${card.gradient} card-animate-hover">
                        <div class="card-body">
                            <i class="fas ${card.icon} card-icon"></i>
                            <h6 class="card-title text-white">${card.label}</h6>
                            <div class="fs-4 fw-bold text-white">${card.value}</div>
                            <small class="text-white">${card.subtitle}</small>
                            ${breakdownHtml}
                        </div>
                    </div>
                </div>`;
            });
            
            document.getElementById('dashboard-summary-cards').innerHTML = html;
            if (typeof renderDashboardCards === 'function') renderDashboardCards(result);
            if (typeof renderCharts === 'function') renderCharts(result);
            
            // Populate additional professional reports
            // populateEmployeeReports(data); // Removed
            // populateCarReports(data); // Removed
            // populateStockReports(data); // Removed
            // populateActivityReports(data); // Removed
        });
}

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
