$(function() {
    $(document).on('click', '.delete-company-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت کۆمپانیاکە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'داخستن'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/company/delete_company.php', {id: id}, function(response) {
                    if (response.success) {
                        // Trigger event to reload grid
                        $(document).trigger('companyDeleted');
                        swalAlert('سەرکەوتوو', 'کۆمپانیا سڕایەوە!', 'success');
                    } else {
                        swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
                    }
                }, 'json').fail(function() {
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                });
            }
        });
    });
});
