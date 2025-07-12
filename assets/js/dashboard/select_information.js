document.addEventListener('DOMContentLoaded', function() {
    fetch('../process/dashboard/select_information.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            
            // Render summary cards
            const cards = [
                { key: 'customers', label: 'کڕیار', icon: 'fa-users', color: 'var(--kelly-green)' },
                { key: 'companies', label: 'کۆمپانیا', icon: 'fa-building', color: 'var(--seafoam-green)' },
                { key: 'employees', label: 'کارمەند', icon: 'fa-user-tie', color: 'var(--lime-green)' },
                { key: 'receipts', label: 'پسوڵەی کۆنکرێت', icon: 'fa-file-invoice', color: 'var(--spearmint)' },
                { key: 'sales', label: 'فرۆشتن', icon: 'fa-cash-register', color: 'var(--kelly-green)' },
                { key: 'materials', label: 'مەواد', icon: 'fa-cubes', color: 'var(--seafoam-green)' },
                { key: 'cars', label: 'سەیارە', icon: 'fa-truck', color: 'var(--lime-green)' },
            ];
            let html = '';
            cards.forEach(card => {
                html += `<div class="col-md-3 col-sm-6">
                    <div class="card text-center shadow" style="border: none;">
                        <div class="card-body">
                            <div class="mb-2"><i class="fa ${card.icon}" style="font-size:2rem;color:${card.color}"></i></div>
                            <h5 class="card-title">${card.label}</h5>
                            <span style="font-size:2rem;font-weight:bold;">${data.summary[card.key]}</span>
                        </div>
                    </div>
                </div>`;
            });
            document.getElementById('dashboard-summary-cards').innerHTML = html;

            // Render stock status cards
            let stockHtml = '';
            data.stock_status.forEach(item => {
                const statusClass = item.status === 'high' ? 'high' : item.status === 'medium' ? 'medium' : 'low';
                const statusText = item.status === 'high' ? 'بەرز' : item.status === 'medium' ? 'مامناوەند' : 'کەم';
                stockHtml += `<div class="col-md-3 col-sm-6">
                    <div class="card text-center shadow stock-card" style="border: none;">
                        <div class="card-body">
                            <div class="mb-2"><i class="fa fa-boxes" style="font-size:2rem;color:var(--stock-accent)"></i></div>
                            <h5 class="card-title">${item.name}</h5>
                            <div style="font-size:1.2rem;font-weight:bold;margin-bottom:0.5rem;">${item.amount.toLocaleString()} kg</div>
                            <div class="stock-progress">
                                <div class="stock-progress-bar ${statusClass}" style="width: ${item.percentage}%"></div>
                            </div>
                            <div style="font-size:0.9rem;color:#666;margin-top:0.5rem;">${item.percentage}% - ${statusText}</div>
                        </div>
                    </div>
                </div>`;
            });
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
            statsItems.forEach(stat => {
                statsHtml += `<div class="col-md-4 col-sm-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="${stat.icon}"></i>
                        </div>
                        <div class="stat-value">${data.stats[stat.key]}</div>
                        <div class="stat-label">${stat.label}</div>
                    </div>
                </div>`;
            });
            document.getElementById('dashboard-stats').innerHTML = statsHtml;

            // Render notifications
            let notificationsHtml = '';
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
            document.getElementById('dashboard-notifications').innerHTML = notificationsHtml;

            // Quick links
            const quickLinks = [
                { label: 'پسوڵەکان', icon: 'fa-file-invoice', color: 'var(--spearmint)', href: '../pages/concrete_receipts.php' },
                { label: 'فرۆشتن', icon: 'fa-cash-register', color: 'var(--kelly-green)', href: '../pages/add_sale.php' },
                { label: 'کڕین', icon: 'fa-cart-plus', color: 'var(--lime-green)', href: '../pages/add_purchase.php' },
                { label: 'کڕیاران', icon: 'fa-users', color: 'var(--kelly-green)', href: '../pages/add_customers.php' },
                { label: 'دابینکەر', icon: 'fa-building', color: 'var(--seafoam-green)', href: '../pages/add_company.php' },
                { label: 'مامەڵەکان', icon: 'fa-list', color: 'var(--spearmint)', href: '../pages/add_sale.php' },
                { label: 'راپۆرتەکان', icon: 'fa-chart-bar', color: 'var(--seafoam-green)', href: '../pages/reports.php' },
                { label: 'قاسەکە', icon: 'fa-cash-stack', color: 'var(--financial-accent)', href: '../pages/cash_box.php' },
            ];
            let ql = '';
            quickLinks.forEach(link => {
                ql += `<div class="col-6 col-md-4 mb-2">
                    <a href="${link.href}" class="quick-link-card d-flex flex-column align-items-center justify-content-center" style="background: #f6f7fb; border-radius: 1rem; padding: 1.2rem; text-decoration: none; box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);">
                        <i class="fa ${link.icon}" style="font-size:2rem;color:${link.color};margin-bottom:8px;"></i>
                        <span style="color: var(--seafoam-green); font-weight: bold;">${link.label}</span>
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
            document.getElementById('dashboard-recent-activities').innerHTML = ra;
        });
});
