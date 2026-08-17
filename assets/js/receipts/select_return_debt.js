// Global variable to track return debt data loading state
window.RETURN_DEBT_DATA_LOADED = false;

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

function getPaidDateFrom() {
    const paidDateFromElem = document.getElementById('paid-date-from-filter');
    return paidDateFromElem ? paidDateFromElem.value : '';
}

function getPaidDateTo() {
    const paidDateToElem = document.getElementById('paid-date-to-filter');
    return paidDateToElem ? paidDateToElem.value : '';
}

function getInvoiceNumberFilter() {
    const invoiceNumberElem = document.getElementById('invoice-number-filter');
    return invoiceNumberElem ? invoiceNumberElem.value.trim() : '';
}

function getInvoiceFilterMode() {
    const modeElem = document.getElementById('invoice-filter-mode');
    const mode = modeElem ? modeElem.value : 'include';
    return mode === 'exclude' ? 'exclude' : 'include';
}

// Note: We intentionally do NOT auto-format the invoice input field.
// Auto-formatting while the user is typing (e.g., removing a trailing comma) is disruptive.

function formatUsdAmount(amount) {
    const num = parseFloat(amount);
    if (!isFinite(num) || num === 0) {
        return '$0';
    }
    return '$' + num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function shouldShowDebtDiscount() {
    const checkbox = document.getElementById('show-debt-discount');
    return checkbox ? checkbox.checked === true : false;
}

function shouldShowChangeBack() {
    const checkbox = document.getElementById('show-change-back');
    return checkbox ? checkbox.checked === true : false;
}

function applyPaidTableColumnVisibility() {
    const paidTable = document.getElementById('paid-table');
    if (!paidTable) return;

    if (shouldShowDebtDiscount()) {
        paidTable.classList.add('show-debt-discount');
    } else {
        paidTable.classList.remove('show-debt-discount');
    }

    if (shouldShowChangeBack()) {
        paidTable.classList.add('show-change-back');
    } else {
        paidTable.classList.remove('show-change-back');
    }
}

function getPaidTableColumnCount() {
    let count = 4;
    if (shouldShowChangeBack()) count += 2;
    if (shouldShowDebtDiscount()) count += 1;
    return count;
}

function formatIqdAmount(amount) {
    const num = parseFloat(amount);
    if (!isFinite(num) || num === 0) {
        return '0 د.ع';
    }
    return num.toLocaleString(undefined, { maximumFractionDigits: 0 }) + ' د.ع';
}

// Function to format date as DD/MM/YYYY
function formatDate(dateString) {
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
        
        // Format as DD/MM/YYYY (day/month/year)
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.warn('Error formatting date:', dateString, e);
        return String(dateString);
    }
}

// Listen for filter changes and reload paid-table
['month-filter', 'date-from-filter', 'date-to-filter', 'paid-date-from-filter', 'paid-date-to-filter'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('change', function() {
            loadReturnDebt();
        });
    }
});

// Invoice number filter with formatting
var invoiceNumberFilter = document.getElementById('invoice-number-filter');
if (invoiceNumberFilter) {
    let invoiceFilterTimeout;
    invoiceNumberFilter.addEventListener('input', function() {
        clearTimeout(invoiceFilterTimeout);
        invoiceFilterTimeout = setTimeout(() => {
            // Reload paid table data when invoice filter changes
            loadReturnDebt();
        }, 500); // 500ms delay to avoid too many requests
    });
}

const invoiceFilterModeSelect = document.getElementById('invoice-filter-mode');
if (invoiceFilterModeSelect) {
    invoiceFilterModeSelect.addEventListener('change', function() {
        const invoiceValue = getInvoiceNumberFilter();
        if (invoiceValue.length === 0) {
            // No invoice numbers; reset mode to include for clarity
            invoiceFilterModeSelect.value = 'include';
        }
        loadReturnDebt();
    });
}

