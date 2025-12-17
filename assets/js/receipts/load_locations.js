// Load locations for receipts filter
function loadLocationsForReceipts() {
    console.log('Loading locations for receipts...');
    
    // First load sales data to get locations
    const params = new URLSearchParams({
        customer_id: CUSTOMER_ID,
        type: 'all',
        month: 'all',
        date_from: '',
        date_to: '',
        location: 'all'
    });
    
    fetch(`../process/receipts/select_sale.php?${params.toString()}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Sales data received:', data);
            if (data.locations) {
                const locationContainer = document.getElementById('location-options-container');
                if (locationContainer) {
                    // Clear existing options
                    locationContainer.innerHTML = '';
                    
                    // Add location options
                    data.locations.forEach(location => {
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'location-option';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `location-${location.location.replace(/\s+/g, '-')}`;
                        checkbox.className = 'location-checkbox';
                        checkbox.value = location.location;
                        
                        const label = document.createElement('label');
                        label.htmlFor = checkbox.id;
                        label.textContent = location.location;
                        
                        optionDiv.appendChild(checkbox);
                        optionDiv.appendChild(label);
                        locationContainer.appendChild(optionDiv);
                        
                        // Add event listener for checkbox change
                        checkbox.addEventListener('change', function() {
                            handleLocationCheckboxChange();
                        });
                    });
                    
                    // Add event listener for "all" checkbox
                    const allCheckbox = document.getElementById('location-all');
                    if (allCheckbox) {
                        allCheckbox.addEventListener('change', function() {
                            handleAllLocationCheckboxChange();
                        });
                    }

                    // Default: if "all" is checked, check all options too (Excel-like)
                    if (allCheckbox && allCheckbox.checked) {
                        handleAllLocationCheckboxChange();
                    } else {
                        handleLocationCheckboxChange();
                    }
                    
                    console.log('Locations loaded successfully:', data.locations.length, 'locations');
                } else {
                    console.error('Location options container not found');
                }
            } else {
                console.error('No locations found in sales data');
            }
        })
        .catch(error => {
            console.error('Error loading locations for receipts:', error);
        });
}

// Handle individual location checkbox changes
function handleLocationCheckboxChange() {
    const allCheckbox = document.getElementById('location-all');
    const locationCheckboxes = document.querySelectorAll('.location-checkbox:not(#location-all)');
    if (allCheckbox && locationCheckboxes.length > 0) {
        const checkedCount = Array.from(locationCheckboxes).filter(cb => cb.checked).length;
        allCheckbox.checked = checkedCount === locationCheckboxes.length;
    }
    
    updateLocationSelectText();
    
    // Reload data if receiptManager exists
    if (window.receiptManager && typeof window.receiptManager.loadSalesData === 'function') {
        window.receiptManager.loadSalesData();
    }
}

// Handle "all" location checkbox change
function handleAllLocationCheckboxChange() {
    const allCheckbox = document.getElementById('location-all');
    const locationCheckboxes = document.querySelectorAll('.location-checkbox:not(#location-all)');
    
    // Excel-like: checked => check all, unchecked => uncheck all
    locationCheckboxes.forEach(checkbox => {
        checkbox.checked = !!(allCheckbox && allCheckbox.checked);
    });
    
    updateLocationSelectText();
    
    // Reload data if receiptManager exists
    if (window.receiptManager && typeof window.receiptManager.loadSalesData === 'function') {
        window.receiptManager.loadSalesData();
    }
}

// Load locations when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadLocationsForReceipts();
});
