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
        recipient: 'all'
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
                    recipientContainer.innerHTML = '';
                    
                    data.recipients.forEach(recipient => {
                        if (recipient.recipient && recipient.recipient.trim() !== '') {
                            const optionDiv = document.createElement('div');
                            optionDiv.className = 'location-option';
                            
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.className = 'location-checkbox';
                            checkbox.id = `recipient-${recipient.recipient.replace(/\s+/g, '-')}`;
                            checkbox.value = recipient.recipient;
                            
                            const label = document.createElement('label');
                            label.htmlFor = checkbox.id;
                            label.textContent = recipient.recipient;
                            
                            optionDiv.appendChild(checkbox);
                            optionDiv.appendChild(label);
                            
                            // Add event listener to handle checkbox changes
                            checkbox.addEventListener('change', function() {
                                const allCheckbox = document.getElementById('recipient-all');
                                const recipientCheckboxes = document.querySelectorAll('#recipient-multiselect .location-checkbox:not(#recipient-all)');
                                
                                if (this.checked) {
                                    // If this checkbox is checked, uncheck "all"
                                    if (allCheckbox) {
                                        allCheckbox.checked = false;
                                    }
                                } else {
                                    // If this checkbox is unchecked, check if all others are unchecked
                                    const checkedCount = Array.from(recipientCheckboxes).filter(cb => cb.checked).length;
                                    if (checkedCount === 0 && allCheckbox) {
                                        allCheckbox.checked = true;
                                    }
                                }
                                
                                updateRecipientSelectText();
                                
                                // Reload data when recipient selection changes
                                if (typeof loadSalesData === 'function') {
                                    loadSalesData();
                                }
                            });
                            
                            recipientContainer.appendChild(optionDiv);
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading recipients:', error);
        });
}

// Load recipients when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
        loadRecipientsForReceipts();
    }
});
