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
            const usd_iqd_rate = data.usd_iqd_rate || 150000;
            const company_debt_usd = Number(data.company.usd) + (Number(data.company.iqd) / (usd_iqd_rate / 100));
            const person_debt_usd = Number(data.person.usd) + (Number(data.person.iqd) / (usd_iqd_rate / 100));
            const purchases_usd = Number(data.purchases.usd) + (Number(data.purchases.iqd) / (usd_iqd_rate / 100));
            const cards = [
                {
                    key: 'customer',
                    label: 'کۆی قەرزی کڕیارەکان',
                    icon: 'fa-users',
                    color: 'var(--kelly-green)',
                    cardClass: 'customer-card',
                    html: function() {
                        return `
                            <div class=\"card-value\">${formatCurrency(data.customer.usd, 'USD')}</div>
                        `;
                    }
                },
                {
                    key: 'company',
                    label: 'قەرزی ئێمە لەگەڵ کۆمپانیاکان',
                    icon: 'fa-building',
                    color: 'var(--seafoam-green)',
                    cardClass: 'company-card',
                    html: function() {
                        return `
                            <div class=\"card-value\">${formatCurrency(company_debt_usd, 'USD')}</div>
                        `;
                    }
                },
                {
                    key: 'person',
                    label: 'قەرزی ئێمە لەگەڵ کەسانی خەرجی تر',
                    icon: 'fa-user-tie',
                    color: 'var(--lime-green)',
                    cardClass: 'person-card',
                    html: function() {
                        const cash_usd = Number(data.person.cash.usd);
                        const credit_usd = Number(data.person.credit.usd);
                        const total_usd = cash_usd + credit_usd;
                        return `
                            <div class=\"card-value\" style=\"font-weight:bold;\">${formatCurrency(total_usd, 'USD')}</div>
                            <div class=\"d-flex justify-content-between align-items-center gap-2\">
                                <div>
                                    <div class=\"section-label\">نەقد</div>
                                    <div class=\"card-value\">${formatCurrency(cash_usd, 'USD')}</div>
                                </div>
                                <div>
                                    <div class=\"section-label\">قەرز</div>
                                    <div class=\"card-value\">${formatCurrency(credit_usd, 'USD')}</div>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    key: 'purchases',
                    label: 'کۆی نرخی کڕین',
                    icon: 'fa-cart-plus',
                    color: 'var(--spearmint)',
                    cardClass: 'purchases-card',
                    html: function() {
                        const cash_usd = Number(data.purchases.cash.usd) + (Number(data.purchases.cash.iqd_converted) || 0);
                        const credit_usd = Number(data.purchases.credit.usd) + (Number(data.purchases.credit.iqd_converted) || 0);
                        const total_usd = cash_usd + credit_usd;
                        return `
                            <div class=\"card-value\" style=\"font-weight:bold;\">${formatCurrency(total_usd, 'USD')}</div>
                            <div class=\"d-flex justify-content-between align-items-center gap-2\">
                                <div>
                                    <div class=\"section-label\">نەقد</div>
                                    <div class=\"card-value\">${formatCurrency(cash_usd, 'USD')}</div>
                                </div>
                                <div>
                                    <div class=\"section-label\">قەرز</div>
                                    <div class=\"card-value\">${formatCurrency(credit_usd, 'USD')}</div>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    key: 'sales',
                    label: 'کۆی نرخی فرۆشتن',
                    icon: 'fa-cash-register',
                    color: 'var(--sales-accent)',
                    cardClass: 'sales-card',
                    html: function() {
                        const cash_usd = Number(data.sales.cash.usd);
                        const credit_usd = Number(data.sales.credit.usd);
                        const total_usd = cash_usd + credit_usd;
                        return `
                            <div class=\"card-value\" style=\"font-weight:bold;\">${formatCurrency(total_usd, 'USD')}</div>
                            <div class=\"d-flex justify-content-between align-items-center gap-2\">
                                <div>
                                    <div class=\"section-label\">نەقد</div>
                                    <div class=\"card-value\">${formatCurrency(cash_usd, 'USD')}</div>
                                </div>
                                <div>
                                    <div class=\"section-label\">قەرز</div>
                                    <div class=\"card-value\">${formatCurrency(credit_usd, 'USD')}</div>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    key: 'remaining_purchases',
                    label: 'کۆی پارەی ماوەی کڕین',
                    icon: 'fa-wallet',
                    color: 'var(--purchases-accent)',
                    cardClass: 'purchases-card',
                    html: function() {
                        return `
                            <div class=\"d-flex justify-content-center align-items-center gap-2\">
                                <div>
                                   
                                    <div class=\"card-value\">${formatCurrency(data.remaining_purchases.usd, 'USD')}</div>
                                </div>
                           
                            </div>
                        `;
                    }
                },
                {
                    key: 'other_expenses',
                    label: 'خەرجی تر',
                    icon: 'fa-wallet',
                    color: 'var(--purchases-accent)',
                    cardClass: 'purchases-card',
                    html: function() {
                        return `

                            <div class=\"card-value\">${formatCurrency(data.other_expenses.usd, 'USD')}</div>
                        `;
                    }
                },
                {
                    key: 'discounts',
                    label: 'کۆی داشکاندن',
                    icon: 'fa-percent',
                    color: 'var(--person-accent)',
                    cardClass: 'person-card',
                    html: function() {
                        return `
                            <div class=\"card-value\">${formatCurrency(data.discounts.usd, 'USD')}</div>
                        `;
                    }
                },
                {
                    key: 'employee_expenses',
                    label: 'خەرجی کارمەندەکان',
                    icon: 'fa-user-friends',
                    color: 'var(--company-accent)',
                    cardClass: 'company-card',
                    html: function() {
                        return `
                            <div class=\"card-value\">${formatCurrency(data.employee_expenses.usd, 'USD')}</div>
                        `;
                    }
                },
                {
                    key: 'net_profit',
                    label: 'قازانجی خاوێن',
                    icon: 'fa-coins',
                    color: 'var(--customer-accent)',
                    cardClass: 'customer-card',
                    html: function() {
                        return `<div class=\"card-value\">${formatCurrency(data.net_profit.usd, 'USD')}</div>`;
                    }
                },
                {
                    key: 'total_expenses',
                    label: 'کۆی خەرجی',
                    icon: 'fa-money-bill-wave',
                    color: '#dc3545',
                    cardClass: 'total-expenses-card',
                    html: function() {
                        const total_expenses = Number(data.total_expenses.usd) || 0;
                        const breakdown = data.total_expenses.breakdown || {};
                        
                        return `
                            <div class=\"card-value\" style=\"font-weight:bold;\">${formatCurrency(total_expenses, 'USD')}</div>
                            <div class=\"d-flex justify-content-between align-items-center gap-2\">
                                <div>
                                    <div class=\"section-label\">پارەدان بە کارمەند</div>
                                    <div class=\"card-value\">${formatCurrency(breakdown.employee_payments || 0, 'USD')}</div>
                                </div>
                                <div>
                                    <div class=\"section-label\">خەرجی تر</div>
                                    <div class=\"card-value\">${formatCurrency(breakdown.other_expenses || 0, 'USD')}</div>
                                </div>
                            </div>
                            <div class=\"d-flex justify-content-between align-items-center gap-2 mt-2\">
                                <div>
                                    <div class=\"section-label\">کڕین مەواد</div>
                                    <div class=\"card-value\">${formatCurrency(breakdown.purchases || 0, 'USD')}</div>
                                </div>
                                <div>
                                    <div class=\"section-label\">کڕینی کاڵا</div>
                                    <div class=\"card-value\">${formatCurrency(breakdown.purchase_materials || 0, 'USD')}</div>
                                </div>
                            </div>
                        `;
                    }
                }
            ];
            let html = '';
            // Group purchases, sales, and person in the same row
            let groupKeys = ['purchases', 'sales', 'person'];
            let groupCards = cards.filter(card => groupKeys.includes(card.key));
            if (groupCards.length === 3) {
                html += '<div class="row">';
                for (let card of groupCards) {
                    html += `
                        <div class=\"col-lg-4 col-md-6 col-sm-12 mb-3\">
                            <div class=\"report-card ${card.cardClass} text-center shadow\">
                                <div class=\"card-body\">
                                    <div class=\"mb-2\"><i class=\"fa ${card.icon}\" style=\"font-size:2.2rem;color:${card.color}\"></i></div>
                                    <h5 class=\"card-title\">${card.label}</h5>
                                    ${card.html()}
                                </div>
                            </div>
                        </div>
                    `;
                }
                html += '</div>';
            }
            // Render the rest of the cards in rows of 3, skipping the grouped ones
            let restCards = cards.filter(card => !groupKeys.includes(card.key));
            for (let i = 0; i < restCards.length; i += 3) {
                html += '<div class="row">';
                for (let j = i; j < i + 3 && j < restCards.length; j++) {
                    const card = restCards[j];
                    html += `
                        <div class=\"col-lg-4 col-md-6 col-sm-12 mb-3\">
                            <div class=\"report-card ${card.cardClass} text-center shadow\">
                                <div class=\"card-body\">
                                    <div class=\"mb-2\"><i class=\"fa ${card.icon}\" style=\"font-size:2.2rem;color:${card.color}\"></i></div>
                                    <h5 class=\"card-title\">${card.label}</h5>
                                    ${card.html()}
                                </div>
                            </div>
                        </div>
                    `;
                }
                html += '</div>';
            }
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
