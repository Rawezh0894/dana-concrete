// Global variable to track data loading state
window.SALES_DATA_LOADED = false;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        console.error('CUSTOMER_ID is not defined');
        return;
    }
    
    // Listen for transaction type, month, and date range filter changes
    const filterElem = document.getElementById('transaction-type-filter');
    const monthElem = document.getElementById('month-filter');
    const dateFromElem = document.getElementById('date-from-filter');
    const dateToElem = document.getElementById('date-to-filter');
    [filterElem, monthElem, dateFromElem, dateToElem].forEach(function(el) {
        if (el) {
            el.addEventListener('change', function() {
                loadSalesData();
            });
        }
    });
    
    loadSalesData();
});

function getSelectedTransactionType() {
    const filterElem = document.getElementById('transaction-type-filter');
    return filterElem ? filterElem.value : 'all';
}

function getSelectedMonth() {
    const monthElem = document.getElementById('month-filter');
    return monthElem ? monthElem.value : 'all';
}

function getDateFrom() {
    const dateFromElem = document.getElementById('date-from-filter');
    return dateFromElem ? dateFromElem.value : '';
}

function getDateTo() {
    const dateToElem = document.getElementById('date-to-filter');
    return dateToElem ? dateToElem.value : '';
}

function loadSalesData() {
    const type = getSelectedTransactionType();
    const month = getSelectedMonth();
    const date_from = getDateFrom();
    const date_to = getDateTo();
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        type,
        month,
        date_from,
        date_to
    });
    return fetch(`../process/receipts/select_sale.php?${params.toString()}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(response => {
            const data = response.sales_data;
            const openingDebt = response.opening_debt;
            
            const tbody = document.getElementById('receipt-table-body');
            if (!tbody) {
                console.error('Table body element not found');
                return;
            }
            
            tbody.innerHTML = '';
            let total = 0;
            let remainingTotal = 0;
            
            if (data && data.length > 0) {
                data.forEach(row => {
                    // Remove $ and commas for calculation
                    let cleanTotal = (row.total_price || '').replace(/[$,]/g, '');
                    let cleanRemaining = (row.remaining_amount || '').replace(/[$,]/g, '');
                    total += parseFloat(cleanTotal) || 0;
                    remainingTotal += parseFloat(cleanRemaining) || 0;
                    
                    // Format receipt number with tooltip if it's long
                    const receiptNumber = row.invoice_number || '';
                    const receiptNumberCell = receiptNumber.length > 10 ? 
                        `<td title="${receiptNumber}">${receiptNumber}</td>` : 
                        `<td>${receiptNumber}</td>`;
                    
                    tbody.innerHTML += `<tr>
                        <td>${row.quantity ?? ''}</td>
                        <td>${row.rezh ?? ''}</td>
                        <td>${row.price_per_unit ?? ''}</td>
                        <td>${row.total_price ?? ''}</td>
                        <td>${row.amount_paid_usd ?? ''}</td>
                        <td>${row.amount_paid_iqd ?? ''}</td>
                        <td>${row.remaining_amount ?? ''}</td>
                        ${receiptNumberCell}
                        <td>${row.order_date ?? ''}</td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #666;">هیچ داتایەک نەدۆزرایەوە</td></tr>';
            }
            
            // هەژمارکردنی کۆی گشتی پارەی ماوە
            let openingDebtValue = parseFloat(openingDebt.replace(/[$,]/g, '')) || 0;
            let totalRemaining = openingDebtValue + remainingTotal;
            
            window.RECEIPT_TOTAL = totalRemaining; // کۆی گشتی پارەی ماوە
            window.REMAINING_TOTAL = remainingTotal; // کۆی پارەی ماوەی sales
            window.OPENING_DEBT = openingDebtValue; // قەرزی پێشوو
            
            // نوێکردنەوەی زانیارییەکانی قەرز لە HTML دا
            const openingDebtElem = document.getElementById('opening-debt');
            const remainingAmountElem = document.getElementById('remaining-amount');
            const totalDebtElem = document.getElementById('total-debt');
            
            if (openingDebtElem) openingDebtElem.textContent = openingDebt;
            if (remainingAmountElem) remainingAmountElem.textContent = '$' + remainingTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (totalDebtElem) totalDebtElem.textContent = '$' + totalRemaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // لابردنی summary row ی پارەی ماوە لە تابلۆ
            const tfoot = document.getElementById('receipt-table-footer');
            if (tfoot) {
                tfoot.innerHTML = `
                    <tr>
                        <td colspan="4" style="font-weight:bold; background:#f4f8fc; color:#003b73;">کۆی نرخ: $${total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td colspan="5" style="font-weight:bold; background:#f4f8fc; color:#003b73;">کۆی پارەی ماوە: $${remainingTotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    </tr>
                `;
            }
            
            // Mark sales data as loaded
            window.SALES_DATA_LOADED = true;
            console.log('Sales data loaded successfully');
            
            // Load return debt data after sales data is loaded
            if (typeof loadReturnDebt === 'function') {
                setTimeout(() => loadReturnDebt(), 100);
            }
            
            return response;
        })
        .catch(error => {
            console.error('Error loading sales data:', error);
            const tbody = document.getElementById('receipt-table-body');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: red;">هەڵە لە بارکردنی داتاکان</td></tr>';
            }
            throw error;
        });
}
