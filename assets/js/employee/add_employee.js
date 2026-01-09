$(function() {
    // Multiple submission prevention flag
    let isSubmitting = false;
    
    $('#addEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (isSubmitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        var mobile = $('#employee_mobile').val().trim();
        if (!/^07\d{9}$/.test(mobile)) {
            swalAlert('هەڵە', 'ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت.', 'error');
            return;
        }
        
        // Set submitting flag and disable submit button
        isSubmitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        // Validate that at least one role is selected
        var selectedRoles = $('#employee_role').val();
        if (!selectedRoles || selectedRoles.length === 0) {
            swalAlert('هەڵە', 'تکایە لانیکەم یەک ڕۆڵ هەڵبژێرە!', 'error');
            return;
        }
        
        var formData = $(this).serialize();
        $.post('../process/employee/add_employee.php', formData, function(response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'کارمەند بەسەرکەوتوویی زیادکرا!', 'success');
                $('#addEmployeeForm')[0].reset();
                if (window.loadEmployees) window.loadEmployees();
                $('#addEmployeeModal').modal('hide');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
        }).always(function() {
            // Reset submitting flag and restore submit button
            isSubmitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        });
    });
});
