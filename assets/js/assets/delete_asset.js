function deleteAsset(assetId, assetName, assetCode) {
    Swal.fire({
        title: 'دڵنیایت لە سڕینەوە؟',
        text: `ئامێر: ${assetName} (${assetCode})`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/assets/delete_asset.php',
                type: 'POST',
                data: { asset_id: assetId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: response.message || 'ئامێر بەسەرکەوتوویی سڕایەوە!',
                            timer: 1500,
                            showConfirmButton: false
                        });
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
                            text: response.message || 'هەڵەیەک ڕوویدا لە سڕینەوەی ئامێر!'
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