function loadReturnDebt() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        console.error('CUSTOMER_ID is not defined for return debt loading');
        return Promise.reject('CUSTOMER_ID not defined');
    }
    const month = getSelectedMonth();
    const date_from = getPaidDateFrom();
    const date_to = getPaidDateTo();
    const invoice_number = getInvoiceNumberFilter();
    const invoice_filter_mode = getInvoiceFilterMode();
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        month,
        date_from,
        date_to
    });
    
    // Add invoice number filter if provided
    if (invoice_number) {
        params.append('invoice_number', invoice_number);
        params.append('invoice_filter_mode', invoice_filter_mode);
    }
    return fetch('../process/receipts/select_return_debt.php?' + params.toString())
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // Find USD to IQD rate from the first row of the main table
            let usdToIqdRate = 0;
            const mainTable = document.getElementById('receipt-table-body');
            if (mainTable) {
                const rows = mainTable.querySelectorAll('tr');
                for (let row of rows) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 5) {
                        const usd = parseFloat((cells[4].textContent || '').replace(/[$,]/g, ''));
                        const iqd = parseFloat((cells[5].textContent || '').replace(/[د.ع,]/g, ''));
                        if (usd && iqd) {
                            usdToIqdRate = iqd / usd;
                            break;
                        }
                    }
                }
            }

            // Fill the vertical paid-table only
            const paidTableBody = document.getElementById('paid-table-body');
            if (paidTableBody) {
                paidTableBody.innerHTML = '';
                let totalPaidUsd = 0;
                let totalPaidIqd = 0;
                let totalDiscount = 0;
                let totalChangeUsd = 0;
                let totalChangeIq = 0;
                let totalNetPaidUsd = 0;
                let iqdConversionPossible = true;
                applyPaidTableColumnVisibility();

                // Determine fallback exchange rate for payments missing explicit dolar_rate
                let fallbackRate = usdToIqdRate || 0;
                if (!fallbackRate && data && data.length > 0) {
                    for (let r of data) {
                        let rRate = parseFloat(r.dolar_rate || 0);
                        if (rRate > 0) {
                            fallbackRate = rRate;
                            break;
                        }
                    }
                }
                if (!fallbackRate) fallbackRate = 150000;

                data.forEach(row => {
                    const amountUsd = parseFloat(row.paid_usd || 0);
                    const amountIqd = parseFloat(row.paid_iqd || 0);
                    const changeUsd = parseFloat(row.change_back_usd || 0);
                    const changeIq = parseFloat(row.change_back_iq || 0);
                    let rate = parseFloat(row.dolar_rate || 0);
                    if (rate <= 0) {
                        rate = fallbackRate;
                    }
                    const discountAmount = parseFloat(row.discount || 0) || 0;
                    
                    // Net paid amount for this payment (subtracting change returned to customer)
                    const netUsd = amountUsd - changeUsd;
                    const netIqd = amountIqd - changeIq;
                    let netIqdToUsd = 0;
                    if (netIqd !== 0) {
                        if (rate > 0) {
                            netIqdToUsd = netIqd / (rate / 100);
                        } else {
                            iqdConversionPossible = false;
                        }
                    }
                    totalNetPaidUsd += (netUsd + netIqdToUsd);

                    totalPaidUsd += amountUsd;
                    totalPaidIqd += amountIqd;
                    totalDiscount += discountAmount;
                    totalChangeUsd += changeUsd;
                    totalChangeIq += changeIq;
                    paidTableBody.innerHTML += `
                        <tr>
                            <td>${'$' + (amountUsd ? amountUsd.toLocaleString() : '0')}</td>
                            <td>${amountIqd ? amountIqd.toLocaleString() + ' د.ع' : '0 د.ع'}</td>
                            <td class="change-back-col">${formatUsdAmount(changeUsd)}</td>
                            <td class="change-back-col">${formatIqdAmount(changeIq)}</td>
                            <td class="debt-discount-col">${formatUsdAmount(discountAmount)}</td>
                            <td class="paid-date-col">${formatDate(row.date)}</td>
                            <td>${row.note && row.note.trim() ? row.note : '–'}</td>
                        </tr>
                    `;
                });

                // Rounding totals to avoid floating point precision issues (e.g. 949.26000001)
                totalDiscount = Math.round(totalDiscount * 100) / 100;
                let grandTotalUsd = Math.round(totalNetPaidUsd * 100) / 100;
                
                // Expose to window for summary component single source of truth
                window.RECEIPT_TOTAL_DISCOUNT = totalDiscount;
                window.RECEIPT_TOTAL_PAID = grandTotalUsd;

                // Add total row if any payments, otherwise show "no payments" message
                if (data.length > 0) {
                    let totalText = '';
                    if (iqdConversionPossible) {
                        totalText = '$' + grandTotalUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    } else {
                        totalText = '$' + totalPaidUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <span style="color:red;font-size:0.95em">(نرخی دۆلار بۆ دینار نییە!)</span>';
                    }
                    paidTableBody.innerHTML += `
                        <tr class="paid-summary-row">
                            <td colspan="2" style="font-weight:bold;">${totalText}</td>
                            <td class="change-back-col" style="font-weight:bold; text-align:center;">
                                ${formatUsdAmount(totalChangeUsd)}
                                <div style="font-size:0.85em; font-weight:normal; margin-top:0.25rem;">کۆی باقی ($)</div>
                            </td>
                            <td class="change-back-col" style="font-weight:bold; text-align:center;">
                                ${formatIqdAmount(totalChangeIq)}
                                <div style="font-size:0.85em; font-weight:normal; margin-top:0.25rem;">کۆی باقی (د.ع)</div>
                            </td>
                            <td class="debt-discount-col" style="font-weight:bold; text-align:center;">
                                ${formatUsdAmount(totalDiscount)}
                                <div style="font-size:0.85em; font-weight:normal; margin-top:0.25rem;">کۆی داشکاندن</div>
                            </td>
                            <td colspan="2" style="font-weight:bold;">کۆی پارەی واسڵ کراو (صافی)</td>
                        </tr>
                    `;
                } else {
                    const colCount = getPaidTableColumnCount();
                    paidTableBody.innerHTML = `
                        <tr>
                            <td colspan="${colCount}" style="text-align: center; padding: 2rem; color: #6c757d; font-style: italic;">
                                <i class="fa fa-info-circle" style="margin-left: 0.5rem;"></i>
                                هیچ پارەدانێک نیە
                            </td>
                        </tr>
                    `;
                }
            }
            
            // Mark return debt data as loaded
            window.RETURN_DEBT_DATA_LOADED = true;
            console.log('Return debt data loaded successfully');
            
            // Update debt summary section with recalculated single source of truth figures
            if (window.receiptManager && typeof window.receiptManager.updateDebtSummary === 'function') {
                window.receiptManager.updateDebtSummary();
            }

            return data;
        })
        .catch(error => {
            console.error('Error loading return debt data:', error);
            const footer = document.getElementById('receipt-table-footer');
            if (footer) {
                footer.innerHTML = '<tr class="summary-row"><td colspan="9" style="text-align: center; color: red;">هەڵە لە بارکردنی داتای قەرز</td></tr>';
            }
            throw error;
        });
}

// Function to check if all data is loaded
function isAllDataLoaded() {
    return window.SALES_DATA_LOADED === true && window.RETURN_DEBT_DATA_LOADED === true;
}

// Function to force reload all data (useful for print preview)
function reloadAllData() {
    window.SALES_DATA_LOADED = false;
    window.RETURN_DEBT_DATA_LOADED = false;
    
    if (typeof loadSalesData === 'function') {
        loadSalesData().then(() => {
            if (typeof loadReturnDebt === 'function') {
                setTimeout(() => loadReturnDebt(), 100);
            }
        });
    }
}
