let currentReportFilter = 'year';

function fetchAndRenderReportData() {
    const fromDate = document.getElementById('from-date')?.value;
    const toDate = document.getElementById('to-date')?.value;
    let url = `../process/reporst/get_information.php?filter=${currentReportFilter}`;
    if (fromDate) url += `&from_date=${fromDate}`;
    if (toDate) url += `&to_date=${toDate}`;
    
    console.log('Fetching data from:', url);
    
    fetch(url)
        .then(res => res.json())
        .then(result => {
            console.log('API Response:', result);
            
            if (!result.success) {
                console.error('API Error:', result.error);
                swalAlert('هەڵە', result.error, 'error');
                return;
            }
            
            const data = result.data;
            console.log('Data received:', data);
            
            // Cards will be rendered by renderDashboardCards function
            if (typeof renderDashboardCards === 'function') {
                console.log('Calling renderDashboardCards');
                renderDashboardCards(result);
            } else {
                console.error('renderDashboardCards function not found');
            }
            
            if (typeof renderCharts === 'function') {
                console.log('Calling renderCharts');
                renderCharts(result);
            } else {
                console.error('renderCharts function not found');
            }
            
            // Render material consumption cards
            renderMaterialConsumptionCards(result);
        })
        .catch(error => {
            console.error('Fetch error:', error);
            swalAlert('هەڵە', 'هەڵە لە وەرگرتنی زانیاری: ' + error.message, 'error');
        });
}

// Function to render dashboard cards with consistent styling
function renderDashboardCards(data) {
    console.log('renderDashboardCards called with data:', data);
    
    const usd_iqd_rate = data.data?.usd_iqd_rate || 0;
    const company_debt_usd = Number(data.data?.company?.usd) || 0;
    const person_debt_usd = Number(data.data?.person?.usd) || 0;
    const purchases_cash_usd = Number(data.data?.purchases?.cash?.usd) || 0;
    const purchases_credit_usd = Number(data.data?.purchases?.credit?.usd) || 0;
    const purchases_usd = purchases_cash_usd + purchases_credit_usd;
    
    console.log('Extracted values:', {
        usd_iqd_rate,
        company_debt_usd,
        person_debt_usd,
        purchases_cash_usd,
        purchases_credit_usd,
        purchases_usd
    });
    
    const cards = [
        {
            key: 'customer',
            label: 'کۆی قەرزی کڕیارەکان',
            icon: 'fa-users',
            cardClass: 'customer-card',
            value: formatCurrency(data.data?.customer?.usd || 0, 'USD'),
            subtitle: 'قەرزی کڕیارەکان'
        },
        {
            key: 'company',
            label: 'قەرزی ئێمە لەگەڵ کۆمپانیاکان',
            icon: 'fa-building',
            cardClass: 'company-card',
            value: formatCurrency(company_debt_usd, 'USD'),
            subtitle: 'قەرزی کۆمپانیاکان'
        },
        {
            key: 'person',
            label: 'قەرزی ئێمە لەگەڵ کەسانی خەرجی تر',
            icon: 'fa-user-tie',
            cardClass: 'person-card',
            value: formatCurrency(person_debt_usd, 'USD'),
            subtitle: 'قەرزی کەسانی تر'
        },
        {
            key: 'purchases',
            label: 'کۆی نرخی کڕین',
            icon: 'fa-cart-plus',
            cardClass: 'purchases-card',
            value: formatCurrency(purchases_usd, 'USD'),
            subtitle: 'کۆی کڕینەکان'
        },
        {
            key: 'sales',
            label: 'کۆی نرخی فرۆشتن',
            icon: 'fa-cash-register',
            cardClass: 'sales-card',
            value: formatCurrency((Number(data.data?.sales?.cash?.usd) || 0) + (Number(data.data?.sales?.credit?.usd) || 0), 'USD'),
            subtitle: 'کۆی فرۆشتنەکان'
        },

        {
            key: 'discounts',
            label: 'کۆی داشکاندن',
            icon: 'fa-percent',
            cardClass: 'dark-card',
            value: formatCurrency(Number(data.data?.discounts?.usd) || 0, 'USD'),
            subtitle: 'داشکاندنەکان'
        },

        {
            key: 'total_expenses',
            label: 'کۆی خەرجی',
            icon: 'fa-money-bill-wave',
            cardClass: 'total-expenses-card',
            value: formatCurrency(Number(data.data?.total_expenses?.usd) || 0, 'USD'),
            subtitle: 'کۆی خەرجی'
        },
        {
            key: 'employee_expenses',
            label: 'کۆی خەرجی کارمەندان',
            icon: 'fa-user-tie',
            cardClass: 'employee-expenses-card',
            value: formatCurrency(Number(data.data?.total_expenses?.breakdown?.employee_payments) || 0, 'USD'),
            subtitle: 'پارەدان بە کارمەند'
        },
        {
            key: 'other_expenses',
            label: 'کۆی خەرجی تر',
            icon: 'fa-receipt',
            cardClass: 'other-expenses-card',
            value: formatCurrency(Number(data.data?.total_expenses?.breakdown?.other_expenses) || 0, 'USD'),
            subtitle: 'خەرجی تر'
        },
        {
            key: 'purchase_materials',
            label: 'کۆی نرخی کڕینی کاڵا',
            icon: 'fa-boxes',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(Number(data.data?.total_expenses?.breakdown?.purchase_materials) || 0, 'USD'),
            subtitle: 'کڕینی کاڵای کۆگا'
        },
        {
            key: 'gas_income',
            label: 'کۆی داهاتی گاز',
            icon: 'fa-gas-pump',
            cardClass: 'gas-income-card',
            value: formatCurrency(Number(data.data?.gas_income?.usd) || 0, 'USD'),
            subtitle: 'داهاتی گاز'
        },
        {
            key: 'usd_rate',
            label: 'نرخی ١٠٠ دۆلار',
            icon: 'fa-dollar-sign',
            cardClass: 'dollar-rate-card',
            value: formatCurrency(usd_iqd_rate, 'IQD'),
            subtitle: 'نرخی دۆلار بە دینار'
        }
    ];
    
    console.log('Cards array created:', cards);
    
    let html = '';
    cards.forEach(card => {
        html += `<div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="report-card ${card.cardClass}">
                <i class="fa ${card.icon}"></i>
                <div class="card-title">${card.label}</div>
                <div class="card-value">${card.value}</div>
                <div class="section-label">${card.subtitle}</div>
            </div>
        </div>`;
    });
    
    console.log('HTML generated:', html);
    console.log('Target element:', document.getElementById('dashboard-summary-cards'));
    
    const targetElement = document.getElementById('dashboard-summary-cards');
    if (targetElement) {
        targetElement.innerHTML = html;
        console.log('Cards rendered successfully');
    } else {
        console.error('Target element dashboard-summary-cards not found');
    }
}

