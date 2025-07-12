document.addEventListener('DOMContentLoaded', function () {
    loadPermissions();
});

function loadPermissions() {
    fetch('../process/users/select_permissions.php')
        .then(res => res.json())
        .then(perms => {
            // Transform data for TableController
            const tableData = perms.map(perm => ({
                name: perm.name,
                description: perm.description || '',
                admin: createCheckbox('admin', perm.id, perm.admin),
                user: createCheckbox('user', perm.id, perm.user),
                accountant: createCheckbox('accountant', perm.id, perm.accountant),
                manager: createCheckbox('manager', perm.id, perm.manager)
            }));
            
            // Define columns for the table
            const columns = ['name', 'description', 'admin', 'user', 'accountant', 'manager'];
            
            // Use TableController with pagination and search
            TableController.renderWithPagination('#permissionsTable', tableData, columns, {
                pageSize: 15,
                currentPage: 1
            });
        });
}

function createCheckbox(role, permissionId, isChecked) {
    return `<input type="checkbox" class="perm-checkbox" data-role="${role}" data-id="${permissionId}" ${isChecked ? 'checked' : ''}>`;
}

document.body.addEventListener('change', function (e) {
    if (e.target.classList.contains('perm-checkbox')) {
        const role = e.target.getAttribute('data-role');
        const permission_id = e.target.getAttribute('data-id');
        const value = e.target.checked ? 1 : 0;
        fetch('../process/users/update_role_permissions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `role=${role}&permission_id=${permission_id}&value=${value}`
        })
        .then(res => res.json())
        .then(data => {
            swalAlert(data.success ? 'success' : 'error', data.message);
        })
        .catch(() => swalAlert('error', 'هەڵەیەک ڕویدا!'));
    }
});
