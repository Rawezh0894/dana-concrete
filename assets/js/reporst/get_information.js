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

    // Profit & Loss Tab Cards (تابی قازانج و زەرەر)
    const profitLossCards = [
        {
            key: 'usd_rate',
            label: 'نرخی ١٠٠ دۆلار',
            icon: 'fa-dollar-sign',
            cardClass: 'dollar-rate-card',
            value: formatCurrency(data.data?.usd_iqd_rate || 0, 'IQD'),
            subtitle: 'نرخی دۆلار بە دینار (لە settings)'
        },
        {
            key: 'sales',
            label: 'کۆی نرخی فرۆشتن',
            icon: 'fa-cash-register',
            cardClass: 'sales-card',
            value: formatCurrency((Number(data.data?.sales?.cash?.usd) || 0) + (Number(data.data?.sales?.credit?.usd) || 0), 'USD'),
            subtitle: 'کۆی فرۆشتنەکان (نەقد + قەرز)'
        },
        {
            key: 'raw_material_sales',
            label: 'کۆی نرخی فرۆشتنی مەوادی خام',
            icon: 'fa-box-seam',
            cardClass: 'raw-material-sales-card',
            value: formatCurrency(Number(data.data?.raw_material_sales?.total_usd) || 0, 'USD'),
            subtitle: 'فرۆشتنی چەو، لم، چیمەنتۆ، دەرمان، گاز'
        },
        {
            key: 'total_material_usage_cost',
            label: 'کۆی گشتی تێچووی مەوادەکان',
            icon: 'fa-calculator',
            cardClass: 'total-expenses-card',
            value: formatCurrency(data.data?.material_consumption?.total_cost_usd || 0, 'USD'),
            subtitle: 'کۆی نرخی کڕینی مەوادی بەکارهاتوو'
        },
        {
            key: 'employee_total_fixed',
            label: 'کۆی مووچەی کارمەندان',
            icon: 'fa-money-check-alt',
            cardClass: 'employee-expenses-card',
            value: formatCurrency(Number(data.data?.employee_stats?.total_fixed_usd) || 0, 'USD'),
            subtitle: 'مووچە + بەخشیش (بە دۆلار)'
        },
        {
            key: 'caravan_hisabi',
            label: 'کۆی کاروان حیسابی',
            icon: 'fa-truck',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(Number(data.data?.caravan_hisabi?.total_usd) || 0, 'USD'),
            subtitle: 'مووچەی کاروان حیسابی بۆ شۆفێرەکانی میکسەر'
        },
        {
            key: 'total_expenses',
            label: 'کۆی نرخی خەرجی',
            icon: 'fa-money-bill-wave',
            cardClass: 'total-expenses-card',
            value: formatCurrency(Number(data.data?.total_expenses?.usd) || 0, 'USD'),
            subtitle: 'کۆی خەرجی (نەقد + قەرز)'
        },
        {
            key: 'sales_discounts',
            label: 'داشکاندنی فرۆشتن',
            icon: 'fa-percent',
            cardClass: 'dark-card',
            value: formatCurrency(Number(data.data?.discounts?.sales_usd) || 0, 'USD'),
            subtitle: 'کۆی داشکاندنی فرۆشتن'
        },
        {
            key: 'customer_debt_discounts',
            label: 'داشکاندنی گەڕاندنەوەی قەرز',
            icon: 'fa-percent',
            cardClass: 'dark-card',
            value: formatCurrency(Number(data.data?.discounts?.customer_debt_usd) || 0, 'USD'),
            subtitle: 'داشکاندنی پارەی قەرز'
        },
        {
            key: 'profit_loss',
            label: 'قازانج و زەرەر',
            icon: 'fa-chart-line',
            cardClass: (Number(data.data?.profit_loss?.profit_loss) || 0) >= 0 ? 'success-card' : 'total-expenses-card',
            value: formatCurrency(Number(data.data?.profit_loss?.profit_loss) || 0, 'USD'),
            subtitle: (Number(data.data?.profit_loss?.profit_loss) || 0) >= 0 ? 'قازانج' : 'زەرەر'
        }
    ];

    // Other Cards (کارتەکانی دیکە)
    const otherCards = [
        {
            key: 'customer',
            label: 'کۆی قەرزی کڕیارەکان',
            icon: 'fa-users',
            cardClass: 'customer-card',
            value: formatCurrency(data.data?.customer?.usd || 0, 'USD'),
            subtitle: 'قەرزی کڕیارەکان'
        },
        {
            key: 'customer_debt_received_usd',
            label: 'کۆی قەرزی وەرگیراو لە کڕیار (دۆلار)',
            icon: 'fa-hand-holding-usd',
            cardClass: 'customer-debt-received-card',
            value: formatCurrency(data.data?.customer_debt_payments?.usd_amount || 0, 'USD'),
            subtitle: 'پارەی وەرگیراو لە کڕیار بە دۆلار'
        },
        {
            key: 'customer_debt_received_iqd',
            label: 'کۆی قەرزی وەرگیراو لە کڕیار (دینار)',
            icon: 'fa-hand-holding-usd',
            cardClass: 'customer-debt-received-card',
            value: formatCurrency(data.data?.customer_debt_payments?.iqd || 0, 'IQD'),
            subtitle: 'پارەی وەرگیراو لە کڕیار بە دینار'
        },
        {
            key: 'person_debt_payments',
            label: 'کۆی پارەی دانەوە بۆ کەسانی خەرجی تر',
            icon: 'fa-hand-holding-usd',
            cardClass: 'person-debt-payments-card',
            value: formatCurrency(data.data?.person_debt_payments?.usd || 0, 'USD') + '<br><small style="font-size: 0.9rem; opacity: 0.9;">' + formatCurrency(data.data?.person_debt_payments?.iqd || 0, 'IQD') + '</small>',
            subtitle: 'پارەی دانەوە بۆ کەسانی خەرجی تر'
        },
        {
            key: 'company_debt_payments_usd',
            label: 'کۆی دانەوەی قەرزی کۆمپانیا (دۆلار)',
            icon: 'fa-building',
            cardClass: 'company-debt-payments-card',
            value: formatCurrency(data.data?.company_debt_payments?.usd_amount || 0, 'USD'),
            subtitle: 'پارەی دانەوە بۆ کۆمپانیاکان بە دۆلار'
        },
        {
            key: 'company_debt_payments_iqd',
            label: 'کۆی دانەوەی قەرزی کۆمپانیا (دینار)',
            icon: 'fa-building',
            cardClass: 'company-debt-payments-card',
            value: formatCurrency(data.data?.company_debt_payments?.iqd || 0, 'IQD'),
            subtitle: 'پارەی دانەوە بۆ کۆمپانیاکان بە دینار'
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
            value: formatCurrency(data.data?.person?.usd || 0, 'USD') + '<br><small style="font-size: 0.9rem; opacity: 0.9;">' + formatCurrency(data.data?.person?.iqd || 0, 'IQD') + '</small>',
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
            key: 'cash_sales_usd',
            label: 'کۆی فرۆشتن بە نەقدی (دۆلار)',
            icon: 'fa-money-bill',
            cardClass: 'cash-sales-card',
            value: formatCurrency(data.data?.cash_sales?.paid_usd || 0, 'USD'),
            subtitle: 'پارەی وەرگیراو بە نەقدی بە دۆلار'
        },
        {
            key: 'cash_sales_iqd',
            label: 'کۆی فرۆشتن بە نەقدی (دینار)',
            icon: 'fa-money-bill',
            cardClass: 'cash-sales-card',
            value: formatCurrency(data.data?.cash_sales?.paid_iqd || 0, 'IQD'),
            subtitle: 'پارەی وەرگیراو بە نەقدی بە دینار'
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
            key: 'employee_total_fixed',
            label: 'کۆی مووچەی کارمەندان',
            icon: 'fa-money-check-alt',
            cardClass: 'employee-expenses-card',
            value: formatCurrency(Number(data.data?.employee_stats?.total_fixed_usd) || 0, 'USD'),
            subtitle: 'مووچە + بەخشیش (بە دۆلار)'
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
        {
            key: 'additive_consumption',
            label: 'بەکارهێنانی دەرمان (زیادکراو)',
            icon: 'fa-flask',
            cardClass: 'material-consumption-card',
            value: formatMaterialConsumption(data.data?.material_consumption?.tons?.additive || 0, 'تۆن'),
            subtitle: 'دەرمانی کۆنکرێت'
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
        },
        // Material Cost Cards
        {
            key: 'black_sand_cost',
            label: 'تێچووی لمی کەسارە',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.black_sand || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.black_sand || 0, 'USD')} / تۆن`
        },
        {
            key: 'brown_sand_cost',
            label: 'تێچووی لمی ڕەش',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.brown_sand || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.brown_sand || 0, 'USD')} / تۆن`
        },
        {
            key: 'gravel_bin3_cost',
            label: 'تێچووی چەوی چاوی ٣',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.gravel_bin3 || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.gravel || 0, 'USD')} / تۆن`
        },
        {
            key: 'gravel_bin4_cost',
            label: 'تێچووی چەوی چاوی ٤',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.gravel_bin4 || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.gravel || 0, 'USD')} / تۆن`
        },
        {
            key: 'cement_cem1_cost',
            label: 'تێچووی چیمەنتۆی سایلۆی ١',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.cement_cem1 || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.cement || 0, 'USD')} / تۆن`
        },
        {
            key: 'cement_cem2_cost',
            label: 'تێچووی چیمەنتۆی سایلۆی ٢',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.cement_cem2 || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.cement || 0, 'USD')} / تۆن`
        },
        {
            key: 'additive_cost',
            label: 'تێچووی دەرمان',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.material_consumption?.costs?.additive || 0, 'USD'),
            subtitle: `تێکڕای نرخ: ${formatCurrency(data.data?.material_consumption?.prices?.additive || 0, 'USD')} / تۆن`
        },
        {
            key: 'raw_material_sales_cost',
            label: 'کۆی تێچووی فرۆشتنی مەوادی خام',
            icon: 'fa-dollar-sign',
            cardClass: 'purchase-materials-card',
            value: formatCurrency(data.data?.raw_material_sales?.cost_usd || 0, 'USD'),
            subtitle: 'تێچووی مەوادی خامە فرۆشراوەکان (چەو، لم، چیمەنتۆ، دەرمان، گاز)'
        }
    ];

    // Combine all cards
    const cards = [...profitLossCards, ...otherCards];

    // Material consumption summary:
    // - cement_cem1: دەلتا + لاڤارج (سایلۆی یەک)
    // - cement_cem2: ماس (سایلۆی دوو)
    // - Total cement = دەلتا + لاڤارج + ماس
    // - Total materials = لمی کەسارە + لمی ڕەش + چەوی چاوی ٣ + چەوی چاوی ٤ + دەلتا + لاڤارج + ماس

    console.log('Cards array created:', cards);

    // Create tabs structure
    let html = `
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist" style="border-bottom: 2px solid #dee2e6;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profit-loss-tab" data-bs-toggle="tab" data-bs-target="#profit-loss" type="button" role="tab" aria-controls="profit-loss" aria-selected="true" style="font-weight: bold; color: #28a745;">
                            <i class="fa fa-chart-line me-2"></i>قازانج و زەرەر
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="other-cards-tab" data-bs-toggle="tab" data-bs-target="#other-cards" type="button" role="tab" aria-controls="other-cards" aria-selected="false" style="font-weight: bold; color: #007bff;">
                            <i class="fa fa-list me-2"></i>کارتەکانی دیکە
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="tab-content" id="reportTabContent">
            <div class="tab-pane fade show active" id="profit-loss" role="tabpanel" aria-labelledby="profit-loss-tab">
                <div class="row" id="profit-loss-cards">
    `;

    // Render profit & loss cards
    profitLossCards.forEach(card => {
        html += `<div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="report-card ${card.cardClass}">
                <i class="fa ${card.icon}"></i>
                <div class="card-title">${card.label}</div>
                <div class="card-value">${card.value}</div>
                <div class="section-label">${card.subtitle}</div>
            </div>
        </div>`;
    });

    html += `
                </div>
            </div>
            <div class="tab-pane fade" id="other-cards" role="tabpanel" aria-labelledby="other-cards-tab">
                <div class="row" id="other-cards-container">
    `;

    // Render other cards
    otherCards.forEach(card => {
        // Add click handler for company_debt_payments cards (both USD and IQD)
        let clickHandler = '';
        let cursorStyle = '';

        if (card.key === 'company_debt_payments_usd' || card.key === 'company_debt_payments_iqd') {
            clickHandler = 'onclick="showCompanyDebtPaymentsDetails()"';
            cursorStyle = 'style="cursor: pointer;"';
        } else if (card.key === 'person_debt_payments') {
            clickHandler = 'onclick="showPersonDebtPaymentsDetails()"';
            cursorStyle = 'style="cursor: pointer;"';
        }

        html += `<div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="report-card ${card.cardClass}" ${clickHandler} ${cursorStyle}>
                <i class="fa ${card.icon}"></i>
                <div class="card-title">${card.label}</div>
                <div class="card-value">${card.value}</div>
                <div class="section-label">${card.subtitle}</div>
            </div>
        </div>`;
    });

    html += `
                </div>
            </div>
        </div>
    `;

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