// Function to render material consumption cards
function renderMaterialConsumptionCards(data) {
    console.log('renderMaterialConsumptionCards called with data:', data);
    
    const materialConsumption = data.data?.material_consumption || [];
    
    console.log('Material consumption data:', materialConsumption);
    
    if (materialConsumption.length === 0) {
        console.log('No material consumption data available');
        return;
    }
    
    let html = '';
    materialConsumption.forEach(material => {
        const consumptionKg = formatNumber(material.consumption_kg);
        const consumptionTon = material.consumption_ton.toFixed(3);
        
        html += `<div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-gradient-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cube me-2"></i>
                        ${material.material_name}
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-6">
                            <div class="material-consumption-value">
                                <div class="consumption-number">${consumptionKg}</div>
                                <div class="consumption-unit">کیلۆگرام</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="material-consumption-value">
                                <div class="consumption-number">${consumptionTon}</div>
                                <div class="consumption-unit">طەن</div>
                            </div>
                        </div>
                    </div>
                    <div class="material-bin-info mt-2">
                        <small class="text-muted">
                            <i class="fas fa-box me-1"></i>
                            ${material.bin_name}
                        </small>
                    </div>
                </div>
            </div>
        </div>`;
    });
    
    const targetElement = document.getElementById('material-consumption-cards');
    if (targetElement) {
        targetElement.innerHTML = html;
        console.log('Material consumption cards rendered successfully');
    } else {
        console.error('Target element material-consumption-cards not found');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    fetchAndRenderReportData();
    // Filter button click
    document.querySelectorAll('#report-date-filter .filter-tab').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
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
        document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    document.getElementById('to-date').addEventListener('change', function() {
        document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    // Clear filters button
    document.getElementById('clear-filters-btn').addEventListener('click', function() {
        document.getElementById('from-date').value = '';
        document.getElementById('to-date').value = '';
        // Reset filter buttons to 'year'
        document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
        const yearBtn = document.querySelector('#report-date-filter .filter-tab[data-filter="year"]');
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
