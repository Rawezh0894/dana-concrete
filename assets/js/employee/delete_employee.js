$(function() {
    $(document).on('click', '.delete-employee', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت کارمەندەکە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'داخستن'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/employee/delete_employee.php', {id: id}, function(response) {
                    if (response.success) {
                        if (window.loadEmployees) window.loadEmployees();
                        swalAlert('سەرکەوتوو', 'کارمەند سڕایەوە!', 'success');
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
