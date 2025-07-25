// This file is for additional filter functionality if needed
// The main filtering logic is handled in get_informations.js

$(document).ready(function() {
    // Initialize Select2 for better dropdown experience
    $('#filter_customer_id, #filter_formulas_id').select2({
        placeholder: 'هەڵبژێرە',
        allowClear: true,
        width: '100%'
    });
    
    // Add loading indicators
    $(document).on('ajaxStart', function() {
        $('#summary-cards').addClass('opacity-50');
        $('.card-body').append('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>');
    });
    
    $(document).on('ajaxComplete', function() {
        $('#summary-cards').removeClass('opacity-50');
        $('.card-body .spinner-border').remove();
    });
});
