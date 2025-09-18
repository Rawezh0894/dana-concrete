// Global variable to track data loading state
window.SALES_DATA_LOADED = false;

// Professional Receipt Management System
class ReceiptManager {
    constructor() {
        try {
            this.tooltip = null;
            this.isLoading = false;
            this.init();
        } catch (error) {
            console.error('Error creating ReceiptManager:', error);
            // Fallback initialization
            this.tooltip = null;
            this.isLoading = false;
            this.showError('هەڵە لە دروستکردنی سیستەمەکە');
        }
    }

    init() {
        try {
            this.createTooltip();
            this.bindEvents();
            this.loadSalesData();
        } catch (error) {
            console.error('Error initializing ReceiptManager:', error);
            // Show error state
            this.showError('هەڵە لە دەستپێکردنی سیستەمەکە');
        }
    }

    createTooltip() {
        try {
            // Create professional tooltip element
            this.tooltip = document.createElement('div');
            this.tooltip.className = 'receipt-tooltip';
            document.body.appendChild(this.tooltip);
        } catch (error) {
            console.warn('Error creating tooltip:', error);
            this.tooltip = null;
        }
    }

    bindEvents() {
        // Bind filter events
        const filters = ['transaction-type-filter', 'month-filter', 'date-from-filter', 'date-to-filter', 'location-filter'];
        filters.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', () => this.loadSalesData());
            }
        });

        // Bind tooltip events with better handling
        document.addEventListener('mouseover', (e) => this.handleTooltipShow(e));
        document.addEventListener('mouseout', (e) => this.handleTooltipHide(e));
        
        // Prevent tooltip from showing on mobile
        document.addEventListener('touchstart', () => this.hideTooltip());
    }

    handleTooltipShow(e) {
        const cell = e.target.closest('.receipt-number-cell');
        if (cell && cell.classList.contains('truncated')) {
            const fullNumber = cell.getAttribute('data-full-number');
            if (fullNumber) {
                this.showTooltip(fullNumber, e.clientX, e.clientY);
            }
        }
    }

    handleTooltipHide(e) {
        const cell = e.target.closest('.receipt-number-cell');
        if (cell) {
            this.hideTooltip();
        }
        // Also hide tooltip when moving away from the cell area
        if (!e.target.closest('.receipt-number-cell')) {
            this.hideTooltip();
        }
    }

    showTooltip(text, x, y) {
        if (!this.tooltip || !text) return;
        
        this.tooltip.textContent = text;
        
        // Calculate position to keep tooltip within viewport
        const tooltipWidth = 300; // max-width from CSS
        const tooltipHeight = 60; // approximate height
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let left = x + 10;
        let top = y - 40;
        
        // Adjust horizontal position if tooltip would go off screen
        if (left + tooltipWidth > viewportWidth) {
            left = x - tooltipWidth - 10;
        }
        
        // Adjust vertical position if tooltip would go off screen
        if (top < 0) {
            top = y + 20;
        }
        if (top + tooltipHeight > viewportHeight) {
            top = viewportHeight - tooltipHeight - 10;
        }
        
        this.tooltip.style.left = left + 'px';
        this.tooltip.style.top = top + 'px';
        this.tooltip.classList.add('show');
    }

    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.classList.remove('show');
        }
    }

    showLoading() {
        this.isLoading = true;
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="table-loading">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: var(--seafoam-green); margin-bottom: 1rem;"></i>
                            <p style="margin: 0; color: #666; font-size: 1rem;">لە بارکردنی داتاکان...</p>
                            <small style="display: block; margin-top: 0.5rem; color: #adb5bd;">تکایە چاوەڕوان بە</small>
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
                        <i class="fa fa-inbox" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin: 0.5rem 0; font-size: 1.1rem; color: #6c757d;">هیچ داتایەک نەدۆزرایەوە</p>
                        <small style="display: block; color: #adb5bd; font-size: 0.9rem;">تکایە فلتەرەکان بگۆڕە یان داتای نوێ زیاد بکە</small>
                    </td>
                </tr>
            `;
        }
        
        // Mark sales data as not loaded
        window.SALES_DATA_LOADED = false;
    }

    showError(message) {
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            const errorMessage = message || 'هەڵەی نەناسراو';
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        <p>هەڵە لە بارکردنی داتاکان</p>
                        <small style="display: block; margin: 0.5rem 0; font-size: 0.9rem;">${errorMessage}</small>
                        <button onclick="receiptManager.loadSalesData()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--seafoam-green); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                            <i class="fa fa-refresh"></i> هەوڵ بدەوە
                        </button>
                    </td>
                </tr>
            `;
        }
        
        // Mark sales data as not loaded
        window.SALES_DATA_LOADED = false;
    }

    formatReceiptNumber(number) {
        if (!number && number !== 0) return '';
        
        try {
            const receiptNumber = String(number);
            
            // Check if this is a grouped invoice number (contains commas)
            if (receiptNumber.includes(',')) {
                const invoices = receiptNumber.split(',').map(inv => inv.trim());
                const isLong = invoices.length > 3; // Show tooltip if more than 3 invoices
                
                // Format to show only first 3 invoices with indication of more
                let displayText = invoices.slice(0, 3).join(', ');
                if (invoices.length > 3) {
                    displayText += ` (+${invoices.length - 3} more)`;
                }
                
                return `
                    <div class="receipt-number-cell ${isLong ? 'truncated' : ''}" 
                         title="${receiptNumber}"
                         data-full-number="${receiptNumber}">
                        ${displayText}
                    </div>
                `;
            } else {
                // Single invoice number
                const isLong = receiptNumber.length > 10;
                return `
                    <div class="receipt-number-cell ${isLong ? 'truncated' : ''}" 
                         title="${isLong ? receiptNumber : ''}"
                         data-full-number="${receiptNumber}">
                        ${receiptNumber}
                    </div>
                `;
            }
        } catch (error) {
            console.warn('Error formatting receipt number:', number, error);
            return `
                <div class="receipt-number-cell">
                    ${String(number || '')}
                </div>
            `;
        }
    }

    formatCurrency(amount) {
        if (!amount && amount !== 0) return '';
        
        // Convert to string if it's not already
        const amountStr = String(amount);
        const num = parseFloat(amountStr.replace(/[$,]/g, '')) || 0;
        return '$' + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    formatDate(dateString) {
        if (!dateString) return '';
        
        try {
            // Handle different date formats
            let date;
            if (typeof dateString === 'string') {
                // Try to parse the date string
                date = new Date(dateString);
            } else if (dateString instanceof Date) {
                date = dateString;
            } else {
                return String(dateString);
            }
            
            // Check if date is valid
            if (isNaN(date.getTime())) {
                return String(dateString);
            }
            
            return date.toLocaleDateString('ku-IQ');
        } catch (e) {
            console.warn('Error formatting date:', dateString, e);
            return String(dateString);
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
            const location = this.getSelectedLocation();
            
            const params = new URLSearchParams({
                customer_id: CUSTOMER_ID,
                type,
                month,
                date_from,
                date_to,
                location
            });

            const response = await fetch(`../process/receipts/select_sale.php?${params.toString()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const textResponse = await response.text();
            let data;
            try {
                data = JSON.parse(textResponse);
            } catch (jsonError) {
                console.error('JSON parsing error:', jsonError);
                console.error('Raw response:', textResponse);
                
                // Check if response contains HTML error (PHP error)
                if (textResponse.includes('<br />') || textResponse.includes('<b>')) {
                    throw new Error('هەڵەی سێرڤەر: داتاکان بە شێوەیەکی دروست نەگەڕانەوە');
                } else if (textResponse.trim() === '') {
                    throw new Error('داتای خاڵی گەڕایەوە لە سێرڤەرەوە');
                } else {
                    throw new Error('داتای نەدراوە بە شێوەیەکی دروست (JSON)');
                }
            }

            this.renderSalesData(data);
            
        } catch (error) {
            console.error('Error loading sales data:', error);
            this.showError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    renderSalesData(response) {
        // Validate response structure
        if (!response || typeof response !== 'object') {
            console.error('Invalid response format:', response);
            this.showError('داتای نەدراوە بە شێوەیەکی دروست');
            return;
        }

        // Check for error in response
        if (response.error) {
            console.error('Server error:', response.error);
            this.showError(response.error);
            return;
        }

        const data = response.sales_data;
        const openingDebt = response.opening_debt;
        
        const tbody = document.getElementById('receipt-table-body');
        if (!tbody) {
            console.error('Table body element not found');
            return;
        }

        if (!data || !Array.isArray(data) || data.length === 0) {
            this.showEmpty();
            return;
        }

        let total = 0;
        let remainingTotal = 0;
        
        const rows = data.map(row => {
            try {
                // Calculate totals with safe parsing
                const cleanTotal = this.safeParseFloat(row.total_price) || 0;
                const cleanRemaining = this.safeParseFloat(row.remaining_amount) || 0;
                total += cleanTotal;
                remainingTotal += cleanRemaining;

                return `
                    <tr class="receipt-row" data-receipt-id="${row.invoice_number || ''}">
                        <td>${row.location || ''}</td>
                        <td>${row.quantity || ''}</td>
                        <td>${row.rezh || ''}</td>
                        <td>${this.formatCurrency(row.price_per_unit)}</td>
                        <td>${this.formatCurrency(row.total_price)}</td>
                        <td>${this.formatCurrency(row.remaining_amount)}</td>
                        <td>${this.formatReceiptNumber(row.invoice_number)}</td>
                        <td>${this.formatDate(row.order_date)}</td>
                    </tr>
                `;
            } catch (error) {
                console.error('Error processing row:', row, error);
                return `
                    <tr class="receipt-row error-row">
                        <td colspan="9" style="color: #dc3545; text-align: center;">
                            <i class="fa fa-exclamation-triangle"></i>
                            هەڵە لە پرۆسێسکردنی ئەم ڕیزە
                        </td>
                    </tr>
                `;
            }
        }).join('');

        tbody.innerHTML = rows;
        this.updateSummary(total, remainingTotal);
        this.updateDebtSummary(openingDebt, remainingTotal);
        
        // Mark sales data as loaded
        window.SALES_DATA_LOADED = true;
        
        // Load return debt data after sales data is loaded
        if (typeof loadReturnDebt === 'function') {
            setTimeout(() => loadReturnDebt(), 100);
        }
    }

    // Helper function for safe float parsing
    safeParseFloat(value) {
        if (!value && value !== 0) return 0;
        
        try {
            const strValue = String(value);
            return parseFloat(strValue.replace(/[$,]/g, '')) || 0;
        } catch (error) {
            console.warn('Error parsing float value:', value, error);
            return 0;
        }
    }

    updateSummary(total, remainingTotal) {
        const tfoot = document.getElementById('receipt-table-footer');
        if (tfoot) {
            // Ensure values are numbers
            const totalValue = typeof total === 'number' ? total : 0;
            const remainingValue = typeof remainingTotal === 'number' ? remainingTotal : 0;
            
            // Check if invoice number column is visible
            const showInvoiceCheckbox = document.getElementById('show-invoice-number');
            const showInvoiceColumn = showInvoiceCheckbox ? showInvoiceCheckbox.checked : true;
            
            // Check if opening debt should be included in total calculation
            const showOpeningDebtCheckbox = document.getElementById('show-opening-debt');
            const includeOpeningDebt = showOpeningDebtCheckbox ? showOpeningDebtCheckbox.checked : true;
            
            // Calculate total based on checkbox state
            const openingDebt = window.OPENING_DEBT || 0;
            const totalRemaining = includeOpeningDebt ? (openingDebt + remainingValue) : remainingValue;
            
            // Set colspan based on invoice column visibility
            // When invoice column is visible: 3 + 5 = 8 columns
            // When invoice column is hidden: 2 + 6 = 8 columns
            const firstColspan = showInvoiceColumn ? '3' : '2';
            const secondColspan = showInvoiceColumn ? '5' : '6';
            
            tfoot.innerHTML = `
                <tr class="summary-row">
                    <td colspan="${firstColspan}">
                        <i class="fa fa-calculator"></i>
                        کۆی نرخ: ${this.formatCurrency(totalValue)}
                    </td>
                    <td colspan="${secondColspan}">
                        <i class="fa fa-money-bill-wave"></i>
                        کۆی پارەی ماوە: ${this.formatCurrency(totalRemaining)}
                    </td>
                </tr>
            `;
        }
    }

    updateDebtSummary(openingDebt, remainingTotal) {
        // Handle opening debt - could be string or number
        let openingDebtValue = 0;
        if (openingDebt) {
            if (typeof openingDebt === 'string') {
                openingDebtValue = parseFloat(openingDebt.replace(/[$,]/g, '')) || 0;
            } else if (typeof openingDebt === 'number') {
                openingDebtValue = openingDebt;
            }
        }
        
        // Ensure remainingTotal is a number
        const remainingValue = typeof remainingTotal === 'number' ? remainingTotal : 0;
        
        // Check if opening debt should be included in total calculation
        const showOpeningDebtCheckbox = document.getElementById('show-opening-debt');
        const includeOpeningDebt = showOpeningDebtCheckbox ? showOpeningDebtCheckbox.checked : true;
        
        // Calculate total based on checkbox state
        const totalRemaining = includeOpeningDebt ? (openingDebtValue + remainingValue) : remainingValue;
        
        // Store global values
        window.RECEIPT_TOTAL = totalRemaining;
        window.REMAINING_TOTAL = remainingValue;
        window.OPENING_DEBT = openingDebtValue;
        
        // Create debt summary data for pagination
        const debtData = {
            openingDebt: openingDebtValue,
            remainingAmount: remainingValue,
            totalDebt: totalRemaining
        };
        
        // Initialize pagination
        this.initializeDebtPagination(debtData);
    }
    
    initializeDebtPagination(debtData) {
        const container = document.getElementById('debt-summary-pages');
        const pagination = document.getElementById('debt-pagination');
        
        if (!container) return;
        
        // Calculate if we need pagination (if content would overflow)
        const needsPagination = this.checkIfPaginationNeeded();
        
        if (needsPagination) {
            // Create multiple pages
            this.createDebtPages(debtData);
            pagination.style.display = 'flex';
            window.DEBT_CURRENT_PAGE = 0;
            window.DEBT_TOTAL_PAGES = 2; // We'll split into 2 pages
            this.updateDebtPaginationControls();
        } else {
            // Show single page
            this.createSingleDebtPage(debtData);
            pagination.style.display = 'none';
        }
    }
    
    checkIfPaginationNeeded() {
        // Check if we need pagination based on content size and layout
        const openingDebt = window.OPENING_DEBT || 0;
        const remainingAmount = window.REMAINING_TOTAL || 0;
        const totalDebt = openingDebt + remainingAmount;
        
        // Check if any of the debt values are very large (would cause layout issues)
        const hasLargeValues = totalDebt > 1000000 || openingDebt > 500000 || remainingAmount > 500000;
        
        // Check if we have significant amounts that would benefit from pagination
        const hasSignificantDebt = totalDebt > 100000;
        
        // Force pagination if we have very large values or if user preference is set
        const forcePagination = localStorage.getItem('forceDebtPagination') === 'true';
        
        return hasLargeValues || hasSignificantDebt || forcePagination;
    }
    
    createDebtPages(debtData) {
        const container = document.getElementById('debt-summary-pages');
        if (!container) return;
        
        // Page 1: Opening debt and remaining amount
        const page1 = document.createElement('div');
        page1.className = 'debt-summary-page';
        page1.innerHTML = `
            <div class="debt-summary-row">
                <div class="debt-summary-box">
                    <i class="fa fa-history"></i>
                    <span class="debt-label">قەرزی پێشوو:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.openingDebt)}</span>
                </div>
                <div class="debt-summary-box">
                    <i class="fa fa-money-bill-wave"></i>
                    <span class="debt-label">پارەی ماوە:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.remainingAmount)}</span>
                </div>
            </div>
        `;
        
        // Page 2: Total debt
        const page2 = document.createElement('div');
        page2.className = 'debt-summary-page debt-summary-page-break';
        page2.innerHTML = `
            <div class="debt-summary-row">
                <div class="debt-summary-box total-box">
                    <i class="fa fa-calculator"></i>
                    <span class="debt-label">کۆی گشتی پارەی ماوە:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.totalDebt)}</span>
                </div>
            </div>
        `;
        
        container.innerHTML = '';
        container.appendChild(page1);
        container.appendChild(page2);
        
        // Show only first page initially
        this.showDebtPage(0);
    }
    
    createSingleDebtPage(debtData) {
        const container = document.getElementById('debt-summary-pages');
        if (!container) return;
        
        container.innerHTML = `
            <div class="debt-summary-row">
                <div class="debt-summary-box">
                    <i class="fa fa-history"></i>
                    <span class="debt-label">قەرزی پێشوو:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.openingDebt)}</span>
                </div>
                <div class="debt-summary-box">
                    <i class="fa fa-money-bill-wave"></i>
                    <span class="debt-label">پارەی ماوە:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.remainingAmount)}</span>
                </div>
                <div class="debt-summary-box total-box">
                    <i class="fa fa-calculator"></i>
                    <span class="debt-label">کۆی گشتی پارەی ماوە:</span>
                    <span class="debt-value">${this.formatCurrency(debtData.totalDebt)}</span>
                </div>
            </div>
        `;
    }
    
    showDebtPage(pageIndex) {
        const pages = document.querySelectorAll('.debt-summary-page');
        pages.forEach((page, index) => {
            page.style.display = index === pageIndex ? 'block' : 'none';
        });
    }
    
    updateDebtPaginationControls() {
        const prevBtn = document.getElementById('prev-page-btn');
        const nextBtn = document.getElementById('next-page-btn');
        const pageInfo = document.getElementById('debt-page-info');
        
        if (!prevBtn || !nextBtn || !pageInfo) return;
        
        const currentPage = window.DEBT_CURRENT_PAGE || 0;
        const totalPages = window.DEBT_TOTAL_PAGES || 1;
        
        prevBtn.disabled = currentPage === 0;
        nextBtn.disabled = currentPage === totalPages - 1;
        pageInfo.textContent = `لاپەڕەی ${currentPage + 1} لە ${totalPages}`;
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

    getSelectedLocation() {
        const locationElem = document.getElementById('location-filter');
        return locationElem ? locationElem.value : 'all';
    }
}

// Initialize the receipt manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
            console.error('CUSTOMER_ID is not defined');
            return;
        }
        
        // Initialize the professional receipt manager
        window.receiptManager = new ReceiptManager();
        
        // Legacy function for backward compatibility
        window.loadSalesData = loadSalesData;
    } catch (error) {
        console.error('Error in DOM ready handler:', error);
        // Show fallback error message
        const tbody = document.getElementById('receipt-table-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        <p>هەڵە لە دەستپێکردنی سیستەمەکە</p>
                        <small style="display: block; margin: 0.5rem 0; font-size: 0.9rem;">تکایە پەڕەکە ڕیفرێش بکە</small>
                        <button onclick="location.reload()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--seafoam-green); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                            <i class="fa fa-refresh"></i> ڕیفرێش
                        </button>
                    </td>
                </tr>
            `;
        }
    }
});

