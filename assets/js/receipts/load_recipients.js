// Load recipients for receipts filter
function loadRecipientsForReceipts() {
    console.log('Loading recipients for receipts...');
    
    // First load sales data to get recipients
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        type: 'all',
        month: 'all',
        date_from: '',
        date_to: '',
        location: 'all',
        recipients: 'all'
    });
    
    fetch(`../process/receipts/select_sale.php?${params.toString()}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Sales data received:', data);
            if (data.recipients) {
                const recipientContainer = document.getElementById('recipient-options-container');
                if (recipientContainer) {
                    // Clear existing options
                    recipientContainer.innerHTML = '';
                    
                    // Add recipient options
                    data.recipients.forEach(recipient => {
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'recipient-option';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `recipient-${recipient.recipient.replace(/\s+/g, '-')}`;
                        checkbox.className = 'recipient-checkbox';
                        checkbox.value = recipient.recipient;
                        
                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        label.textContent = recipient.recipient;
                        
                        optionDiv.appendChild(checkbox);
                        optionDiv.appendChild(label);
                        recipientContainer.appendChild(optionDiv);
                        
                        // Add event listener for checkbox change
                        checkbox.addEventListener('change', function() {
                            handleRecipientCheckboxChange();
                        });
                    });
                    
                    // Add event listener for "all" checkbox
                    const allCheckbox = document.getElementById('recipient-all');
                    if (allCheckbox) {
                        allCheckbox.addEventListener('change', function() {
                            handleAllRecipientCheckboxChange();
                        });
                    }
                    
                    console.log('Recipients loaded successfully:', data.recipients.length, 'recipients');
                } else {
                    console.error('Recipient options container not found');
                }
            } else {
                console.error('No recipients found in sales data');
            }
        })
        .catch(error => {
            console.error('Error loading recipients for receipts:', error);
        });
}

// Handle individual recipient checkbox changes
function handleRecipientCheckboxChange() {
    const allCheckbox = document.getElementById('recipient-all');
    const recipientCheckboxes = document.querySelectorAll('.recipient-checkbox:not(#recipient-all)');
    const checkedCount = Array.from(recipientCheckboxes).filter(cb => cb.checked).length;
    
    // If any individual recipient is checked, uncheck "all"
    if (checkedCount > 0 && allCheckbox.checked) {
        allCheckbox.checked = false;
    }
    
    // If all individual recipients are unchecked, check "all"
    if (checkedCount === 0 && !allCheckbox.checked) {
        allCheckbox.checked = true;
    }
    
    updateRecipientSelectText();
    
    // Reload data if receiptManager exists
    if (window.receiptManager && typeof window.receiptManager.loadSalesData === 'function') {
        window.receiptManager.loadSalesData();
    }
}

// Handle "all" recipient checkbox change
function handleAllRecipientCheckboxChange() {
    const allCheckbox = document.getElementById('recipient-all');
    const recipientCheckboxes = document.querySelectorAll('.recipient-checkbox:not(#recipient-all)');
    
    if (allCheckbox.checked) {
        // Uncheck all individual recipients
        recipientCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    
    updateRecipientSelectText();
    
    // Reload data if receiptManager exists
    if (window.receiptManager && typeof window.receiptManager.loadSalesData === 'function') {
        window.receiptManager.loadSalesData();
    }
}

// Load recipients when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadRecipientsForReceipts();
});
