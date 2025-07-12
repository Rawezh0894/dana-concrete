// Global variable to track if data is loaded
window.DATA_LOADED = false;

document.addEventListener('DOMContentLoaded', function() {
    // Set current date
    const dateElem = document.getElementById('payment-date');
    if (dateElem) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateElem.textContent = `${yyyy}-${mm}-${dd}`;
    }

    // Load customer information
    loadCustomerInfo();
    
    // Check if data is loaded after a delay
    setTimeout(checkDataLoaded, 2000);
});

function loadCustomerInfo() {
    if (!CUSTOMER_ID) return;
    
    fetch(`../process/receipts/select_sale.php?customer_id=${CUSTOMER_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.customer_info) {
                // Update customer name
                const customerNameElem = document.querySelector('.customer-name');
                if (customerNameElem && data.customer_info.company_name) {
                    customerNameElem.textContent = data.customer_info.company_name;
                }
                // Update customer mobile
                const customerMobileElem = document.querySelector('.customer-mobile');
                if (customerMobileElem && data.customer_info.mobile) {
                    customerMobileElem.textContent = data.customer_info.mobile;
                }
            }
        })
        .catch(error => {
            console.error('Error loading customer info:', error);
        });
}

// Function to check if all data is loaded
function checkDataLoaded() {
    if (window.SALES_DATA_LOADED && window.RETURN_DEBT_DATA_LOADED) {
        window.DATA_LOADED = true;
        console.log('All data loaded successfully');
    } else {
        console.log('Data still loading... Sales:', window.SALES_DATA_LOADED, 'Return Debt:', window.RETURN_DEBT_DATA_LOADED);
        // Retry after 1 second
        setTimeout(checkDataLoaded, 1000);
    }
}

// Function to ensure data is ready for print
function ensureDataReady() {
    return new Promise((resolve, reject) => {
        if (window.DATA_LOADED) {
            resolve();
            return;
        }
        
        // Wait for data to load
        const checkInterval = setInterval(() => {
            if (window.DATA_LOADED) {
                clearInterval(checkInterval);
                resolve();
            }
        }, 100);
        
        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            reject(new Error('Data loading timeout'));
        }, 10000);
    });
}

// Function to force reload all data
function forceReloadData() {
    window.DATA_LOADED = false;
    window.SALES_DATA_LOADED = false;
    window.RETURN_DEBT_DATA_LOADED = false;
    
    if (typeof loadSalesData === 'function') {
        loadSalesData();
    }
    
    if (typeof loadReturnDebt === 'function') {
        setTimeout(() => loadReturnDebt(), 500);
    }
}

// Override window.print to ensure data is loaded first
const originalPrint = window.print;
window.print = function() {
    ensureDataReady()
        .then(() => {
            console.log('Data ready, proceeding with print');
            originalPrint.call(window);
        })
        .catch(error => {
            console.error('Print failed:', error);
            alert('هەڵە لە بارکردنی داتاکان. تکایە دواتر هەوڵ بدەوە.');
        });
}; 