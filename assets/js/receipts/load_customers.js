// Load customers for multi-select dropdown
async function loadCustomers() {
    try {
        const response = await fetch('../process/receipts/get_customers.php');
        const customers = await response.json();
        
        if (customers.success === false) {
            console.error('Failed to load customers');
            return;
        }
        
        const container = document.getElementById('customer-options-container');
        if (!container) return;
        
        container.innerHTML = '';
        
        customers.forEach((customer, index) => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'location-option';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `customer-${customer.id}`;
            checkbox.className = 'customer-checkbox location-checkbox';
            checkbox.value = customer.id;
            
            const label = document.createElement('label');
            label.htmlFor = `customer-${customer.id}`;
            label.textContent = customer.name;
            
            optionDiv.appendChild(checkbox);
            optionDiv.appendChild(label);
            container.appendChild(optionDiv);
            
            // Add event listeners
            checkbox.addEventListener('change', function() {
                handleCustomerCheckboxChange(this);
            });
        });
        
    } catch (error) {
        console.error('Error loading customers:', error);
    }
}

function handleCustomerCheckboxChange(checkbox) {
    const allCheckbox = document.getElementById('customer-all');
    const customerCheckboxes = document.querySelectorAll('.customer-checkbox:not(#customer-all)');
    
    if (checkbox.id === 'customer-all') {
        // If "all" is checked, uncheck all others
        customerCheckboxes.forEach(cb => {
            cb.checked = false;
        });
        checkbox.checked = true;
    } else {
        // If any individual checkbox is checked, uncheck "all"
        if (checkbox.checked && allCheckbox) {
            allCheckbox.checked = false;
        }
        
        // If no individual checkboxes are checked, check "all"
        const anyChecked = Array.from(customerCheckboxes).some(cb => cb.checked);
        if (!anyChecked && allCheckbox) {
            allCheckbox.checked = true;
        }
    }
    
    updateCustomerSelectText();
    
    // Reload data when customer selection changes
    if (typeof loadSalesData === 'function') {
        loadSalesData();
    }
}

// Setup event listener for "all customers" checkbox
document.addEventListener('DOMContentLoaded', function() {
    const allCheckbox = document.getElementById('customer-all');
    if (allCheckbox) {
        allCheckbox.addEventListener('change', function() {
            handleCustomerCheckboxChange(this);
        });
    }
    
    // Load customers on page load
    loadCustomers();
});

