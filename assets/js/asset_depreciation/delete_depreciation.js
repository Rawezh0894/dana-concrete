function deleteDepreciation(id) {
    Swal.fire({
        title: 'ئایا دڵنیای؟',
        text: "ئەم کردارە ناگەڕێتەوە!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بیسڕەوە!',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/asset_depreciation/delete_depreciation.php',
                type: 'POST',
                data: { id: id },
                success: function (response) {
                    if (response.success) {
                        Swal.fire(
                            'سڕایەوە!',
                            response.message,
                            'success'
                        );
                        refreshDepreciationGrid();
                    } else {
                        Swal.fire(
                            'هەڵە!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function () {
                    Swal.fire(
                        'هەڵە!',
                        'هەڵەیەک ڕوویدا لە کاتی سڕینەوە',
                        'error'
                    );
                }
            });
        }
    });
}

window.deleteDepreciation = deleteDepreciation;
