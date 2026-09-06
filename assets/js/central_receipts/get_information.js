// Global variable to store current receipt data
let currentReceiptData = null;

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
    currentReceiptData = data;
    const receipt = data.receipt;
    
    // Populate basic info
    setElementText('receipt_number', receipt.receipt_number || 'W-0001');
    setElementText('location', receipt.location || '-');
    setElementText('created_date', data.formatted_date || '-');
    
    const customerLine = buildContactLine('کڕیار', receipt.customer_name, [
        receipt.customer_phone,
        receipt.customer_phone2
    ]);
    const recipientLine = buildContactLine('وەرگر', receipt.receiver_name, [
        receipt.recipient_phone1,
        receipt.recipient_phone2
    ]);
    setElementText('customer_recipient_line', `${customerLine}  |  ${recipientLine}`);
    
    // Populate table data
    setElementText('mixer_car_name', receipt.mixer_car_name || '-');
    setElementText('mixer_driver_name', receipt.mixer_driver_name || '-');
    setElementText('mixer_driver_mobile', receipt.mixer_driver_mobile || '-');
    
    setElementText('pump_car_name', receipt.pump_car_name || '-');
    setElementText('pump_driver_name', receipt.pump_driver_name || '-');
    setElementText('pump_driver_mobile', receipt.pump_driver_mobile || '-');
    
    setElementText('meter_amount', data.formatted_quantity + ' M³');

    // Initialize formula checkbox state
    const showFormulaCheckbox = document.getElementById('show_formula_name_checkbox');
    const isFormulaChecked = localStorage.getItem('central_receipt_show_formula') === 'true';
    if (showFormulaCheckbox) {
        showFormulaCheckbox.checked = isFormulaChecked;
        showFormulaCheckbox.onchange = function() {
            toggleFormulaNameDisplay(this.checked);
        };
    }

    updateStrengthDisplay();
}

// Function to update strength or formula name display
function updateStrengthDisplay() {
    if (!currentReceiptData) return;
    const showFormulaCheckbox = document.getElementById('show_formula_name_checkbox');
    const isChecked = showFormulaCheckbox ? showFormulaCheckbox.checked : (localStorage.getItem('central_receipt_show_formula') === 'true');
    
    const receipt = currentReceiptData.receipt || {};
    let textToDisplay = '-';
    
    if (isChecked) {
        textToDisplay = receipt.formula_name || currentReceiptData.strength_info || '-';
    } else {
        textToDisplay = currentReceiptData.strength_info || '-';
    }
    
    setElementText('strength_info', textToDisplay);
}

// Function to toggle formula name display
function toggleFormulaNameDisplay(checked) {
    localStorage.setItem('central_receipt_show_formula', checked ? 'true' : 'false');
    updateStrengthDisplay();
}
window.toggleFormulaNameDisplay = toggleFormulaNameDisplay;

// Helper function to set element text content
function setElementText(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
    } else {
        console.warn(`Element with ID '${elementId}' not found`);
    }
}

function buildContactLine(label, name, phones = []) {
    const cleanedName = (name || '').trim();
    const phoneList = (phones || []).filter(Boolean).map(phone => phone.trim()).filter(Boolean);
    const primaryPhone = phoneList.length ? phoneList[0] : '';
    let line = `${label}:`;
    if (cleanedName) {
        line += cleanedName;
        if (primaryPhone) {
            line += '-' + primaryPhone;
        }
    } else {
        line += '-';
    }
    return line;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadReceiptData();
});