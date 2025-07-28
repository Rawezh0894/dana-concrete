// Notes Page Initialization Script
// This file handles Select2 cleanup and permissions setup for the notes page

// Pass permissions to JavaScript (this will be set from PHP)
// window.userPermissions is set in the main page

// Ensure Select2 is destroyed for specific dropdowns when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Destroy Select2 for formula, mixer, and pump dropdowns in add modal
    if ($('#formula_id').length > 0) {
        try { $('#formula_id').select2('destroy'); } catch(e) {}
    }
    if ($('#mixer_car_id').length > 0) {
        try { $('#mixer_car_id').select2('destroy'); } catch(e) {}
    }
    if ($('#mixer_driver_id').length > 0) {
        try { $('#mixer_driver_id').select2('destroy'); } catch(e) {}
    }
    if ($('#pump_car_id').length > 0) {
        try { $('#pump_car_id').select2('destroy'); } catch(e) {}
    }
    if ($('#pump_driver_id').length > 0) {
        try { $('#pump_driver_id').select2('destroy'); } catch(e) {}
    }
    
    // Destroy Select2 for formula, mixer, and pump dropdowns in edit modal
    if ($('#edit_formula_id').length > 0) {
        try { $('#edit_formula_id').select2('destroy'); } catch(e) {}
    }
    if ($('#edit_mixer_car_id').length > 0) {
        try { $('#edit_mixer_car_id').select2('destroy'); } catch(e) {}
    }
    if ($('#edit_mixer_driver_id').length > 0) {
        try { $('#edit_mixer_driver_id').select2('destroy'); } catch(e) {}
    }
    if ($('#edit_pump_car_id').length > 0) {
        try { $('#edit_pump_car_id').select2('destroy'); } catch(e) {}
    }
    if ($('#edit_pump_driver_id').length > 0) {
        try { $('#edit_pump_driver_id').select2('destroy'); } catch(e) {}
    }
});

// Prevent Select2 from being initialized on specific dropdowns when modals are shown
$(document).on('shown.bs.modal', function(e) {
    if (e.target.id === 'addNoteModal') {
        // Destroy Select2 for formula, mixer, and pump dropdowns in add modal
        try { $('#formula_id').select2('destroy'); } catch(e) {}
        try { $('#mixer_car_id').select2('destroy'); } catch(e) {}
        try { $('#mixer_driver_id').select2('destroy'); } catch(e) {}
        try { $('#pump_car_id').select2('destroy'); } catch(e) {}
        try { $('#pump_driver_id').select2('destroy'); } catch(e) {}
    }
    if (e.target.id === 'editNoteModal') {
        // Destroy Select2 for formula, mixer, and pump dropdowns in edit modal
        try { $('#edit_formula_id').select2('destroy'); } catch(e) {}
        try { $('#edit_mixer_car_id').select2('destroy'); } catch(e) {}
        try { $('#edit_mixer_driver_id').select2('destroy'); } catch(e) {}
        try { $('#edit_pump_car_id').select2('destroy'); } catch(e) {}
        try { $('#edit_pump_driver_id').select2('destroy'); } catch(e) {}
    }
}); 