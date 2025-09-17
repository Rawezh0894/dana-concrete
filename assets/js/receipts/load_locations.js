// Load locations for receipts filter
function loadLocationsForReceipts() {
    fetch('../process/location_driver/select_locations.php')
        .then(response => response.json())
        .then(data => {
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
                }
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
