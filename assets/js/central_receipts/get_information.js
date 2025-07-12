// Function to load receipt data
function loadReceiptData() {
    const receiptId = getReceiptIdFromUrl();
    
    if (!receiptId) {
        console.log('No receipt ID provided');
        return;
    }
    
    fetch(`../process/central_receipts/get_information.php?id=${receiptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateReceiptData(data);
            } else {
                console.error('Error loading receipt:', data.error);
                alert('Error loading receipt data: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading receipt data');
        });
}

// Function to get receipt ID from URL parameters
function getReceiptIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// Function to populate receipt data
function populateReceiptData(data) {
    const receipt = data.receipt;
    
    // Populate basic info
    setElementText('receipt_number', receipt.receipt_number || 'W-0001');
    setElementText('customer_name', receipt.customer_name || '-');
    setElementText('location', receipt.location || '-');
    setElementText('created_date', data.formatted_date || '-');
    
    // Populate customer phone
    let phoneText = receipt.customer_phone || '-';
    if (receipt.customer_phone2) {
        phoneText += ' - ' + receipt.customer_phone2;
    }
    setElementText('customer_phone', phoneText);
    
    // Populate table data
    setElementText('mixer_car_name', receipt.mixer_car_name || '-');
    setElementText('mixer_driver_name', receipt.mixer_driver_name || '-');
    setElementText('mixer_driver_mobile', receipt.mixer_driver_mobile || '-');
    
    setElementText('pump_car_name', receipt.pump_car_name || '-');
    setElementText('pump_driver_name', receipt.pump_driver_name || '-');
    setElementText('pump_driver_mobile', receipt.pump_driver_mobile || '-');
    
    setElementText('strength_info', data.strength_info || '-');
    setElementText('meter_amount', data.formatted_quantity + ' M³');
}

// Helper function to set element text content
function setElementText(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
    } else {
        console.warn(`Element with ID '${elementId}' not found`);
    }
}

// Function to ensure printing in portrait mode
function printInPortrait() {
    // Set print settings before opening dialog
    const mediaQueryList = window.matchMedia('print');
    mediaQueryList.addEventListener('change', function(mql) {
        if (mql.matches) {
            document.title = "پسوڵەی کۆنکرێت - دانا کۆنکرێت";
        }
    });
    
    // Short delay to ensure everything is ready
    setTimeout(function() {
        window.print();
    }, 200);
}

// Auto print function for when auto_print parameter is set
function autoPrint() {
    // Set print settings before opening dialog
    const mediaQueryList = window.matchMedia('print');
    mediaQueryList.addEventListener('change', function(mql) {
        if (mql.matches) {
            // Before print
            document.title = "پسوڵەی کۆنکرێت - دانا کۆنکرێت";
        }
    });
    
    // Wait a moment to ensure the page is fully loaded
    setTimeout(function() {
        window.print();
    }, 500);
}

// Check if auto print is requested
function checkAutoPrint() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('auto_print')) {
        autoPrint();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadReceiptData();
    checkAutoPrint();
});
