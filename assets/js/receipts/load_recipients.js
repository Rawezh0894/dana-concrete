// Load recipients for receipts filter
function loadRecipientsForReceipts() {
    console.log('Loading recipients for receipts...');
    
    fetch('../process/recipients/select.php')
        .then(response => {
            console.log('Recipients response status:', response.status);
            return response.json();
        })
        .then(data => {
            if (!(data && data.success && Array.isArray(data.data))) {
                console.warn('Invalid recipients response:', data);
                return;
            }
            
            const sortedRecipients = data.data
                .filter(recipient => recipient.name && recipient.name.trim() !== '')
                .sort((a, b) => a.name.localeCompare(b.name, 'ku'));
            
            const recipientContainer = document.getElementById('recipient-options-container');
            if (!recipientContainer) return;
            
            recipientContainer.innerHTML = '';
            
            sortedRecipients.forEach(recipient => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'location-option';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'location-checkbox';
                checkbox.id = `recipient-${recipient.id}`;
                checkbox.value = recipient.name;
                
                const label = document.createElement('label');
                label.htmlFor = checkbox.id;
                label.textContent = recipient.name;
                
                optionDiv.appendChild(checkbox);
                optionDiv.appendChild(label);
                
                checkbox.addEventListener('change', function() {
                    const allCheckbox = document.getElementById('recipient-all');
                    const recipientCheckboxes = document.querySelectorAll('#recipient-multiselect .location-checkbox:not(#recipient-all)');
                    
                    if (this.checked) {
                        if (allCheckbox) {
                            allCheckbox.checked = false;
                        }
                    } else {
                        const checkedCount = Array.from(recipientCheckboxes).filter(cb => cb.checked).length;
                        if (checkedCount === 0 && allCheckbox) {
                            allCheckbox.checked = true;
                        }
                    }
                    
                    updateRecipientSelectText();
                    
                    if (typeof loadSalesData === 'function') {
                        loadSalesData();
                    }
                });
                
                recipientContainer.appendChild(optionDiv);
            });
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
