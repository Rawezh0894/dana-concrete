
$(function() {
    $(document).on('click', '.edit-employee', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const mobile = $(this).data('mobile');
        const role = $(this).data('role');
        let salary = $(this).data('salary');
        salary = String(salary).replace(/[^\d.]/g, '');
        $('#edit_employee_id').val(id);
        $('#edit_employee_name').val(name);
        $('#edit_employee_mobile').val(mobile);
        $('#edit_employee_role').val(role);
        $('#edit_employee_salary').val(salary);
        $('#editEmployeeModal').modal('show');
    });
    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        var mobile = $('#edit_employee_mobile').val().trim();
        if (!/^07\d{9}$/.test(mobile)) {
            swalAlert('هەڵە', 'ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت.', 'error');
            return;
        }
        var formData = $(this).serialize();
        $.post('../process/employee/update_employee.php', formData, function(response) {
            if (response.success) {
                $('#editEmployeeModal').modal('hide');
                if (window.loadEmployees) window.loadEmployees();
                swalAlert('سەرکەوتوو', 'زانیاری کارمەند نوێکرایەوە!', 'success');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function() {
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
        });
    });
});
