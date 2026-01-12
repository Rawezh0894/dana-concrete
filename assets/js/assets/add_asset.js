let submitting = false;

$(document).ready(function() {
    // Auto-fill depreciation method and useful life from category
    $('#category_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const method = selectedOption.data('method');
        const life = selectedOption.data('life');
        
        if (method) {
            $('#depreciation_method').val(method);
            updateDepreciationFields();
        }
        
        if (life) {
            $('#useful_life_years').val(life);
        }
    });
    
    // Show/hide depreciation rate and units fields based on method
    $('#depreciation_method').on('change', updateDepreciationFields);
    
    function updateDepreciationFields() {
        const method = $('#depreciation_method').val();
        
        if (method === 'declining_balance') {
            $('#depreciation_rate_container').show();
            $('#useful_life_units_container').hide();
        } else if (method === 'units_of_production') {
            $('#depreciation_rate_container').hide();
            $('#useful_life_units_container').show();
        } else {
            $('#depreciation_rate_container').hide();
            $('#useful_life_units_container').hide();
        }
    }
    
    // Set default purchase date to today
    const today = new Date().toISOString().split('T')[0];
    $('#purchase_date').val(today);
    
    // Form submission
    $('#addAssetForm').on('submit', function(e) {
        e.preventDefault();
        
        if (submitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        submitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../process/assets/add_asset.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: response.message || 'ئامێر بەسەرکەوتوویی زیادکرا!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#addAssetForm')[0].reset();
                    $('#category_id').val('').trigger('change');
                    $('#addAssetModal').modal('hide');
                    if (typeof window.reloadAssets === 'function') {
                        window.reloadAssets();
                    }
                    if (typeof loadSummaryCardsData === 'function') {
                        loadSummaryCardsData();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە زیادکردنی ئامێر!'
                    });
                }
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr, xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'هەڵەیەک ڕوویدا لە پەیوەندیکردن!'
                });
            },
            complete: function() {
                submitting = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
    
    // Clear form when modal is hidden
    $('#addAssetModal').on('hidden.bs.modal', function() {
        $('#addAssetForm')[0].reset();
        $('#category_id').val('').trigger('change');
        updateDepreciationFields();
    });
});
