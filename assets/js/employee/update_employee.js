
$(function() {
    $(document).on('click', '.edit-employee', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const mobile = $(this).data('mobile');
        const role = $(this).data('role');
        let salary = $(this).data('salary');
        let bonus = $(this).data('bonus') || 0;
        let status = $(this).data('status') || 'active';
        
        // Clean salary and bonus values
        salary = String(salary).replace(/[^\d.]/g, '');
        bonus = String(bonus).replace(/[^\d.]/g, '');
        
        // Ensure status is in English (not Kurdish)
        const statusMap = {
            'چالاک': 'active',
            'نەچالاک': 'inactive',
            'لە پشوودا': 'on_leave',
            'دەستلەکارکێشان': 'resigned'
        };
        if (statusMap[status]) {
            status = statusMap[status];
        }
        
        // Set form values
        $('#edit_employee_id').val(id);
        $('#edit_employee_name').val(name);
        $('#edit_employee_mobile').val(mobile);
        
        // Handle multiple roles - split by comma if it's a string
        let rolesArray = [];
        if (typeof role === 'string' && role.includes(',')) {
            rolesArray = role.split(',').map(r => r.trim()).filter(r => r);
        } else if (role) {
            rolesArray = [role];
        }
        // Clear previous selections
        $('#edit_employee_role').val(null);
        // Set multiple selected roles
        if (rolesArray.length > 0) {
            $('#edit_employee_role').val(rolesArray);
        }
        
        $('#edit_employee_salary').val(salary);
        $('#edit_employee_bonus').val(bonus);
        $('#edit_employee_status').val(status);
        
        // Trigger change event to ensure dropdown updates
        $('#edit_employee_status').trigger('change');
        
        $('#editEmployeeModal').modal('show');
    });
    // Multiple submission prevention flag
    let isUpdating = false;
    
    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (isUpdating) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        var mobile = $('#edit_employee_mobile').val().trim();
        if (!/^07\d{9}$/.test(mobile)) {
            swalAlert('هەڵە', 'ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت.', 'error');
            return;
        }
        
        // Validate that at least one role is selected
        var selectedRoles = $('#edit_employee_role').val();
        if (!selectedRoles || selectedRoles.length === 0) {
            swalAlert('هەڵە', 'تکایە لانیکەم یەک ڕۆڵ هەڵبژێرە!', 'error');
            return;
        }
        
        // Set updating flag and disable submit button
        isUpdating = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
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
        }).always(function() {
            // Reset updating flag and restore submit button
            isUpdating = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        });
    });
});
