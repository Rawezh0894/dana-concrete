// Wait for DOM to load
window.addEventListener('DOMContentLoaded', function() {
    // Get references to the selects and fields
    var strengthTypeSelect = document.getElementById('strength_type');
    var strengthKgDiv = document.getElementById('strength_kg').closest('.col-md-6');
    var strengthMpaDiv = document.getElementById('strength_mpa').closest('.col-md-6');

    // Hide both by default
    strengthKgDiv.style.display = 'none';
    strengthMpaDiv.style.display = 'none';
    document.getElementById('strength_kg').value = '';
    document.getElementById('strength_mpa').value = '';

    // Listen for changes
    strengthTypeSelect.addEventListener('change', function() {
        if (this.value === 'kg') {
            strengthKgDiv.style.display = '';
            strengthMpaDiv.style.display = 'none';
            document.getElementById('strength_mpa').value = '';
        } else if (this.value === 'mpa') {
            strengthKgDiv.style.display = 'none';
            strengthMpaDiv.style.display = '';
            document.getElementById('strength_kg').value = '';
        } else {
            strengthKgDiv.style.display = 'none';
            strengthMpaDiv.style.display = 'none';
            document.getElementById('strength_kg').value = '';
            document.getElementById('strength_mpa').value = '';
        }
    });

    // If modal is opened, reset fields
    var addFormulaModal = document.getElementById('addFormulaModal');
    if (addFormulaModal) {
        addFormulaModal.addEventListener('show.bs.modal', function() {
            strengthKgDiv.style.display = 'none';
            strengthMpaDiv.style.display = 'none';
            document.getElementById('strength_kg').value = '';
            document.getElementById('strength_mpa').value = '';
            strengthTypeSelect.value = '';
        });
    }
});