// Legacy functions for backward compatibility
function getSelectedTransactionType() {
    try {
        return window.receiptManager ? window.receiptManager.getSelectedTransactionType() : 'all';
    } catch (error) {
        console.warn('Error in getSelectedTransactionType:', error);
        return 'all';
    }
}

function getSelectedMonth() {
    try {
        return window.receiptManager ? window.receiptManager.getSelectedMonth() : 'all';
    } catch (error) {
        console.warn('Error in getSelectedMonth:', error);
        return 'all';
    }
}

function getDateFrom() {
    try {
        return window.receiptManager ? window.receiptManager.getDateFrom() : '';
    } catch (error) {
        console.warn('Error in getDateFrom:', error);
        return '';
    }
}

function getDateTo() {
    try {
        return window.receiptManager ? window.receiptManager.getDateTo() : '';
    } catch (error) {
        console.warn('Error in getDateTo:', error);
        return '';
    }
}

function getSelectedLocation() {
    try {
        return window.receiptManager ? window.receiptManager.getSelectedLocation() : 'all';
    } catch (error) {
        console.warn('Error in getSelectedLocation:', error);
        return 'all';
    }
}

// Global loadSalesData function for backward compatibility
function loadSalesData() {
    try {
        if (window.receiptManager) {
            window.receiptManager.loadSalesData();
        } else {
            console.warn('ReceiptManager not initialized');
        }
    } catch (error) {
        console.error('Error in global loadSalesData:', error);
    }
}
