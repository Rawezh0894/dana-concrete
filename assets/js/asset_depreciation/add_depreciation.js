$(document).ready(function () {
    $('#addDepreciationForm').on('submit', function (e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: '../process/asset_depreciation/add_depreciation.php',
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'بۆردی سەرکەوتن',
                        text: response.message,
                        confirmButtonText: 'باشە'
                    });
                    $('#addDepreciationModal').modal('hide');
                    $('#addDepreciationForm')[0].reset();
                    // Set date back to today
                    $('#date').val(new Date().toISOString().split('T')[0]);
                    refreshDepreciationGrid();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: response.message,
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەر',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
});
