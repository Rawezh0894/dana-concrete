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

// Listen for filter changes and reload paid-table
['month-filter', 'date-from-filter', 'date-to-filter', 'paid-date-from-filter', 'paid-date-to-filter'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('change', function() {
            loadReturnDebt();
        });
    }
});

function loadReturnDebt() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        console.error('CUSTOMER_ID is not defined for return debt loading');
        return Promise.reject('CUSTOMER_ID not defined');
    }
    const month = getSelectedMonth();
    const date_from = getPaidDateFrom();
    const date_to = getPaidDateTo();
    const invoice_number = getInvoiceNumberFilter();
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        month,
        date_from,
        date_to
    });
    
    // Add invoice number filter if provided
    if (invoice_number) {
        params.append('invoice_number', invoice_number);
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
                let totalIqdToUsd = 0;
                let iqdConversionPossible = true;
                data.forEach(row => {
                    const amountUsd = parseFloat(row.paid_usd || 0);
                    const amountIqd = parseFloat(row.paid_iqd || 0);
                    const rate = parseFloat(row.dolar_rate || 0);
                    let iqdToUsd = 0;
                    if (amountIqd > 0) {
                        if (rate > 0) {
                            iqdToUsd = amountIqd / (rate / 100);
                        } else {
                            iqdConversionPossible = false;
                        }
                    }
                    totalPaidUsd += amountUsd;
                    totalPaidIqd += amountIqd;
                    totalIqdToUsd += iqdToUsd;
                    paidTableBody.innerHTML += `
                        <tr>
                            <td>${'$' + (amountUsd ? amountUsd.toLocaleString() : '0')}</td>
                            <td>${amountIqd ? amountIqd.toLocaleString() + ' د.ع' : '0 د.ع'}</td>
                            <td>${row.date}</td>
                            <td>${row.note && row.note.trim() ? row.note : '–'}</td>
                        </tr>
                    `;
                });
                // Add total row if any payments, otherwise show "no payments" message
                if (data.length > 0) {
                    let grandTotalUsd = (iqdConversionPossible ? (totalPaidUsd + totalIqdToUsd) : totalPaidUsd);
                    let totalText = '';
                    if (iqdConversionPossible) {
                        totalText = '$' + grandTotalUsd.toLocaleString(undefined, {maximumFractionDigits:2});
                    } else {
                        totalText = '$' + totalPaidUsd.toLocaleString(undefined, {maximumFractionDigits:2}) + ' <span style="color:red;font-size:0.95em">(نرخی دۆلار بۆ دینار نییە!)</span>';
                    }
                    paidTableBody.innerHTML += `
                        <tr>
                        <td colspan="2" style="font-weight:bold;">${totalText}</td>
                            <td colspan="2" style="font-weight:bold;">کۆی پارەی واسڵ کراو</td>
                            
                        </tr>
                    `;
                } else {
                    // Show "no payments" message when there are no payments
                    paidTableBody.innerHTML = `
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: #6c757d; font-style: italic;">
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