document.addEventListener('DOMContentLoaded', function () {
    // Initial load
    fetchAndRenderReportData();
    // Filter button click
    document.querySelectorAll('#report-date-filter .filter-tab').forEach(btn => {
        btn.addEventListener('click', function () {
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
    document.getElementById('from-date').addEventListener('change', function () {
        // Remove active from filter buttons
        document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    document.getElementById('to-date').addEventListener('change', function () {
        document.querySelectorAll('#report-date-filter .filter-tab').forEach(b => b.classList.remove('active'));
        fetchAndRenderReportData();
    });
    // Clear filters button
    document.getElementById('clear-filters-btn').addEventListener('click', function () {
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
        return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $';
    } else {
        return Number(amount).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' دینار';
    }
}

function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0';
    }
    const num = parseFloat(amount);
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Function to show company debt payments details
function showCompanyDebtPaymentsDetails() {
    const fromDate = document.getElementById('from-date')?.value || '';
    const toDate = document.getElementById('to-date')?.value || '';

    // Build URL with date filters
    let url = '../process/reporst/get_company_debt_payments_details.php';
    const params = new URLSearchParams();
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    if (params.toString()) url += '?' + params.toString();

    console.log('Fetching company debt payments details from:', url);

    // Show loading
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'وردەکاریەکان وەردەگرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(url)
        .then(res => {
            console.log('Response status:', res.status);
            if (!res.ok) {
                throw new Error('HTTP error! status: ' + res.status);
            }
            return res.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const result = JSON.parse(text);
                console.log('Parsed result:', result);
                if (!result.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: result.error || 'هەڵەیەک ڕویدا'
                    });
                    return null;
                }
                return result;
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵە لە وەرگرتنی وردەکاریەکان: ' + e.message
                });
                return null;
            }
        })
        .then(result => {
            if (!result) return;

            const data = result.data || {};
            const totalUsd = parseFloat(data.total_usd || 0);
            const totalIqd = parseFloat(data.total_iqd || 0);
            const usdAmount = parseFloat(data.usd_amount || 0);
            const count = parseInt(data.count || 0);
            const payments = data.payments || [];

            // Build details HTML
            let detailsHtml = `
                <div style="text-align: right; direction: rtl; font-family: 'Rabar', sans-serif;">
                    <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <h5 style="color: #1976d2; margin-bottom: 0.5rem;">کۆی گشتی:</h5>
                        <p style="margin: 0.25rem 0;"><strong>کۆی گشتی بە دۆلار:</strong> ${formatCurrency(totalUsd, 'USD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>بڕی دۆلار:</strong> ${formatCurrency(usdAmount, 'USD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>بڕی دینار:</strong> ${formatCurrency(totalIqd, 'IQD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>ژمارەی دانەوەکان:</strong> ${count}</p>
                    </div>
            `;

            if (payments.length > 0) {
                detailsHtml += `
                    <div style="max-height: 400px; overflow-y: auto;">
                        <h6 style="color: #1976d2; margin-bottom: 0.5rem;">لیستی دانەوەکان:</h6>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #e3f2fd;">
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">#</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">کۆمپانیا</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">بەروار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">دۆلار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">دینار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">داشکاندن (دۆلار)</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">داشکاندن (دینار)</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">نرخی دۆلار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">تێبینی</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                payments.forEach((payment, index) => {
                    detailsHtml += `
                        <tr>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.company_name || '-'}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.date || '-'}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.amount_usd || 0, 'USD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.amount_iqd || 0, 'IQD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.discount_usd || 0, 'USD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.discount_iqd || 0, 'IQD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.dollar_rate ? formatCurrency(payment.dollar_rate, 'IQD') : '-'}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.note || '-'}</td>
                        </tr>
                    `;
                });

                detailsHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                detailsHtml += `<p style="text-align: center; color: #666; padding: 1rem;">هیچ دانەوەیەک نەدۆزرایەوە</p>`;
            }

            detailsHtml += `</div>`;

            Swal.fire({
                icon: 'info',
                title: 'وردەکاریەکانی دانەوەی قەرزی کۆمپانیا',
                html: detailsHtml,
                width: '90%',
                customClass: {
                    popup: 'rtl-popup'
                },
                showConfirmButton: true,
                confirmButtonText: 'داخستن',
                confirmButtonColor: '#1976d2'
            });
        })
        .catch(error => {
            console.error('Error fetching details:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵە لە وەرگرتنی وردەکاریەکان: ' + error.message
            });
        });
}

// Function to show person debt payments details
function showPersonDebtPaymentsDetails() {
    const fromDate = document.getElementById('from-date')?.value || '';
    const toDate = document.getElementById('to-date')?.value || '';

    // Build URL with date filters
    let url = '../process/reporst/get_person_debt_payments_details.php';
    const params = new URLSearchParams();
    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    if (params.toString()) url += '?' + params.toString();

    console.log('Fetching person debt payments details from:', url);

    // Show loading
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'وردەکاریەکان وەردەگرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(url)
        .then(res => {
            console.log('Response status:', res.status);
            if (!res.ok) {
                throw new Error('HTTP error! status: ' + res.status);
            }
            return res.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const result = JSON.parse(text);
                console.log('Parsed result:', result);
                if (!result.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: result.error || 'هەڵەیەک ڕویدا'
                    });
                    return null;
                }
                return result;
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵە لە وەرگرتنی وردەکاریەکان: ' + e.message
                });
                return null;
            }
        })
        .then(result => {
            if (!result) return;

            const data = result.data || {};
            const totalUsd = parseFloat(data.total_usd || 0);
            const totalIqd = parseFloat(data.total_iqd || 0);
            const usdAmount = parseFloat(data.usd_amount || 0);
            const count = parseInt(data.count || 0);
            const payments = data.payments || [];

            // Build details HTML
            let detailsHtml = `
                <div style="text-align: right; direction: rtl; font-family: 'Rabar', sans-serif;">
                    <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <h5 style="color: #1976d2; margin-bottom: 0.5rem;">کۆی گشتی:</h5>
                        <p style="margin: 0.25rem 0;"><strong>کۆی گشتی بە دۆلار:</strong> ${formatCurrency(totalUsd, 'USD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>بڕی دۆلار:</strong> ${formatCurrency(usdAmount, 'USD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>بڕی دینار:</strong> ${formatCurrency(totalIqd, 'IQD')}</p>
                        <p style="margin: 0.25rem 0;"><strong>ژمارەی دانەوەکان:</strong> ${count}</p>
                    </div>
            `;

            if (payments.length > 0) {
                detailsHtml += `
                    <div style="max-height: 400px; overflow-y: auto;">
                        <h6 style="color: #1976d2; margin-bottom: 0.5rem;">لیستی دانەوەکان:</h6>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #e3f2fd;">
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">#</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">کەس</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">بەروار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">دۆلار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">دینار</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">داشکاندن (دۆلار)</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">داشکاندن (دینار)</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">تێبینی</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                payments.forEach((payment, index) => {
                    detailsHtml += `
                        <tr>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.person_name || '-'}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.date || '-'}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.amount_usd || 0, 'USD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.amount_iqd || 0, 'IQD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.discount_usd || 0, 'USD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${formatCurrency(payment.discount_iqd || 0, 'IQD')}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">${payment.note || '-'}</td>
                        </tr>
                    `;
                });

                detailsHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                detailsHtml += `<p style="text-align: center; color: #666; padding: 1rem;">هیچ دانەوەیەک نەدۆزرایەوە</p>`;
            }

            detailsHtml += `</div>`;

            Swal.fire({
                icon: 'info',
                title: 'وردەکاریەکانی دانەوەی قەرزی کەسانی خەرجی تر',
                html: detailsHtml,
                width: '90%',
                customClass: {
                    popup: 'rtl-popup'
                },
                showConfirmButton: true,
                confirmButtonText: 'داخستن',
                confirmButtonColor: '#1976d2'
            });
        })
        .catch(error => {
            console.error('Error fetching details:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵە لە وەرگرتنی وردەکاریەکان: ' + error.message
            });
        });
}
