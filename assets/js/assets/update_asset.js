function loadAssetForEdit(assetId) {
    $.ajax({
        url: '../process/assets/select_assets.php',
        type: 'GET',
        data: {},
        dataType: 'json',
        success: function(assets) {
            const asset = assets.find(a => a.id == assetId);
            if (asset) {
                $('#edit_asset_id').val(asset.id);
                $('#edit_asset_code').val(asset.asset_code);
                $('#edit_asset_name').val(asset.name);
                $('#edit_category_id').val(asset.category_id);
                $('#edit_serial_number').val(asset.serial_number || '');
                $('#edit_purchase_date').val(asset.purchase_date);
                $('#edit_purchase_cost').val(asset.purchase_cost);
                $('#edit_salvage_value').val(asset.salvage_value);
                $('#edit_location').val(asset.location || '');
                $('#edit_supplier').val(asset.supplier || '');
                $('#edit_warranty_expiry').val(asset.warranty_expiry || '');
                $('#edit_status').val(asset.status);
                $('#edit_notes').val(asset.notes || '');
                
                $('#editAssetModal').modal('show');
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'نەتوانرا زانیارییەکان وەربگیرێت!'
            });
        }
    });
}

let updating = false;

$(document).ready(function() {
    $('#editAssetForm').on('submit', function(e) {
        e.preventDefault();
        
        if (updating) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        updating = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../process/assets/update_asset.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: response.message || 'ئامێر بەسەرکەوتوویی نوێکرایەوە!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#editAssetModal').modal('hide');
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
                        text: response.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی ئامێر!'
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
                updating = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
            }
        });
    });
});
