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
        })
        .catch(error => {
            console.error('Fetch error:', error);
            swalAlert('هەڵە', 'هەڵە لە وەرگرتنی زانیاری: ' + error.message, 'error');
        });
}

// Function to format material consumption values
function formatMaterialConsumption(value, unit) {
    if (value === 0) return `0 ${unit}`;
    if (value < 1) return `${(value * 1000).toFixed(0)} کیلۆگرام`;
    return `${value.toFixed(2)} ${unit}`;
}

// Function to get stock status text
function getStockStatusText(currentStock) {
    if (!currentStock || Object.keys(currentStock).length === 0) {
        return 'هیچ زانیارییەک نییە';
    }
    
    let totalItems = 0;
    let lowStockItems = 0;
    
    Object.values(currentStock).forEach(stock => {
        totalItems++;
        if (stock.amount < 1000) { // Less than 1 ton
            lowStockItems++;
        }
    });
    
    if (lowStockItems === 0) {
        return 'کۆگا پڕە';
    } else if (lowStockItems <= totalItems * 0.3) {
        return 'کۆگا باشە';
    } else {
        return 'کۆگا کەمە';
    }
}

// Function to format stock vs consumption information
function formatStockInfo(stock, consumption) {
    if (!stock || !stock.amount) {
        return `بەکارهێنان: ${consumption.toFixed(2)} تۆن`;
    }
    
    const stockTons = stock.amount;
    const consumptionTons = consumption;
    const remaining = stockTons - consumptionTons;
    
    if (remaining <= 0) {
        return `ستۆک: 0 تۆن (کەمە)`;
    } else if (remaining < stockTons * 0.2) {
        return `ستۆک: ${remaining.toFixed(2)} تۆن (کەمە)`;
    } else {
        return `ستۆک: ${remaining.toFixed(2)} تۆن (باشە)`;
    }
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
    const purchases_cash_iqd = Number(data.data?.purchases?.cash?.iqd) || 0;
    const purchases_credit_iqd = Number(data.data?.purchases?.credit?.iqd) || 0;
    const purchases_iqd_total = purchases_cash_iqd + purchases_credit_iqd;
    
    console.log('Extracted values:', {
        usd_iqd_rate,
        company_debt_usd,
        person_debt_usd,
        purchases_cash_usd,
        purchases_credit_usd,
        purchases_usd,
        purchases_cash_iqd,
        purchases_credit_iqd,
        purchases_iqd_total
    });
    
    const discountsTotalUsd = Number(data.data?.discounts?.total_usd) || 0;
    const salesDiscountUsd = Number(data.data?.discounts?.sales_usd) || 0;
    const debtDiscountUsd = Number(data.data?.discounts?.customer_debt_usd) || 0;

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
            key: 'customer_debt_received',
            label: 'کۆی قەرزی وەرگیراو لە کڕیار',
            icon: 'fa-hand-holding-usd',
            cardClass: 'customer-debt-received-card',
            value: formatCurrency(data.data?.customer_debt_payments?.usd || 0, 'USD') + '<br><small style="font-size: 0.9rem; opacity: 0.9;">' + formatCurrency(data.data?.customer_debt_payments?.iqd || 0, 'IQD') + '</small>',
            subtitle: 'پارەی وەرگیراو لە کڕیار'
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
            key: 'purchases_iqd',
            label: 'کۆی نرخی کڕین بە دینار',
            icon: 'fa-cart-plus',
            cardClass: 'purchases-card',
            value: formatCurrency(purchases_iqd_total, 'IQD'),
            subtitle: 'کۆی کڕینەکان بە دینار'
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
            key: 'sales_discounts',
            label: 'داشکاندنی فرۆشتن',
            icon: 'fa-percent',
            cardClass: 'dark-card',
            value: formatCurrency(salesDiscountUsd, 'USD'),
            subtitle: 'کۆی داشکاندنی فرۆشتن'
        },
        {
            key: 'customer_debt_discounts',
            label: 'داشکاندنی گەڕاندنەوەی قەرز',
            icon: 'fa-percent',
            cardClass: 'dark-card',
            value: formatCurrency(debtDiscountUsd, 'USD'),
            subtitle: 'داشکاندنی پارەی قەرز'
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
        },
        // Material Consumption Cards
        // cement_cem1 = دەلتا + لاڤارج (سایلۆی یەک)
        // cement_cem2 = ماس (سایلۆی دوو)
        {
            key: 'black_sand_consumption',
            label: 'بەکارهێنانی لمی کەسارە',
            icon: 'fa-cubes',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.black_sand || 0, 'تۆن'),
            subtitle: 'چاوی ١ - لمی کەسارە'
        },
        {
            key: 'brown_sand_consumption',
            label: 'بەکارهێنانی لمی ڕەش',
            icon: 'fa-cubes',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.brown_sand || 0, 'تۆن'),
            subtitle: 'چاوی ٢ - لمی ڕەش'
        },
        {
            key: 'gravel_bin3_consumption',
            label: 'بەکارهێنانی چەوی چاوی ٣',
            icon: 'fa-cubes',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.gravel_bin3 || 0, 'تۆن'),
            subtitle: 'چەوی چاوی ٣'
        },
        {
            key: 'gravel_bin4_consumption',
            label: 'بەکارهێنانی چەوی چاوی ٤',
            icon: 'fa-cubes',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.gravel_bin4 || 0, 'تۆن'),
            subtitle: 'چەوی چاوی ٤'
        },
        {
            key: 'cement_cem1_consumption',
            label: 'بەکارهێنانی چیمەنتۆی سایلۆی ١',
            icon: 'fa-industry',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.cement_cem1 || 0, 'تۆن'),
            subtitle: 'دەلتا + لاڤارج'
        },
        {
            key: 'cement_cem2_consumption',
            label: 'بەکارهێنانی چیمەنتۆی سایلۆی ٢',
            icon: 'fa-industry',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.cement_cem2 || 0, 'تۆن'),
            subtitle: 'ماس'
        },
        // Material Summary Cards
        {
            key: 'total_material_consumption',
            label: 'کۆی بەکارهێنانی ماتریاڵەکان',
            icon: 'fa-chart-line',
            cardClass: 'material-summary-card',
            value: formatMaterialConsumption(
                (data.data?.material_consumption?.tons?.black_sand || 0) +
                (data.data?.material_consumption?.tons?.brown_sand || 0) +
                (data.data?.material_consumption?.tons?.gravel_bin3 || 0) +
                (data.data?.material_consumption?.tons?.gravel_bin4 || 0) +
                (data.data?.material_consumption?.tons?.cement_cem1 || 0) +
                (data.data?.material_consumption?.tons?.cement_cem2 || 0), 'تۆن'),
            subtitle: 'کۆی هەموو ماتریاڵەکان'
        },
        {
            key: 'current_stock_status',
            label: 'دۆخی ئێستای کۆگا',
            icon: 'fa-warehouse',
            cardClass: 'stock-status-card',
            value: getStockStatusText(data.data?.material_consumption?.current_stock || {}),
            subtitle: 'بەکارهێنانی ماتریاڵەکان'
        },
        // Stock vs Consumption Cards
        {
            key: 'black_sand_stock',
            label: 'ستۆکی لمی کەسارە',
            icon: 'fa-cube',
            cardClass: 'stock-vs-consumption-card',
            value: formatStockInfo(data.data?.material_consumption?.current_stock?.['لمی کەسارە'] || {}, data.data?.material_consumption?.tons?.black_sand || 0),
            subtitle: 'چاوی ١ - ستۆک vs بەکارهێنان'
        },
        {
            key: 'brown_sand_stock',
            label: 'ستۆکی لمی ڕەش',
            icon: 'fa-cube',
            cardClass: 'stock-vs-consumption-card',
            value: formatStockInfo(data.data?.material_consumption?.current_stock?.['لمی ڕەش'] || {}, data.data?.material_consumption?.tons?.brown_sand || 0),
            subtitle: 'چاوی ٢ - ستۆک vs بەکارهێنان'
        },
        {
            key: 'cement_stock',
            label: 'ستۆکی چیمەنتۆ',
            icon: 'fa-industry',
            cardClass: 'stock-vs-consumption-card',
            value: formatStockInfo(
                {
                    amount: (data.data?.material_consumption?.current_stock?.['چیمەنتۆ']?.amount || 0) / 1000,
                    value: data.data?.material_consumption?.current_stock?.['چیمەنتۆ']?.value || 0
                },
                (data.data?.material_consumption?.tons?.cement_cem1 || 0) + (data.data?.material_consumption?.tons?.cement_cem2 || 0)
            ),
            subtitle: 'سایلۆی ١ (دەلتا+لاڤارج) + سایلۆی ٢ (ماس) - ستۆک vs بەکارهێنان'
        }
    ];
    
    // Material consumption summary:
    // - cement_cem1: دەلتا + لاڤارج (سایلۆی یەک)
    // - cement_cem2: ماس (سایلۆی دوو)
    // - Total cement = دەلتا + لاڤارج + ماس
    // - Total materials = لمی کەسارە + لمی ڕەش + چەوی چاوی ٣ + چەوی چاوی ٤ + دەلتا + لاڤارج + ماس
    
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
