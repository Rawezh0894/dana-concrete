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
                const locationSelect = document.getElementById('location-filter');
                if (locationSelect) {
                    // Clear existing options except the first one
                    locationSelect.innerHTML = '<option value="all">هەموو</option>';
                    
                    // Add location options
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.location;
                        option.textContent = location.location;
                        locationSelect.appendChild(option);
                    });
                    console.log('Locations loaded successfully:', data.locations.length, 'locations');
                } else {
                    console.error('Location select element not found');
                }
            } else {
                console.error('No locations found in sales data');
            }
        })
        .catch(error => {
            console.error('Error loading locations for receipts:', error);
        });
}

// Load locations when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadLocationsForReceipts();
});
