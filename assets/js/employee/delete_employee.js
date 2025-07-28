$(function() {
    // Multiple deletion prevention flag
    let isDeleting = false;
    
    $(document).on('click', '.delete-employee', function() {
        // Prevent multiple delete operations
        if (isDeleting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return;
        }
        
        const id = $(this).data('id');
        const deleteBtn = $(this);
        const originalBtnText = deleteBtn.html();
        
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
                // Set deleting flag and disable button
                isDeleting = true;
                deleteBtn.prop('disabled', true);
                deleteBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
                
                $.post('../process/employee/delete_employee.php', {id: id}, function(response) {
                    if (response.success) {
                        if (window.loadEmployees) window.loadEmployees();
                        swalAlert('سەرکەوتوو', 'کارمەند سڕایەوە!', 'success');
                    } else {
                        swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
                    }
                }, 'json').fail(function() {
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                }).always(function() {
                    // Reset deleting flag and restore button
                    isDeleting = false;
                    deleteBtn.prop('disabled', false);
                    deleteBtn.html(originalBtnText);
                });
            }
        });
    });
});
