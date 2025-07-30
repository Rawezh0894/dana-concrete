// Function to format price with proper currency display
function formatPrice(price, currency) {
    let formattedPrice;
    
    // Format large numbers with K/M
    if (price >= 1000000) {
        formattedPrice = (price / 1000000).toFixed(1) + 'M';
    } else if (price >= 1000) {
        formattedPrice = (price / 1000).toFixed(0) + 'K';
    } else {
        formattedPrice = price.toLocaleString();
    }
    
    if (currency === 'دۆلار') {
        return `${formattedPrice}$`;
    } else {
        return `${formattedPrice} د.ع`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // First fetch USD rate directly from API (like other pages)
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    fetch(`${apiUrl}?id=${id}&api_token=${apiToken}`)
        .then(res => {
            if (!res.ok) {
                throw new Error('USD rate API failed');
            }
            return res.json();
        })
        .then(usdData => {
            console.log('USD API response:', usdData); // Debug log
            return usdData;
        })
        .catch(error => {
            console.warn('USD rate fetch failed, using default:', error);
            return { value: 139250 };
        })
        .then(usdData => {
            // Then fetch dashboard data
            return fetch('../process/dashboard/select_information.php')
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.text(); // Get response as text first
                })
                .then(text => {
                    // Try to parse as JSON
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', text);
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response from server');
                    }
                    
                    if (!data.success) {
                        console.error('Server returned error:', data.message);
                        return;
                    }
                    
                    // Get USD rate - fix the property name to match API response
                    let usdRate = 139250; // Default fallback
                    if (usdData && usdData.value && !isNaN(usdData.value)) {
                        usdRate = parseFloat(usdData.value);
                    } else if (usdData && usdData.rate && !isNaN(usdData.rate)) {
                        usdRate = parseFloat(usdData.rate);
                    }
                    console.log('Final USD rate used:', usdRate); // Debug log
                    
                    // Render summary cards with USD rate as first card
                    const cards = [
                        { 
                            key: 'usd_rate', 
                            label: 'نرخی 100 دۆلار', 
                            icon: 'fa-dollar-sign', 
                            gradient: 'card-gradient-info',
                            value: usdRate,
                            isUsdRate: true
                        },
                        { key: 'customers', label: 'کڕیار', icon: 'fa-users', gradient: 'card-gradient-success' },
                        { key: 'companies', label: 'کۆمپانیا', icon: 'fa-building', gradient: 'card-gradient-warning' },
                        { key: 'employees', label: 'کارمەند', icon: 'fa-user-tie', gradient: 'card-gradient-purple' },
                        { key: 'receipts', label: 'پسوڵەی کۆنکرێت', icon: 'fa-file-invoice', gradient: 'card-gradient-teal' },
                        { key: 'sales', label: 'فرۆشتن', icon: 'fa-cash-register', gradient: 'card-gradient-orange' },
                        { key: 'materials', label: 'مەواد', icon: 'fa-cubes', gradient: 'card-gradient-red' },
                        { key: 'cars', label: 'سەیارە', icon: 'fa-truck', gradient: 'card-gradient-dark' },
                    ];
                    let html = '';
                    cards.forEach(card => {
                        let displayValue;
                        if (card.isUsdRate) {
                            // Format USD rate with full number display (no K/M abbreviation)
                            displayValue = card.value.toLocaleString() + ' د.ع';
                        } else {
                            displayValue = data.summary[card.key] || 0;
                        }
                        
                        html += `<div class="col-md-3 col-sm-6 mb-3">
                            <div class="card text-center shadow ${card.gradient} card-animate-hover">
                                <div class="card-body">
                                    <i class="fas ${card.icon} card-icon"></i>
                                    <h6 class="card-title text-white">${card.label}</h6>
                                    <div class="fs-4 fw-bold text-white">${displayValue}</div>
                                    <small class="text-white">${card.isUsdRate ? 'نرخی دۆلار بە دینار' : 'کۆی گشتی'}</small>
                                </div>
                            </div>
                        </div>`;
                    });
                    document.getElementById('dashboard-summary-cards').innerHTML = html;

            // Render stock status cards
            let stockHtml = '';
            const stockGradients = ['card-gradient-teal', 'card-gradient-orange', 'card-gradient-red', 'card-gradient-dark', 'card-gradient-info', 'card-gradient-success', 'card-gradient-warning', 'card-gradient-purple'];
            if (data.stock_status && Array.isArray(data.stock_status)) {
                data.stock_status.forEach((item, index) => {
                    // Format amount display in tons
                    const amountInTons = item.amount / 1000; // Convert kg to tons
                    const amountText = amountInTons >= 1000 ? 
                        `${(amountInTons / 1000).toFixed(1)}K` : 
                        amountInTons >= 1 ? 
                        `${amountInTons.toFixed(1)}` : 
                        `${amountInTons.toFixed(2)}`;
                    
                    const stockGradient = stockGradients[index % stockGradients.length];
                    stockHtml += `<div class="col-md-3 col-sm-6 mb-3">
                        <div class="card text-center shadow ${stockGradient} card-animate-hover">
                                                    <div class="card-body">
                            <i class="fas fa-boxes card-icon"></i>
                            <h6 class="card-title text-white">${item.name}</h6>
                            <div class="fs-4 fw-bold text-white">${amountText} طەن</div>
                            <small class="text-white">${item.type} - ${item.material_type}</small>
                                ${window.userPermissions && window.userPermissions.canViewDashboardPrices ? 
                                    `<div class="mt-2"><small class="text-white">${formatPrice(item.average_price_per_kg, item.price_currency)}/کگم</small></div>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>`;
                });
            }
            document.getElementById('stock-status-cards').innerHTML = stockHtml;

            // Render statistics
            const statsItems = [
                { key: 'monthly_sales', label: 'فرۆشتنەکانی مانگ', icon: 'bi-cart-check' },
                { key: 'monthly_receipts', label: 'پسوڵەکانی مانگ', icon: 'bi-file-earmark-text' },
                { key: 'monthly_purchases', label: 'کڕینەکانی مانگ', icon: 'bi-cart-plus' },
                { key: 'pending_debts', label: 'قەرزە بەجێماوەکان', icon: 'bi-exclamation-triangle' },
                { key: 'low_stock_items', label: 'بەرهەمە کەمەکان', icon: 'bi-box-seam' },
                { key: 'active_employees', label: 'کارمەندە چالاکەکان', icon: 'bi-people' },
            ];
            let statsHtml = '';
            const gradientClasses = ['card-gradient-info', 'card-gradient-success', 'card-gradient-warning', 'card-gradient-purple', 'card-gradient-teal', 'card-gradient-orange', 'card-gradient-red', 'card-gradient-dark'];
            statsItems.forEach((stat, index) => {
                const gradientClass = gradientClasses[index % gradientClasses.length];
                statsHtml += `<div class="col-md-4 col-sm-6 mb-3">
                    <div class="card text-center shadow ${gradientClass} card-animate-hover">
                        <div class="card-body">
                            <i class="bi ${stat.icon} card-icon"></i>
                            <h6 class="card-title text-white">${stat.label}</h6>
                            <div class="fs-4 fw-bold text-white">${data.stats[stat.key] || 0}</div>
                            <small class="text-white">ئامارەکانی مانگ</small>
                        </div>
                    </div>
                </div>`;
            });
            document.getElementById('dashboard-stats').innerHTML = statsHtml;

            // Render notifications
            let notificationsHtml = '';
            if (data.notifications && Array.isArray(data.notifications)) {
                data.notifications.forEach(notification => {
                    notificationsHtml += `<div class="notification-item ${notification.type}">
                        <div class="d-flex align-items-start">
                            <i class="${notification.icon} notification-icon"></i>
                            <div class="flex-grow-1">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-text">${notification.text}</div>
                            </div>
                        </div>
                    </div>`;
                });
            }
            document.getElementById('dashboard-notifications').innerHTML = notificationsHtml;

            // Quick links
            const quickLinks = [
                { label: 'پسوڵەکان', icon: 'fa-file-invoice', href: '../pages/concrete_receipts.php' },
                { label: 'فرۆشتن', icon: 'fa-cash-register', href: '../pages/add_sale.php' },
                { label: 'کڕین', icon: 'fa-cart-plus', href: '../pages/add_purchase.php' },
                { label: 'کڕیاران', icon: 'fa-users', href: '../pages/add_customers.php' },
                { label: 'دابینکەر', icon: 'fa-building', href: '../pages/add_company.php' },
                { label: 'مامەڵەکان', icon: 'fa-list', href: '../pages/add_sale.php' },
                { label: 'راپۆرتەکان', icon: 'fa-chart-bar', href: '../pages/reports.php' },
                { label: 'قاسەکە', icon: 'fa-cash-stack', href: '../pages/cash_box.php' },
            ];
            let ql = '';
            const quickGradients = ['card-gradient-info', 'card-gradient-success', 'card-gradient-warning', 'card-gradient-purple', 'card-gradient-teal', 'card-gradient-orange', 'card-gradient-red', 'card-gradient-dark', 'card-gradient-primary', 'card-gradient-secondary'];
            quickLinks.forEach((link, index) => {
                const gradientClass = quickGradients[index % quickGradients.length];
                ql += `<div class="col-6 col-md-4 mb-2">
                    <a href="${link.href}" class="card text-center shadow ${gradientClass} card-animate-hover text-decoration-none">
                        <div class="card-body">
                            <i class="fas ${link.icon} card-icon"></i>
                            <h6 class="card-title text-white">${link.label}</h6>
                        </div>
                    </a>
                </div>`;
            });
            document.getElementById('dashboard-quick-links').innerHTML = ql;

            // Recent activities
            const icons = {
                'receipt': { icon: 'fa-file-invoice', color: 'var(--spearmint)' },
                'sale': { icon: 'fa-cash-register', color: 'var(--kelly-green)' },
                'purchase': { icon: 'fa-cart-plus', color: 'var(--lime-green)' }
            };
            let ra = '';
            if (data.recent && Array.isArray(data.recent)) {
                data.recent.forEach(act => {
                    let who = act.customer || act.company || '-';
                    let label = act.type === 'receipt' ? 'پسوڵە' : act.type === 'sale' ? 'فرۆشتن' : 'کڕین';
                    let amount = '';
                    if (act.amount) {
                        if (act.type === 'receipt') {
                            amount = `<span style='font-weight:bold;'>${act.amount} m³</span>`;
                        } else {
                            amount = `<span style='font-weight:bold;'>${act.amount}</span>`;
                        }
                    }
                    let date = act.date ? `<span style='font-size:0.9rem;color:#888;'>${act.date.split(' ')[0]}</span>` : '';
                    ra += `<li class="list-group-item d-flex align-items-center justify-content-between" style="border:none;background: #f6f7fb; margin-bottom: 8px; border-radius: 0.7rem;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa ${icons[act.type].icon}" style="color:${icons[act.type].color};font-size:1.3rem;"></i>
                            <div>
                                <div style="font-weight:bold;">${label} <span style='color:var(--seafoam-green)'>${act.name}</span></div>
                                <div style="font-size:0.95rem;">${who} ${amount}</div>
                            </div>
                        </div>
                        ${date}
                    </li>`;
                });
            }
            document.getElementById('dashboard-recent-activities').innerHTML = ra;
        });
    })
    .catch(error => {
        console.error('Error loading dashboard data:', error);
        // Show error message to user
        const errorMessage = `
            <div class="alert alert-danger" role="alert">
                <i class="fa fa-exclamation-triangle"></i>
                هەڵە لە بارکردنی داتای داشبۆرد: ${error.message}
            </div>
        `;
        document.getElementById('dashboard-summary-cards').innerHTML = errorMessage;
    });
});
