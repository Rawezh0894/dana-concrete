// Drivers Management JavaScript
$(document).ready(function() {
    // Flag to prevent double submission
    let isSubmitting = false;
    
    // Load drivers when modal opens
    $('#driversManagementModal').on('shown.bs.modal', function() {
        loadDrivers();
    });

    // Add driver form submission - Remove existing listeners first to prevent duplicates
    $('#addDriverFormManagement').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent double submission
        if (isSubmitting) {
            return false;
        }
        
        isSubmitting = true;
        addDriver();
        
        // Reset flag after a delay
        setTimeout(function() {
            isSubmitting = false;
        }, 2000);
    });

    // Edit driver form submission - Remove existing listeners first to prevent duplicates
    $('#editDriverForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent double submission
        if (isSubmitting) {
            return false;
        }
        
        isSubmitting = true;
        updateDriver();
        
        // Reset flag after a delay
        setTimeout(function() {
            isSubmitting = false;
        }, 2000);
    });

    // Load drivers function
    function loadDrivers() {
        $.ajax({
            url: '../process/drivers/select_drivers.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderDriversTable(response.data);
                } else {
                    showError('هەڵە لە وەرگرتنی لیستی شۆفێرەکان');
                }
            },
            error: function() {
                showError('هەڵە لە وەرگرتنی لیستی شۆفێرەکان');
            }
        });
    }

    // Render drivers table
    function renderDriversTable(drivers) {
        let html = '';
        drivers.forEach((driver, index) => {
            const loadCapacity = driver.load_capacity ? 
                `${parseFloat(driver.load_capacity).toLocaleString()} کگم` : 
                '-';
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${driver.name}</td>
                    <td>${loadCapacity}</td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-driver" data-id="${driver.id}" data-name="${driver.name}" data-load-capacity="${driver.load_capacity || ''}" title="نوێکردنەوە">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-driver" data-id="${driver.id}" data-name="${driver.name}" title="سڕینەوە">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#driversTable tbody').html(html);
    }

    // Add driver function
    function addDriver() {
        const formData = {
            name: $('#driver_name').val(),
            load_capacity: $('#driver_load_capacity').val() || null
        };

        $.ajax({
            url: '../process/drivers/add_driver.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                isSubmitting = false; // Reset flag on success
                if (response.success) {
                    showSuccess('شۆفێر بە سەرکەوتوویی زیاد کرا');
                    $('#addDriverFormManagement')[0].reset();
                    loadDrivers();
                    // Refresh driver select in purchase form
                    refreshDriverSelects();
                } else {
                    showError(response.message || 'هەڵە لە زیادکردنی شۆفێر');
                }
            },
            error: function() {
                isSubmitting = false; // Reset flag on error
                showError('هەڵە لە زیادکردنی شۆفێر');
            }
        });
    }

    // Update driver function
    function updateDriver() {
        const formData = {
            id: $('#edit_driver_modal_id').val(),
            name: $('#edit_driver_name').val(),
            load_capacity: $('#edit_driver_load_capacity').val() || null
        };

        $.ajax({
            url: '../process/drivers/update_driver.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                isSubmitting = false; // Reset flag on success
                if (response.success) {
                    showSuccess('شۆفێر بە سەرکەوتوویی نوێکرایەوە');
                    $('#editDriverModal').modal('hide');
                    loadDrivers();
                    // Refresh driver select in purchase form
                    refreshDriverSelects();
                } else {
                    showError(response.message || 'هەڵە لە نوێکردنەوەی شۆفێر');
                }
            },
            error: function() {
                isSubmitting = false; // Reset flag on error
                showError('هەڵە لە نوێکردنەوەی شۆفێر');
            }
        });
    }

    // Delete driver function
    function deleteDriver(driverId, driverName) {
        Swal.fire({
            title: 'دڵنیای لە سڕینەوە؟',
            text: `دەتەوێت شۆفێری "${driverName}" بسڕیتەوە؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../process/drivers/delete_driver.php',
                    method: 'POST',
                    data: { id: driverId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccess('شۆفێر بە سەرکەوتوویی سڕایەوە');
                            loadDrivers();
                            // Refresh driver select in purchase form
                            refreshDriverSelects();
                        } else {
                            showError(response.message || 'هەڵە لە سڕینەوەی شۆفێر');
                        }
                    },
                    error: function() {
                        showError('هەڵە لە سڕینەوەی شۆفێر');
                    }
                });
            }
        });
    }

    // Edit driver click handler
    $(document).on('click', '.edit-driver', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const loadCapacity = $(this).data('load-capacity');

        $('#edit_driver_modal_id').val(id);
        $('#edit_driver_name').val(name);
        $('#edit_driver_load_capacity').val(loadCapacity);

        $('#editDriverModal').modal('show');
    });

    // Delete driver click handler
    $(document).on('click', '.delete-driver', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        deleteDriver(id, name);
    });

    // Helper functions
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: message,
            confirmButtonText: 'باشە'
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: message,
            confirmButtonText: 'باشە'
        });
    }

    // Function to refresh driver select options
    function refreshDriverSelects() {
        $.ajax({
            url: '../process/drivers/select_drivers.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Refresh add purchase modal driver select
                    let addOptions = '<option value="">شۆفێرەکان</option>';
                    response.data.forEach(driver => {
                        addOptions += `<option value="${driver.id}">${driver.name}</option>`;
                    });
                    $('#driver_id').html(addOptions);

                    // Refresh edit purchase modal driver select
                    let editOptions = '<option value="">شۆفێرەکان</option>';
                    response.data.forEach(driver => {
                        editOptions += `<option value="${driver.id}">${driver.name}</option>`;
                    });
                    $('#edit_driver_id').html(editOptions);
                }
            }
        });
    }
}); 