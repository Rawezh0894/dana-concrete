// Load locations for receipts filter
function loadLocationsForReceipts() {
    console.log('Loading locations for receipts...');
    fetch('../process/location_driver/select_locations.php')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Locations data received:', data);
            if (data.success && data.locations) {
                const locationSelect = document.getElementById('location-filter');
                if (locationSelect) {
                    // Clear existing options except the first one
                    locationSelect.innerHTML = '<option value="all">هەموو</option>';
                    
                    // Add location options
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.name;
                        option.textContent = location.name;
                        locationSelect.appendChild(option);
                    });
                    console.log('Locations loaded successfully:', data.locations.length, 'locations');
                } else {
                    console.error('Location select element not found');
                }
            } else {
                console.error('Error loading locations:', data.message || 'Unknown error');
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
