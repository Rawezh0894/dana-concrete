// Filter button active state management
$(document).ready(function() {
    // Initialize Select2 for filter dropdowns
    $('#filter_customer_id').select2({
        placeholder: 'کڕیار: هەموو',
        allowClear: true,
        width: '100%'
    });
    
    $('#filter_formulas_id').select2({
        placeholder: 'ڕێژە: هەموو',
        allowClear: true,
        width: '100%'
    });
    
    $('#filter_driver_id').select2({
        placeholder: 'شۆفێر: هەموو',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        // Trigger the filter function when driver is selected
        if (typeof loadFilteredReceipts === 'function') {
            loadFilteredReceipts();
        }
    });
    
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            if (this.id === 'filter_reset') {
                setTimeout(() => {
                    this.classList.remove('active');
                }, 2000);
            }
        });
    });
    const filterInputs = document.querySelectorAll('#filter_customer_id, #filter_location, #filter_formulas_id, #filter_driver_id, #filter_date_from, #filter_date_to');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
        });
    });
}); 