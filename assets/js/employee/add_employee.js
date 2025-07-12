$(function() {
    $('#addEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        var mobile = $('#employee_mobile').val().trim();
        if (!/^07\d{9}$/.test(mobile)) {
            swalAlert('هەڵە', 'ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت.', 'error');
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
        });
    });
});
