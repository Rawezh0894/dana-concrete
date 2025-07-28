// Notes Page Initialization Script
// This file handles Select2 cleanup and permissions setup for the notes page

// Pass permissions to JavaScript (this will be set from PHP)
// window.userPermissions is set in the main page

// Ensure Select2 is destroyed for specific dropdowns when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to safely destroy Select2
    function safeDestroySelect2(selector) {
        try {
            const element = $(selector);
            if (element.length > 0 && element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }
        } catch(e) {
            // Silently ignore errors
        }
    }
    
    // Destroy Select2 for formula, mixer, and pump dropdowns in add modal
    safeDestroySelect2('#formula_id');
    safeDestroySelect2('#mixer_car_id');
    safeDestroySelect2('#mixer_driver_id');
    safeDestroySelect2('#pump_car_id');
    safeDestroySelect2('#pump_driver_id');
    
    // Destroy Select2 for formula, mixer, and pump dropdowns in edit modal
    safeDestroySelect2('#edit_formula_id');
    safeDestroySelect2('#edit_mixer_car_id');
    safeDestroySelect2('#edit_mixer_driver_id');
    safeDestroySelect2('#edit_pump_car_id');
    safeDestroySelect2('#edit_pump_driver_id');
});

// Prevent Select2 from being initialized on specific dropdowns when modals are shown
$(document).on('shown.bs.modal', function(e) {
    // Helper function to safely destroy Select2
    function safeDestroySelect2(selector) {
        try {
            const element = $(selector);
            if (element.length > 0 && element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }
        } catch(e) {
            // Silently ignore errors
        }
    }
    
    if (e.target.id === 'addNoteModal') {
        // Destroy Select2 for formula, mixer, and pump dropdowns in add modal
        safeDestroySelect2('#formula_id');
        safeDestroySelect2('#mixer_car_id');
        safeDestroySelect2('#mixer_driver_id');
        safeDestroySelect2('#pump_car_id');
        safeDestroySelect2('#pump_driver_id');
    }
    if (e.target.id === 'editNoteModal') {
        // Destroy Select2 for formula, mixer, and pump dropdowns in edit modal
        safeDestroySelect2('#edit_formula_id');
        safeDestroySelect2('#edit_mixer_car_id');
        safeDestroySelect2('#edit_mixer_driver_id');
        safeDestroySelect2('#edit_pump_car_id');
        safeDestroySelect2('#edit_pump_driver_id');
    }
}); 