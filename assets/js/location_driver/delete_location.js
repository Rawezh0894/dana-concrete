// Delete location function
function deleteLocation(locationId, locationName) {
    Swal.fire({
        title: 'دڵنیایی',
        text: `ئایا دڵنیایت کە دەتەوێت شوێنەکە "${locationName}" بسڕیتەوە؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەرەوە',
        cancelButtonText: 'نەخێر',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'سڕینەوە...',
                text: 'تکایە چاوەڕێ بکە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Make AJAX request to delete location
            $.ajax({
                url: '../process/location_driver/delete_location.php',
                type: 'POST',
                data: {
                    id: locationId
                },
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        Swal.fire({
                            title: 'سەرکەوتوو',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload locations table
                        if (typeof loadLocations === 'function') {
                            loadLocations();
                        }
                        
                        // Reload locations in select dropdowns
                        if (typeof loadLocationSelects === 'function') {
                            loadLocationSelects();
                        }
                        
                    } else {
                        Swal.fire({
                            title: 'هەڵە',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەرەوە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }
    });
}

// Add delete button to locations table
function addDeleteButtonToLocationsTable() {
    // Add delete button to each location row
    $('#locationsTable tbody tr').each(function() {
        const locationId = $(this).find('td:first').text();
        const locationName = $(this).find('td:nth-child(2)').text();
        
        // Check if delete button already exists
        if ($(this).find('.btn-delete-location').length === 0) {
            const deleteButton = `
                <button class="btn btn-sm btn-danger btn-delete-location" 
                        onclick="deleteLocation(${locationId}, '${locationName}')"
                        title="سڕینەوەی شوێنەکە">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            $(this).find('td:last').append(deleteButton);
        }
    });
}

// Initialize delete functionality when document is ready
$(document).ready(function() {
    // Add delete functionality after locations table is loaded
    $(document).on('DOMNodeInserted', '#locationsTable tbody', function() {
        setTimeout(addDeleteButtonToLocationsTable, 100);
    });
    
    // Also add delete buttons immediately if table already exists
    setTimeout(addDeleteButtonToLocationsTable, 500);
});
