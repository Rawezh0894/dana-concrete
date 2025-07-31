// Filter button active state management
$(document).ready(function() {
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