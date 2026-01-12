let calculating = false;

$(document).ready(function() {
    // Show/hide units_used field based on asset depreciation method
    $('#dep_asset_id').on('change', function() {
        const assetId = $(this).val();
        if (assetId) {
            // Fetch asset info to check depreciation method
            $.ajax({
                url: '../process/assets/select_assets.php',
                type: 'GET',
                data: {},
                dataType: 'json',
                success: function(assets) {
                    const asset = assets.find(a => a.id == assetId);
                    if (asset && asset.depreciation_method === 'units_of_production') {
                        $('#units_used_container').show();
                        $('#units_used').prop('required', true);
                    } else {
                        $('#units_used_container').hide();
                        $('#units_used').prop('required', false);
                    }
                }
            });
        } else {
            $('#units_used_container').hide();
            $('#units_used').prop('required', false);
        }
    });
    
    // Set default dates (current month)
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    $('#period_start').val(firstDay.toISOString().split('T')[0]);
    $('#period_end').val(lastDay.toISOString().split('T')[0]);
    
    // Form submission
    $('#calculateDepreciationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (calculating) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        calculating = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../process/depreciation/calculate_depreciation.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: response.message || 'داخوران بەسەرکەوتوویی ژمێریاری کرا!',
                        html: response.data ? `
                            <p>بڕی داخوران: $${response.data.depreciation_amount.toFixed(2)}</p>
                            <p>کۆی داخوران: $${response.data.accumulated_depreciation.toFixed(2)}</p>
                            <p>نرخی کتێب: $${response.data.book_value.toFixed(2)}</p>
                        ` : '',
                        timer: 3000,
                        showConfirmButton: true
                    });
                    $('#calculateDepreciationForm')[0].reset();
                    $('#dep_asset_id').val('').trigger('change');
                    $('#calculateDepreciationModal').modal('hide');
                    if (typeof window.reloadDepreciation === 'function') {
                        window.reloadDepreciation();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message || 'هەڵەیەک ڕوویدا لە ژمێریاری داخوران!'
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
                calculating = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
    
    // Clear form when modal is hidden
    $('#calculateDepreciationModal').on('hidden.bs.modal', function() {
        $('#calculateDepreciationForm')[0].reset();
        $('#dep_asset_id').val('').trigger('change');
    });
});

function postDepreciation(scheduleId) {
    Swal.fire({
        title: 'دڵنیایت لە پۆستکردن؟',
        text: 'داخوران پۆست دەکرێت و ناتوانیت بیگۆڕیت',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'بەڵێ، پۆست بکە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/depreciation/post_depreciation.php',
                type: 'POST',
                data: { schedule_id: scheduleId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: response.message || 'داخوران بەسەرکەوتوویی پۆست کرا!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        if (typeof window.reloadDepreciation === 'function') {
                            window.reloadDepreciation();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕوویدا لە پۆستکردنی داخوران!'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('AJAX error:', xhr, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن!'
                    });
                }
            });
        }
    });
}

function deleteDepreciationSchedule(scheduleId) {
    Swal.fire({
        title: 'دڵنیایت لە سڕینەوە؟',
        text: 'کاتی داخوران دەسڕدرێتەوە',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/depreciation/delete_depreciation_schedule.php',
                type: 'POST',
                data: { schedule_id: scheduleId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: response.message || 'کاتی داخوران بەسەرکەوتوویی سڕایەوە!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        if (typeof window.reloadDepreciation === 'function') {
                            window.reloadDepreciation();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی کاتی داخوران!'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('AJAX error:', xhr, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن!'
                    });
                }
            });
        }
    });
}
