// Load locations function
function loadLocations() {
    $.ajax({
        url: '../process/location_driver/select_locations.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const locations = response.data || [];
                const tbody = $('#locationsTable tbody');
                tbody.empty();
                
                if (locations.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                هیچ شوێنێک نەدۆزرایەوە
                            </td>
                        </tr>
                    `);
                } else {
                    locations.forEach((location, index) => {
                        const row = `
                            <tr>
                                <td>${location.id}</td>
                                <td>${location.name}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-delete-location" 
                                            onclick="deleteLocation(${location.id}, '${location.name}')"
                                            title="سڕینەوەی شوێنەکە">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            } else {
                console.error('Error loading locations:', response.message);
                $('#locationsTable tbody').html(`
                    <tr>
                        <td colspan="3" class="text-center text-danger">
                            هەڵەیەک ڕوویدا لە بارکردنی شوێنەکان
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#locationsTable tbody').html(`
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەرەوە
                    </td>
                </tr>
            `);
        }
    });
}

// Load locations in select dropdowns
function loadLocationSelects() {
    $.ajax({
        url: '../process/location_driver/select_locations.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const locations = response.data || [];
                
                // Update location select in add purchase modal
                const locationSelect = $('#location_id');
                locationSelect.find('option:not(:first)').remove();
                
                locations.forEach(location => {
                    locationSelect.append(`
                        <option value="${location.id}">${location.name}</option>
                    `);
                });
                
                // Update location select in edit purchase modal
                const editLocationSelect = $('#edit_location_id');
                editLocationSelect.find('option:not(:first)').remove();
                
                locations.forEach(location => {
                    editLocationSelect.append(`
                        <option value="${location.id}">${location.name}</option>
                    `);
                });
                
                // Update filter location select
                const filterLocationSelect = $('#filter_location');
                filterLocationSelect.find('option:not(:first)').remove();
                
                locations.forEach(location => {
                    filterLocationSelect.append(`
                        <option value="${location.id}">${location.name}</option>
                    `);
                });
                
                // Reinitialize select2 if it exists
                if (typeof $().select2 === 'function') {
                    // Destroy existing select2 instances
                    if (locationSelect.hasClass('select2-hidden-accessible')) {
                        locationSelect.select2('destroy');
                    }
                    if (editLocationSelect.hasClass('select2-hidden-accessible')) {
                        editLocationSelect.select2('destroy');
                    }
                    
                    // Reinitialize select2 with proper configuration
                    locationSelect.select2({
                        dropdownParent: $('#addPurchaseModal'),
                        width: '100%',
                        placeholder: 'شوێن هەڵبژێرە',
                        dir: 'rtl',
                        allowClear: true
                    });
                    
                    editLocationSelect.select2({
                        dropdownParent: $('#editPurchaseModal'),
                        width: '100%',
                        placeholder: 'شوێن هەڵبژێرە',
                        dir: 'rtl',
                        allowClear: true
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading locations for selects:', error);
        }
    });
}

// Initialize when document is ready
$(document).ready(function() {
    // Load locations when drivers management modal is shown
    $('#driversManagementModal').on('shown.bs.modal', function() {
        loadLocations();
    });
    
    // Load locations on page load
    loadLocations();
    loadLocationSelects();
});
