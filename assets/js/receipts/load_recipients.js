function createRecipientOptionId(recipient, index) {
    if (recipient.id) {
        return `recipient-${recipient.id}`;
    }
    
    const baseName = (recipient.name || `option-${index}`).toString().trim();
    if (!baseName) {
        return `recipient-auto-${index}`;
    }
    
    const slug = baseName
        .toLowerCase()
        .replace(/[^a-z0-9\u0600-\u06FF]+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    return `recipient-${slug || 'auto'}-${index}`;
}

function setAllRecipientsChecked(checked) {
    const recipientCheckboxes = document.querySelectorAll('#recipient-multiselect .location-checkbox:not(#recipient-all)');
    recipientCheckboxes.forEach(cb => {
        cb.checked = checked;
    });
}

function syncRecipientAllCheckboxState() {
    const allCheckbox = document.getElementById('recipient-all');
    const recipientCheckboxes = document.querySelectorAll('#recipient-multiselect .location-checkbox:not(#recipient-all)');
    if (!allCheckbox || recipientCheckboxes.length === 0) return;
    
    const checkedCount = Array.from(recipientCheckboxes).filter(cb => cb.checked).length;
    allCheckbox.checked = checkedCount === recipientCheckboxes.length;
}

// Load recipients for receipts filter
function loadRecipientsForReceipts() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        console.warn('Cannot load recipients: CUSTOMER_ID is missing.');
        return;
    }
    
    console.log('Loading recipients for receipts...');
    const params = new URLSearchParams({ customer_id: CUSTOMER_ID });
    
    fetch(`../process/recipients/select.php?${params.toString()}`)
        .then(response => {
            console.log('Recipients response status:', response.status);
            return response.json();
        })
        .then(data => {
            if (!(data && data.success && Array.isArray(data.data))) {
                console.warn('Invalid recipients response:', data);
                return;
            }
            
            const normalizedRecipients = data.data
                .map((recipient, index) => {
                    if (typeof recipient === 'string') {
                        return { id: null, name: recipient };
                    }
                    
                    return {
                        id: recipient.id || null,
                        name: recipient.name || recipient.recipient || ''
                    };
                })
                .filter(recipient => recipient.name && recipient.name.trim() !== '');
            
            const sortedRecipients = normalizedRecipients
                .sort((a, b) => a.name.localeCompare(b.name, 'ku'));
            
            const recipientContainer = document.getElementById('recipient-options-container');
            if (!recipientContainer) return;
            
            recipientContainer.innerHTML = '';
            
            if (sortedRecipients.length === 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'location-option';
                emptyDiv.textContent = 'هیچ وەرگرێک نەدۆزرایەوە';
                emptyDiv.style.color = '#6c757d';
                recipientContainer.appendChild(emptyDiv);
                updateRecipientSelectText();
                return;
            }
            
            sortedRecipients.forEach((recipient, index) => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'location-option';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'location-checkbox';
                checkbox.id = createRecipientOptionId(recipient, index);
                checkbox.value = recipient.name;
                
                const label = document.createElement('label');
                label.htmlFor = checkbox.id;
                label.textContent = recipient.name;
                
                optionDiv.appendChild(checkbox);
                optionDiv.appendChild(label);
                
                checkbox.addEventListener('change', function() {
                    syncRecipientAllCheckboxState();
                    
                    updateRecipientSelectText();
                    
                    if (typeof loadSalesData === 'function') {
                        loadSalesData();
                    }
                });
                
                recipientContainer.appendChild(optionDiv);
            });
            
            // Wire "Select All" (Excel-like)
            const allCheckbox = document.getElementById('recipient-all');
            if (allCheckbox && !allCheckbox.dataset.boundExcelLike) {
                allCheckbox.dataset.boundExcelLike = 'true';
                allCheckbox.addEventListener('change', function() {
                    setAllRecipientsChecked(this.checked);
                    updateRecipientSelectText();
                    if (typeof loadSalesData === 'function') {
                        loadSalesData();
                    }
                });
            }

            // Default behavior: if "all" is checked, ensure all options are checked too
            const all = document.getElementById('recipient-all');
            if (all && all.checked) {
                setAllRecipientsChecked(true);
            } else {
                // If nothing checked initially, keep as-is (allows "none" => no results)
                syncRecipientAllCheckboxState();
            }

            updateRecipientSelectText();
        })
        .catch(error => {
            console.error('Error loading recipients:', error);
        });
}

// Load recipients when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    loadRecipientsForReceipts();
});
