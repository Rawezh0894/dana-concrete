// Professional Receipt Management System
class ReceiptManager {
    constructor() {
        this.tooltip = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.createTooltip();
        this.bindEvents();
        this.loadSalesData();
    }

    createTooltip() {
        // Create professional tooltip element
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'receipt-tooltip';
        document.body.appendChild(this.tooltip);
    }

    bindEvents() {
        // Bind filter events
        const filters = ['transaction-type-filter', 'month-filter', 'date-from-filter', 'date-to-filter'];
        filters.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', () => this.loadSalesData());
            }
        });

        // Bind tooltip events
        document.addEventListener('mouseover', (e) => this.handleTooltipShow(e));
        document.addEventListener('mouseout', (e) => this.handleTooltipHide(e));
    }

    handleTooltipShow(e) {
        const cell = e.target.closest('.receipt-number-cell');
        if (cell && cell.classList.contains('truncated')) {
            const text = cell.textContent;
            this.showTooltip(text, e.clientX, e.clientY);
        }
    }

    handleTooltipHide(e) {
        const cell = e.target.closest('.receipt-number-cell');
        if (cell) {
            this.hideTooltip();
        }
    }

    showTooltip(text, x, y) {
        this.tooltip.textContent = text;
        this.tooltip.style.left = x + 10 + 'px';
        this.tooltip.style.top = y - 40 + 'px';
        this.tooltip.classList.add('show');
    }

    hideTooltip() {
        this.tooltip.classList.remove('show');
    }

    showLoading() {
        this.isLoading = true;
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="table-loading">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: var(--seafoam-green);"></i>
                            <p style="margin-top: 1rem; color: #666;">لە بارکردنی داتاکان...</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    showEmpty() {
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="table-empty">
                        <i class="fa fa-inbox"></i>
                        <p>هیچ داتایەک نەدۆزرایەوە</p>
                        <small>تکایە فلتەرەکان بگۆڕە یان داتای نوێ زیاد بکە</small>
                    </td>
                </tr>
            `;
        }
    }

    showError(message) {
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        <p>هەڵە لە بارکردنی داتاکان</p>
                        <small>${message}</small>
                        <button onclick="receiptManager.loadSalesData()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--seafoam-green); color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fa fa-refresh"></i> هەوڵ بدەوە
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    formatReceiptNumber(number) {
        if (!number) return '';
        
        const receiptNumber = String(number);
        const isLong = receiptNumber.length > 10;
        
        return `
            <div class="receipt-number-cell ${isLong ? 'truncated' : ''}" 
                 title="${isLong ? receiptNumber : ''}"
                 data-full-number="${receiptNumber}">
                ${receiptNumber}
            </div>
        `;
    }

    formatCurrency(amount) {
        if (!amount) return '';
        const num = parseFloat(amount.replace(/[$,]/g, '')) || 0;
        return '$' + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('ku-IQ');
        } catch (e) {
            return dateString;
        }
    }

    async loadSalesData() {
        if (this.isLoading) return;

        try {
            this.showLoading();
            
            const type = this.getSelectedTransactionType();
            const month = this.getSelectedMonth();
            const date_from = this.getDateFrom();
            const date_to = this.getDateTo();
            
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        type,
        month,
        date_from,
        date_to
    });

            const response = await fetch(`../process/receipts/select_sale.php?${params.toString()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.renderSalesData(data);
            
        } catch (error) {
            console.error('Error loading sales data:', error);
            this.showError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    renderSalesData(response) {
            const data = response.sales_data;
            const openingDebt = response.opening_debt;
            
            const tbody = document.getElementById('receipt-table-body');
        if (!tbody) return;

        if (!data || data.length === 0) {
            this.showEmpty();
                return;
            }
            
            let total = 0;
            let remainingTotal = 0;
            
        const rows = data.map(row => {
            // Calculate totals
            const cleanTotal = parseFloat((row.total_price || '').replace(/[$,]/g, '')) || 0;
            const cleanRemaining = parseFloat((row.remaining_amount || '').replace(/[$,]/g, '')) || 0;
            total += cleanTotal;
            remainingTotal += cleanRemaining;

            return `
                <tr class="receipt-row" data-receipt-id="${row.invoice_number || ''}">
                    <td>${row.quantity || ''}</td>
                    <td>${row.rezh || ''}</td>
                    <td>${this.formatCurrency(row.price_per_unit)}</td>
                    <td>${this.formatCurrency(row.total_price)}</td>
                    <td>${this.formatCurrency(row.amount_paid_usd)}</td>
                    <td>${row.amount_paid_iqd || ''}</td>
                    <td>${this.formatCurrency(row.remaining_amount)}</td>
                    <td>${this.formatReceiptNumber(row.invoice_number)}</td>
                    <td>${this.formatDate(row.order_date)}</td>
                    </tr>
                `;
        }).join('');

        tbody.innerHTML = rows;
        this.updateSummary(total, remainingTotal, openingDebt);
        this.updateDebtSummary(openingDebt, remainingTotal);
            
            // Mark sales data as loaded
            window.SALES_DATA_LOADED = true;
            
            // Load return debt data after sales data is loaded
            if (typeof loadReturnDebt === 'function') {
                setTimeout(() => loadReturnDebt(), 100);
        }
    }

    updateSummary(total, remainingTotal) {
        const tfoot = document.getElementById('receipt-table-footer');
        if (tfoot) {
            tfoot.innerHTML = `
                <tr class="summary-row">
                    <td colspan="4">
                        <i class="fa fa-calculator"></i>
                        کۆی نرخ: ${this.formatCurrency(total)}
                    </td>
                    <td colspan="5">
                        <i class="fa fa-money-bill-wave"></i>
                        کۆی پارەی ماوە: ${this.formatCurrency(remainingTotal)}
                    </td>
                </tr>
            `;
        }
    }

    updateDebtSummary(openingDebt, remainingTotal) {
        const openingDebtValue = parseFloat(openingDebt.replace(/[$,]/g, '')) || 0;
        const totalRemaining = openingDebtValue + remainingTotal;
        
        // Store global values
        window.RECEIPT_TOTAL = totalRemaining;
        window.REMAINING_TOTAL = remainingTotal;
        window.OPENING_DEBT = openingDebtValue;
        
        // Update UI elements
        const elements = {
            'opening-debt': this.formatCurrency(openingDebtValue),
            'remaining-amount': this.formatCurrency(remainingTotal),
            'total-debt': this.formatCurrency(totalRemaining)
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }

    getSelectedTransactionType() {
        const filterElem = document.getElementById('transaction-type-filter');
        return filterElem ? filterElem.value : 'all';
    }

    getSelectedMonth() {
        const monthElem = document.getElementById('month-filter');
        return monthElem ? monthElem.value : 'all';
    }

    getDateFrom() {
        const dateFromElem = document.getElementById('date-from-filter');
        return dateFromElem ? dateFromElem.value : '';
    }

    getDateTo() {
        const dateToElem = document.getElementById('date-to-filter');
        return dateToElem ? dateToElem.value : '';
    }
}

// Initialize the receipt manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        console.error('CUSTOMER_ID is not defined');
        return;
    }
    
    // Initialize the professional receipt manager
    window.receiptManager = new ReceiptManager();
    
    // Legacy function for backward compatibility
    window.loadSalesData = () => window.receiptManager.loadSalesData();
});

// Legacy functions for backward compatibility
function getSelectedTransactionType() {
    return window.receiptManager ? window.receiptManager.getSelectedTransactionType() : 'all';
}

function getSelectedMonth() {
    return window.receiptManager ? window.receiptManager.getSelectedMonth() : 'all';
}

function getDateFrom() {
    return window.receiptManager ? window.receiptManager.getDateFrom() : '';
}

function getDateTo() {
    return window.receiptManager ? window.receiptManager.getDateTo() : '';
}
